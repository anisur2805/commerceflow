<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Shipping;

use CommerceFlow\Shipping\ShippingRateResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ShippingRateResolver — priority-ordered, first-match-wins (FR-SHIP-2).
 */
class ShippingRateResolverTest extends TestCase {

	/**
	 * @param array<string, mixed> $overrides
	 * @return array<string, mixed>
	 */
	private function rule( array $overrides ): array {
		return array_merge(
			array(
				'id'         => 1,
				'name'       => 'Rule',
				'enabled'    => true,
				'priority'   => 0,
				'conditions' => array(),
				'rate'       => array(
					'label' => 'Flat',
					'cost'  => 5.0,
				),
			),
			$overrides
		);
	}

	public function test_no_rules_returns_null(): void {
		$this->assertNull( ShippingRateResolver::resolve( array(), array( 'country' => 'US' ) ) );
	}

	public function test_matches_a_single_rule(): void {
		$rules = array(
			$this->rule(
				array(
					'conditions' => array(
						array(
							'field'    => 'country',
							'operator' => 'eq',
							'value'    => 'US',
						),
					),
				)
			),
		);

		$result = ShippingRateResolver::resolve( $rules, array( 'country' => 'US' ) );

		$this->assertNotNull( $result );
		$this->assertSame( 'Flat', $result['label'] );
		$this->assertSame( 5.0, $result['cost'] );
	}

	public function test_highest_priority_wins(): void {
		$rules = array(
			$this->rule(
				array(
					'id'       => 1,
					'priority' => 1,
					'rate'     => array(
						'label' => 'Low',
						'cost'  => 9.0,
					),
				)
			),
			$this->rule(
				array(
					'id'       => 2,
					'priority' => 10,
					'rate'     => array(
						'label' => 'High',
						'cost'  => 3.0,
					),
				)
			),
		);

		$result = ShippingRateResolver::resolve( $rules, array( 'country' => 'US' ) );

		$this->assertSame( 2, $result['rule_id'] );
		$this->assertSame( 'High', $result['label'] );
	}

	public function test_disabled_rules_are_skipped(): void {
		$rules = array(
			$this->rule(
				array(
					'id'       => 1,
					'priority' => 10,
					'enabled'  => false,
					'rate'     => array(
						'label' => 'Disabled',
						'cost'  => 1.0,
					),
				)
			),
			$this->rule(
				array(
					'id'       => 2,
					'priority' => 1,
					'rate'     => array(
						'label' => 'Enabled',
						'cost'  => 7.0,
					),
				)
			),
		);

		$result = ShippingRateResolver::resolve( $rules, array( 'country' => 'US' ) );

		$this->assertSame( 2, $result['rule_id'] );
	}

	public function test_non_matching_conditions_fall_through(): void {
		$rules = array(
			$this->rule(
				array(
					'conditions' => array(
						array(
							'field'    => 'country',
							'operator' => 'eq',
							'value'    => 'CA',
						),
					),
				)
			),
		);

		$this->assertNull( ShippingRateResolver::resolve( $rules, array( 'country' => 'US' ) ) );
	}

	public function test_weight_and_subtotal_numeric_operators(): void {
		$rules = array(
			$this->rule(
				array(
					'conditions' => array(
						array(
							'field'    => 'weight',
							'operator' => 'gt',
							'value'    => 10,
						),
						array(
							'field'    => 'subtotal',
							'operator' => 'lte',
							'value'    => 100,
						),
					),
				)
			),
		);

		$this->assertNotNull(
			ShippingRateResolver::resolve(
				$rules,
				array(
					'weight'   => 12,
					'subtotal' => 80,
				)
			)
		);
		$this->assertNull(
			ShippingRateResolver::resolve(
				$rules,
				array(
					'weight'   => 5,
					'subtotal' => 80,
				)
			)
		);
	}

	public function test_contains_operator_for_list_fields(): void {
		$rules = array(
			$this->rule(
				array(
					'conditions' => array(
						array(
							'field'    => 'category',
							'operator' => 'contains',
							'value'    => 'fragile',
						),
					),
				)
			),
		);

		$this->assertNotNull(
			ShippingRateResolver::resolve( $rules, array( 'category' => array( 'books', 'fragile' ) ) )
		);
		$this->assertNull(
			ShippingRateResolver::resolve( $rules, array( 'category' => array( 'books' ) ) )
		);
	}

	public function test_missing_snapshot_field_does_not_match(): void {
		$rules = array(
			$this->rule(
				array(
					'conditions' => array(
						array(
							'field'    => 'state',
							'operator' => 'eq',
							'value'    => 'NY',
						),
					),
				)
			),
		);

		$this->assertNull( ShippingRateResolver::resolve( $rules, array( 'country' => 'US' ) ) );
	}
}
