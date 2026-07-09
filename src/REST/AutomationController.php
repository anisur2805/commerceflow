<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Automation\DryRunReporter;
use CommerceFlow\Automation\Rule;
use CommerceFlow\Automation\RuleLogRepository;
use CommerceFlow\Automation\RuleRepository;
use CommerceFlow\Automation\RuleValidator;
use InvalidArgumentException;
use WC_Order;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for /commerceflow/v1/automation — rule CRUD + dry-run.
 */
class AutomationController extends WP_REST_Controller {

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
	protected $rest_base = 'automation';

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
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/dry-run',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dry_run' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_logs' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Read permission check.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function read_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view automation rules.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Write permission check.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function write_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage automation rules.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * List all rules.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$rules = ( new RuleRepository() )->find_all();

		return rest_ensure_response( array_map( array( $this, 'serialize' ), $rules ) );
	}

	/**
	 * Get a single rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$id   = (int) $request['id'];
		$rule = ( new RuleRepository() )->find( $id );

		if ( ! $rule ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Rule not found.', 'commerceflow' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( $this->serialize( $rule ) );
	}

	/**
	 * Create a rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		try {
			$clean = RuleValidator::sanitize( $request->get_json_params() ?? array() );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'rest_invalid_param',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}

		$repo = new RuleRepository();
		$id   = $repo->insert( $clean );
		if ( ! $id ) {
			global $wpdb;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$detail = $wpdb->last_error;
			} else {
				$detail = __( 'Database insert failed.', 'commerceflow' );
			}

			if ( empty( $detail ) ) {
				$detail = __( 'Failed to create rule.', 'commerceflow' );
			}

			return new \WP_Error(
				'rest_insert_failed',
				$detail,
				array( 'status' => 500 )
			);
		}

		$rule = $repo->find( $id );
		return rest_ensure_response( $this->serialize( $rule ) );
	}

	/**
	 * Update a rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$id   = (int) $request['id'];
		$repo = new RuleRepository();

		if ( ! $repo->find( $id ) ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Rule not found.', 'commerceflow' ),
				array( 'status' => 404 )
			);
		}

		try {
			$clean = RuleValidator::sanitize( $request->get_json_params() ?? array() );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'rest_invalid_param',
				$e->getMessage(),
				array( 'status' => 400 )
			);
		}

		$repo->update( $id, $clean );
		$rule = $repo->find( $id );

		return rest_ensure_response( $this->serialize( $rule ) );
	}

	/**
	 * Delete a rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id   = (int) $request['id'];
		$repo = new RuleRepository();

		if ( ! $repo->find( $id ) ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Rule not found.', 'commerceflow' ),
				array( 'status' => 404 )
			);
		}

		$repo->delete( $id );

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * Dry-run a rule against an order — no side effects (FR-AUTO-7).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function dry_run( $request ) {
		$id   = (int) $request['id'];
		$rule = ( new RuleRepository() )->find( $id );

		if ( ! $rule ) {
			return new \WP_Error(
				'rest_not_found',
				__( 'Rule not found.', 'commerceflow' ),
				array( 'status' => 404 )
			);
		}

		$order_id = (int) ( $request->get_json_params()['order_id'] ?? 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		$sample   = $request->get_json_params()['sample'] ?? array();

		if ( $order instanceof WC_Order ) {
			$snapshot = array(
				'id'             => $order->get_id(),
				'status'         => 'wc-' . $order->get_status(),
				'total'          => (float) $order->get_total(),
				'payment_method' => $order->get_payment_method(),
				'customer_id'    => $order->get_customer_id(),
				'email'          => $order->get_billing_email(),
			);
		} elseif ( is_array( $sample ) && array() !== $sample ) {
			$snapshot = array_map( 'strval', $sample );
		} else {
			$snapshot = array();
		}
		$report   = DryRunReporter::report( $rule->to_array(), $snapshot );

		$this->log_dry_run( $rule, $order_id );

		return rest_ensure_response( $report );
	}

	/**
	 * List recent automation logs.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_logs( $request ): WP_REST_Response {
		$limit = isset( $request['per_page'] ) ? absint( $request['per_page'] ) : 20;
		$logs  = ( new RuleLogRepository() )->find_recent( max( 1, min( 100, $limit ) ) );

		return rest_ensure_response( $logs );
	}

	/**
	 * Serialize a Rule for REST output.
	 *
	 * @param  Rule|null $rule Rule.
	 * @return array<string, mixed>
	 */
	private function serialize( ?Rule $rule ): array {
		if ( ! $rule ) {
			return array();
		}
		return $rule->to_array();
	}

	/**
	 * Record a dry-run log entry.
	 *
	 * @param Rule $rule     Rule.
	 * @param int  $order_id Order ID.
	 */
	private function log_dry_run( Rule $rule, int $order_id ): void {
		( new RuleLogRepository() )->insert(
			array(
				'rule_id'  => $rule->id,
				'order_id' => $order_id,
				'trigger'  => $rule->trigger,
				'status'   => 'dry_run',
				'run_id'   => '',
				'detail'   => array( 'mode' => 'dry_run' ),
			)
		);
	}
}
