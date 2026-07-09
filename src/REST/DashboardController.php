<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Analytics\DashboardQuery;
use CommerceFlow\Cache\CacheModule;
use CommerceFlow\Container\Container;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for GET /commerceflow/v1/dashboard.
 */
class DashboardController extends WP_REST_Controller {

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	protected Container $container;

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
	protected $rest_base = 'dashboard';

	/**
	 * Constructor.
	 *
	 * @param Container $container DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

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
			)
		);
	}

	/**
	 * Check if the current user can view dashboard data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view the dashboard.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Return dashboard data (cached).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$query = new DashboardQuery();
		$cache = new CacheModule( $this->container );
		$data  = $cache->remember( array( $query, 'get_data' ) );

		return rest_ensure_response( $data );
	}
}
