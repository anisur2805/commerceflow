<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Cache;

use CommerceFlow\Cache\CacheModule;
use PHPUnit\Framework\TestCase;

/**
 * The dashboard transient key must be version-scoped so a plugin upgrade
 * that changes the payload shape never serves a stale blob missing
 * newly-added fields (e.g. fulfillment, shipping).
 */
class CacheKeyTest extends TestCase {

	public function test_transient_key_is_version_scoped(): void {
		$key = CacheModule::transient_key();

		$this->assertStringStartsWith( CacheModule::DASHBOARD_TRANSIENT . '_', $key );
		$this->assertNotSame(
			CacheModule::DASHBOARD_TRANSIENT,
			$key,
			'Key must be version-scoped, not the bare base key.'
		);
	}

	public function test_transient_key_tracks_the_plugin_version(): void {
		$expected = defined( 'COMMERCEFLOW_VERSION' ) ? COMMERCEFLOW_VERSION : 'dev';

		$this->assertStringEndsWith( '_' . $expected, CacheModule::transient_key() );
	}
}
