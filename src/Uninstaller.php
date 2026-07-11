<?php

declare(strict_types=1);

namespace CommerceFlow;

/**
 * Handle plugin uninstall — clean up all plugin data.
 *
 * Runs outside the plugin namespace context via the uninstall hook,
 * so this is called statically from the main plugin file.
 */
class Uninstaller {

	/**
	 * Run on plugin uninstall.
	 *
	 * Clears options, transients, and any custom tables.
	 */
	public static function uninstall(): void {
		global $wpdb;

		// Remove plugin options.
		delete_option( 'commerceflow_settings' );
		delete_option( 'commerceflow_db_version' );

		// Remove cached data.
		delete_transient( 'commerceflow_dashboard_data' );

		// Drop plugin tables (v0.2 automation + v0.3 order events + v0.4 shipping).
		$rules_table    = $wpdb->prefix . 'commerceflow_rules';
		$logs_table     = $wpdb->prefix . 'commerceflow_rule_logs';
		$events_table   = $wpdb->prefix . 'commerceflow_order_events';
		$shipping_table = $wpdb->prefix . 'commerceflow_shipping_rules';
		$wpdb->query( "DROP TABLE IF EXISTS {$rules_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$logs_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$events_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$shipping_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
