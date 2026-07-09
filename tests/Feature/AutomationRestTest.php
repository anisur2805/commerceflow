<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Feature/integration tests for the Automation REST endpoints.
 *
 * @requires function rest_get_server
 */
class AutomationRestTest extends TestCase {

	private const NAMESPACE = 'commerceflow/v1';

	public function test_list_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . '/automation',
			$routes,
			'GET/POST /commerceflow/v1/automation route is not registered.'
		);
	}

	public function test_item_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . '/automation/(?P<id>[\d]+)',
			$routes,
			'GET/PUT/DELETE /automation/{id} route is not registered.'
		);
	}

	public function test_dry_run_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . '/automation/(?P<id>[\d]+)/dry-run',
			$routes,
			'POST /automation/{id}/dry-run route is not registered.'
		);
	}

	public function test_logs_route_is_registered(): void {
		if ( ! function_exists( 'rest_get_server' ) ) {
			$this->markTestSkipped( 'WordPress REST API not available.' );
		}

		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey(
			self::NAMESPACE . '/automation/logs',
			$routes,
			'GET /automation/logs route is not registered.'
		);
	}
}
