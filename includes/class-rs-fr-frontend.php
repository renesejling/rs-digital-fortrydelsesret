<?php
/**
 * Public withdrawal form.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders and handles the public withdrawal form.
 */
final class RS_FR_Frontend
{
    const ACTION = 'digital_fortrydelse_submit';
    const SHORTCODE = 'digital_fortrydelse';
    const RATE_LIMIT_MAX = 3;
    const RATE_LIMIT_WINDOW = 15 * MINUTE_IN_SECONDS;

    /**
     * Register hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_shortcode(self::SHORTCODE, array(__CLASS__, 'render_shortcode'));
        add_action('admin_post_nopriv_' . self::ACTION, array(__CLASS__, 'handle_submit'));
        add_action('admin_post_' . self::ACTION, array(__CLASS__, 'handle_submit'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'register_assets'));
    }

    /**
     * Register frontend assets.
     *
     * @return void
     */
    public static function register_assets()
    {
        wp_register_style(
            'digital-fortrydelse-frontend',
            RS_FR_PLUGIN_URL . 'assets/frontend/digital-fortrydelse.css',
            array(),
            RS_FR_VERSION
        );
    }

    /**
     * Render the shortcode form.
     *
     * @return string
     */
    public static function render_shortcode()
    {
        wp_enqueue_style('digital-fortrydelse-frontend');

        $errors = self::get_redirect_errors();
        $success_reference = isset($_GET['digital_fortrydelse_reference'])
            ? sanitize_text_field(wp_unslash($_GET['digital_fortrydelse_reference']))
            : '';

        ob_start();
        ?>
        <div class="digital-fortrydelse">
            <?php if ($success_reference) : ?>
                <div class="digital-fortrydelse__notice digital-fortrydelse__notice--success" role="status">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: cancellation request reference. */
                            esc_html__('Din anmodning om fortrydelse er modtaget. Din reference er %s.', 'rs-digital-fortrydelsesret'),
                            '<strong>' . esc_html($success_reference) . '</strong>'
                        );
                        ?>
                    </p>
                    <p><?php echo esc_html__('Der er sendt en kvittering til den mailadresse, du har angivet.', 'rs-digital-fortrydelsesret'); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($errors) : ?>
                <div class="digital-fortrydelse__notice digital-fortrydelse__notice--error" role="alert">
                    <ul>
                        <?php foreach ($errors as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <p class="digital-fortrydelse__intro"><?php echo wp_kses_post(self::get_form_intro()); ?></p>
            <p class="digital-fortrydelse__legal"><?php echo esc_html__('Kvitteringen bekræfter kun, at vi har modtaget din anmodning om fortrydelse. Du hører fra os med en endelig afgørelse.', 'rs-digital-fortrydelsesret'); ?></p>

            <form class="digital-fortrydelse__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
                <input type="hidden" name="digital_fortrydelse_return_url" value="<?php echo esc_url(self::current_url()); ?>" />
                <?php wp_nonce_field(self::ACTION, 'digital_fortrydelse_nonce'); ?>

                <p class="digital-fortrydelse__hp" aria-hidden="true">
                    <label for="digital_fortrydelse_website"><?php echo esc_html__('Website', 'rs-digital-fortrydelsesret'); ?></label>
                    <input id="digital_fortrydelse_website" type="text" name="digital_fortrydelse_website" tabindex="-1" autocomplete="off" />
                </p>

                <p>
                    <label for="digital_fortrydelse_name"><?php echo esc_html__('Navn', 'rs-digital-fortrydelsesret'); ?> <span aria-hidden="true">*</span></label>
                    <input id="digital_fortrydelse_name" type="text" name="digital_fortrydelse_name" autocomplete="name" required />
                </p>

                <p>
                    <label for="digital_fortrydelse_email"><?php echo esc_html__('Mailadresse', 'rs-digital-fortrydelsesret'); ?> <span aria-hidden="true">*</span></label>
                    <input id="digital_fortrydelse_email" type="email" name="digital_fortrydelse_email" autocomplete="email" required />
                </p>

                <p>
                    <label for="digital_fortrydelse_order_number"><?php echo esc_html__('Ordrenummer', 'rs-digital-fortrydelsesret'); ?> <span aria-hidden="true">*</span></label>
                    <input id="digital_fortrydelse_order_number" type="text" name="digital_fortrydelse_order_number" inputmode="numeric" required />
                </p>

                <fieldset>
                    <legend><?php echo esc_html__('Hvad vil du fortryde?', 'rs-digital-fortrydelsesret'); ?></legend>
                    <label>
                        <input type="radio" name="digital_fortrydelse_request_type" value="full_order" checked />
                        <?php echo esc_html__('Hele ordren', 'rs-digital-fortrydelsesret'); ?>
                    </label>
                    <label>
                        <input type="radio" name="digital_fortrydelse_request_type" value="partial" />
                        <?php echo esc_html__('Enkelte produkter', 'rs-digital-fortrydelsesret'); ?>
                    </label>
                </fieldset>

                <p>
                    <label for="digital_fortrydelse_requested_items"><?php echo esc_html__('Produkter eller bemærkning', 'rs-digital-fortrydelsesret'); ?></label>
                    <textarea id="digital_fortrydelse_requested_items" name="digital_fortrydelse_requested_items" rows="4"></textarea>
                </p>

                <p>
                    <button type="submit" class="digital-fortrydelse__button"><?php echo esc_html__('Bekræft fortrydelse', 'rs-digital-fortrydelsesret'); ?></button>
                </p>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Handle a submitted withdrawal form.
     *
     * @return void
     */
    public static function handle_submit()
    {
        $return_url = self::get_return_url();
        $errors = self::validate_submission();

        if ($errors) {
            self::redirect_with_errors($return_url, $errors);
        }

        $submitted_at = current_time('mysql');
        $request_type = self::post_text('digital_fortrydelse_request_type');
        $requested_items = self::post_textarea('digital_fortrydelse_requested_items');

        $payload = array(
            'name' => self::post_text('digital_fortrydelse_name'),
            'email' => self::post_email('digital_fortrydelse_email'),
            'order_number' => self::post_text('digital_fortrydelse_order_number'),
            'request_type' => $request_type,
            'requested_items' => $requested_items,
            'submitted_at' => $submitted_at,
            'ip_hash' => self::ip_hash(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
        );

        $order_context = RS_FR_WooCommerce::get_order_context(
            $payload['order_number'],
            $payload['email'],
            $submitted_at
        );

        self::increment_rate_limit();

        $order_errors = self::validate_order_submission($order_context, $request_type, $requested_items);

        if ($order_errors) {
            self::redirect_with_errors($return_url, $order_errors);
        }

        $payload['order'] = $order_context;

        $created = RS_FR_Repository::create(
            array(
                'order_id' => $order_context['order_id'],
                'order_number' => $order_context['order_number'] ? $order_context['order_number'] : $payload['order_number'],
                'order_date' => $order_context['order_date'],
                'customer_user_id' => $order_context['customer_user_id'],
                'customer_name' => $payload['name'],
                'customer_email' => $payload['email'],
                'order_email' => $order_context['order_email'],
                'email_mismatch' => $order_context['email_mismatch'],
                'request_type' => $request_type,
                'requested_items' => $requested_items,
                'request_message' => $requested_items,
                'request_payload' => wp_json_encode($payload),
                'deadline_at' => $order_context['deadline_at'],
                'deadline_status' => $order_context['deadline_status'],
                'metadata' => wp_json_encode(
                    array(
                        'source' => 'frontend_shortcode',
                        'ip_hash' => $payload['ip_hash'],
                        'user_agent' => $payload['user_agent'],
                        'order_found' => $order_context['found'],
                        'order_items' => 'partial' === $request_type ? $order_context['items'] : array(),
                    )
                ),
                'retention_until' => RS_FR_Retention::calculate_retention_until($submitted_at),
                'submitted_at' => $submitted_at,
            )
        );

        if (is_wp_error($created)) {
            self::redirect_with_errors($return_url, array($created->get_error_message()));
        }

        RS_FR_Mailer::send_submission_mails($created);

        $case = RS_FR_Repository::get($created);
        $reference = $case ? $case->reference : '';

        wp_safe_redirect(
            add_query_arg(
                array(
                    'digital_fortrydelse_reference' => rawurlencode($reference),
                ),
                remove_query_arg(array('digital_fortrydelse_errors', 'digital_fortrydelse_reference'), $return_url)
            )
        );
        exit;
    }

    /**
     * Validate form submission.
     *
     * @return string[]
     */
    private static function validate_submission()
    {
        $errors = array();

        if (
            !isset($_POST['digital_fortrydelse_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['digital_fortrydelse_nonce'])), self::ACTION)
        ) {
            $errors[] = __('Formularen kunne ikke valideres. Genindlæs siden og prøv igen.', 'rs-digital-fortrydelsesret');
        }

        if (self::post_text('digital_fortrydelse_website')) {
            $errors[] = __('Fortrydelsen kunne ikke sendes.', 'rs-digital-fortrydelsesret');
        }

        if (self::is_rate_limited()) {
            $errors[] = __('Der er sendt for mange anmodninger fra samme forbindelse. Prøv igen senere.', 'rs-digital-fortrydelsesret');
        }

        if ('' === self::post_text('digital_fortrydelse_name')) {
            $errors[] = __('Indtast dit navn.', 'rs-digital-fortrydelsesret');
        }

        if (!is_email(self::post_email('digital_fortrydelse_email'))) {
            $errors[] = __('Indtast en gyldig mailadresse.', 'rs-digital-fortrydelsesret');
        }

        if ('' === self::post_text('digital_fortrydelse_order_number')) {
            $errors[] = __('Indtast ordrenummer.', 'rs-digital-fortrydelsesret');
        }

        $request_type = self::post_text('digital_fortrydelse_request_type');

        if (!in_array($request_type, RS_FR_Schema::request_types(), true)) {
            $errors[] = __('Vælg om du vil fortryde hele ordren eller enkelte produkter.', 'rs-digital-fortrydelsesret');
        }

        if ('partial' === $request_type && '' === self::post_textarea('digital_fortrydelse_requested_items')) {
            $errors[] = __('Skriv hvilke produkter du vil fortryde.', 'rs-digital-fortrydelsesret');
        }

        return $errors;
    }

    /**
     * Validate the submitted order context and duplicate rules.
     *
     * @param array  $order_context Normalized WooCommerce order context.
     * @param string $request_type Submitted request type.
     * @param string $requested_items Submitted partial request description.
     * @return string[]
     */
    private static function validate_order_submission($order_context, $request_type, $requested_items)
    {
        $errors = array();

        if (empty($order_context['found']) || !empty($order_context['email_mismatch'])) {
            $errors[] = __('Ordrenummer og mailadresse passer ikke sammen. Kontrollér oplysningerne og prøv igen.', 'rs-digital-fortrydelsesret');
            return $errors;
        }

        $duplicate = RS_FR_Repository::find_duplicate_for_order(
            $order_context['order_id'],
            $request_type,
            $requested_items
        );

        if (!$duplicate) {
            return $errors;
        }

        if ('partial' === $request_type && 'partial' === $duplicate->request_type) {
            $errors[] = __('Der findes allerede en fortrydelsessag for de angivne produkter på denne ordre. Skriv kun andre ordrelinjer, hvis du vil oprette en ny delvis fortrydelse.', 'rs-digital-fortrydelsesret');
            return $errors;
        }

        $errors[] = __('Der findes allerede en fortrydelsessag for denne ordre.', 'rs-digital-fortrydelsesret');

        return $errors;
    }

    /**
     * Redirect with compact error codes.
     *
     * @param string   $return_url Return URL.
     * @param string[] $errors Error messages.
     * @return void
     */
    private static function redirect_with_errors($return_url, $errors)
    {
        $key = 'digital_fortrydelse_errors_' . wp_generate_uuid4();

        set_transient($key, array_values($errors), 5 * MINUTE_IN_SECONDS);

        wp_safe_redirect(
            add_query_arg(
                array(
                    'digital_fortrydelse_errors' => rawurlencode($key),
                ),
                remove_query_arg(array('digital_fortrydelse_errors', 'digital_fortrydelse_reference'), $return_url)
            )
        );
        exit;
    }

    /**
     * Get errors stored for the redirected request.
     *
     * @return string[]
     */
    private static function get_redirect_errors()
    {
        if (empty($_GET['digital_fortrydelse_errors'])) {
            return array();
        }

        $key = sanitize_key(wp_unslash($_GET['digital_fortrydelse_errors']));
        $errors = get_transient($key);

        delete_transient($key);

        return is_array($errors) ? $errors : array();
    }

    /**
     * Get intro text.
     *
     * @return string
     */
    private static function get_form_intro()
    {
        $settings = RS_FR_Settings::get_settings();

        if (!empty($settings['form_intro'])) {
            return $settings['form_intro'];
        }

        return __('Udfyld formularen herunder for at fortryde dit køb. Du modtager en kvittering for din anmodning pr. e-mail.', 'rs-digital-fortrydelsesret');
    }

    /**
     * Get safe return URL.
     *
     * @return string
     */
    private static function get_return_url()
    {
        $return_url = isset($_POST['digital_fortrydelse_return_url'])
            ? esc_url_raw(wp_unslash($_POST['digital_fortrydelse_return_url']))
            : home_url('/');

        return wp_validate_redirect($return_url, home_url('/'));
    }

    /**
     * Get the current URL.
     *
     * @return string
     */
    private static function current_url()
    {
        global $wp;

        return home_url(add_query_arg(array(), $wp->request));
    }

    /**
     * Check if the requester is rate limited.
     *
     * @return bool
     */
    private static function is_rate_limited()
    {
        return self::rate_count() >= self::RATE_LIMIT_MAX;
    }

    /**
     * Increment rate limit counter.
     *
     * @return void
     */
    private static function increment_rate_limit()
    {
        $key = self::rate_limit_key();
        $count = self::rate_count();

        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);
    }

    /**
     * Get rate limit counter.
     *
     * @return int
     */
    private static function rate_count()
    {
        return (int) get_transient(self::rate_limit_key());
    }

    /**
     * Get rate limit transient key.
     *
     * @return string
     */
    private static function rate_limit_key()
    {
        return 'digital_fortrydelse_rate_' . self::ip_hash();
    }

    /**
     * Hash IP for rate limiting and metadata without storing the raw address.
     *
     * @return string
     */
    private static function ip_hash()
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

        return hash_hmac('sha256', $ip, wp_salt('nonce'));
    }

    /**
     * Get sanitized text field from POST.
     *
     * @param string $key POST key.
     * @return string
     */
    private static function post_text($key)
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    }

    /**
     * Get sanitized textarea from POST.
     *
     * @param string $key POST key.
     * @return string
     */
    private static function post_textarea($key)
    {
        return isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
    }

    /**
     * Get sanitized email from POST.
     *
     * @param string $key POST key.
     * @return string
     */
    private static function post_email($key)
    {
        return isset($_POST[$key]) ? sanitize_email(wp_unslash($_POST[$key])) : '';
    }
}
