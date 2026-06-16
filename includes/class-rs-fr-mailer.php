<?php
/**
 * Mail flow for withdrawal requests.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sends customer receipts and internal notifications.
 */
final class RS_FR_Mailer
{
    /**
     * Send all mails for a newly created withdrawal case.
     *
     * @param int $case_id Case ID.
     * @return array
     */
    public static function send_submission_mails($case_id)
    {
        $case = RS_FR_Repository::get($case_id);

        if (!$case) {
            return array(
                'customer' => false,
                'internal' => false,
            );
        }

        $customer_sent = self::send_customer_receipt($case);
        $internal_sent = self::send_internal_notification($case);

        if ($customer_sent) {
            RS_FR_Repository::mark_mail_sent($case_id, 'receipt_sent_at');
        }

        if ($internal_sent) {
            RS_FR_Repository::mark_mail_sent($case_id, 'internal_notification_sent_at');
        }

        return array(
            'customer' => $customer_sent,
            'internal' => $internal_sent,
        );
    }

    /**
     * Send receipt to the customer.
     *
     * @param object $case Case row.
     * @return bool
     */
    private static function send_customer_receipt($case)
    {
        if (!is_email($case->customer_email)) {
            return false;
        }

        return wp_mail(
            $case->customer_email,
            sprintf(
                /* translators: %s: cancellation request reference. */
                __('Kvittering for fortrydelse - %s', 'rs-digital-fortrydelsesret'),
                $case->reference
            ),
            self::customer_receipt_body($case),
            self::headers()
        );
    }

    /**
     * Send internal notification to shop.
     *
     * @param object $case Case row.
     * @return bool
     */
    private static function send_internal_notification($case)
    {
        $recipient = self::internal_recipient();

        if (!$recipient) {
            return false;
        }

        return wp_mail(
            $recipient,
            sprintf(
                /* translators: %s: cancellation request reference. */
                __('Ny fortrydelse modtaget - %s', 'rs-digital-fortrydelsesret'),
                $case->reference
            ),
            self::internal_notification_body($case),
            self::headers()
        );
    }

    /**
     * Customer receipt body.
     *
     * @param object $case Case row.
     * @return string
     */
    private static function customer_receipt_body($case)
    {
        $settings = RS_FR_Settings::get_settings();
        $template = $settings['customer_mail_template'] ? $settings['customer_mail_template'] : self::default_customer_template();

        return self::render_template($template, $case);
    }

    /**
     * Internal notification body.
     *
     * @param object $case Case row.
     * @return string
     */
    private static function internal_notification_body($case)
    {
        $settings = RS_FR_Settings::get_settings();
        $template = $settings['internal_mail_template'] ? $settings['internal_mail_template'] : self::default_internal_template();

        return self::render_template($template, $case);
    }

    /**
     * Default customer receipt template.
     *
     * @return string
     */
    public static function default_customer_template()
    {
        return implode(
            "\n",
            array(
                'Vi har modtaget din anmodning om fortrydelse.',
                '',
                'Denne kvittering bekræfter kun, at vi har modtaget din anmodning. Den er ikke en endelig afgørelse af sagen.',
                '',
                'Reference: {reference}',
                'Dato og tidspunkt: {submitted_at}',
                'Navn: {customer_name}',
                'Mailadresse: {customer_email}',
                'Ordrenummer: {order_number}',
                'Fortrydelse: {request_type}',
                '',
                'Indhold af din indsendelse:',
                '{submitted_content}',
                '',
                'Venlig hilsen',
                '{site_name}',
            )
        );
    }

    /**
     * Default internal notification template.
     *
     * @return string
     */
    public static function default_internal_template()
    {
        return implode(
            "\n",
            array(
                'Der er modtaget en ny digital fortrydelse.',
                '',
                'Reference: {reference}',
                'Dato og tidspunkt: {submitted_at}',
                'Status: {status}',
                'Navn: {customer_name}',
                'Indsendt mailadresse: {customer_email}',
                'Ordre-mailadresse: {order_email}',
                'E-mailafvigelse: {email_mismatch}',
                'Ordre-ID: {order_id}',
                'Ordrenummer: {order_number}',
                'Ordredato: {order_date}',
                'Frist: {deadline_at}',
                'Friststatus: {deadline_status}',
                'Fortrydelse: {request_type}',
                '',
                'Indhold af indsendelsen:',
                '{submitted_content}',
            )
        );
    }

