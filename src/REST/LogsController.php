<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Automation\RuleLogRepository;
use CommerceFlow\Workflow\OrderEventRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for /commerceflow/v1/logs — a global activity feed merging
 * order workflow events with automation rule logs (FR-FLOW-2).
 */
class LogsController {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private string $namespace = 'commerceflow/v1';

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Permission check.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view logs.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Return the merged, newest-first activity feed.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$limit = max( 1, min( 100, (int) ( $request['per_page'] ?? 30 ) ) );

		$entries = array();
		foreach ( ( new OrderEventRepository() )->find_recent( $limit ) as $event ) {
			$entries[] = array(
				'source'     => 'workflow',
				'order_id'   => (int) $event['order_id'],
				'actor'      => (string) $event['actor'],
				'message'    => sprintf( '%s → %s', (string) $event['from_status'], (string) $event['to_status'] ),
				'status'     => (string) $event['to_status'],
				'created_at' => (string) $event['created_at'],
			);
		}
		foreach ( ( new RuleLogRepository() )->find_recent( $limit ) as $log ) {
			$entries[] = array(
				'source'     => 'automation',
				'order_id'   => (int) $log['order_id'],
				'actor'      => 'automation',
				'message'    => sprintf(
					/* translators: 1: rule id, 2: trigger. */
					__( 'Rule #%1$d (%2$s)', 'commerceflow' ),
					(int) $log['rule_id'],
					(string) $log['trigger']
				),
				'status'     => (string) $log['status'],
				'created_at' => (string) $log['created_at'],
			);
		}

		usort(
			$entries,
			static function ( array $a, array $b ): int {
				return strcmp( (string) $b['created_at'], (string) $a['created_at'] );
			}
		);

		return rest_ensure_response( array_slice( $entries, 0, $limit ) );
	}
}
