<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\RecursionGuard;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for RecursionGuard — loop prevention (FR-AUTO-4).
 *
 * A rule whose action would re-satisfy its own trigger must run once, not
 * unbounded. Suppression is tracked.
 */
class RecursionGuardTest extends TestCase {

	public function test_allows_first_entry(): void {
		$guard = new RecursionGuard( 3 );

		$this->assertTrue( $guard->enter( 1, 100 ) );
	}

	public function test_blocks_entry_beyond_max_depth(): void {
		$guard = new RecursionGuard( 1 );

		$this->assertTrue( $guard->enter( 1, 100 ) );
		$this->assertFalse( $guard->enter( 1, 100 ) );
	}

	public function test_exit_allows_re_entry_after_completion(): void {
		$guard = new RecursionGuard( 1 );

		$this->assertTrue( $guard->enter( 1, 100 ) );
		$guard->exit( 1, 100 );
		$this->assertTrue( $guard->enter( 1, 100 ) );
	}

	public function test_tracks_suppression_count(): void {
		$guard = new RecursionGuard( 1 );

		$guard->enter( 1, 100 );
		$guard->enter( 1, 100 );
		$guard->enter( 1, 100 );

		$this->assertSame( 2, $guard->suppression_count( 1, 100 ) );
	}

	public function test_different_orders_are_independent(): void {
		$guard = new RecursionGuard( 1 );

		$this->assertTrue( $guard->enter( 1, 100 ) );
		$this->assertTrue( $guard->enter( 1, 200 ) );
	}

	public function test_reset_clears_all_state(): void {
		$guard = new RecursionGuard( 1 );

		$guard->enter( 1, 100 );
		$guard->reset();

		$this->assertTrue( $guard->enter( 1, 100 ) );
		$this->assertSame( 0, $guard->suppression_count( 1, 100 ) );
	}
}
