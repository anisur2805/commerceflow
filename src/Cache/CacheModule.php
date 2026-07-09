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
	 * Transient key for dashboard data.
	 *
	 * @var string
	 */
	public const DASHBOARD_TRANSIENT = 'commerceflow_dashboard_data';

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
		$cached = get_transient( self::DASHBOARD_TRANSIENT );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = $compute();

		$defaults = array( 'dashboard_cache_ttl' => self::CACHE_TTL );
		$settings = get_option( 'commerceflow_settings', $defaults );
		$ttl      = isset( $settings['dashboard_cache_ttl'] ) ? absint( $settings['dashboard_cache_ttl'] ) : self::CACHE_TTL;

		set_transient( self::DASHBOARD_TRANSIENT, $data, $ttl );
		return $data;
	}

	/**
	 * Invalidate the dashboard cache.
	 */
	public function invalidate_dashboard(): void {
		delete_transient( self::DASHBOARD_TRANSIENT );
	}
}
