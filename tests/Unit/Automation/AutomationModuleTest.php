<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\AutomationModule;
use CommerceFlow\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Unit test verifying the AutomationModule constructs cleanly.
 */
class AutomationModuleTest extends TestCase {

	public function test_module_constructs(): void {
		$module = new AutomationModule( new Container() );
		$this->assertInstanceOf( AutomationModule::class, $module );
	}

	public function test_module_implements_module_interface(): void {
		$module = new AutomationModule( new Container() );
		$this->assertContains( 'CommerceFlow\\Module\\ModuleInterface', class_implements( $module ) ?: array() );
	}
}
