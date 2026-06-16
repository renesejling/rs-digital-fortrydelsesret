<?php
/**
 * Database schema definitions.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Owns the plugin database schema.
 */
final class RS_FR_Schema
{
    /**
     * Get the full table name for withdrawal requests.
     *
     * @return string
     */
    public static function withdrawals_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . RS_FR_TABLE;
    }

    /**
     * Get SQL for the withdrawal requests table.
     *
     * @return string
     */
    public static function withdrawals_table_sql()
    {
        global $wpdb;

        $table_name = self::withdrawals_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reference varchar(32) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'received',
            order_id bigint(20) unsigned DEFAULT NULL,
            order_number varchar(64) NOT NULL DEFAULT '',
            order_date datetime DEFAULT NULL,
            customer_user_id bigint(20) unsigned DEFAULT NULL,
            customer_name varchar(190) NOT NULL DEFAULT '',
            customer_email varchar(190) NOT NULL DEFAULT '',
            order_email varchar(190) NOT NULL DEFAULT '',
            email_mismatch tinyint(1) NOT NULL DEFAULT 0,
            request_type varchar(20) NOT NULL DEFAULT 'full_order',
            requested_items longtext NULL,
            request_message longtext NULL,
            request_payload longtext NULL,
            deadline_at datetime DEFAULT NULL,
            deadline_status varchar(20) NOT NULL DEFAULT 'unknown',
            receipt_sent_at datetime DEFAULT NULL,
            internal_notification_sent_at datetime DEFAULT NULL,
            admin_note longtext NULL,
            metadata longtext NULL,
            retention_until datetime DEFAULT NULL,
            submitted_at datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY reference (reference),
            KEY status (status),
            KEY order_id (order_id),
            KEY order_number (order_number),
            KEY customer_user_id (customer_user_id),
            KEY customer_email (customer_email),
            KEY email_mismatch (email_mismatch),
            KEY request_type (request_type),
            KEY deadline_status (deadline_status),
            KEY submitted_at (submitted_at),
            KEY retention_until (retention_until)
        ) {$charset_collate};";
    }

    /**
     * Valid statuses for manual case handling.
     *
     * @return string[]
     */
    public static function statuses()
    {
        return array(
            'received',
            'processing',
            'approved',
            'rejected',
            'completed',
        );
    }

    /**
     * Valid request types.
     *
     * @return string[]
     */
    public static function request_types()
    {
        return array(
            'full_order',
            'partial',
        );
    }
}
