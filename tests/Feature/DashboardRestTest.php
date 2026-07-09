<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature/integration tests for the Dashboard REST endpoint.
 *
 * These tests require WordPress + WooCommerce to be running.
 *
 * @requires function wc_get_orders
 */
class DashboardRestTest extends TestCase {

	private const NAMESPACE = 'commerceflow/v1';
	private const ROUTE = '/dashboard';

	public function test_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . self::ROUTE,
			$routes,
			'GET /commerceflow/v1/dashboard route is not registered.'
		);
	}
}
