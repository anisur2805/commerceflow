<?php
/**
 * PHPStan bootstrap — defines plugin constants for static analysis.
 *
 * @package CommerceFlow
 */

define( 'COMMERCEFLOW_VERSION', '0.1.0' );
define( 'COMMERCEFLOW_FILE', __DIR__ . '/commerceflow.php' );
define( 'COMMERCEFLOW_DIR', __DIR__ );
define( 'COMMERCEFLOW_URL', 'https://example.com/wp-content/plugins/commerceflow/' );

// Define ABSPATH if not set (CLI context).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
