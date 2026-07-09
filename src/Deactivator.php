<?php

declare(strict_types=1);

namespace CommerceFlow;

/**
 * Handle plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
