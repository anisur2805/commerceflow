<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature tests for the Orders + Logs REST endpoints (v0.3).
 *
 * @requires function rest_get_server
 */
class OrdersRestTest extends TestCase {

	private const NAMESPACE = 'commerceflow/v1';

	/**
	 * @dataProvider route_provider
	 */
	public function test_route_is_registered( string $route ): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . $route,
			$routes,
			sprintf( '%s route is not registered.', $route )
		);
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	public static function route_provider(): array {
		return array(
			array( '/orders' ),
			array( '/orders/(?P<id>[\d]+)' ),
			array( '/orders/(?P<id>[\d]+)/transition' ),
			array( '/orders/(?P<id>[\d]+)/timeline' ),
			array( '/logs' ),
		);
	}
}
