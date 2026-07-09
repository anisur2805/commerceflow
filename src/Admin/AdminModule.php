<?php

declare(strict_types=1);

namespace CommerceFlow\Admin;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;

/**
 * Admin module — registers the admin menu, enqueues the SPA, declares HPOS compat.
 */
class AdminModule implements ModuleInterface {

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op for v0.1.
	}

	/**
	 * Declare HPOS compatibility.
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				COMMERCEFLOW_FILE,
				true
			);
		}
	}

	/**
	 * Register the wp-admin menu page for the SPA.
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'CommerceFlow', 'commerceflow' ),
			__( 'CommerceFlow', 'commerceflow' ),
			'manage_woocommerce',
			'commerceflow',
			array( $this, 'render_app' ),
			'dashicons-chart-area',
			55
		);
	}

	/**
	 * Render the React SPA mount point.
	 */
	public function render_app(): void {
		?>
		<div id="commerceflow-root" class="commerceflow-app">
			<div class="commerceflow-loading">
				<?php esc_html_e( 'Loading CommerceFlow…', 'commerceflow' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue the built SPA assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_commerceflow' !== $hook ) {
			return;
		}

		$asset_file = COMMERCEFLOW_DIR . '/assets/build/index.asset.php';

		// Defaults for when the build file does not exist (e.g. CI).
		$asset = array(
			'dependencies' => array(),
			'version'      => COMMERCEFLOW_VERSION,
		);

		if ( file_exists( $asset_file ) ) {
			$asset = (array) require $asset_file;
		}

		wp_enqueue_script(
			'commerceflow-admin',
			COMMERCEFLOW_URL . 'assets/build/index.js',
			$asset['dependencies'] ?? array(),
			$asset['version'] ?? COMMERCEFLOW_VERSION,
			true
		);

		// Enqueue built CSS if the file exists (@wordpress/scripts only emits CSS when imported).
		$css_path = COMMERCEFLOW_DIR . '/assets/build/index.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'commerceflow-admin',
				COMMERCEFLOW_URL . 'assets/build/index.css',
				array( 'wp-components' ),
				$asset['version'] ?? COMMERCEFLOW_VERSION
			);
		}

		// Pass initial state to the SPA.
		wp_add_inline_script(
			'commerceflow-admin',
			sprintf(
				'var COMMERCEFLOW_SETTINGS = %s;',
				wp_json_encode(
					array(
						'rest_url'   => rest_url( 'commerceflow/v1/' ),
						'nonce'      => wp_create_nonce( 'wp_rest' ),
						'assets_url' => COMMERCEFLOW_URL . 'assets/build/',
					)
				)
			),
			'before'
		);
	}
}
