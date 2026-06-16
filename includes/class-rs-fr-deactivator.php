<?php
/**
 * Plugin deactivation routines.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles cleanup when the plugin is deactivated.
 */
final class RS_FR_Deactivator
{
    /**
     * Run deactivation cleanup.
     *
     * @return void
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook(RS_FR_Retention::CRON_HOOK);
        flush_rewrite_rules();
    }
}
