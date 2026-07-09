<?php
/**
 * Plugin Name:       CommerceFlow for WooCommerce
 * Plugin URI:        https://github.com/anisur2805/commerceflow
 * Description:       WooCommerce operations and automation platform — analytics dashboard, automation rules engine, order workflow, and shipping rules.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Anisur Rahman
 * Author URI:        https://github.com/anisur2805
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       commerceflow
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package           CommerceFlow
 * @license           GPL-2.0-or-later
 */

declare(strict_types=1);

namespace CommerceFlow;

// Abort if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'COMMERCEFLOW_VERSION', '0.1.0' );
define( 'COMMERCEFLOW_FILE', __FILE__ );
define( 'COMMERCEFLOW_DIR', __DIR__ );
define( 'COMMERCEFLOW_URL', plugin_dir_url( __FILE__ ) );

// Require Composer autoloader.
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	add_action(
		'admin_notices',
		function () {
			$message = __( 'CommerceFlow requires its dependencies. Run <code>composer install</code> in the plugin directory.', 'commerceflow' );
			printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
		}
	);
	return;
}
require_once $autoloader;

// Load bootstrap.
$bootstrap = new Bootstrap();
$bootstrap->init();
