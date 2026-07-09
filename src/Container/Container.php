<?php

declare(strict_types=1);

namespace CommerceFlow\Container;

use Closure;
use RuntimeException;

/**
 * Simple DI container with singleton and factory support.
 */
class Container {

	/**
	 * Registered bindings.
	 *
	 * @var array<string, Closure>
	 */
	private array $bindings = array();

	/**
	 * Resolved singletons.
	 *
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Register a factory binding.
	 *
	 * @param string  $abstract Interface or class name.
	 * @param Closure $factory  Factory that returns an instance.
	 */
	public function bind( string $abstract, Closure $factory ): void {
		$this->bindings[ $abstract ] = $factory;
	}

	/**
	 * Register a singleton binding.
	 *
	 * @param string  $abstract Interface or class name.
	 * @param Closure $factory  Factory that returns an instance.
	 */
	public function singleton( string $abstract, Closure $factory ): void {
		$this->bindings[ $abstract ] = static function ( Container $container ) use ( $factory ) {
			static $resolved;
			if ( null === $resolved ) {
				$resolved = $factory( $container );
			}
			return $resolved;
		};
	}

	/**
	 * Resolve a binding.
	 *
	 * @param  string $abstract Interface or class name.
	 * @return mixed
	 *
	 * @throws RuntimeException If the binding is not registered.
	 */
	public function get( string $abstract ) {
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		if ( isset( $this->bindings[ $abstract ] ) ) {
			$instance = $this->bindings[ $abstract ]( $this );
			return $instance;
		}

		throw new RuntimeException(
			sprintf( 'Binding not found for "%s".', $abstract ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		);
	}

	/**
	 * Check if a binding is registered.
	 *
	 * @param string $abstract Interface or class name.
	 * @return bool
	 */
	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->instances[ $abstract ] );
	}
}
