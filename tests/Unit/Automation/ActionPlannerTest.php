<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\ActionPlanner;
use CommerceFlow\Automation\DryRunReporter;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for ActionPlanner and DryRunReporter (FR-AUTO-7 dry-run).
 *
 * ActionPlanner normalizes rule actions into PlannedAction objects with a
 * stable hash for idempotency. DryRunReporter produces a side-effect-free
 * report of what would happen.
 */
class ActionPlannerTest extends TestCase {

	public function test_plan_normalizes_actions_into_planned_actions(): void {
		$actions = array(
			array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ),
			array( 'type' => 'add_order_note', 'config' => array( 'note' => 'Auto-completed' ) ),
		);

		$planned = ActionPlanner::plan( $actions );

		$this->assertCount( 2, $planned );
		$this->assertSame( 'change_status', $planned[0]->type );
		$this->assertSame( 'wc-completed', $planned[0]->config['status'] );
		$this->assertSame( 'add_order_note', $planned[1]->type );
	}

	public function test_planned_action_has_stable_hash(): void {
		$planned = ActionPlanner::plan(
			array( array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ) )
		);

		$this->assertNotEmpty( $planned[0]->hash() );
		$this->assertSame( $planned[0]->hash(), $planned[0]->hash() );
	}

	public function test_planned_action_hash_differs_for_different_config(): void {
		$a = ActionPlanner::plan( array( array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ) ) )[0];
		$b = ActionPlanner::plan( array( array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-processing' ) ) ) )[0];

		$this->assertNotSame( $a->hash(), $b->hash() );
	}

	public function test_plan_empty_actions_returns_empty_array(): void {
		$this->assertSame( array(), ActionPlanner::plan( array() ) );
	}

	public function test_dry_run_report_lists_would_apply_without_side_effects(): void {
		$rule   = array(
			'id'         => 1,
			'name'       => 'Complete paid',
			'trigger'    => 'order_paid',
			'conditions' => array(),
			'actions'    => array(
				array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ),
				array( 'type' => 'add_order_note', 'config' => array( 'note' => 'Done' ) ),
			),
		);
		$report = DryRunReporter::report( $rule, array( 'id' => 42, 'status' => 'wc-processing' ) );

		$this->assertTrue( $report['would_match'] );
		$this->assertFalse( $report['applied'] );
		$this->assertCount( 2, $report['planned_actions'] );
	}

	public function test_dry_run_report_no_match_with_conditions_that_dont_pass(): void {
		$rule = array(
			'id'         => 1,
			'name'       => 'Big orders only',
			'trigger'    => 'order_paid',
			'conditions' => array( array( 'field' => 'total', 'operator' => 'gt', 'value' => 100 ) ),
			'actions'    => array( array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ) ),
		);
		$report = DryRunReporter::report( $rule, array( 'id' => 42, 'total' => 50 ) );

		$this->assertFalse( $report['would_match'] );
		$this->assertSame( array(), $report['planned_actions'] );
	}
}
