<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\ActionPlanner;
use CommerceFlow\Automation\ExecutionPolicy;
use CommerceFlow\Automation\IdempotencyStore;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for ExecutionPolicy — partial-failure handling (FR-AUTO-6) and
 * idempotent skip-on-retry integration (FR-AUTO-5).
 *
 * ExecutionPolicy accepts a pure executor callable so it can be unit tested
 * without WooCommerce. In production the executor performs real order
 * mutations through the CRUD layer.
 */
class ExecutionPolicyTest extends TestCase {

	private function make_actions( int $count ): array {
		$out = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$out[] = array(
				'type'   => 'change_status',
				'config' => array( 'status' => 'wc-completed-' . $i ),
			);
		}
		return ActionPlanner::plan( $out );
	}

	public function test_all_actions_succeed(): void {
		$planned   = $this->make_actions( 3 );
		$policy    = new ExecutionPolicy();
		$store     = new IdempotencyStore();
		$executor  = static function () {
			return array( 'success' => true, 'message' => 'ok' );
		};

		$result = $policy->execute( $planned, $executor, 'stop', $store, 'run-1' );

		$this->assertSame( 'success', $result->status );
		$this->assertCount( 3, $result->actions );
		$this->assertSame( 'success', $result->actions[0]['status'] );
	}

	public function test_stop_policy_halts_after_failure(): void {
		$planned  = $this->make_actions( 3 );
		$policy   = new ExecutionPolicy();
		$store    = new IdempotencyStore();
		$call     = 0;
		$executor = function () use ( &$call ) {
			$call++;
			if ( 2 === $call ) {
				return array( 'success' => false, 'message' => 'boom' );
			}
			return array( 'success' => true, 'message' => 'ok' );
		};

		$result = $policy->execute( $planned, $executor, 'stop', $store, 'run-1' );

		$this->assertSame( 'partial_failure', $result->status );
		$this->assertSame( 'success', $result->actions[0]['status'] );
		$this->assertSame( 'failed', $result->actions[1]['status'] );
		$this->assertSame( 'skipped', $result->actions[2]['status'] );
	}

	public function test_continue_policy_runs_remaining_after_failure(): void {
		$planned  = $this->make_actions( 3 );
		$policy   = new ExecutionPolicy();
		$store    = new IdempotencyStore();
		$call     = 0;
		$executor = function () use ( &$call ) {
			$call++;
			if ( 2 === $call ) {
				return array( 'success' => false, 'message' => 'boom' );
			}
			return array( 'success' => true, 'message' => 'ok' );
		};

		$result = $policy->execute( $planned, $executor, 'continue', $store, 'run-1' );

		$this->assertSame( 'partial_failure', $result->status );
		$this->assertSame( 'success', $result->actions[0]['status'] );
		$this->assertSame( 'failed', $result->actions[1]['status'] );
		$this->assertSame( 'success', $result->actions[2]['status'] );
	}

	public function test_retry_does_not_double_apply_idempotent_actions(): void {
		$planned  = $this->make_actions( 2 );
		$policy   = new ExecutionPolicy();
		$store    = new IdempotencyStore();
		$applies  = 0;
		$executor = function () use ( &$applies ) {
			$applies++;
			return array( 'success' => true, 'message' => 'ok' );
		};

		$policy->execute( $planned, $executor, 'stop', $store, 'run-1' );
		$first = $applies;
		$policy->execute( $planned, $executor, 'stop', $store, 'run-1' );
		$second = $applies;

		$this->assertSame( 2, $first );
		$this->assertSame( 2, $second );
	}

	public function test_empty_actions_yield_success_with_nothing(): void {
		$policy   = new ExecutionPolicy();
		$executor = static function () {
			return array( 'success' => true, 'message' => 'ok' );
		};

		$result = $policy->execute( array(), $executor, 'stop', new IdempotencyStore(), 'run-1' );

		$this->assertSame( 'success', $result->status );
		$this->assertSame( array(), $result->actions );
	}
}
