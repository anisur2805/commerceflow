<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Container;

use CommerceFlow\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {

	public function test_bind_and_resolve(): void {
		$container = new Container();

		$container->bind( 'greeting', function () {
			return 'hello';
		} );

		$this->assertSame( 'hello', $container->get( 'greeting' ) );
	}

	public function test_bind_returns_fresh_instance_each_time(): void {
		$container = new Container();

		$container->bind( 'counter', function () {
			return new \stdClass();
		} );

		$this->assertNotSame( $container->get( 'counter' ), $container->get( 'counter' ) );
	}

	public function test_singleton_returns_same_instance(): void {
		$container = new Container();

		$container->singleton( 'shared', function () {
			return new \stdClass();
		} );

		$this->assertSame( $container->get( 'shared' ), $container->get( 'shared' ) );
	}

	public function test_has_returns_true_for_registered_binding(): void {
		$container = new Container();
		$container->bind( 'foo', function () {
			return 'bar';
		} );

		$this->assertTrue( $container->has( 'foo' ) );
		$this->assertFalse( $container->has( 'nonexistent' ) );
	}

	public function test_has_returns_true_for_resolved_singleton(): void {
		$container = new Container();
		$container->singleton( 's', function () {
			return new \stdClass();
		} );

		$container->get( 's' );

		$this->assertTrue( $container->has( 's' ) );
	}

	public function test_get_unregistered_throws_exception(): void {
		$container = new Container();

		$this->expectException( \RuntimeException::class );
		$container->get( 'nope' );
	}
}
