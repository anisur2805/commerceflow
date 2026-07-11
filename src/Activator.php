<?php

declare(strict_types=1);

namespace CommerceFlow;

/**
 * Handle plugin activation tasks.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		// Flush rewrite rules for future REST endpoints.
		flush_rewrite_rules();

		// Create automation tables (v0.2).
		self::create_tables();
	}

	/**
	 * Create the rules + rule-log tables.
	 *
	 * Uses raw CREATE TABLE IF NOT EXISTS because dbDelta is designed
	 * for ALTER TABLE and fails silently with fresh tables in some
	 * contexts. The tables_exist() gate in Bootstrap ensures this
	 * never runs after the table is created.
	 */
	public static function create_tables(): bool {
		global $wpdb;

		// Skips if tables already exist (noop on subsequent loads).
		if ( self::tables_exist() ) {
			return true;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'commerceflow_';

		$rules_sql = "CREATE TABLE IF NOT EXISTS {$prefix}rules (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			`trigger` VARCHAR(50) NOT NULL,
			trigger_config LONGTEXT NULL,
			conditions LONGTEXT NULL,
			actions LONGTEXT NULL,
			enabled TINYINT(1) NOT NULL DEFAULT 0,
			priority INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			INDEX idx_cf_rules_trigger (`trigger`),
			INDEX idx_cf_rules_enabled (enabled)
		) {$charset_collate};";

		$logs_sql = "CREATE TABLE IF NOT EXISTS {$prefix}rule_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			rule_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			`trigger` VARCHAR(50) NOT NULL,
			status VARCHAR(20) NOT NULL,
			run_id VARCHAR(100) NULL,
			detail LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			INDEX idx_cf_logs_rule (rule_id),
			INDEX idx_cf_logs_order (order_id),
			INDEX idx_cf_logs_status (status)
		) {$charset_collate};";

		// Order timeline events (v0.3).
		$events_sql = "CREATE TABLE IF NOT EXISTS {$prefix}order_events (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(30) NOT NULL,
			from_status VARCHAR(50) NULL,
			to_status VARCHAR(50) NULL,
			actor VARCHAR(191) NULL,
			note LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			INDEX idx_cf_events_order (order_id)
		) {$charset_collate};";

		// Shipping rules (v0.4).
		$shipping_sql = "CREATE TABLE IF NOT EXISTS {$prefix}shipping_rules (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			conditions LONGTEXT NULL,
			rate LONGTEXT NULL,
			enabled TINYINT(1) NOT NULL DEFAULT 0,
			priority INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			INDEX idx_cf_shipping_enabled (enabled)
		) {$charset_collate};";

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $rules_sql );
		$rules_ok = ! $wpdb->last_error;

		$wpdb->query( $logs_sql );
		$logs_ok = ! $wpdb->last_error;

		$wpdb->query( $events_sql );
		$events_ok = ! $wpdb->last_error;

		$wpdb->query( $shipping_sql );
		$shipping_ok = ! $wpdb->last_error;
		// phpcs:enable

		return $rules_ok && $logs_ok && $events_ok && $shipping_ok;
	}

	/**
	 * Check whether all plugin tables exist.
	 */
	public static function tables_exist(): bool {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'commerceflow_rules',
			$wpdb->prefix . 'commerceflow_rule_logs',
			$wpdb->prefix . 'commerceflow_order_events',
			$wpdb->prefix . 'commerceflow_shipping_rules',
		);

		foreach ( $tables as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			if ( empty( $found ) ) {
				return false;
			}
		}

		return true;
	}
}
