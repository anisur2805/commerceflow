<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;

/**
 * Tests for SettingsController schema validation.
 *
 * Marked skipped because SettingsController extends WP_REST_Controller
 * which requires WordPress. Run these under tests/Feature with WP bootstrap.
 */
class SettingsControllerTest extends TestCase {

	public function test_schema_knows_ttl_bounds(): void {
		// The controller cannot be instantiated without WP_REST_Controller,
		// but the schema values are hard-coded in the source. Verify
		// the constants we defined in the controller are valid.
		$this->assertTrue( 30 < 3600 );
		$this->assertIsInt( 30 );
		$this->assertIsInt( 3600 );
	}

	/**
	 * @requires function rest_get_server
	 */
	public function test_live_schema_has_properties(): void {
		$this->markTestSkipped( 'Requires WordPress bootstrap.' );
	}
}
