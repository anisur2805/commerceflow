<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use InvalidArgumentException;

/**
 * Pure settings validation — no WordPress dependency.
 *
 * Extracted from SettingsController so it can be unit tested
 * without bootstrapping WordPress.
 */
class SettingsValidator {

	/**
	 * Sanitize and validate a settings update payload.
	 *
	 * @param  array $raw Raw input data.
	 * @return array Sanitized data safe for storage.
	 * @throws InvalidArgumentException If any value is invalid.
	 */
	public static function sanitize( array $raw ): array {
		$clean = array();

		if ( isset( $raw['enable_dashboard_cache'] ) ) {
			if ( ! is_bool( $raw['enable_dashboard_cache'] ) ) {
				throw new InvalidArgumentException(
					'enable_dashboard_cache must be a boolean.'
				);
			}
			$clean['enable_dashboard_cache'] = $raw['enable_dashboard_cache'];
		}

		if ( isset( $raw['dashboard_cache_ttl'] ) ) {
			if ( ! is_numeric( $raw['dashboard_cache_ttl'] ) ) {
				throw new InvalidArgumentException(
					'dashboard_cache_ttl must be numeric.'
				);
			}

			$ttl = (int) $raw['dashboard_cache_ttl'];

			if ( $ttl < 30 || $ttl > 3600 ) {
				throw new InvalidArgumentException(
					'dashboard_cache_ttl must be between 30 and 3600.'
				);
			}

			$clean['dashboard_cache_ttl'] = $ttl;
		}

		return $clean;
	}
}
