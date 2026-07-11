<?php

declare(strict_types=1);

namespace CommerceFlow\REST;

use CommerceFlow\Shipping\ShippingRateResolver;
use CommerceFlow\Shipping\ShippingRule;
use CommerceFlow\Shipping\ShippingRuleRepository;
use CommerceFlow\Shipping\ShippingRuleValidator;
use InvalidArgumentException;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for /commerceflow/v1/shipping — rule CRUD + preview.
 */
class ShippingController extends WP_REST_Controller {

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
	protected $rest_base = 'shipping';

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
			'/' . $this->rest_base . '/preview',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'preview' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
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
				__( 'You do not have permission to view shipping rules.', 'commerceflow' ),
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
				__( 'You do not have permission to manage shipping rules.', 'commerceflow' ),
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
		$rules = ( new ShippingRuleRepository() )->find_all();

		return rest_ensure_response( array_map( array( $this, 'serialize' ), $rules ) );
	}

	/**
	 * Get a single rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$rule = ( new ShippingRuleRepository() )->find( (int) $request['id'] );

		if ( ! $rule ) {
			return $this->not_found();
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
			$clean = ShippingRuleValidator::sanitize( $request->get_json_params() ?? array() );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error( 'rest_invalid_param', $e->getMessage(), array( 'status' => 400 ) );
		}

		$repo = new ShippingRuleRepository();
		$id   = $repo->insert( $clean );
		if ( ! $id ) {
			return new \WP_Error(
				'rest_insert_failed',
				__( 'Failed to create shipping rule.', 'commerceflow' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( $this->serialize( $repo->find( $id ) ) );
	}

	/**
	 * Update a rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$id   = (int) $request['id'];
		$repo = new ShippingRuleRepository();

		if ( ! $repo->find( $id ) ) {
			return $this->not_found();
		}

		try {
			$clean = ShippingRuleValidator::sanitize( $request->get_json_params() ?? array() );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error( 'rest_invalid_param', $e->getMessage(), array( 'status' => 400 ) );
		}

		$repo->update( $id, $clean );

		return rest_ensure_response( $this->serialize( $repo->find( $id ) ) );
	}

	/**
	 * Delete a rule.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id   = (int) $request['id'];
		$repo = new ShippingRuleRepository();

		if ( ! $repo->find( $id ) ) {
			return $this->not_found();
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
	 * Preview which rule wins for a sample package — no side effects (FR-SHIP-3).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function preview( $request ): WP_REST_Response {
		$sample   = (array) ( $request->get_json_params()['sample'] ?? array() );
		$snapshot = $this->sample_snapshot( $sample );

		$rules = array_map(
			static function ( ShippingRule $rule ): array {
				return $rule->to_array();
			},
			( new ShippingRuleRepository() )->find_all()
		);

		$winner = ShippingRateResolver::resolve( $rules, $snapshot );

		return rest_ensure_response(
			array(
				'snapshot' => $snapshot,
				'matched'  => null !== $winner,
				'rate'     => $winner,
			)
		);
	}

	/**
	 * Normalize raw sample input into a package snapshot.
	 *
	 * @param  array<string, mixed> $sample Raw sample fields.
	 * @return array<string, mixed>
	 */
	private function sample_snapshot( array $sample ): array {
		return array(
			'country'        => sanitize_text_field( (string) ( $sample['country'] ?? '' ) ),
			'state'          => sanitize_text_field( (string) ( $sample['state'] ?? '' ) ),
			'postcode'       => sanitize_text_field( (string) ( $sample['postcode'] ?? '' ) ),
			'weight'         => (float) ( $sample['weight'] ?? 0 ),
			'subtotal'       => (float) ( $sample['subtotal'] ?? 0 ),
			'category'       => array_map( 'sanitize_text_field', (array) ( $sample['category'] ?? array() ) ),
			'shipping_class' => array_map( 'sanitize_text_field', (array) ( $sample['shipping_class'] ?? array() ) ),
			'coupon'         => array_map( 'sanitize_text_field', (array) ( $sample['coupon'] ?? array() ) ),
		);
	}

	/**
	 * Serialize a ShippingRule for REST output.
	 *
	 * @param  ShippingRule|null $rule Rule.
	 * @return array<string, mixed>
	 */
	private function serialize( ?ShippingRule $rule ): array {
		return $rule ? $rule->to_array() : array();
	}

	/**
	 * Standard 404 response.
	 *
	 * @return \WP_Error
	 */
	private function not_found(): \WP_Error {
		return new \WP_Error(
			'rest_not_found',
			__( 'Shipping rule not found.', 'commerceflow' ),
			array( 'status' => 404 )
		);
	}
}
