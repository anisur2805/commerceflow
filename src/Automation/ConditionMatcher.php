<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Pure condition evaluation against an order snapshot (array).
 *
 * All conditions are AND-ed; an empty conditions list matches everything.
 * No WordPress dependency — fully unit-testable.
 */
class ConditionMatcher {

	/**
	 * Evaluate whether an order snapshot matches all conditions.
	 *
	 * @param  array<int, array<string, mixed>> $conditions Condition specs.
	 * @param  array<string, mixed>             $snapshot   Order snapshot.
	 * @return bool
	 */
	public static function match( array $conditions, array $snapshot ): bool {
		if ( array() === $conditions ) {
			return true;
		}

		foreach ( $conditions as $condition ) {
			$field    = (string) ( $condition['field'] ?? '' );
			$operator = (string) ( $condition['operator'] ?? '' );
			$value    = $condition['value'] ?? null;

			if ( ! array_key_exists( $field, $snapshot ) ) {
				return false;
			}

			$actual = $snapshot[ $field ];

			if ( ! self::compare( $actual, $operator, $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare a snapshot value against the expected value using an operator.
	 *
	 * @param  mixed  $actual   Snapshot value.
	 * @param  string $operator Comparison operator.
	 * @param  mixed  $expected Condition value.
	 * @return bool
	 */
	private static function compare( $actual, string $operator, $expected ): bool {
		switch ( $operator ) {
			case 'eq':
				return (string) $actual === (string) $expected;
			case 'neq':
				return (string) $actual !== (string) $expected;
			case 'gt':
				return (float) $actual > (float) $expected;
			case 'gte':
				return (float) $actual >= (float) $expected;
			case 'lt':
				return (float) $actual < (float) $expected;
			case 'lte':
				return (float) $actual <= (float) $expected;
			case 'in':
				return is_array( $expected ) && in_array( (string) $actual, array_map( 'strval', $expected ), true );
			default:
				return false;
		}
	}
}
