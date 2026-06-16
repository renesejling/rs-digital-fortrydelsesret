<?php
/**
 * Oprydning ved afinstallering af RS Digital Fortrydelsesret.
 *
 * @package RS_FR
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'RS_FR_TABLE' ) ) {
	define( 'RS_FR_TABLE', 'digital_fortrydelser' );
}

require_once __DIR__ . '/includes/class-rs-fr-schema.php';
require_once __DIR__ . '/includes/class-rs-fr-retention.php';

$settings    = get_option( 'digital_fortrydelse_settings', array() );
$delete_data = is_array( $settings ) && ! empty( $settings['delete_data_on_uninstall'] );

delete_option( 'digital_fortrydelse_version' );
delete_option( 'digital_fortrydelse_db_version' );
wp_clear_scheduled_hook( RS_FR_Retention::CRON_HOOK );

$roles = array( 'administrator', 'shop_manager' );

foreach ( $roles as $role_name ) {
	$role = get_role( $role_name );

	if ( $role ) {
		$role->remove_cap( 'manage_digital_fortrydelse' );
	}
}

if ( $delete_data ) {
	global $wpdb;

	$wpdb->query( 'DROP TABLE IF EXISTS ' . RS_FR_Schema::withdrawals_table_name() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	delete_option( 'digital_fortrydelse_settings' );
}
