<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Idempotency tracking — prevents double-applying actions on retry (FR-AUTO-5).
 *
 * Pure and stateful — backed by an in-memory array. In production this is
 * sufficient per Action Scheduler job run; for cross-process durability the
 * module can persist run state, but the contract is identical.
 */
class IdempotencyStore {

	/**
	 * Recorded action hashes per run ID.
	 *
	 * @var array<string, array<string, true>>
	 */
	private array $runs = array();

	/**
	 * Record an action hash for a run.
	 *
	 * @param  string $run_id      Run identifier.
	 * @param  string $action_hash Action hash.
	 * @return bool True if newly recorded, false if already applied.
	 */
	public function record( string $run_id, string $action_hash ): bool {
		if ( ! isset( $this->runs[ $run_id ] ) ) {
			$this->runs[ $run_id ] = array();
		}

		if ( isset( $this->runs[ $run_id ][ $action_hash ] ) ) {
			return false;
		}

		$this->runs[ $run_id ][ $action_hash ] = true;
		return true;
	}

	/**
	 * Check whether an action hash was already applied for a run.
	 *
	 * @param  string $run_id      Run identifier.
	 * @param  string $action_hash Action hash.
	 * @return bool
	 */
	public function is_applied( string $run_id, string $action_hash ): bool {
		return isset( $this->runs[ $run_id ][ $action_hash ] );
	}

	/**
	 * Clear all recorded actions for a run.
	 *
	 * @param string $run_id Run identifier.
	 */
	public function clear( string $run_id ): void {
		unset( $this->runs[ $run_id ] );
	}
}
