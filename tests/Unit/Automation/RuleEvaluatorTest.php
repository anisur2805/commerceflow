<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\Rule;
use CommerceFlow\Automation\RuleEvaluator;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for RuleEvaluator — pure matching of rules to a trigger + snapshot.
 */
class RuleEvaluatorTest extends TestCase {

	private function make_rule( array $overrides = array() ): Rule {
		return Rule::from_array(
			array_merge(
				array(
					'id'             => 0,
					'name'           => 'Rule',
					'trigger'        => 'order_created',
					'trigger_config' => array(),
					'conditions'     => array(),
					'actions'        => array(),
					'enabled'        => true,
					'priority'       => 0,
					'created_at'     => '',
					'updated_at'     => '',
				),
				$overrides
			)
		);
	}

	public function test_returns_enabled_rule_matching_trigger(): void {
		$rule      = $this->make_rule( array( 'id' => 1, 'trigger' => 'order_paid' ) );
		$evaluator = new RuleEvaluator();

		$matches = $evaluator->evaluate( array( $rule ), 'order_paid', array(), array( 'id' => 1 ) );

		$this->assertCount( 1, $matches );
		$this->assertSame( 1, $matches[0]->id );
	}

	public function test_skips_disabled_rules(): void {
		$rule      = $this->make_rule( array( 'id' => 1, 'enabled' => false ) );
		$evaluator = new RuleEvaluator();

		$matches = $evaluator->evaluate( array( $rule ), 'order_created', array(), array( 'id' => 1 ) );

		$this->assertSame( array(), $matches );
	}

	public function test_skips_rules_with_different_trigger(): void {
		$rule      = $this->make_rule( array( 'id' => 1, 'trigger' => 'order_paid' ) );
		$evaluator = new RuleEvaluator();

		$matches = $evaluator->evaluate( array( $rule ), 'order_failed', array(), array( 'id' => 1 ) );

		$this->assertSame( array(), $matches );
	}

	public function test_filters_by_conditions(): void {
		$rule = $this->make_rule(
			array(
				'id'         => 1,
				'conditions' => array( array( 'field' => 'total', 'operator' => 'gt', 'value' => 100 ) ),
			)
		);
		$evaluator = new RuleEvaluator();

		$match = $evaluator->evaluate( array( $rule ), 'order_created', array(), array( 'id' => 1, 'total' => 200 ) );
		$no    = $evaluator->evaluate( array( $rule ), 'order_created', array(), array( 'id' => 1, 'total' => 50 ) );

		$this->assertCount( 1, $match );
		$this->assertSame( array(), $no );
	}

	public function test_results_ordered_by_priority_desc(): void {
		$low  = $this->make_rule( array( 'id' => 1, 'priority' => 1 ) );
		$high = $this->make_rule( array( 'id' => 2, 'priority' => 99 ) );
		$evaluator = new RuleEvaluator();

		$matches = $evaluator->evaluate( array( $low, $high ), 'order_created', array(), array( 'id' => 1 ) );

		$this->assertSame( 2, $matches[0]->id );
		$this->assertSame( 1, $matches[1]->id );
	}

	public function test_status_changed_trigger_config_matches_from_to(): void {
		$rule = $this->make_rule(
			array(
				'id'             => 1,
				'trigger'        => 'order_status_changed',
				'trigger_config' => array( 'from' => 'wc-pending', 'to' => 'wc-processing' ),
			)
		);
		$evaluator = new RuleEvaluator();

		$match = $evaluator->evaluate(
			array( $rule ),
			'order_status_changed',
			array( 'from' => 'wc-pending', 'to' => 'wc-processing' ),
			array( 'id' => 1 )
		);
		$no = $evaluator->evaluate(
			array( $rule ),
			'order_status_changed',
			array( 'from' => 'wc-pending', 'to' => 'wc-cancelled' ),
			array( 'id' => 1 )
		);

		$this->assertCount( 1, $match );
		$this->assertSame( array(), $no );
	}

	public function test_status_changed_trigger_config_wildcard_matches_any(): void {
		$rule = $this->make_rule(
			array(
				'id'             => 1,
				'trigger'        => 'order_status_changed',
				'trigger_config' => array(),
			)
		);
		$evaluator = new RuleEvaluator();

		$match = $evaluator->evaluate(
			array( $rule ),
			'order_status_changed',
			array( 'from' => 'wc-on-hold', 'to' => 'wc-completed' ),
			array( 'id' => 1 )
		);

		$this->assertCount( 1, $match );
	}
}
