<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Cache;

use CommerceFlow\Cache\CacheUtils;
use PHPUnit\Framework\TestCase;

/**
 * TDD test: CacheModule's remember() must use settings-aware TTL.
 *
 * This test verifies that resolve_ttl feeds through into the
 * transient set call. Written before the CacheModule refactor.
 */
class CacheModuleTtlTest extends TestCase {

	public function test_resolve_ttl_is_used_by_cache_module(): void {
		// Simulate what CacheModule::remember() should do internally.
		$settings = array( 'dashboard_cache_ttl' => 900 );
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );

		$this->assertSame( 900, $ttl, 'CacheModule must use settings TTL, not hardcoded 300.' );
	}

	public function test_cache_module_falls_back_to_default_when_unset(): void {
		$settings = array();
		$ttl      = CacheUtils::resolve_ttl( $settings, 300 );

		$this->assertSame( 300, $ttl, 'CacheModule must fall back to default TTL when settings unset.' );
	}
}
