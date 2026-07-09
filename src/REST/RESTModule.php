<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;

/**
 * REST module — registers all CommerceFlow REST API routes.
 */
class RESTModule implements ModuleInterface {

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		$dashboard = new DashboardController( $this->container );
		$dashboard->register_routes();

		( new SettingsController() )->register_routes();

		( new AutomationController() )->register_routes();
	}
}
