<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Module;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;
use CommerceFlow\Module\ModuleLoader;
use PHPUnit\Framework\TestCase;

class ModuleLoaderTest extends TestCase {

	public function test_boot_does_not_crash_on_empty_modules(): void {
		$container = new Container();
		$loader    = new ModuleLoader( $container );

		// Should not throw.
		$loader->boot();
		$this->addToAssertionCount( 1 );
	}

	public function test_boot_with_valid_module(): void {
		$container = new Container();
		$loader    = new ModuleLoader( $container );

		$loader->register_module( FakeModule::class );
		$loader->boot();

		$this->assertTrue( FakeModule::$registered );
		$this->assertTrue( FakeModule::$booted );
	}
}

class FakeModule implements ModuleInterface {
	public static bool $registered = false;
	public static bool $booted     = false;

	public function register(): void {
		self::$registered = true;
	}

	public function boot(): void {
		self::$booted = true;
	}
}
