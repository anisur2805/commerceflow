<?php

declare(strict_types=1);

namespace CommerceFlow;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleLoader;
use CommerceFlow\Logger\Logger;

/**
 * Plugin bootstrap — initialises DI, modules, and lifecycle hooks.
 */
class Bootstrap {

	/**
	 * DI container instance.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Initialise the plugin.
	 */
	public function init(): void {
		$this->container = new Container();

		// Register core services.
		$this->register_services();

		// Boot modules.
		$loader = new ModuleLoader( $this->container );
		$loader->boot();

		// Register lifecycle hooks.
		$this->register_lifecycle_hooks();

		/**
		 * Fires after CommerceFlow is fully initialised.
		 *
		 * @param Container $container The DI container.
		 */
		do_action( 'commerceflow_init', $this->container );
	}

	/**
	 * Register core services in the DI container.
	 */
	private function register_services(): void {
		$this->container->singleton(
			Logger::class,
			function () {
				return new Logger();
			}
		);
	}

	/**
	 * Register activation, deactivation, and uninstall hooks.
	 */
	private function register_lifecycle_hooks(): void {
		register_activation_hook( COMMERCEFLOW_FILE, array( Activator::class, 'activate' ) );
		register_deactivation_hook( COMMERCEFLOW_FILE, array( Deactivator::class, 'deactivate' ) );
		register_uninstall_hook( COMMERCEFLOW_FILE, array( Uninstaller::class, 'uninstall' ) );
	}
}
