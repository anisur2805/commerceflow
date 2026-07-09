<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Loop prevention — depth guard per (rule, order) pair (FR-AUTO-4).
 *
 * An action that mutates an order and re-satisfies the same trigger must not
 * run unbounded. The guard tracks an in-progress stack and suppresses
 * re-entries beyond a configurable max depth.
 *
 * Pure and stateful — no WordPress dependency.
 */
class RecursionGuard {

	/**
	 * Maximum allowed depth per (rule, order).
	 *
	 * @var int
	 */
	private int $max_depth;

	/**
	 * Current depth per (rule, order) key.
	 *
	 * @var array<string, int>
	 */
	private array $depth = array();

	/**
	 * Suppressions per (rule, order) key.
	 *
	 * @var array<string, int>
	 */
	private array $suppressed = array();

	/**
	 * Constructor.
	 *
	 * @param int $max_depth Maximum allowed re-entry depth.
	 */
	public function __construct( int $max_depth = 1 ) {
		$this->max_depth = $max_depth;
	}

	/**
	 * Attempt to enter execution for a rule + order.
	 *
	 * @param  int $rule_id  Rule ID.
	 * @param  int $order_id Order ID.
	 * @return bool True if allowed, false if suppressed (loop guard).
	 */
	public function enter( int $rule_id, int $order_id ): bool {
		$key = $this->key( $rule_id, $order_id );

		$current = $this->depth[ $key ] ?? 0;
		if ( $current >= $this->max_depth ) {
			if ( ! isset( $this->suppressed[ $key ] ) ) {
				$this->suppressed[ $key ] = 0;
			}
			++$this->suppressed[ $key ];
			return false;
		}

		$this->depth[ $key ] = $current + 1;
		return true;
	}

	/**
	 * Mark execution complete for a rule + order.
	 *
	 * @param int $rule_id  Rule ID.
	 * @param int $order_id Order ID.
	 */
	public function exit( int $rule_id, int $order_id ): void {
		$key = $this->key( $rule_id, $order_id );

		if ( isset( $this->depth[ $key ] ) ) {
			--$this->depth[ $key ];
			if ( $this->depth[ $key ] <= 0 ) {
				unset( $this->depth[ $key ] );
			}
		}
	}

	/**
	 * Return how many re-entries were suppressed for a rule + order.
	 *
	 * @param  int $rule_id  Rule ID.
	 * @param  int $order_id Order ID.
	 * @return int
	 */
	public function suppression_count( int $rule_id, int $order_id ): int {
		return $this->suppressed[ $this->key( $rule_id, $order_id ) ] ?? 0;
	}

	/**
	 * Reset all guard state.
	 */
	public function reset(): void {
		$this->depth      = array();
		$this->suppressed = array();
	}

	/**
	 * Build a storage key for a rule + order pair.
	 *
	 * @param  int $rule_id  Rule ID.
	 * @param  int $order_id Order ID.
	 * @return string
	 */
	private function key( int $rule_id, int $order_id ): string {
		return $rule_id . ':' . $order_id;
	}
}
