<?php

declare(strict_types=1);

namespace CommerceFlow;

/**
 * Handle plugin uninstall — clean up all plugin data.
 *
 * Runs outside the plugin namespace context via the uninstall hook,
 * so this is called statically from the main plugin file.
 */
class Uninstaller {

	/**
	 * Run on plugin uninstall.
	 *
	 * Clears options, transients, and any custom tables.
	 */
	public static function uninstall(): void {
		// Remove plugin options.
		delete_option( 'commerceflow_settings' );

		// Remove cached data.
		delete_transient( 'commerceflow_dashboard_data' );

		// Future slices will clean up their own tables here.
	}
}
