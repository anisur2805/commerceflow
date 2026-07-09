<?php

declare(strict_types=1);

namespace CommerceFlow\Module;

use CommerceFlow\Container\Container;
use CommerceFlow\Logger\Logger;

/**
 * Loads and boots registered plugin modules.
 */
class ModuleLoader {

	/**
	 * Registered module class names.
	 *
	 * @var array<int, class-string<ModuleInterface>>
	 */
	private array $modules = array();

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
		$this->register_core_modules();
	}

	/**
	 * Register the modules that ship with v0.1.
	 *
	 * Each later slice adds its own module here.
	 */
	private function register_core_modules(): void {
		$this->modules[] = \CommerceFlow\Admin\AdminModule::class;
		$this->modules[] = \CommerceFlow\REST\RESTModule::class;
		$this->modules[] = \CommerceFlow\Analytics\AnalyticsModule::class;
		$this->modules[] = \CommerceFlow\Cache\CacheModule::class;
	}

	/**
	 * Register a module dynamically.
	 *
	 * @param class-string<ModuleInterface> $module_class FQCN of the module.
	 */
	public function register_module( string $module_class ): void {
		$this->modules[] = $module_class;
	}

	/**
	 * Boot all registered modules.
	 */
	public function boot(): void {
		$logger = null;
		if ( $this->container->has( Logger::class ) ) {
			try {
				$logger = $this->container->get( Logger::class );
			} catch ( \Throwable $e ) {
				$logger = null;
			}
		}

		foreach ( $this->modules as $module_class ) {
			if ( ! is_subclass_of( $module_class, ModuleInterface::class ) ) {
				continue;
			}

			try {
				/** @var ModuleInterface $module */
				$module = new $module_class( $this->container );
				$module->register();
				$module->boot();
			} catch ( \Throwable $e ) {
				if ( $logger ) {
					$logger->error(
						sprintf(
							'Failed to boot module "%s": %s',
							$module_class,
							$e->getMessage()
						)
					);
				}
			}
		}
	}
}
