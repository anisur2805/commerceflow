<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature/integration tests for the Settings REST endpoint.
 *
 * @requires function rest_get_server
 */
class SettingsRestTest extends TestCase {

	private const NAMESPACE = 'commerceflow/v1';
	private const ROUTE = '/settings';

	public function test_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . self::ROUTE,
			$routes,
			'GET/PUT /commerceflow/v1/settings route is not registered.'
		);
	}
}
