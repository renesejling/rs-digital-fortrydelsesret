<?php
/**
 * Admin settings registration.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers plugin settings in WordPress admin.
 */
final class RS_FR_Settings
{
    const TERMS_START_MARKER = '<!-- digital-fortrydelse-terms-start -->';
    const TERMS_END_MARKER = '<!-- digital-fortrydelse-terms-end -->';

    /**
     * Register hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_notices', array(__CLASS__, 'render_sync_notice'));
        add_action('update_option_digital_fortrydelse_settings', array(__CLASS__, 'sync_terms_section_on_update'), 10, 2);
    }

    /**
     * Add settings page below WooCommerce when available.
     *
     * @return void
     */
    public static function add_settings_page()
    {
        add_submenu_page(
            'woocommerce',
            __('Digital Fortrydelse indstillinger', 'rs-digital-fortrydelsesret'),
            __('Fortrydelse indstillinger', 'rs-digital-fortrydelsesret'),
            self::menu_capability(),
            'digital-fortrydelse-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Capability til at vise indstillings-menupunktet (med manage_woocommerce-fallback).
     *
     * @return string
     */
    private static function menu_capability()
    {
        return current_user_can('manage_digital_fortrydelse') ? 'manage_digital_fortrydelse' : 'manage_woocommerce';
    }


    /**
     * Register settings and fields.
     *
     * @return void
     */
    public static function register_settings()
    {
        register_setting(
            'digital_fortrydelse',
            'digital_fortrydelse_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array(__CLASS__, 'sanitize_settings'),
                'default' => array(),
            )
        );

        add_settings_section(
            'digital_fortrydelse_general',
            __('Generelle indstillinger', 'rs-digital-fortrydelsesret'),
            '__return_false',
            'digital-fortrydelse-settings'
        );

        add_settings_field(
            'internal_recipient_email',
            __('Intern modtager', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_email_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'locale',
            __('Sprog', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_locale_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'retention_years',
            __('Opbevaring', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_retention_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'form_intro',
            __('Formulartekst', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_form_intro_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'form_outro',
            __('Tekst før knap', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_form_outro_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'customer_mail_template',
            __('Kundekvittering', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_customer_mail_template_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'internal_mail_template',
            __('Intern mail', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_internal_mail_template_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_field(
            'delete_data_on_uninstall',
            __('Afinstallering', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_delete_data_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_general'
        );

        add_settings_section(
            'digital_fortrydelse_order_email',
            __('Ordremail (fortrydelsestekst)', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_order_email_section_intro'),
            'digital-fortrydelse-settings'
        );

        add_settings_field(
            'order_email_heading',
            __('Overskrift', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_order_email_heading_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_order_email'
        );

        add_settings_field(
            'order_email_intro',
            __('Introtekst', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_order_email_intro_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_order_email'
        );

        add_settings_field(
            'order_email_link_text',
            __('Link-tekst', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_order_email_link_text_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_order_email'
        );

        add_settings_field(
            'order_email_pdf_note',
            __('PDF-note', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_order_email_pdf_note_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_order_email'
        );


        add_settings_section(
            'digital_fortrydelse_terms',
            __('Handelsbetingelser', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_terms_section_intro'),
            'digital-fortrydelse-settings'
        );

        add_settings_field(
            'terms_page_id',
            __('Handelsbetingelsesside', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_terms_page_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_terms'
        );

        add_settings_field(
            'terms_auto_sync',
            __('Automatisk indsættelse', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_terms_auto_sync_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_terms'
        );

        add_settings_field(
            'terms_section_text',
            __('Tekstafsnit', 'rs-digital-fortrydelsesret'),
            array(__CLASS__, 'render_terms_section_text_field'),
            'digital-fortrydelse-settings',
            'digital_fortrydelse_terms'
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Raw settings.
     * @return array
     */
    public static function sanitize_settings($input)
    {
        $input = is_array($input) ? $input : array();

        return array(
            'internal_recipient_email' => isset($input['internal_recipient_email']) ? sanitize_email($input['internal_recipient_email']) : '',
            'locale' => isset($input['locale']) && 'da_DK' === $input['locale'] ? 'da_DK' : 'da_DK',
            'retention_years' => isset($input['retention_years']) ? min(5, max(1, absint($input['retention_years']))) : 5,
            'delete_data_on_uninstall' => !empty($input['delete_data_on_uninstall']) ? 1 : 0,
            'form_intro' => isset($input['form_intro']) ? wp_kses_post($input['form_intro']) : '',
            'form_outro' => isset($input['form_outro']) ? wp_kses_post($input['form_outro']) : '',
            'customer_mail_template' => isset($input['customer_mail_template']) ? sanitize_textarea_field($input['customer_mail_template']) : '',
            'internal_mail_template' => isset($input['internal_mail_template']) ? sanitize_textarea_field($input['internal_mail_template']) : '',
            'order_email_heading' => isset($input['order_email_heading']) ? sanitize_text_field($input['order_email_heading']) : '',
            'order_email_intro' => isset($input['order_email_intro']) ? sanitize_textarea_field($input['order_email_intro']) : '',
            'order_email_link_text' => isset($input['order_email_link_text']) ? sanitize_text_field($input['order_email_link_text']) : '',
            'order_email_pdf_note' => isset($input['order_email_pdf_note']) ? sanitize_textarea_field($input['order_email_pdf_note']) : '',
            'terms_page_id' => isset($input['terms_page_id']) ? absint($input['terms_page_id']) : 0,

            'terms_auto_sync' => !empty($input['terms_auto_sync']) ? 1 : 0,
            'terms_section_text' => isset($input['terms_section_text']) ? wp_kses_post($input['terms_section_text']) : self::default_terms_section_text(),
        );
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public static function render_settings_page()
    {
        if (!current_user_can('manage_digital_fortrydelse') && !current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Du har ikke adgang til denne side.', 'rs-digital-fortrydelsesret'));
        }


        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Digital Fortrydelse', 'rs-digital-fortrydelsesret'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('digital_fortrydelse');
                do_settings_sections('digital-fortrydelse-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render internal recipient field.
     *
     * @return void
     */
    public static function render_email_field()
    {
        $settings = self::get_settings();
        ?>
        <input
            type="email"
            class="regular-text"
            name="digital_fortrydelse_settings[internal_recipient_email]"
            value="<?php echo esc_attr($settings['internal_recipient_email']); ?>"
            placeholder="<?php echo esc_attr__('Brug WooCommerce standardmodtager', 'rs-digital-fortrydelsesret'); ?>"
        />
        <p class="description"><?php echo esc_html__('Efterlad tom for at bruge modtageren fra WooCommerce nye ordre-mails.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render retention field.
     *
     * @return void
     */
    public static function render_retention_field()
    {
        $settings = self::get_settings();
        ?>
        <input
            type="number"
            min="1"
            max="5"
            name="digital_fortrydelse_settings[retention_years]"
            value="<?php echo esc_attr($settings['retention_years']); ?>"
        />
        <p class="description"><?php echo esc_html__('Antal år fortrydelsessager opbevares. Maksimum er 5 år.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render locale field.
     *
     * @return void
     */
    public static function render_locale_field()
    {
        $settings = self::get_settings();
        ?>
        <select name="digital_fortrydelse_settings[locale]">
            <option value="da_DK" <?php selected($settings['locale'], 'da_DK'); ?>><?php echo esc_html__('Dansk', 'rs-digital-fortrydelsesret'); ?></option>
        </select>
        <p class="description"><?php echo esc_html__('Første version er dansk. Feltet er lagt ind, så flere sprog kan tilføjes senere.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render form intro field.
     *
     * @return void
     */
    public static function render_form_intro_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text"
            rows="4"
            name="digital_fortrydelse_settings[form_intro]"
        ><?php echo esc_textarea($settings['form_intro']); ?></textarea>
        <?php
    }

    /**
     * Render form outro field (vises lige før knappen på formularen).
     *
     * @return void
     */
    public static function render_form_outro_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text"
            rows="4"
            name="digital_fortrydelse_settings[form_outro]"
        ><?php echo esc_textarea($settings['form_outro']); ?></textarea>
        <p class="description"><?php echo esc_html__('Valgfri tekst der vises i en infoboks lige før "Bekræft fortrydelse"-knappen. Brug fx til information om specialfremstillede varer. Efterlad tom for at skjule boksen. Simpel HTML som links og fed skrift er tilladt.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render customer mail template field.
     *
     * @return void
     */
    public static function render_customer_mail_template_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text code"
            rows="10"
            name="digital_fortrydelse_settings[customer_mail_template]"
            placeholder="<?php echo esc_attr(RS_FR_Mailer::default_customer_template()); ?>"
        ><?php echo esc_textarea($settings['customer_mail_template']); ?></textarea>
        <p class="description"><?php echo esc_html__('Kundekvitteringen må kun bekræfte modtagelse af anmodningen. Den må ikke formuleres som en endelig godkendelse eller refundering.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php self::render_template_help(); ?>
        <?php
    }

    /**
     * Render internal mail template field.
     *
     * @return void
     */
    public static function render_internal_mail_template_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text code"
            rows="12"
            name="digital_fortrydelse_settings[internal_mail_template]"
            placeholder="<?php echo esc_attr(RS_FR_Mailer::default_internal_template()); ?>"
        ><?php echo esc_textarea($settings['internal_mail_template']); ?></textarea>
        <?php self::render_template_help(); ?>
        <?php
    }

    /**
     * Render uninstall cleanup field.
     *
     * @return void
     */
    public static function render_delete_data_field()
    {
        $settings = self::get_settings();
        ?>
        <label>
            <input
                type="checkbox"
                name="digital_fortrydelse_settings[delete_data_on_uninstall]"
                value="1"
                <?php checked($settings['delete_data_on_uninstall'], 1); ?>
            />
            <?php echo esc_html__('Slet pluginets data ved afinstallering.', 'rs-digital-fortrydelsesret'); ?>
        </label>
        <?php
    }

    /**
     * Render intro text for order email settings.
     *
     * @return void
     */
    public static function render_order_email_section_intro()
    {
        ?>
        <p><?php echo esc_html__('Her kan du tilpasse teksten, der indsættes i WooCommerce ordremails (behandler-/færdigbehandlet-mails). Efterlad et felt tomt for at bruge pluginets indbyggede standardtekst (som også oversættes automatisk via WPML/Polylang). Udfyld felterne, hvis du fx også sælger specialfremstillede varer, der ikke er omfattet af fortrydelsesretten.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render order email heading field.
     *
     * @return void
     */
    public static function render_order_email_heading_field()
    {
        $settings = self::get_settings();
        ?>
        <input
            type="text"
            class="regular-text"
            name="digital_fortrydelse_settings[order_email_heading]"
            value="<?php echo esc_attr($settings['order_email_heading']); ?>"
            placeholder="<?php echo esc_attr(rs_fr_t('heading')); ?>"
        />
        <p class="description"><?php echo esc_html__('Overskriften på info-boksen i ordremailen. Standard:', 'rs-digital-fortrydelsesret'); ?> <code><?php echo esc_html(rs_fr_t('heading')); ?></code></p>
        <?php
    }

    /**
     * Render order email intro field.
     *
     * @return void
     */
    public static function render_order_email_intro_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text"
            rows="4"
            name="digital_fortrydelse_settings[order_email_intro]"
            placeholder="<?php echo esc_attr(rs_fr_t('intro')); ?>"
        ><?php echo esc_textarea($settings['order_email_intro']); ?></textarea>
        <p class="description"><?php echo esc_html__('Introteksten i info-boksen. Brug fx til at oplyse, at specialfremstillede varer ikke er omfattet af fortrydelsesretten. Standard:', 'rs-digital-fortrydelsesret'); ?> <code><?php echo esc_html(rs_fr_t('intro')); ?></code></p>
        <?php
    }

    /**
     * Render order email link text field.
     *
     * @return void
     */
    public static function render_order_email_link_text_field()
    {
        $settings = self::get_settings();
        ?>
        <input
            type="text"
            class="regular-text"
            name="digital_fortrydelse_settings[order_email_link_text]"
            value="<?php echo esc_attr($settings['order_email_link_text']); ?>"
            placeholder="<?php echo esc_attr(rs_fr_t('link_text')); ?>"
        />
        <p class="description"><?php echo esc_html__('Teksten på linket til fortrydelsesfunktionen. Standard:', 'rs-digital-fortrydelsesret'); ?> <code><?php echo esc_html(rs_fr_t('link_text')); ?></code></p>
        <?php
    }

    /**
     * Render order email PDF note field.
     *
     * @return void
     */
    public static function render_order_email_pdf_note_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text"
            rows="3"
            name="digital_fortrydelse_settings[order_email_pdf_note]"
            placeholder="<?php echo esc_attr(rs_fr_t('pdf_note')); ?>"
        ><?php echo esc_textarea($settings['order_email_pdf_note']); ?></textarea>
        <p class="description"><?php echo esc_html__('Noten om, at handelsbetingelserne er vedhæftet som PDF. Standard:', 'rs-digital-fortrydelsesret'); ?> <code><?php echo esc_html(rs_fr_t('pdf_note')); ?></code></p>
        <?php
    }

    /**
     * Render intro text for terms settings.
     *
     * @return void
     */
    public static function render_terms_section_intro()

    {
        ?>
        <p><?php echo esc_html__('Her kan teksten til handelsbetingelserne vedligeholdes. Når automatisk indsættelse er slået til, opdaterer pluginet sit eget markerede afsnit på den valgte side, hver gang indstillingerne gemmes.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render terms page selector.
     *
     * @return void
     */
    public static function render_terms_page_field()
    {
        $settings = self::get_settings();
        $selected = $settings['terms_page_id'] ? $settings['terms_page_id'] : self::woocommerce_terms_page_id();

        wp_dropdown_pages(
            array(
                'name' => 'digital_fortrydelse_settings[terms_page_id]',
                'selected' => $selected,
                'show_option_none' => __('Vælg side', 'rs-digital-fortrydelsesret'),
                'option_none_value' => '0',
            )
        );

        if (self::woocommerce_terms_page_id()) {
            echo '<p class="description">' . esc_html__('WooCommerce handelsbetingelsessiden er valgt som standard.', 'rs-digital-fortrydelsesret') . '</p>';
        } else {
            echo '<p class="description">' . esc_html__('Vælg den side, der bruges til handelsbetingelser. Hvis WooCommerce har en handelsbetingelsesside sat, bruges den som standard.', 'rs-digital-fortrydelsesret') . '</p>';
        }
    }

    /**
     * Render terms auto sync field.
     *
     * @return void
     */
    public static function render_terms_auto_sync_field()
    {
        $settings = self::get_settings();
        ?>
        <label>
            <input
                type="checkbox"
                name="digital_fortrydelse_settings[terms_auto_sync]"
                value="1"
                <?php checked($settings['terms_auto_sync'], 1); ?>
            />
            <?php echo esc_html__('Opdater automatisk afsnittet i handelsbetingelserne, når indstillingerne gemmes.', 'rs-digital-fortrydelsesret'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Pluginet erstatter kun indhold mellem sine egne start- og slutmarkører. Hvis markørerne ikke findes, tilføjes afsnittet nederst på siden.', 'rs-digital-fortrydelsesret'); ?></p>
        <?php
    }

    /**
     * Render terms text field.
     *
     * @return void
     */
    public static function render_terms_section_text_field()
    {
        $settings = self::get_settings();
        ?>
        <textarea
            class="large-text"
            rows="10"
            name="digital_fortrydelse_settings[terms_section_text]"
        ><?php echo esc_textarea($settings['terms_section_text']); ?></textarea>
        <p class="description">
            <?php echo esc_html__('Token:', 'rs-digital-fortrydelsesret'); ?>
            <code>{fortryd_aftale_url}</code>
        </p>
        <?php
    }

    /**
     * Get settings merged with defaults.
     *
     * @return array
     */
    public static function get_settings()
    {
        $defaults = array(
            'internal_recipient_email' => '',
            'locale' => 'da_DK',
            'retention_years' => 5,
            'delete_data_on_uninstall' => 0,
            'form_intro' => '',
            'form_outro' => '',
            'customer_mail_template' => '',
            'internal_mail_template' => '',
            'order_email_heading' => '',
            'order_email_intro' => '',
            'order_email_link_text' => '',
            'order_email_pdf_note' => '',
            'terms_page_id' => 0,

            'terms_auto_sync' => 0,
            'terms_section_text' => self::default_terms_section_text(),
        );

        $settings = get_option('digital_fortrydelse_settings', array());

        return wp_parse_args(is_array($settings) ? $settings : array(), $defaults);
    }

    /**
     * Default text inserted into terms and conditions.
     *
     * @return string
     */
    public static function default_terms_section_text()
    {
        return implode(
            "\n\n",
            array(
                'Du kan fortryde dit køb ved at bruge vores digitale fortrydelsesfunktion på webshoppen.',
                'Funktionen findes på siden Fortryd aftale: {fortryd_aftale_url}',
                'Når du udfylder og sender formularen, skal du oplyse navn, e-mailadresse og ordrenummer. Du kan vælge, om du ønsker at fortryde hele ordren eller enkelte produkter.',
                'Når formularen er sendt, modtager du uden unødig forsinkelse en kvittering pr. e-mail. Kvitteringen bekræfter, at vi har modtaget din anmodning om fortrydelse, og indeholder det indsendte indhold samt dato og tidspunkt for indsendelsen.',
                'Kvitteringen er alene en bekræftelse på modtagelse af din anmodning om fortrydelse. Den er ikke en endelig afgørelse af sagen.',
                'Du anses for at have fortrudt rettidigt, hvis du sender fortrydelsen via den digitale fortrydelsesfunktion inden fortrydelsesfristen udløber.',
            )
        );
    }

    /**
     * Sync terms section after settings are saved.
     *
     * @param array $old_value Previous settings.
     * @param array $value New settings.
     * @return void
     */
    public static function sync_terms_section_on_update($old_value, $value)
    {
        if (empty($value['terms_auto_sync'])) {
            return;
        }

        $page_id = !empty($value['terms_page_id']) ? absint($value['terms_page_id']) : self::woocommerce_terms_page_id();

        if (!$page_id || !get_post($page_id)) {
            set_transient('digital_fortrydelse_terms_sync_notice', 'missing_page', 60);
            return;
        }

        $updated = self::sync_terms_section($page_id, $value);

        set_transient('digital_fortrydelse_terms_sync_notice', $updated ? 'synced' : 'failed', 60);
    }

    /**
     * Render admin notice after terms sync.
     *
     * @return void
     */
    public static function render_sync_notice()
    {
        if (!current_user_can('manage_digital_fortrydelse')) {
            return;
        }

        $notice = get_transient('digital_fortrydelse_terms_sync_notice');

        if (!$notice) {
            return;
        }

        delete_transient('digital_fortrydelse_terms_sync_notice');

        if ('synced' === $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Afsnittet om digital fortrydelse blev opdateret på handelsbetingelsessiden.', 'rs-digital-fortrydelsesret') . '</p></div>';
            return;
        }

        if ('missing_page' === $notice) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Afsnittet kunne ikke indsættes, fordi der ikke er valgt en gyldig handelsbetingelsesside.', 'rs-digital-fortrydelsesret') . '</p></div>';
            return;
        }

        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Afsnittet kunne ikke opdateres på handelsbetingelsessiden.', 'rs-digital-fortrydelsesret') . '</p></div>';
    }

    /**
     * Sync the managed section into a page.
     *
     * @param int   $page_id Page ID.
     * @param array $settings Settings.
     * @return bool
     */
    private static function sync_terms_section($page_id, $settings)
    {
        $post = get_post($page_id);

        if (!$post) {
            return false;
        }

        $section = self::render_terms_section($settings);
        $content = (string) $post->post_content;
        $pattern = '/' . preg_quote(self::TERMS_START_MARKER, '/') . '.*?' . preg_quote(self::TERMS_END_MARKER, '/') . '/s';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $section, $content);
        } else {
            $content = rtrim($content) . "\n\n" . $section;
        }

        $result = wp_update_post(
            array(
                'ID' => $page_id,
                'post_content' => $content,
            ),
            true
        );

        return !is_wp_error($result);
    }

    /**
     * Render managed terms section.
     *
     * @param array $settings Settings.
     * @return string
     */
    private static function render_terms_section($settings)
    {
        $text = !empty($settings['terms_section_text']) ? $settings['terms_section_text'] : self::default_terms_section_text();
        $text = str_replace('{fortryd_aftale_url}', self::withdrawal_page_url(), $text);

        return self::TERMS_START_MARKER . "\n"
            . '<section class="digital-fortrydelse-terms">' . "\n"
            . '<h2>' . esc_html__('Digital fortrydelsesfunktion', 'rs-digital-fortrydelsesret') . '</h2>' . "\n"
            . wpautop(wp_kses_post($text)) . "\n"
            . '</section>' . "\n"
            . self::TERMS_END_MARKER;
    }

    /**
     * Get the WooCommerce terms page ID.
     *
     * @return int
     */
    private static function woocommerce_terms_page_id()
    {
        return absint(get_option('woocommerce_terms_page_id', 0));
    }

    /**
     * Get withdrawal page URL.
     *
     * @return string
     */
    private static function withdrawal_page_url()
    {
        $page = get_page_by_path('fortryd-aftale');

        if ($page) {
            return get_permalink($page);
        }

        return home_url('/fortryd-aftale/');
    }

    /**
     * Render available template tokens.
     *
     * @return void
     */
    private static function render_template_help()
    {
        ?>
        <p class="description">
            <?php echo esc_html__('Tilgængelige tokens:', 'rs-digital-fortrydelsesret'); ?>
            <code>{reference}</code>
            <code>{submitted_at}</code>
            <code>{customer_name}</code>
            <code>{customer_email}</code>
            <code>{order_number}</code>
            <code>{request_type}</code>
            <code>{submitted_content}</code>
            <code>{site_name}</code>
            <code>{deadline_status}</code>
            <code>{email_mismatch}</code>
        </p>
        <?php
    }
}
