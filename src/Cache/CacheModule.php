<?php

declare(strict_types=1);

namespace CommerceFlow\Cache;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;

/**
 * Cache module — wraps dashboard data in transients with TTL and invalidation hooks.
 */
class CacheModule implements ModuleInterface {

	/**
	 * Cache TTL in seconds (5 minutes).
	 *
	 * @var int
	 */
	public const CACHE_TTL = 300;

	/**
	 * Base transient key for dashboard data. The live key is version-scoped
	 * (see transient_key()) so a plugin upgrade that changes the payload
	 * shape never serves a stale blob missing newly-added fields.
	 *
	 * @var string
	 */
	public const DASHBOARD_TRANSIENT = 'commerceflow_dashboard_data';

	/**
	 * Version-scoped transient key for dashboard data.
	 *
	 * @return string
	 */
	public static function transient_key(): string {
		$version = defined( 'COMMERCEFLOW_VERSION' ) ? COMMERCEFLOW_VERSION : 'dev';
		return self::DASHBOARD_TRANSIENT . '_' . $version;
	}

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container|null $container DI container (optional for direct use).
	 */
	public function __construct( ?Container $container = null ) {
		if ( $container ) {
			$this->container = $container;
		}
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_new_order', array( $this, 'invalidate_dashboard' ) );
		add_action( 'woocommerce_update_order', array( $this, 'invalidate_dashboard' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'invalidate_dashboard' ) );
		// Non-order data that feeds dashboard cards (e.g. shipping rules).
		add_action( 'commerceflow_shipping_rules_changed', array( $this, 'invalidate_dashboard' ) );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}

	/**
	 * Get cached dashboard data, or compute and cache it.
	 *
	 * Reads the TTL from saved settings; falls back to self::CACHE_TTL.
	 *
	 * @param callable $compute Callback that returns the data to cache.
	 * @return mixed
	 */
	public function remember( callable $compute ) {
		$cached = get_transient( self::transient_key() );
		if ( false !== $cached ) {
			return $cached;
		}

		$data     = $compute();
		$defaults = array( 'dashboard_cache_ttl' => self::CACHE_TTL );
		$settings = get_option( 'commerceflow_settings', $defaults );
		$ttl      = CacheUtils::resolve_ttl( $settings, self::CACHE_TTL );

		set_transient( self::transient_key(), $data, $ttl );
		return $data;
	}

	/**
	 * Invalidate the dashboard cache.
	 */
	public function invalidate_dashboard(): void {
		delete_transient( self::transient_key() );
	}
}
