<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Shipping;

use CommerceFlow\Shipping\ShippingRuleValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ShippingRuleValidator — pure input sanitization (FR-SHIP-1).
 */
class ShippingRuleValidatorTest extends TestCase {

	public function test_sanitizes_a_valid_rule(): void {
		$clean = ShippingRuleValidator::sanitize(
			array(
				'name'       => '  Free over $50  ',
				'priority'   => 10,
				'enabled'    => 1,
				'conditions' => array(
					array(
						'field'    => 'subtotal',
						'operator' => 'gte',
						'value'    => 50,
					),
				),
				'rate'       => array(
					'label' => 'Free Shipping',
					'cost'  => 0,
				),
			)
		);

		$this->assertSame( 'Free over $50', $clean['name'] );
		$this->assertSame( 10, $clean['priority'] );
		$this->assertTrue( $clean['enabled'] );
		$this->assertCount( 1, $clean['conditions'] );
		$this->assertSame( 'Free Shipping', $clean['rate']['label'] );
		$this->assertSame( 0.0, $clean['rate']['cost'] );
	}

	public function test_rejects_empty_name(): void {
		$this->expectException( InvalidArgumentException::class );
		ShippingRuleValidator::sanitize(
			array(
				'name' => '   ',
				'rate' => array(
					'label' => 'X',
					'cost'  => 5,
				),
			)
		);
	}

	public function test_rejects_unknown_field(): void {
		$this->expectException( InvalidArgumentException::class );
		ShippingRuleValidator::sanitize(
			array(
				'name'       => 'Bad',
				'conditions' => array(
					array(
						'field'    => 'moon_phase',
						'operator' => 'eq',
						'value'    => 'full',
					),
				),
				'rate'       => array(
					'label' => 'X',
					'cost'  => 5,
				),
			)
		);
	}

	public function test_rejects_unknown_operator(): void {
		$this->expectException( InvalidArgumentException::class );
		ShippingRuleValidator::sanitize(
			array(
				'name'       => 'Bad',
				'conditions' => array(
					array(
						'field'    => 'country',
						'operator' => 'startswith',
						'value'    => 'US',
					),
				),
				'rate'       => array(
					'label' => 'X',
					'cost'  => 5,
				),
			)
		);
	}

	public function test_rejects_empty_rate_label(): void {
		$this->expectException( InvalidArgumentException::class );
		ShippingRuleValidator::sanitize(
			array(
				'name' => 'No label',
				'rate' => array(
					'label' => '',
					'cost'  => 5,
				),
			)
		);
	}

	public function test_rejects_negative_cost(): void {
		$this->expectException( InvalidArgumentException::class );
		ShippingRuleValidator::sanitize(
			array(
				'name' => 'Negative',
				'rate' => array(
					'label' => 'X',
					'cost'  => -1,
				),
			)
		);
	}
}
