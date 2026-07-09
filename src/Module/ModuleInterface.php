<?php

declare(strict_types=1);

namespace CommerceFlow\Module;

/**
 * Contract each CommerceFlow module must implement.
 */
interface ModuleInterface {

	/**
	 * Register hooks, REST routes, and assets.
	 */
	public function register(): void;

	/**
	 * Boot the module (called after all modules are registered).
	 */
	public function boot(): void;
}
