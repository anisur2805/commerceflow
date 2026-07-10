<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Workflow;

use CommerceFlow\Workflow\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OrderStatus — custom fulfillment statuses (FR-FLOW-1).
 */
class OrderStatusTest extends TestCase {

	public function test_all_returns_the_three_custom_slugs(): void {
		$this->assertSame(
			array( OrderStatus::PACKING, OrderStatus::READY, OrderStatus::SHIPPED ),
			OrderStatus::all()
		);
	}

	public function test_labels_are_keyed_by_slug(): void {
		$labels = OrderStatus::labels();

		$this->assertArrayHasKey( OrderStatus::PACKING, $labels );
		$this->assertSame( 'Ready to Ship', $labels[ OrderStatus::READY ] );
	}

	public function test_is_custom_distinguishes_plugin_statuses(): void {
		$this->assertTrue( OrderStatus::is_custom( OrderStatus::SHIPPED ) );
		$this->assertFalse( OrderStatus::is_custom( 'completed' ) );
		$this->assertFalse( OrderStatus::is_custom( 'processing' ) );
	}
}
