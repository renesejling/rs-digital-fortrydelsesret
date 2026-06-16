<?php
/**
 * WooCommerce integration helpers.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Looks up WooCommerce order context for withdrawal cases.
 */
final class RS_FR_WooCommerce
{
    /**
     * Get normalized order context.
     *
     * @param string $order_number Submitted order number.
     * @param string $submitted_email Submitted customer e-mail.
     * @param string $submitted_at MySQL datetime for submission.
     * @return array
     */
    public static function get_order_context($order_number, $submitted_email, $submitted_at)
    {
        $order = self::find_order($order_number);
        $context = self::empty_context($order_number);

        if (!$order) {
            return $context;
        }

        $order_date = $order->get_date_created();
        $order_date_mysql = $order_date ? $order_date->date('Y-m-d H:i:s') : null;
        $deadline_at = self::calculate_deadline_at($order_date);
        $order_email = sanitize_email($order->get_billing_email());

        $context['found'] = true;
        $context['order_id'] = $order->get_id();
        $context['order_number'] = $order->get_order_number();
        $context['order_date'] = $order_date_mysql;
        $context['customer_user_id'] = $order->get_customer_id() ? $order->get_customer_id() : (get_current_user_id() ?: null);
        $context['order_email'] = $order_email;
        $context['email_mismatch'] = self::emails_mismatch($submitted_email, $order_email) ? 1 : 0;
        $context['deadline_at'] = $deadline_at;
        $context['deadline_status'] = self::deadline_status($deadline_at, $submitted_at);
        $context['items'] = self::get_order_items($order);

        return $context;
    }

    /**
     * Find an order from a submitted order number.
     *
     * @param string $order_number Submitted order number.
     * @return WC_Order|false
     */
    private static function find_order($order_number)
    {
        if (!function_exists('wc_get_order')) {
            return false;
        }

        $order_number = trim(ltrim((string) $order_number, '#'));

        if ('' === $order_number) {
            return false;
        }

        if (function_exists('wc_get_order_id_by_order_key') && 0 === strpos($order_number, 'wc_order_')) {
            $order_id = wc_get_order_id_by_order_key($order_number);
            $order = $order_id ? wc_get_order($order_id) : false;

            if ($order) {
                return $order;
            }
        }

        if (ctype_digit($order_number)) {
            $order = wc_get_order(absint($order_number));

            if ($order && self::order_number_matches($order, $order_number)) {
                return $order;
            }
        }

        return self::find_order_by_order_number_meta($order_number);
    }

    /**
     * Try common custom order number meta fields.
     *
     * @param string $order_number Submitted order number.
     * @return WC_Order|false
     */
    private static function find_order_by_order_number_meta($order_number)
    {
        if (!function_exists('wc_get_orders')) {
            return false;
        }

        $meta_keys = array(
            '_order_number',
            '_alg_wc_custom_order_number',
            '_wc_sequential_order_number',
        );

        foreach ($meta_keys as $meta_key) {
            $orders = wc_get_orders(
                array(
                    'limit' => 1,
                    'return' => 'objects',
                    'meta_key' => $meta_key,
                    'meta_value' => $order_number,
                )
            );

            if (!empty($orders[0])) {
                return $orders[0];
            }
        }

        return false;
    }

    /**
     * Calculate deadline from order creation date.
     *
     * @param WC_DateTime|null $order_date Order creation date.
     * @return string|null
     */
    private static function calculate_deadline_at($order_date)
    {
        if (!$order_date) {
            return null;
        }

        return wp_date('Y-m-d H:i:s', strtotime('+14 days', $order_date->getTimestamp()));
    }

    /**
     * Calculate whether submission was within deadline.
     *
     * @param string|null $deadline_at Deadline datetime.
     * @param string      $submitted_at Submission datetime.
     * @return string
     */
    private static function deadline_status($deadline_at, $submitted_at)
    {
        if (!$deadline_at) {
            return 'unknown';
        }

        $deadline_timestamp = strtotime($deadline_at);
        $submitted_timestamp = strtotime($submitted_at);

        if (!$deadline_timestamp || !$submitted_timestamp) {
            return 'unknown';
        }

        return $submitted_timestamp <= $deadline_timestamp ? 'within_deadline' : 'expired';
    }

    /**
     * Check whether submitted e-mail differs from order billing e-mail.
     *
     * @param string $submitted_email Submitted customer e-mail.
     * @param string $order_email Order billing e-mail.
     * @return bool
     */
    private static function emails_mismatch($submitted_email, $order_email)
    {
        if (!$submitted_email || !$order_email) {
            return false;
        }

        return strtolower($submitted_email) !== strtolower($order_email);
    }

    /**
     * Check whether a WooCommerce order's visible number matches the submitted value.
     *
     * @param WC_Order $order Order object.
     * @param string   $order_number Submitted order number.
     * @return bool
     */
    private static function order_number_matches($order, $order_number)
    {
        return trim(ltrim((string) $order->get_order_number(), '#')) === trim(ltrim((string) $order_number, '#'));
    }

    /**
     * Get normalized order line items.
     *
     * @param WC_Order $order Order object.
     * @return array
     */
    private static function get_order_items($order)
    {
        $items = array();

        foreach ($order->get_items('line_item') as $item_id => $item) {
            $product = $item->get_product();

            $items[] = array(
                'item_id' => (int) $item_id,
                'product_id' => (int) $item->get_product_id(),
                'variation_id' => (int) $item->get_variation_id(),
                'name' => $item->get_name(),
                'sku' => $product ? $product->get_sku() : '',
                'quantity' => (float) $item->get_quantity(),
                'total' => (string) $item->get_total(),
            );
        }

        return $items;
    }

    /**
     * Empty order context used when WooCommerce or the order is unavailable.
     *
     * @param string $order_number Submitted order number.
     * @return array
     */
    private static function empty_context($order_number)
    {
        return array(
            'found' => false,
            'submitted_order_number' => $order_number,
            'order_id' => null,
            'order_number' => '',
            'order_date' => null,
            'customer_user_id' => get_current_user_id() ?: null,
            'order_email' => '',
            'email_mismatch' => 0,
            'deadline_at' => null,
            'deadline_status' => 'unknown',
            'items' => array(),
        );
    }
}
