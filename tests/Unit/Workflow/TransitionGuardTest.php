<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Workflow;

use CommerceFlow\Workflow\OrderStatus;
use CommerceFlow\Workflow\TransitionGuard;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TransitionGuard — guarded order transitions (FR-FLOW-1).
 */
class TransitionGuardTest extends TestCase {

	public function test_allows_a_mapped_transition(): void {
		$guard = new TransitionGuard();

		$this->assertTrue( $guard->can( 'processing', OrderStatus::PACKING ) );
		$this->assertTrue( $guard->can( OrderStatus::PACKING, OrderStatus::READY ) );
		$this->assertTrue( $guard->can( OrderStatus::READY, OrderStatus::SHIPPED ) );
		$this->assertTrue( $guard->can( OrderStatus::SHIPPED, 'completed' ) );
	}

	public function test_rejects_an_unmapped_transition(): void {
		$guard = new TransitionGuard();

		// Cannot skip straight from processing to shipped.
		$this->assertFalse( $guard->can( 'processing', OrderStatus::SHIPPED ) );
		// Cannot ship an order that is only packing.
		$this->assertFalse( $guard->can( OrderStatus::PACKING, OrderStatus::SHIPPED ) );
	}

	public function test_rejects_transition_to_same_status(): void {
		$guard = new TransitionGuard();

		$this->assertFalse( $guard->can( OrderStatus::PACKING, OrderStatus::PACKING ) );
	}

	public function test_rejects_transition_from_unknown_source(): void {
		$guard = new TransitionGuard();

		$this->assertFalse( $guard->can( 'nonsense', OrderStatus::PACKING ) );
		$this->assertSame( array(), $guard->allowed_from( 'nonsense' ) );
	}

	public function test_allowed_from_lists_targets(): void {
		$guard = new TransitionGuard();

		$this->assertContains( OrderStatus::READY, $guard->allowed_from( OrderStatus::PACKING ) );
		$this->assertContains( 'cancelled', $guard->allowed_from( OrderStatus::PACKING ) );
	}

	public function test_accepts_a_custom_map(): void {
		$guard = new TransitionGuard( array( 'a' => array( 'b' ) ) );

		$this->assertTrue( $guard->can( 'a', 'b' ) );
		$this->assertFalse( $guard->can( 'b', 'a' ) );
	}
}
