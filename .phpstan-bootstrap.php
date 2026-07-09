<?php
/**
 * PHPStan bootstrap — defines plugin constants for static analysis.
 *
 * @package CommerceFlow
 */

define( 'COMMERCEFLOW_VERSION', '0.2.0' );
define( 'COMMERCEFLOW_FILE', __DIR__ . '/commerceflow.php' );
define( 'COMMERCEFLOW_DIR', __DIR__ );
define( 'COMMERCEFLOW_URL', 'https://example.com/wp-content/plugins/commerceflow/' );

// Define ABSPATH if not set (CLI context).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// WordPress $wpdb result-mode constants used by repositories.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
