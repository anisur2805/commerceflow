<?php

declare(strict_types=1);

namespace CommerceFlow\Cache;

/**
 * Cache utility functions — pure, stateless, testable.
 */
class CacheUtils {

	/**
	 * Resolve a cache TTL from settings with defaults and clamping.
	 *
	 * @param  array $settings    Plugin settings array.
	 * @param  int   $default_ttl Fallback TTL in seconds.
	 * @return int Resolved TTL clamped to [30, 3600].
	 */
	public static function resolve_ttl( array $settings, int $default_ttl = 300 ): int {
		$raw = $settings['dashboard_cache_ttl'] ?? null;

		if ( null === $raw || ! is_numeric( $raw ) ) {
			return $default_ttl;
		}

		$ttl = (int) $raw;

		if ( $ttl < 30 ) {
			return 30;
		}

		if ( $ttl > 3600 ) {
			return 3600;
		}

		return $ttl;
	}
}
