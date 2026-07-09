<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Bootstrap;

use CommerceFlow\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Bootstrap unit tests — verify init path doesn't fatally error.
 */
class BootstrapTest extends TestCase {

	public function test_bootstrap_constructs(): void {
		$bootstrap = new Bootstrap();
		$this->assertInstanceOf( Bootstrap::class, $bootstrap );
	}
}
