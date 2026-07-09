<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\Rule;
use CommerceFlow\Automation\RuleValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for the Automation Rule DTO and RuleValidator.
 *
 * Written before the classes exist — defines the contract.
 */
class RuleValidatorTest extends TestCase {

	public function test_sanitize_valid_rule(): void {
		$payload = array(
			'name'            => 'Auto-complete digital orders',
			'trigger'         => 'order_paid',
			'trigger_config'  => array(),
			'conditions'      => array(
				array( 'field' => 'total', 'operator' => 'gt', 'value' => 0 ),
			),
			'actions'         => array(
				array( 'type' => 'change_status', 'config' => array( 'status' => 'wc-completed' ) ),
			),
			'enabled'         => true,
			'priority'        => 10,
		);

		$clean = RuleValidator::sanitize( $payload );

		$this->assertSame( 'Auto-complete digital orders', $clean['name'] );
		$this->assertSame( 'order_paid', $clean['trigger'] );
		$this->assertTrue( $clean['enabled'] );
		$this->assertSame( 10, $clean['priority'] );
	}

	public function test_sanitize_rejects_empty_name(): void {
		$this->expectException( InvalidArgumentException::class );
		RuleValidator::sanitize( array( 'name' => '', 'trigger' => 'order_created' ) );
	}

	public function test_sanitize_rejects_unknown_trigger(): void {
		$this->expectException( InvalidArgumentException::class );
		RuleValidator::sanitize( array( 'name' => 'Rule', 'trigger' => 'order_blasted' ) );
	}

	public function test_sanitize_defaults_enabled_to_false(): void {
		$clean = RuleValidator::sanitize(
			array(
				'name'    => 'Rule',
				'trigger' => 'order_created',
				'actions' => array( array( 'type' => 'add_order_note', 'config' => array( 'note' => 'Test' ) ) ),
			)
		);
		$this->assertFalse( $clean['enabled'] );
	}

	public function test_sanitize_defaults_priority_to_zero(): void {
		$clean = RuleValidator::sanitize(
			array(
				'name'    => 'Rule',
				'trigger' => 'order_created',
				'actions' => array( array( 'type' => 'add_order_note', 'config' => array( 'note' => 'Test' ) ) ),
			)
		);
		$this->assertSame( 0, $clean['priority'] );
	}

	public function test_sanitize_rejects_unknown_action_type(): void {
		$this->expectException( InvalidArgumentException::class );
		RuleValidator::sanitize(
			array(
				'name'    => 'Rule',
				'trigger' => 'order_created',
				'actions' => array( array( 'type' => 'delete_everything', 'config' => array() ) ),
			)
		);
	}

	public function test_sanitize_rejects_condition_missing_field(): void {
		$this->expectException( InvalidArgumentException::class );
		RuleValidator::sanitize(
			array(
				'name'       => 'Rule',
				'trigger'    => 'order_created',
				'conditions' => array( array( 'operator' => 'eq', 'value' => 'x' ) ),
			)
		);
	}

	public function test_sanitize_rejects_unknown_operator(): void {
		$this->expectException( InvalidArgumentException::class );
		RuleValidator::sanitize(
			array(
				'name'       => 'Rule',
				'trigger'    => 'order_created',
				'conditions' => array( array( 'field' => 'total', 'operator' => 'roughly', 'value' => 5 ) ),
			)
		);
	}

	public function test_sanitize_normalizes_empty_conditions_and_trigger_config(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'At least one action is required.' );
		RuleValidator::sanitize( array( 'name' => 'Rule', 'trigger' => 'order_created' ) );
	}

	public function test_sanitize_rejects_empty_actions(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'At least one action is required.' );
		RuleValidator::sanitize(
			array(
				'name'    => 'Rule',
				'trigger' => 'order_created',
				'actions' => array(),
			)
		);
	}

	public function test_rule_from_array_round_trips_to_array(): void {
		$rule = Rule::from_array(
			array(
				'id'             => 7,
				'name'           => 'Test',
				'trigger'        => 'order_failed',
				'trigger_config' => array( 'from' => 'wc-pending' ),
				'conditions'     => array(),
				'actions'        => array(),
				'enabled'        => true,
				'priority'       => 5,
				'created_at'     => '2026-07-09 00:00:00',
				'updated_at'     => '2026-07-09 00:00:00',
			)
		);

		$out = $rule->to_array();

		$this->assertSame( 7, $out['id'] );
		$this->assertSame( 'Test', $out['name'] );
		$this->assertSame( 'order_failed', $out['trigger'] );
		$this->assertTrue( $out['enabled'] );
	}
}
