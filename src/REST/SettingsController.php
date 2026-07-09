<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for GET/PUT /commerceflow/v1/settings.
 */
class SettingsController extends WP_REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'commerceflow/v1';

	/**
	 * Resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Option key.
	 *
	 * @var string
	 */
	private string $option_key = 'commerceflow_settings';

	/**
	 * Default settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = array(
		'enable_dashboard_cache' => true,
		'dashboard_cache_ttl'    => 300,
	);

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
			)
		);
	}

	/**
	 * Check GET permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view settings.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Check PUT permissions.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function update_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to update settings.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Get current settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$settings = get_option( $this->option_key, $this->defaults );
		return rest_ensure_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function update_items( $request ) {
		$current = get_option( $this->option_key, $this->defaults );
		$updated = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$merged = array_merge( $current, $updated );
		update_option( $this->option_key, $merged );

		return rest_ensure_response( $merged );
	}

	/**
	 * Validate and sanitise incoming settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|\WP_Error
	 */
	protected function prepare_item_for_database( $request ) {
		$data = array();

		if ( isset( $request['enable_dashboard_cache'] ) ) {
			$data['enable_dashboard_cache'] = rest_sanitize_boolean( $request['enable_dashboard_cache'] );
		}

		if ( isset( $request['dashboard_cache_ttl'] ) ) {
			$ttl = absint( $request['dashboard_cache_ttl'] );
			if ( $ttl < 30 || $ttl > 3600 ) {
				return new \WP_Error(
					'rest_invalid_param',
					__( 'Cache TTL must be between 30 and 3600 seconds.', 'commerceflow' ),
					array( 'status' => 400 )
				);
			}
			$data['dashboard_cache_ttl'] = $ttl;
		}

		return $data;
	}

	/**
	 * Get the REST schema.
	 *
	 * @return array
	 */
	public function get_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'commerceflow-settings',
			'type'       => 'object',
			'properties' => array(
				'enable_dashboard_cache' => array(
					'type' => 'boolean',
				),
				'dashboard_cache_ttl'    => array(
					'type'    => 'integer',
					'minimum' => 30,
					'maximum' => 3600,
				),
			),
		);
	}
}
