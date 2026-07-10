<?php

declare(strict_types=1);

namespace CommerceFlow\Workflow;

/**
 * Guards order status transitions (FR-FLOW-1).
 *
 * Pure — holds a map of {from status => allowed target statuses} and answers
 * whether a transition is permitted. The WorkflowModule / OrdersController
 * consult this before writing any status change through the CRUD layer, so an
 * invalid transition is rejected and never persisted.
 *
 * Statuses are stored without the `wc-` prefix (the form WooCommerce's
 * get_status() returns), so callers normalise before asking.
 */
class TransitionGuard {

	/**
	 * Transition map: source status => list of allowed target statuses.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $map;

	/**
	 * Constructor.
	 *
	 * @param array<string, array<int, string>>|null $map Optional custom map; defaults to the fulfillment map.
	 */
	public function __construct( ?array $map = null ) {
		$this->map = $map ?? self::default_map();
	}

	/**
	 * Default fulfillment transition map, weaving custom statuses into the
	 * core order lifecycle.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function default_map(): array {
		return array(
			'pending'            => array( 'processing', 'on-hold', 'cancelled', 'failed' ),
			'on-hold'            => array( 'processing', OrderStatus::PACKING, 'cancelled' ),
			'processing'         => array( OrderStatus::PACKING, 'on-hold', 'completed', 'cancelled' ),
			OrderStatus::PACKING => array( OrderStatus::READY, 'on-hold', 'cancelled' ),
			OrderStatus::READY   => array( OrderStatus::SHIPPED, OrderStatus::PACKING ),
			OrderStatus::SHIPPED => array( 'completed', 'refunded' ),
			'completed'          => array( 'refunded' ),
		);
	}

	/**
	 * Whether transitioning from one status to another is allowed.
	 *
	 * @param string $from Source status (no wc- prefix).
	 * @param string $to   Target status (no wc- prefix).
	 */
	public function can( string $from, string $to ): bool {
		if ( $from === $to ) {
			return false;
		}
		return in_array( $to, $this->allowed_from( $from ), true );
	}

	/**
	 * Target statuses reachable from a given source status.
	 *
	 * @param  string $from Source status (no wc- prefix).
	 * @return array<int, string>
	 */
	public function allowed_from( string $from ): array {
		return $this->map[ $from ] ?? array();
	}
}
