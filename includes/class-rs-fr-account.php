<?php
/**
 * WooCommerce My Account integration.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Displays a customer's own withdrawal cases.
 */
final class RS_FR_Account
{
    const ENDPOINT = 'fortrydelser';

    /**
     * Register hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'add_endpoint'));
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_filter('woocommerce_account_menu_items', array(__CLASS__, 'add_menu_item'));
        add_action('woocommerce_account_' . self::ENDPOINT . '_endpoint', array(__CLASS__, 'render_endpoint'));
    }

    /**
     * Register account endpoint.
     *
     * @return void
     */
    public static function add_endpoint()
    {
        add_rewrite_endpoint(self::ENDPOINT, EP_ROOT | EP_PAGES);
    }

    /**
     * Add query var.
     *
     * @param string[] $vars Query vars.
     * @return string[]
     */
    public static function add_query_vars($vars)
    {
        $vars[] = self::ENDPOINT;

        return $vars;
    }

    /**
     * Add menu item to WooCommerce My Account.
     *
     * @param array $items Menu items.
     * @return array
     */
    public static function add_menu_item($items)
    {
        $new_items = array();

        foreach ($items as $key => $label) {
            if ('customer-logout' === $key) {
                $new_items[self::ENDPOINT] = __('Mine fortrydelser', 'rs-digital-fortrydelsesret');
            }

            $new_items[$key] = $label;
        }

        if (!isset($new_items[self::ENDPOINT])) {
            $new_items[self::ENDPOINT] = __('Mine fortrydelser', 'rs-digital-fortrydelsesret');
        }

        return $new_items;
    }

    /**
     * Render account endpoint.
     *
     * @return void
     */
    public static function render_endpoint()
    {
        if (!is_user_logged_in()) {
            echo '<p>' . esc_html__('Log ind for at se dine fortrydelser.', 'rs-digital-fortrydelsesret') . '</p>';
            return;
        }

        $case_id = isset($_GET['digital_fortrydelse_case']) ? absint($_GET['digital_fortrydelse_case']) : 0;

        if ($case_id) {
            self::render_detail($case_id);
            return;
        }

        self::render_list();
    }

    /**
     * Render list of current user's cases.
     *
     * @return void
     */
    private static function render_list()
    {
        $cases = RS_FR_Repository::query(
            array(
                'customer_user_id' => get_current_user_id(),
                'limit' => 100,
            )
        );

        echo '<h2>' . esc_html__('Mine fortrydelser', 'rs-digital-fortrydelsesret') . '</h2>';

        if (!$cases) {
            echo '<p>' . esc_html__('Du har ingen registrerede fortrydelser.', 'rs-digital-fortrydelsesret') . '</p>';
            return;
        }

        ?>
        <table class="woocommerce-orders-table shop_table shop_table_responsive my_account_orders">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Reference', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Dato', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Ordre', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Status', 'rs-digital-fortrydelsesret'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cases as $case) : ?>
                    <tr>
                        <td data-title="<?php echo esc_attr__('Reference', 'rs-digital-fortrydelsesret'); ?>">
                            <a href="<?php echo esc_url(self::detail_url($case->id)); ?>"><?php echo esc_html($case->reference); ?></a>
                        </td>
                        <td data-title="<?php echo esc_attr__('Dato', 'rs-digital-fortrydelsesret'); ?>"><?php echo esc_html(self::format_datetime($case->submitted_at)); ?></td>
                        <td data-title="<?php echo esc_attr__('Ordre', 'rs-digital-fortrydelsesret'); ?>"><?php echo esc_html($case->order_number); ?></td>
                        <td data-title="<?php echo esc_attr__('Status', 'rs-digital-fortrydelsesret'); ?>"><?php echo esc_html(self::status_label($case->status)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render detail view for current user's case.
     *
     * @param int $case_id Case ID.
     * @return void
     */
    private static function render_detail($case_id)
    {
        $case = RS_FR_Repository::get($case_id);

        if (!$case || (int) $case->customer_user_id !== get_current_user_id()) {
            echo '<p>' . esc_html__('Fortrydelsen blev ikke fundet.', 'rs-digital-fortrydelsesret') . '</p>';
            return;
        }

        /* translators: %s: cancellation request reference. */
        echo '<h2>' . esc_html(sprintf(__('Fortrydelse %s', 'rs-digital-fortrydelsesret'), $case->reference)) . '</h2>';
        echo '<p><a href="' . esc_url(self::endpoint_url()) . '">' . esc_html__('Tilbage til mine fortrydelser', 'rs-digital-fortrydelsesret') . '</a></p>';

        ?>
        <table class="shop_table shop_table_responsive">
            <tbody>
                <?php self::row(__('Reference', 'rs-digital-fortrydelsesret'), $case->reference); ?>
                <?php self::row(__('Dato', 'rs-digital-fortrydelsesret'), self::format_datetime($case->submitted_at)); ?>
                <?php self::row(__('Ordrenummer', 'rs-digital-fortrydelsesret'), $case->order_number); ?>
                <?php self::row(__('Fortrydelse', 'rs-digital-fortrydelsesret'), self::request_type_label($case->request_type)); ?>
                <?php self::row(__('Status', 'rs-digital-fortrydelsesret'), self::status_label($case->status)); ?>
                <?php self::row(__('Indsendt indhold', 'rs-digital-fortrydelsesret'), nl2br(esc_html($case->request_message ? $case->request_message : __('Hele ordren', 'rs-digital-fortrydelsesret')))); ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render table row.
     *
     * @param string $label Label.
     * @param string $value Value.
     * @return void
     */
    private static function row($label, $value)
    {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . wp_kses_post($value) . '</td></tr>';
    }

    /**
     * Detail URL.
     *
     * @param int $case_id Case ID.
     * @return string
     */
    private static function detail_url($case_id)
    {
        return add_query_arg('digital_fortrydelse_case', absint($case_id), self::endpoint_url());
    }

    /**
     * Account endpoint URL.
     *
     * @return string
     */
    private static function endpoint_url()
    {
        if (function_exists('wc_get_account_endpoint_url')) {
            return wc_get_account_endpoint_url(self::ENDPOINT);
        }

        return home_url('/my-account/' . self::ENDPOINT . '/');
    }

    /**
     * Status label.
     *
     * @param string $status Status key.
     * @return string
     */
    private static function status_label($status)
    {
        $labels = array(
            'received' => __('Modtaget', 'rs-digital-fortrydelsesret'),
            'processing' => __('Under behandling', 'rs-digital-fortrydelsesret'),
            'approved' => __('Godkendt', 'rs-digital-fortrydelsesret'),
            'rejected' => __('Afvist', 'rs-digital-fortrydelsesret'),
            'completed' => __('Fuldført', 'rs-digital-fortrydelsesret'),
        );

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Request type label.
     *
     * @param string $request_type Request type.
     * @return string
     */
    private static function request_type_label($request_type)
    {
        return 'partial' === $request_type
            ? __('Enkelte produkter', 'rs-digital-fortrydelsesret')
            : __('Hele ordren', 'rs-digital-fortrydelsesret');
    }

    /**
     * Format MySQL datetime.
     *
     * @param string $datetime MySQL datetime.
     * @return string
     */
    private static function format_datetime($datetime)
    {
        $timestamp = strtotime($datetime);

        return $timestamp ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) : $datetime;
    }
}
