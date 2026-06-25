<?php
/**
 * Withdrawal request persistence.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stores withdrawal cases in the custom table.
 */
final class RS_FR_Repository
{
    /**
     * Create a withdrawal request.
     *
     * @param array $data Case data.
     * @return int|WP_Error Inserted row ID or error.
     */
    public static function create($data)
    {
        global $wpdb;

        $now = current_time('mysql');
        $reference = self::generate_reference();
        $table_name = RS_FR_Schema::withdrawals_table_name();

        $row = wp_parse_args(
            $data,
            array(
                'reference' => $reference,
                'status' => 'received',
                'order_id' => null,
                'order_number' => '',
                'order_date' => null,
                'customer_user_id' => get_current_user_id() ?: null,
                'customer_name' => '',
                'customer_email' => '',
                'order_email' => '',
                'email_mismatch' => 0,
                'request_type' => 'full_order',
                'requested_items' => null,
                'request_message' => null,
                'request_payload' => null,
                'deadline_at' => null,
                'deadline_status' => 'unknown',
                'receipt_sent_at' => null,
                'internal_notification_sent_at' => null,
                'admin_note' => null,
                'metadata' => null,
                'retention_until' => RS_FR_Retention::calculate_retention_until($now),
                'submitted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            )
        );

        $inserted = $wpdb->insert(
            $table_name,
            $row,
            array(
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        if (false === $inserted) {
            // Log den præcise DB-fejl (kun ved aktiveret debug-logging), så en
            // fremtidig insert-fejl kan diagnosticeres uden gætteri.
            if (defined('WP_DEBUG') && WP_DEBUG && !empty($wpdb->last_error)) {
                error_log('RS_FR insert failed: ' . $wpdb->last_error);
            }

            return new WP_Error(
                'digital_fortrydelse_insert_failed',
                __('Fortrydelsen kunne ikke gemmes. Prøv igen senere.', 'rs-digital-fortrydelsesret')
            );
        }


        return (int) $wpdb->insert_id;
    }

    /**
     * Get a withdrawal request by ID.
     *
     * @param int $id Case ID.
     * @return object|null
     */
    public static function get($id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . RS_FR_Schema::withdrawals_table_name() . ' WHERE id = %d',
                absint($id)
            )
        );
    }

    /**
     * Mark a mail timestamp on a withdrawal request.
     *
     * @param int    $id Case ID.
     * @param string $field Timestamp field.
     * @return bool
     */
    public static function mark_mail_sent($id, $field)
    {
        global $wpdb;

        $allowed_fields = array(
            'receipt_sent_at',
            'internal_notification_sent_at',
        );

        if (!in_array($field, $allowed_fields, true)) {
            return false;
        }

        return false !== $wpdb->update(
            RS_FR_Schema::withdrawals_table_name(),
            array(
                $field => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ),
            array('id' => absint($id)),
            array('%s', '%s'),
            array('%d')
        );
    }

    /**
     * Query withdrawal requests for admin views.
     *
     * @param array $args Query args.
     * @return object[]
     */
    public static function query($args = array())
    {
        global $wpdb;

        $args = wp_parse_args(
            $args,
            array(
                'search' => '',
                'status' => '',
                'customer_user_id' => 0,
                'limit' => 100,
            )
        );

        $where = array('1=1');
        $values = array();

        if ($args['status'] && in_array($args['status'], RS_FR_Schema::statuses(), true)) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ($args['search']) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = '(reference LIKE %s OR order_number LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        if ($args['customer_user_id']) {
            $where[] = 'customer_user_id = %d';
            $values[] = absint($args['customer_user_id']);
        }

        $limit = max(1, min(500, absint($args['limit'])));
        $sql = 'SELECT * FROM ' . RS_FR_Schema::withdrawals_table_name()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY submitted_at DESC LIMIT ' . $limit;

        if ($values) {
            $sql = $wpdb->prepare($sql, $values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Update case status and optional admin note.
     *
     * @param int    $id Case ID.
     * @param string $status New status.
     * @param string $admin_note Admin note.
     * @return bool
     */
    public static function update_status($id, $status, $admin_note = '')
    {
        global $wpdb;

        if (!in_array($status, RS_FR_Schema::statuses(), true)) {
            return false;
        }

        return false !== $wpdb->update(
            RS_FR_Schema::withdrawals_table_name(),
            array(
                'status' => $status,
                'admin_note' => $admin_note,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => absint($id)),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Find an existing request that conflicts with a new order request.
     *
     * A full-order request blocks all later requests for the same order. Partial
     * requests may repeat only when the submitted line description differs.
     *
     * @param int    $order_id WooCommerce order ID.
     * @param string $request_type New request type.
     * @param string $requested_items Submitted partial request description.
     * @return object|null
     */
    public static function find_duplicate_for_order($order_id, $request_type, $requested_items = '')
    {
        global $wpdb;

        $order_id = absint($order_id);

        if (!$order_id) {
            return null;
        }

        $table_name = RS_FR_Schema::withdrawals_table_name();

        if ('full_order' === $request_type) {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, reference, request_type, requested_items FROM {$table_name} WHERE order_id = %d ORDER BY submitted_at ASC LIMIT 1",
                    $order_id
                )
            );
        }

        $full_order_case = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, reference, request_type, requested_items FROM {$table_name} WHERE order_id = %d AND request_type = %s ORDER BY submitted_at ASC LIMIT 1",
                $order_id,
                'full_order'
            )
        );

        if ($full_order_case) {
            return $full_order_case;
        }

        $partial_cases = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, reference, request_type, requested_items FROM {$table_name} WHERE order_id = %d AND request_type = %s",
                $order_id,
                'partial'
            )
        );

        $normalized_requested_items = self::normalize_requested_items($requested_items);

        foreach ($partial_cases as $case) {
            if ($normalized_requested_items === self::normalize_requested_items($case->requested_items)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Normalize partial request text for duplicate comparison.
     *
     * @param string $requested_items Submitted partial request description.
     * @return string
     */
    private static function normalize_requested_items($requested_items)
    {
        $text = wp_strip_all_tags((string) $requested_items);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }

        return strtolower($text);
    }

    /**
     * Generate a unique reference ID.
     *
     * @return string
     */
    private static function generate_reference()
    {
        global $wpdb;

        $table_name = RS_FR_Schema::withdrawals_table_name();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = 'DF-' . gmdate('Ymd') . '-' . strtoupper(wp_generate_password(6, false, false));
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table_name} WHERE reference = %s LIMIT 1",
                    $reference
                )
            );

            if (!$exists) {
                return $reference;
            }
        }

        return 'DF-' . gmdate('YmdHis') . '-' . strtoupper(wp_generate_password(4, false, false));
    }
}
