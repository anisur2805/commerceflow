<?php
/**
 * CommerceFlow uninstall handler.
 *
 * @package CommerceFlow
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up plugin data.
delete_option( 'commerceflow_settings' );
delete_transient( 'commerceflow_dashboard_data' );
