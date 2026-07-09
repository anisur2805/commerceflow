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
	 * Option key tracking the installed DB schema version.
	 */
	public const DB_VERSION_OPTION = 'commerceflow_db_version';

	/**
	 * Initialise the plugin.
	 */
	public function init(): void {
		$this->container = new Container();

		// Register core services.
		$this->register_services();

		// Run DB migrations if the schema version is stale.
		$this->maybe_migrate();

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
	 * Get the logger if available.
	 */
	private function logger(): ?Logger {
		if ( ! $this->container->has( Logger::class ) ) {
			return null;
		}
		try {
			$logger = $this->container->get( Logger::class );
			return $logger instanceof Logger ? $logger : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Register activation, deactivation, and uninstall hooks.
	 */
	private function register_lifecycle_hooks(): void {
		register_activation_hook( COMMERCEFLOW_FILE, array( Activator::class, 'activate' ) );
		register_deactivation_hook( COMMERCEFLOW_FILE, array( Deactivator::class, 'deactivate' ) );
		register_uninstall_hook( COMMERCEFLOW_FILE, array( Uninstaller::class, 'uninstall' ) );
	}

	/**
	 * Run DB migrations when the stored schema version is stale.
	 *
	 * This ensures tables are created not only on activation but also when
	 * new code (e.g. v0.2) is deployed to an already-active plugin.
	 */
	private function maybe_migrate(): void {
		$installed = get_option( self::DB_VERSION_OPTION, '' );

		// If tables are missing, force re-run regardless of stored version.
		if ( COMMERCEFLOW_VERSION === $installed && Activator::tables_exist() ) {
			return;
		}

		if ( ! Activator::create_tables() ) {
			// Migration failed — delete stale option so we retry next load.
			delete_option( self::DB_VERSION_OPTION );

			$logger = $this->logger();
			if ( $logger ) {
				$logger->error( 'CommerceFlow DB migration failed.', array( 'db_error' => $GLOBALS['wpdb']->last_error ?? 'unknown' ) );
			}
			return;
		}

		update_option( self::DB_VERSION_OPTION, COMMERCEFLOW_VERSION );
	}
}
