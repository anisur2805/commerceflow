<?php

declare(strict_types=1);

namespace CommerceFlow;

/**
 * Handle plugin activation tasks.
 */
class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		// Flush rewrite rules for future REST endpoints.
		flush_rewrite_rules();
	}
}
