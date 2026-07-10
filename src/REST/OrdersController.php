<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Automation\RuleLogRepository;
use CommerceFlow\Workflow\OrderEventRepository;
use CommerceFlow\Workflow\TransitionGuard;
use CommerceFlow\Workflow\WorkflowModule;
use WC_Order;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for /commerceflow/v1/orders — workflow list, guarded
 * transitions, and per-order timeline (FR-FLOW-1, FR-FLOW-2).
 */
class OrdersController extends WP_REST_Controller {

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
	protected $rest_base = 'orders';

	/**
	 * Transition guard.
	 *
	 * @var TransitionGuard
	 */
	private TransitionGuard $guard;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->guard = new TransitionGuard();
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
					'permission_callback' => array( $this, 'permissions_check' ),
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
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/transition',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'transition' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/timeline',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_timeline' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Permission check — order operations require manage_woocommerce.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public function permissions_check( $request ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage orders.', 'commerceflow' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * List orders for the workflow view.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$args = array(
			'limit'   => max( 1, min( 100, (int) ( $request['per_page'] ?? 20 ) ) ),
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		$status = isset( $request['status'] ) ? sanitize_text_field( (string) $request['status'] ) : '';
		if ( '' !== $status ) {
			$args['status'] = $status;
		}

		$orders = wc_get_orders( $args );

		$data = array();
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$data[] = $this->summarize( $order );
			}
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Get a single order summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order instanceof WC_Order ) {
			return $this->not_found();
		}
		return rest_ensure_response( $this->summarize( $order ) );
	}

	/**
	 * Apply a guarded status transition (FR-FLOW-1).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function transition( $request ) {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order instanceof WC_Order ) {
			return $this->not_found();
		}

		$to = sanitize_text_field( (string) ( $request->get_json_params()['to'] ?? '' ) );
		if ( '' === $to ) {
			return new \WP_Error(
				'rest_invalid_param',
				__( 'Target status is required.', 'commerceflow' ),
				array( 'status' => 400 )
			);
		}

		$from = $order->get_status();
		if ( ! $this->guard->can( $from, $to ) ) {
			return new \WP_Error(
				'rest_transition_forbidden',
				sprintf(
					/* translators: 1: source status, 2: target status. */
					__( 'Transition from "%1$s" to "%2$s" is not allowed.', 'commerceflow' ),
					$from,
					$to
				),
				array( 'status' => 409 )
			);
		}

		// Write through the CRUD layer; the status_changed hook records the
		// timeline entry with its actor.
		$order->update_status( $to, __( 'CommerceFlow workflow transition.', 'commerceflow' ) );

		return rest_ensure_response( $this->summarize( wc_get_order( $order->get_id() ) ) );
	}

	/**
	 * Return the merged order timeline: status changes + automation actions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_timeline( $request ) {
		$order_id = (int) $request['id'];
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->not_found();
		}

		return rest_ensure_response( self::build_timeline( $order_id ) );
	}

	/**
	 * Build a normalised, newest-first timeline for an order by merging
	 * workflow events with automation rule logs.
	 *
	 * @param  int $order_id Order ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_timeline( int $order_id ): array {
		$events = ( new OrderEventRepository() )->find_by_order( $order_id );
		$logs   = ( new RuleLogRepository() )->find_by_order( $order_id );

		$entries = array();
		foreach ( $events as $event ) {
			$entries[] = self::normalize_event( $event );
		}
		foreach ( $logs as $log ) {
			$entries[] = self::normalize_log( $log );
		}

		usort(
			$entries,
			static function ( array $a, array $b ): int {
				return strcmp( (string) $b['created_at'], (string) $a['created_at'] );
			}
		);

		return $entries;
	}

	/**
	 * Normalise a workflow event row to a timeline entry.
	 *
	 * @param  array<string, mixed> $event Event row.
	 * @return array<string, mixed>
	 */
	private static function normalize_event( array $event ): array {
		return array(
			'source'     => 'workflow',
			'type'       => (string) ( $event['type'] ?? 'status_change' ),
			'actor'      => (string) ( $event['actor'] ?? '' ),
			'message'    => sprintf(
				'%s → %s',
				(string) ( $event['from_status'] ?? '' ),
				(string) ( $event['to_status'] ?? '' )
			),
			'status'     => (string) ( $event['to_status'] ?? '' ),
			'created_at' => (string) ( $event['created_at'] ?? '' ),
		);
	}

	/**
	 * Normalise an automation rule-log row to a timeline entry.
	 *
	 * @param  array<string, mixed> $log Log row.
	 * @return array<string, mixed>
	 */
	private static function normalize_log( array $log ): array {
		return array(
			'source'     => 'automation',
			'type'       => 'automation',
			'actor'      => 'automation',
			'message'    => sprintf(
				/* translators: 1: rule id, 2: trigger. */
				__( 'Rule #%1$d (%2$s)', 'commerceflow' ),
				(int) ( $log['rule_id'] ?? 0 ),
				(string) ( $log['trigger'] ?? '' )
			),
			'status'     => (string) ( $log['status'] ?? '' ),
			'created_at' => (string) ( $log['created_at'] ?? '' ),
		);
	}

	/**
	 * Summarise an order for the workflow list.
	 *
	 * @param  WC_Order $order Order.
	 * @return array<string, mixed>
	 */
	private function summarize( WC_Order $order ): array {
		$status = $order->get_status();

		return array(
			'id'                  => $order->get_id(),
			'number'              => $order->get_order_number(),
			'status'              => $status,
			'status_label'        => wc_get_order_status_name( $status ),
			'total'               => (float) $order->get_total(),
			'currency'            => $order->get_currency(),
			'customer'            => trim( $order->get_formatted_billing_full_name() ),
			'date_created'        => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
			'allowed_transitions' => $this->transition_options( $status ),
		);
	}

	/**
	 * Build {slug, label} options for the statuses reachable from a source.
	 *
	 * @param  string $from Source status (no wc- prefix).
	 * @return array<int, array{slug: string, label: string}>
	 */
	private function transition_options( string $from ): array {
		$options = array();
		foreach ( $this->guard->allowed_from( $from ) as $slug ) {
			$options[] = array(
				'slug'  => $slug,
				'label' => wc_get_order_status_name( $slug ),
			);
		}
		return $options;
	}

	/**
	 * Standard 404 response.
	 *
	 * @return \WP_Error
	 */
	private function not_found(): \WP_Error {
		return new \WP_Error(
			'rest_not_found',
			__( 'Order not found.', 'commerceflow' ),
			array( 'status' => 404 )
		);
	}
}
