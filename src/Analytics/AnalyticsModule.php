<?php

declare(strict_types=1);

namespace CommerceFlow\Analytics;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;

/**
 * Analytics module — registers the analytics query layer.
 */
class AnalyticsModule implements ModuleInterface {

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		// Nothing to hook for v0.1.
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}
}
