<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;

/**
 * TDD test: verify settings validation logic as a pure function.
 *
 * Written BEFORE the utility exists. Currently there is no
 * SettingsValidator class — this test defines the contract.
 */
class SettingsValidatorTest extends TestCase {

	public function test_validates_boolean_toggle(): void {
		$result = \CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'enable_dashboard_cache' => true )
		);
		$this->assertSame( array( 'enable_dashboard_cache' => true ), $result );
	}

	public function test_validates_false_toggle(): void {
		$result = \CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'enable_dashboard_cache' => false )
		);
		$this->assertSame( array( 'enable_dashboard_cache' => false ), $result );
	}

	public function test_rejects_non_boolean_toggle(): void {
		$this->expectException( \InvalidArgumentException::class );
		\CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'enable_dashboard_cache' => 'yes' )
		);
	}

	public function test_rejects_out_of_range_ttl(): void {
		$this->expectException( \InvalidArgumentException::class );
		\CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'dashboard_cache_ttl' => 10 )
		);
	}

	public function test_rejects_over_max_ttl(): void {
		$this->expectException( \InvalidArgumentException::class );
		\CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'dashboard_cache_ttl' => 5000 )
		);
	}

	public function test_validates_ttl_at_minimum_edge(): void {
		$result = \CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'dashboard_cache_ttl' => 30 )
		);
		$this->assertSame( array( 'dashboard_cache_ttl' => 30 ), $result );
	}

	public function test_validates_ttl_at_maximum_edge(): void {
		$result = \CommerceFlow\REST\SettingsValidator::sanitize(
			array( 'dashboard_cache_ttl' => 3600 )
		);
		$this->assertSame( array( 'dashboard_cache_ttl' => 3600 ), $result );
	}
}
