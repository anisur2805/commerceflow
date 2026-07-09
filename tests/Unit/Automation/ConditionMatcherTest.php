<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\ConditionMatcher;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for ConditionMatcher — pure condition evaluation against an
 * order snapshot (array), no WordPress dependency.
 */
class ConditionMatcherTest extends TestCase {

	public function test_empty_conditions_match_everything(): void {
		$this->assertTrue( ConditionMatcher::match( array(), array( 'status' => 'wc-completed' ) ) );
	}

	public function test_eq_operator_matches(): void {
		$conditions = array(
			array( 'field' => 'status', 'operator' => 'eq', 'value' => 'wc-completed' ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'status' => 'wc-completed' ) ) );
	}

	public function test_eq_operator_does_not_match(): void {
		$conditions = array(
			array( 'field' => 'status', 'operator' => 'eq', 'value' => 'wc-completed' ),
		);
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'status' => 'wc-pending' ) ) );
	}

	public function test_gt_operator_with_numeric(): void {
		$conditions = array(
			array( 'field' => 'total', 'operator' => 'gt', 'value' => 50 ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'total' => 100 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'total' => 50 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'total' => 10 ) ) );
	}

	public function test_gte_operator(): void {
		$conditions = array(
			array( 'field' => 'total', 'operator' => 'gte', 'value' => 50 ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'total' => 50 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'total' => 49 ) ) );
	}

	public function test_lt_operator(): void {
		$conditions = array(
			array( 'field' => 'total', 'operator' => 'lt', 'value' => 50 ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'total' => 49 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'total' => 50 ) ) );
	}

	public function test_lte_operator(): void {
		$conditions = array(
			array( 'field' => 'total', 'operator' => 'lte', 'value' => 50 ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'total' => 50 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'total' => 51 ) ) );
	}

	public function test_neq_operator(): void {
		$conditions = array(
			array( 'field' => 'payment_method', 'operator' => 'neq', 'value' => 'cod' ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'payment_method' => 'stripe' ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'payment_method' => 'cod' ) ) );
	}

	public function test_in_operator_matches_membership(): void {
		$conditions = array(
			array( 'field' => 'status', 'operator' => 'in', 'value' => array( 'wc-completed', 'wc-processing' ) ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'status' => 'wc-completed' ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'status' => 'wc-failed' ) ) );
	}

	public function test_missing_field_treated_as_no_match(): void {
		$conditions = array(
			array( 'field' => 'total', 'operator' => 'gt', 'value' => 0 ),
		);
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'status' => 'wc-completed' ) ) );
	}

	public function test_all_conditions_must_match_and_logic(): void {
		$conditions = array(
			array( 'field' => 'status', 'operator' => 'eq', 'value' => 'wc-processing' ),
			array( 'field' => 'total', 'operator' => 'gt', 'value' => 100 ),
		);
		$this->assertTrue( ConditionMatcher::match( $conditions, array( 'status' => 'wc-processing', 'total' => 200 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'status' => 'wc-processing', 'total' => 50 ) ) );
		$this->assertFalse( ConditionMatcher::match( $conditions, array( 'status' => 'wc-completed', 'total' => 200 ) ) );
	}
}
