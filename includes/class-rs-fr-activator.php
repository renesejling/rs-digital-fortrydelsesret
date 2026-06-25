<?php
/**
 * Plugin activation routines.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles setup tasks when the plugin is activated.
 */
final class RS_FR_Activator
{
    /**
     * Run activation setup.
     *
     * @return void
     */
    public static function activate()
    {
        self::create_tables();
        self::add_capabilities();
        self::add_default_options();
        self::schedule_retention_cleanup();
        self::flush_account_endpoint();

        update_option('digital_fortrydelse_version', RS_FR_VERSION, false);
        update_option('digital_fortrydelse_db_version', RS_FR_DB_VERSION, false);
    }

    /**
     * Ensure the custom table exists and matches the current schema.
     *
     * Kaldes både ved aktivering og fra bootstrap (ved en DB-version-bump),
     * så installationer der er opdateret via auto-opdatering – hvor
     * aktiverings-hooket ikke kører – også får oprettet/opdateret tabellen.
     *
     * @return void
     */
    public static function maybe_upgrade()
    {
        self::create_tables();

        update_option('digital_fortrydelse_db_version', RS_FR_DB_VERSION, false);
    }

    /**
     * Create the initial custom table for withdrawal requests.
     *
     * @return void
     */
    private static function create_tables()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta(RS_FR_Schema::withdrawals_table_sql());
    }


    /**
     * Add custom capabilities to common shop roles.
     *
     * Public + statisk så den også kan kaldes fra bootstrap ved version-bump
     * (ikke kun ved aktivering), så opdaterede installationer også får
     * capability'en og dermed admin-menuerne.
     *
     * @return void
     */
    public static function add_capabilities()
    {
        $roles = array('administrator', 'shop_manager');

        foreach ($roles as $role_name) {
            $role = get_role($role_name);

            if ($role && ! $role->has_cap('manage_digital_fortrydelse')) {
                $role->add_cap('manage_digital_fortrydelse');
            }
        }
    }


    /**
     * Add default options without overwriting existing settings.
     *
     * @return void
     */
    private static function add_default_options()
    {
        if (false !== get_option('digital_fortrydelse_settings', false)) {
            return;
        }

        add_option(
            'digital_fortrydelse_settings',
            array(
                'internal_recipient_email' => '',
                'locale' => 'da_DK',
                'retention_years' => 5,
                'delete_data_on_uninstall' => 0,
                'form_intro' => 'Udfyld formularen herunder for at fortryde dit køb. Du modtager en kvittering for din anmodning pr. e-mail.',
                'customer_mail_template' => '',
                'internal_mail_template' => '',
                'terms_page_id' => 0,
                'terms_auto_sync' => 0,
                'terms_section_text' => RS_FR_Settings::default_terms_section_text(),
            ),
            '',
            false
        );
    }

    /**
     * Schedule daily retention cleanup.
     *
     * @return void
     */
    private static function schedule_retention_cleanup()
    {
        if (!wp_next_scheduled(RS_FR_Retention::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', RS_FR_Retention::CRON_HOOK);
        }
    }

    /**
     * Register and flush account endpoint rewrite rules.
     *
     * @return void
     */
    private static function flush_account_endpoint()
    {
        RS_FR_Account::add_endpoint();
        flush_rewrite_rules();
    }
}
