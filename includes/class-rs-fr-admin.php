<?php
/**
 * Admin case handling.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders admin list/detail views for withdrawal cases.
 */
final class RS_FR_Admin
{
    const PAGE = 'rs-digital-fortrydelsesret';
    const STATUS_ACTION = 'digital_fortrydelse_update_status';
    const EXPORT_ACTION = 'digital_fortrydelse_export';

    /**
     * Register hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        add_action('admin_post_' . self::STATUS_ACTION, array(__CLASS__, 'handle_status_update'));
        add_action('admin_post_' . self::EXPORT_ACTION, array(__CLASS__, 'handle_export'));
    }

    /**
     * Add WooCommerce submenu.
     *
     * @return void
     */
    public static function add_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Fortrydelser', 'rs-digital-fortrydelsesret'),
            __('Fortrydelser', 'rs-digital-fortrydelsesret'),
            self::menu_capability(),
            self::PAGE,
            array(__CLASS__, 'render_page')
        );
    }

    /**
     * Capability der bruges til at vise menupunktet.
     *
     * Bruger den brugerdefinerede capability hvis den aktuelle bruger har den,
     * ellers falder vi tilbage til 'manage_woocommerce', så menuen altid er
     * synlig for WooCommerce-administratorer (også hvis capability'en mangler).
     *
     * @return string
     */
    private static function menu_capability()
    {
        return current_user_can('manage_digital_fortrydelse') ? 'manage_digital_fortrydelse' : 'manage_woocommerce';
    }

    /**
     * Må den aktuelle bruger administrere fortrydelser?
     *
     * @return bool
     */
    private static function current_user_can_manage()
    {
        return current_user_can('manage_digital_fortrydelse') || current_user_can('manage_woocommerce');
    }


    /**
     * Enqueue admin styles for the plugin pages.

     *
     * @return void
     */
    public static function enqueue_assets()
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if (self::PAGE !== $page) {
            return;
        }

        wp_enqueue_style(
            'digital-fortrydelse-admin',
            RS_FR_PLUGIN_URL . 'assets/admin/digital-fortrydelse-admin.css',
            array(),
            RS_FR_VERSION
        );
    }

    /**
     * Render list or detail page.
     *
     * @return void
     */
    public static function render_page()
    {
        if (!self::current_user_can_manage()) {
            wp_die(esc_html__('Du har ikke adgang til denne side.', 'rs-digital-fortrydelsesret'));
        }


        $case_id = isset($_GET['digital_fortrydelse_case']) ? absint($_GET['digital_fortrydelse_case']) : 0;

        echo '<div class="wrap">';

        if ($case_id) {
            self::render_detail($case_id);
        } else {
            self::render_list();
        }

        echo '</div>';
    }

    /**
     * Render admin case list.
     *
     * @return void
     */
    private static function render_list()
    {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $cases = RS_FR_Repository::query(
            array(
                'search' => $search,
                'status' => $status,
                'limit' => 200,
            )
        );

        ?>
        <h1><?php echo esc_html__('Fortrydelser', 'rs-digital-fortrydelsesret'); ?></h1>
        <?php self::render_admin_notice(); ?>

        <form method="get" class="digital-fortrydelse-admin-filter">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE); ?>" />
            <p class="search-box">
                <label class="screen-reader-text" for="digital-fortrydelse-search-input"><?php echo esc_html__('Søg fortrydelser', 'rs-digital-fortrydelsesret'); ?></label>
                <input id="digital-fortrydelse-search-input" type="search" name="s" value="<?php echo esc_attr($search); ?>" />
                <?php self::render_status_select($status, true); ?>
                <?php submit_button(__('Filtrer', 'rs-digital-fortrydelsesret'), '', '', false); ?>
                <a class="button" href="<?php echo esc_url(self::export_url($search, $status)); ?>"><?php echo esc_html__('Eksporter CSV', 'rs-digital-fortrydelsesret'); ?></a>
            </p>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Reference', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Dato', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Kunde', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Ordre', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Type', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Status', 'rs-digital-fortrydelsesret'); ?></th>
                    <th><?php echo esc_html__('Frist', 'rs-digital-fortrydelsesret'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($cases) : ?>
                    <?php foreach ($cases as $case) : ?>
                        <tr>
                            <td><a href="<?php echo esc_url(self::detail_url($case->id)); ?>"><?php echo esc_html($case->reference); ?></a></td>
                            <td><?php echo esc_html(self::format_datetime($case->submitted_at)); ?></td>
                            <td><?php echo esc_html($case->customer_name); ?><br /><small><?php echo esc_html($case->customer_email); ?></small></td>
                            <td><?php echo esc_html($case->order_number); ?></td>
                            <td><?php echo esc_html(self::request_type_label($case->request_type)); ?></td>
                            <td><?php echo esc_html(self::status_label($case->status)); ?></td>
                            <td><?php echo wp_kses_post(self::deadline_status_badge($case->deadline_status)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php echo esc_html__('Ingen fortrydelser fundet.', 'rs-digital-fortrydelsesret'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render a single case detail page.
     *
     * @param int $case_id Case ID.
     * @return void
     */
    private static function render_detail($case_id)
    {
        $case = RS_FR_Repository::get($case_id);

        if (!$case) {
            echo '<h1>' . esc_html__('Fortrydelse ikke fundet', 'rs-digital-fortrydelsesret') . '</h1>';
            echo '<p><a href="' . esc_url(self::list_url()) . '">' . esc_html__('Tilbage til oversigten', 'rs-digital-fortrydelsesret') . '</a></p>';
            return;
        }

        ?>
        <h1><?php echo esc_html(sprintf(
            /* translators: %s: cancellation request reference. */
            __('Fortrydelse %s', 'rs-digital-fortrydelsesret'),
            $case->reference
        )); ?></h1>
        <?php self::render_admin_notice(); ?>
        <p><a href="<?php echo esc_url(self::list_url()); ?>"><?php echo esc_html__('Tilbage til oversigten', 'rs-digital-fortrydelsesret'); ?></a></p>

        <table class="widefat striped">
            <tbody>
                <?php self::detail_row(__('Reference', 'rs-digital-fortrydelsesret'), $case->reference); ?>
                <?php self::detail_row(__('Status', 'rs-digital-fortrydelsesret'), self::status_label($case->status)); ?>
                <?php self::detail_row(__('Indsendt', 'rs-digital-fortrydelsesret'), self::format_datetime($case->submitted_at)); ?>
                <?php self::detail_row(__('Navn', 'rs-digital-fortrydelsesret'), $case->customer_name); ?>
                <?php self::detail_row(__('Mailadresse', 'rs-digital-fortrydelsesret'), $case->customer_email); ?>
                <?php self::detail_row(__('Ordre-mailadresse', 'rs-digital-fortrydelsesret'), $case->order_email ? $case->order_email : __('Ikke fundet', 'rs-digital-fortrydelsesret')); ?>
                <?php self::detail_row(__('E-mailafvigelse', 'rs-digital-fortrydelsesret'), $case->email_mismatch ? __('Ja', 'rs-digital-fortrydelsesret') : __('Nej', 'rs-digital-fortrydelsesret')); ?>
                <?php self::detail_row(__('Ordrenummer', 'rs-digital-fortrydelsesret'), $case->order_number); ?>
                <?php self::detail_row(__('Ordre', 'rs-digital-fortrydelsesret'), self::order_link($case)); ?>
                <?php self::detail_row(__('Ordredato', 'rs-digital-fortrydelsesret'), $case->order_date ? self::format_datetime($case->order_date) : __('Ukendt', 'rs-digital-fortrydelsesret')); ?>
                <?php self::detail_row(__('Frist', 'rs-digital-fortrydelsesret'), $case->deadline_at ? self::format_datetime($case->deadline_at) : __('Ukendt', 'rs-digital-fortrydelsesret')); ?>
                <?php self::detail_row(__('Friststatus', 'rs-digital-fortrydelsesret'), self::deadline_status_badge($case->deadline_status)); ?>
                <?php self::detail_row(__('Fortrydelse', 'rs-digital-fortrydelsesret'), self::request_type_label($case->request_type)); ?>
                <?php self::detail_row(__('Indsendt indhold', 'rs-digital-fortrydelsesret'), nl2br(esc_html($case->request_message ? $case->request_message : __('Hele ordren', 'rs-digital-fortrydelsesret')))); ?>
                <?php self::detail_row(__('Kundekvittering sendt', 'rs-digital-fortrydelsesret'), $case->receipt_sent_at ? self::format_datetime($case->receipt_sent_at) : __('Nej', 'rs-digital-fortrydelsesret')); ?>
                <?php self::detail_row(__('Intern mail sendt', 'rs-digital-fortrydelsesret'), $case->internal_notification_sent_at ? self::format_datetime($case->internal_notification_sent_at) : __('Nej', 'rs-digital-fortrydelsesret')); ?>
            </tbody>
        </table>

        <h2><?php echo esc_html__('Behandling', 'rs-digital-fortrydelsesret'); ?></h2>
        <details class="digital-fortrydelse-admin-help">
            <summary><?php echo esc_html__('Hvad sker der, når status ændres?', 'rs-digital-fortrydelsesret'); ?></summary>
            <p><?php echo esc_html__('Når du ændrer status og klikker Gem behandling, opdateres kun den interne fortrydelsessag.', 'rs-digital-fortrydelsesret'); ?></p>
            <ul>
                <li><?php echo esc_html__('Sagens status bliver gemt.', 'rs-digital-fortrydelsesret'); ?></li>
                <li><?php echo esc_html__('Den interne note bliver gemt.', 'rs-digital-fortrydelsesret'); ?></li>
                <li><?php echo esc_html__('Tidspunktet for seneste opdatering bliver gemt.', 'rs-digital-fortrydelsesret'); ?></li>
            </ul>
            <p><?php echo esc_html__('Der ændres ikke automatisk på WooCommerce-ordren: ordrestatus, refundering, lager og kundemails håndteres fortsat manuelt i denne version.', 'rs-digital-fortrydelsesret'); ?></p>
        </details>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr(self::STATUS_ACTION); ?>" />
            <input type="hidden" name="case_id" value="<?php echo esc_attr($case->id); ?>" />
            <?php wp_nonce_field(self::STATUS_ACTION . '_' . $case->id); ?>
            <p>
                <label for="digital-fortrydelse-status"><?php echo esc_html__('Status', 'rs-digital-fortrydelsesret'); ?></label><br />
                <?php self::render_status_select($case->status, false, 'digital-fortrydelse-status'); ?>
            </p>
            <p>
                <label for="digital-fortrydelse-admin-note"><?php echo esc_html__('Intern note', 'rs-digital-fortrydelsesret'); ?></label><br />
                <textarea id="digital-fortrydelse-admin-note" name="admin_note" class="large-text" rows="5"><?php echo esc_textarea($case->admin_note); ?></textarea>
            </p>
            <?php submit_button(__('Gem behandling', 'rs-digital-fortrydelsesret')); ?>
        </form>
        <?php
    }

    /**
     * Handle status update.
     *
     * @return void
     */
    public static function handle_status_update()
    {
        if (!self::current_user_can_manage()) {
            wp_die(esc_html__('Du har ikke adgang til denne handling.', 'rs-digital-fortrydelsesret'));
        }


        $case_id = isset($_POST['case_id']) ? absint($_POST['case_id']) : 0;

        check_admin_referer(self::STATUS_ACTION . '_' . $case_id);

        $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
        $admin_note = isset($_POST['admin_note']) ? sanitize_textarea_field(wp_unslash($_POST['admin_note'])) : '';
        $updated = RS_FR_Repository::update_status($case_id, $status, $admin_note);

        wp_safe_redirect(
            add_query_arg(
                array(
                    'digital_fortrydelse_case' => $case_id,
                    'updated' => $updated ? '1' : '0',
                ),
                self::list_url()
            )
        );
        exit;
    }

    /**
     * Export filtered cases as CSV.
     *
     * @return void
     */
    public static function handle_export()
    {
        if (!self::current_user_can_manage()) {
            wp_die(esc_html__('Du har ikke adgang til denne handling.', 'rs-digital-fortrydelsesret'));
        }


        check_admin_referer(self::EXPORT_ACTION);

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $cases = RS_FR_Repository::query(
            array(
                'search' => $search,
                'status' => $status,
                'limit' => 500,
            )
        );

        nocache_headers();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=digital-fortrydelser-' . gmdate('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv(
            $output,
            self::csv_row(
                array(
                    'reference',
                    'status',
                    'submitted_at',
                    'customer_name',
                    'customer_email',
                    'order_number',
                    'request_type',
                    'deadline_status',
                    'email_mismatch',
                )
            )
        );

        foreach ($cases as $case) {
            fputcsv(
                $output,
                self::csv_row(
                    array(
                        $case->reference,
                        $case->status,
                        $case->submitted_at,
                        $case->customer_name,
                        $case->customer_email,
                        $case->order_number,
                        $case->request_type,
                        $case->deadline_status,
                        $case->email_mismatch,
                    )
                )
            );
        }

        fclose($output);
        exit;
    }

    /**
     * Render status select.
     *
     * @param string $selected Selected status.
     * @param bool   $include_all Include all option.
     * @param string $id Field ID.
     * @return void
     */
    private static function render_status_select($selected, $include_all = false, $id = '')
    {
        $labels = self::status_labels();
        ?>
        <select name="status" <?php echo $id ? 'id="' . esc_attr($id) . '"' : ''; ?>>
            <?php if ($include_all) : ?>
                <option value=""><?php echo esc_html__('Alle statusser', 'rs-digital-fortrydelsesret'); ?></option>
            <?php endif; ?>
            <?php foreach ($labels as $status => $label) : ?>
                <option value="<?php echo esc_attr($status); ?>" <?php selected($selected, $status); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render detail row.
     *
     * @param string $label Row label.
     * @param string $value Row value.
     * @return void
     */
    private static function detail_row($label, $value)
    {
        echo '<tr><th scope="row">' . esc_html($label) . '</th><td>' . wp_kses_post($value) . '</td></tr>';
    }

    /**
     * Sanitize a row for spreadsheet-safe CSV output.
     *
     * @param array $row CSV row values.
     * @return array
     */
    private static function csv_row($row)
    {
        return array_map(array(__CLASS__, 'csv_cell'), $row);
    }

    /**
     * Prevent spreadsheet formula execution from exported user-controlled data.
     *
     * @param mixed $value CSV cell value.
     * @return string
     */
    private static function csv_cell($value)
    {
        $value = (string) $value;

        if (preg_match('/^\s*[=+\-@]/', $value) || preg_match('/^[\t\r\n]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Render admin update notice.
     *
     * @return void
     */
    private static function render_admin_notice()
    {
        if (!isset($_GET['updated'])) {
            return;
        }

        $success = '1' === sanitize_text_field(wp_unslash($_GET['updated']));
        $class = $success ? 'notice notice-success' : 'notice notice-error';
        $message = $success
            ? __('Sagen blev opdateret.', 'rs-digital-fortrydelsesret')
            : __('Sagen kunne ikke opdateres.', 'rs-digital-fortrydelsesret');

        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($message) . '</p></div>';
    }

    /**
     * Get order admin link.
     *
     * @param object $case Case row.
     * @return string
     */
    private static function order_link($case)
    {
        if (!$case->order_id) {
            return esc_html__('Ikke fundet', 'rs-digital-fortrydelsesret');
        }

        $url = get_edit_post_link($case->order_id, '');

        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            $url = admin_url('admin.php?page=wc-orders&action=edit&id=' . absint($case->order_id));
        }

        return '<a href="' . esc_url($url) . '">' . esc_html(sprintf(
            /* translators: %s: WooCommerce order number. */
            __('Åbn ordre #%s', 'rs-digital-fortrydelsesret'),
            $case->order_number
        )) . '</a>';
    }

    /**
     * Status labels.
     *
     * @return array
     */
    private static function status_labels()
    {
        return array(
            'received' => __('Modtaget', 'rs-digital-fortrydelsesret'),
            'processing' => __('Under behandling', 'rs-digital-fortrydelsesret'),
            'approved' => __('Godkendt', 'rs-digital-fortrydelsesret'),
            'rejected' => __('Afvist', 'rs-digital-fortrydelsesret'),
            'completed' => __('Fuldført', 'rs-digital-fortrydelsesret'),
        );
    }

    /**
     * Get status label.
     *
     * @param string $status Status key.
     * @return string
     */
    private static function status_label($status)
    {
        $labels = self::status_labels();

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Render deadline status as an admin badge.
     *
     * @param string $deadline_status Deadline status key.
     * @return string
     */
    private static function deadline_status_badge($deadline_status)
    {
        $status = sanitize_key($deadline_status);
        $labels = self::deadline_status_labels();
        $label = isset($labels[$status]) ? $labels[$status] : $deadline_status;

        return sprintf(
            '<span class="digital-fortrydelse-deadline-badge digital-fortrydelse-deadline-badge--%s">%s</span>',
            esc_attr($status ? $status : 'unknown'),
            esc_html($label)
        );
    }

    /**
     * Deadline status labels.
     *
     * @return array
     */
    private static function deadline_status_labels()
    {
        return array(
            'within_deadline' => __('Inden for frist', 'rs-digital-fortrydelsesret'),
            'expired' => __('Overskredet', 'rs-digital-fortrydelsesret'),
            'unknown' => __('Ukendt', 'rs-digital-fortrydelsesret'),
        );
    }

    /**
     * Get request type label.
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

    /**
     * List URL.
     *
     * @return string
     */
    private static function list_url()
    {
        return admin_url('admin.php?page=' . self::PAGE);
    }

    /**
     * Detail URL.
     *
     * @param int $case_id Case ID.
     * @return string
     */
    private static function detail_url($case_id)
    {
        return add_query_arg('digital_fortrydelse_case', absint($case_id), self::list_url());
    }

    /**
     * Export URL for current filters.
     *
     * @param string $search Search query.
     * @param string $status Status filter.
     * @return string
     */
    private static function export_url($search, $status)
    {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'action' => self::EXPORT_ACTION,
                    's' => $search,
                    'status' => $status,
                ),
                admin_url('admin-post.php')
            ),
            self::EXPORT_ACTION
        );
    }
}
