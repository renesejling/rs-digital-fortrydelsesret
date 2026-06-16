<?php
/**
 * Retention cleanup.
 *
 * @package RS_FR
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles scheduled deletion of expired withdrawal cases.
 */
final class RS_FR_Retention
{
    const CRON_HOOK = 'digital_fortrydelse_retention_cleanup';

    /**
     * Register hooks.
     *
     * @return void
     */
    public static function init()
    {
        add_action(self::CRON_HOOK, array(__CLASS__, 'cleanup_expired_cases'));
    }

    /**
     * Delete cases whose retention date has passed.
     *
     * @return int Number of deleted rows.
     */
    public static function cleanup_expired_cases()
    {
        global $wpdb;

        $table_name = RS_FR_Schema::withdrawals_table_name();
        $now = current_time('mysql');

        return (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE retention_until IS NOT NULL AND retention_until < %s",
                $now
            )
        );
    }

    /**
     * Calculate retention date from a submission timestamp and settings.
     *
     * @param string $submitted_at MySQL datetime string.
     * @return string MySQL datetime string.
     */
    public static function calculate_retention_until($submitted_at)
    {
        $settings = get_option('digital_fortrydelse_settings', array());
        $years = 5;

        if (is_array($settings) && isset($settings['retention_years'])) {
            $years = min(5, max(1, absint($settings['retention_years'])));
        }

        $timestamp = strtotime($submitted_at);

        if (!$timestamp) {
            $timestamp = current_time('timestamp');
        }

        return date('Y-m-d H:i:s', strtotime('+' . $years . ' years', $timestamp));
    }
}
