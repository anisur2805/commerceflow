<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Executes a rule's planned actions with idempotency and partial-failure
 * handling (FR-AUTO-5, FR-AUTO-6).
 *
 * Accepts a pure executor callable so it can be unit tested without
 * WooCommerce. The executor receives a PlannedAction and returns
 * array{success: bool, message: string}.
 */
class ExecutionPolicy {

	/** @var string Continue executing remaining actions after a failure. */
	public const MODE_CONTINUE = 'continue';

	/** @var string Stop executing after the first failure. */
	public const MODE_STOP = 'stop';

	/**
	 * Execute planned actions with idempotency and a failure policy.
	 *
	 * @param  array<int, PlannedAction> $actions   Planned actions.
	 * @param  callable                  $executor  Executor: PlannedAction => array{success: bool, message: string}.
	 * @param  string                    $mode      'continue' or 'stop'.
	 * @param  IdempotencyStore          $store     Idempotency store.
	 * @param  string                    $run_id    Run identifier.
	 * @return ExecutionResult
	 */
	public function execute( array $actions, callable $executor, string $mode, IdempotencyStore $store, string $run_id ): ExecutionResult {
		$results     = array();
		$had_failure = false;
		$count       = count( $actions );

		for ( $i = 0; $i < $count; $i++ ) {
			$action = $actions[ $i ];

			if ( $store->is_applied( $run_id, $action->hash() ) ) {
				$results[] = array(
					'type'    => $action->type,
					'status'  => 'skipped',
					'message' => 'Already applied (idempotent skip).',
				);
				continue;
			}

			$outcome = $executor( $action );
			$success = (bool) ( $outcome['success'] ?? false );
			$message = (string) ( $outcome['message'] ?? '' );

			if ( $success ) {
				$store->record( $run_id, $action->hash() );
				$results[] = array(
					'type'    => $action->type,
					'status'  => 'success',
					'message' => $message,
				);
				continue;
			}

			$had_failure = true;
			$results[]   = array(
				'type'    => $action->type,
				'status'  => 'failed',
				'message' => $message,
			);

			if ( self::MODE_STOP === $mode ) {
				for ( $j = $i + 1; $j < $count; $j++ ) {
					$results[] = array(
						'type'    => $actions[ $j ]->type,
						'status'  => 'skipped',
						'message' => 'Skipped due to stop policy.',
					);
				}
				break;
			}
		}

		if ( array() === $actions ) {
			return new ExecutionResult( 'success', $results );
		}

		$status = $had_failure ? 'partial_failure' : 'success';

		return new ExecutionResult( $status, $results );
	}
}
