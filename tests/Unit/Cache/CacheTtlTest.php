<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Cache;

use CommerceFlow\Cache\CacheUtils;
use PHPUnit\Framework\TestCase;

/**
 * TDD test: verify cache TTL extraction logic.
 *
 * This test was written BEFORE the utility class existed.
 */
class CacheTtlTest extends TestCase {

	public function test_valid_ttl_from_settings(): void {
		$settings = array( 'dashboard_cache_ttl' => 600 );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 600, $ttl );
	}

	public function test_missing_ttl_falls_back_to_default(): void {
		$settings = array();
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 300, $ttl );
	}

	public function test_null_ttl_falls_back(): void {
		$settings = array( 'dashboard_cache_ttl' => null );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 300, $ttl );
	}

	public function test_zero_ttl_is_clamped(): void {
		$settings = array( 'dashboard_cache_ttl' => 0 );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 30, $ttl );
	}

	public function test_negative_ttl_is_clamped(): void {
		$settings = array( 'dashboard_cache_ttl' => -5 );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 30, $ttl );
	}

	public function test_over_max_ttl_is_clamped(): void {
		$settings = array( 'dashboard_cache_ttl' => 9999 );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 3600, $ttl );
	}

	public function test_string_ttl_is_parsed(): void {
		$settings = array( 'dashboard_cache_ttl' => '450' );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 450, $ttl );
	}

	public function test_non_numeric_string_falls_back(): void {
		$settings = array( 'dashboard_cache_ttl' => 'abc' );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );
		$this->assertSame( 300, $ttl );
	}
}
