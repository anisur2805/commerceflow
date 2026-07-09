<?php
/**
 * PHPUnit bootstrap for CommerceFlow unit tests.
 *
 * These are pure unit tests — no WordPress bootstrap needed.
 * Tests that require WordPress/WooCommerce should live in tests/Feature
 * and bootstrap via WP_Testsuite.
 *
 * @package CommerceFlow
 */

// Composer autoloader.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	echo "Composer autoloader not found. Run 'composer install' first.\n";
	exit( 1 );
}
require_once $autoloader;

// Define plugin constants so stubs are available.
if ( ! defined( 'COMMERCEFLOW_VERSION' ) ) {
	define( 'COMMERCEFLOW_VERSION', '0.1.0' );
}
if ( ! defined( 'COMMERCEFLOW_FILE' ) ) {
	define( 'COMMERCEFLOW_FILE', dirname( __DIR__ ) . '/commerceflow.php' );
}
if ( ! defined( 'COMMERCEFLOW_DIR' ) ) {
	define( 'COMMERCEFLOW_DIR', dirname( __DIR__ ) );
}
