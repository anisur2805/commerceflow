<?php

declare(strict_types=1);

namespace CommerceFlow\Tests\Unit\Automation;

use CommerceFlow\Automation\IdempotencyStore;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for IdempotencyStore — idempotency under retry (FR-AUTO-5).
 *
 * A retried job must not double-apply its actions.
 */
class IdempotencyStoreTest extends TestCase {

	public function test_first_record_returns_true(): void {
		$store = new IdempotencyStore();

		$this->assertTrue( $store->record( 'run-1', 'hash-a' ) );
	}

	public function test_second_record_of_same_hash_returns_false(): void {
		$store = new IdempotencyStore();

		$store->record( 'run-1', 'hash-a' );
		$this->assertFalse( $store->record( 'run-1', 'hash-a' ) );
	}

	public function test_different_run_ids_are_independent(): void {
		$store = new IdempotencyStore();

		$store->record( 'run-1', 'hash-a' );
		$this->assertTrue( $store->record( 'run-2', 'hash-a' ) );
	}

	public function test_different_hashes_in_same_run_are_independent(): void {
		$store = new IdempotencyStore();

		$store->record( 'run-1', 'hash-a' );
		$this->assertTrue( $store->record( 'run-1', 'hash-b' ) );
	}

	public function test_is_applied_reflects_state(): void {
		$store = new IdempotencyStore();

		$this->assertFalse( $store->is_applied( 'run-1', 'hash-a' ) );
		$store->record( 'run-1', 'hash-a' );
		$this->assertTrue( $store->is_applied( 'run-1', 'hash-a' ) );
	}

	public function test_clear_run_removes_only_that_run(): void {
		$store = new IdempotencyStore();

		$store->record( 'run-1', 'hash-a' );
		$store->record( 'run-2', 'hash-a' );
		$store->clear( 'run-1' );

		$this->assertFalse( $store->is_applied( 'run-1', 'hash-a' ) );
		$this->assertTrue( $store->is_applied( 'run-2', 'hash-a' ) );
	}
}