    /**
     * Get internal recipient with WooCommerce fallback.
     *
     * @return string
     */
    private static function internal_recipient()
    {
        $settings = RS_FR_Settings::get_settings();

        if (!empty($settings['internal_recipient_email']) && is_email($settings['internal_recipient_email'])) {
            return $settings['internal_recipient_email'];
        }

        $woocommerce_recipient = self::woocommerce_new_order_recipient();

        if ($woocommerce_recipient) {
            return $woocommerce_recipient;
        }

        return get_option('admin_email');
    }

    /**
     * Get recipient from WooCommerce new order e-mail when available.
     *
     * @return string
     */
    private static function woocommerce_new_order_recipient()
    {
        if (!function_exists('WC') || !WC() || !WC()->mailer()) {
            return '';
        }

        $emails = WC()->mailer()->get_emails();

        if (empty($emails['WC_Email_New_Order']) || !method_exists($emails['WC_Email_New_Order'], 'get_recipient')) {
            return '';
        }

        return $emails['WC_Email_New_Order']->get_recipient();
    }

    /**
     * Get mail headers.
     *
     * @return string[]
     */
    private static function headers()
    {
        return array('Content-Type: text/plain; charset=UTF-8');
    }

    /**
     * Format a label/value line.
     *
     * @param string $label Label.
     * @param mixed  $value Value.
     * @return string
     */
    private static function line($label, $value)
    {
        return $label . ': ' . wp_strip_all_tags((string) $value);
    }

    /**
     * Render a mail template using case tokens.
     *
     * @param string $template Mail template.
     * @param object $case Case row.
     * @return string
     */
    private static function render_template($template, $case)
    {
        $tokens = array(
            '{reference}' => $case->reference,
            '{status}' => $case->status,
            '{submitted_at}' => self::format_datetime($case->submitted_at),
            '{customer_name}' => $case->customer_name,
            '{customer_email}' => $case->customer_email,
            '{order_email}' => $case->order_email ? $case->order_email : __('Ikke fundet', 'rs-digital-fortrydelsesret'),
            '{email_mismatch}' => $case->email_mismatch ? __('Ja', 'rs-digital-fortrydelsesret') : __('Nej', 'rs-digital-fortrydelsesret'),
            '{order_id}' => $case->order_id ? $case->order_id : __('Ikke fundet', 'rs-digital-fortrydelsesret'),
            '{order_number}' => $case->order_number,
            '{order_date}' => $case->order_date ? self::format_datetime($case->order_date) : __('Ukendt', 'rs-digital-fortrydelsesret'),
            '{deadline_at}' => $case->deadline_at ? self::format_datetime($case->deadline_at) : __('Ukendt', 'rs-digital-fortrydelsesret'),
            '{deadline_status}' => $case->deadline_status,
            '{request_type}' => self::request_type_label($case->request_type),
            '{submitted_content}' => self::submitted_content($case),
            '{site_name}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        );

        return strtr($template, $tokens);
    }

    /**
     * Format submitted content.
     *
     * @param object $case Case row.
     * @return string
     */
    private static function submitted_content($case)
    {
        if ($case->request_message) {
            return $case->request_message;
        }

        if ('full_order' === $case->request_type) {
            return __('Kunden ønsker at fortryde hele ordren.', 'rs-digital-fortrydelsesret');
        }

        return __('Ingen yderligere bemærkning angivet.', 'rs-digital-fortrydelsesret');
    }

    /**
     * Human label for request type.
     *
     * @param string $request_type Request type.
     * @return string
     */
    private static function request_type_label($request_type)
    {
        if ('partial' === $request_type) {
            return __('Enkelte produkter', 'rs-digital-fortrydelsesret');
        }

        return __('Hele ordren', 'rs-digital-fortrydelsesret');
    }

    /**
     * Format MySQL datetime for mail output.
     *
     * @param string $datetime MySQL datetime.
     * @return string
     */
    private static function format_datetime($datetime)
    {
        $timestamp = strtotime($datetime);

        if (!$timestamp) {
            return $datetime;
        }

        return wp_date(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }
}
