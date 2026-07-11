<?php

declare(strict_types=1);

namespace CommerceFlow\Shipping;

/**
 * Pure, priority-ordered shipping-rate resolution against a package snapshot.
 *
 * Rules are evaluated highest-priority first; the first rule whose conditions
 * all match wins ("first match wins"). No WordPress dependency — fully
 * unit-testable. The same resolver powers live rate injection and the preview
 * tool, so both agree by construction.
 */
class ShippingRateResolver {

	/**
	 * Resolve the winning rate for a package snapshot.
	 *
	 * @param  array<int, array<string, mixed>> $rules    Rule arrays (id, priority, enabled, conditions, rate).
	 * @param  array<string, mixed>             $snapshot Package snapshot.
	 * @return array{rule_id: int, name: string, label: string, cost: float}|null Winning rate, or null if none match.
	 */
	public static function resolve( array $rules, array $snapshot ): ?array {
		$enabled = array_values(
			array_filter(
				$rules,
				static function ( array $rule ): bool {
					return ! empty( $rule['enabled'] );
				}
			)
		);

		usort(
			$enabled,
			static function ( array $a, array $b ): int {
				return ( (int) ( $b['priority'] ?? 0 ) ) <=> ( (int) ( $a['priority'] ?? 0 ) );
			}
		);

		foreach ( $enabled as $rule ) {
			if ( self::matches( (array) ( $rule['conditions'] ?? array() ), $snapshot ) ) {
				$rate = (array) ( $rule['rate'] ?? array() );
				return array(
					'rule_id' => (int) ( $rule['id'] ?? 0 ),
					'name'    => (string) ( $rule['name'] ?? '' ),
					'label'   => (string) ( $rate['label'] ?? '' ),
					'cost'    => (float) ( $rate['cost'] ?? 0 ),
				);
			}
		}

		return null;
	}

	/**
	 * Whether all conditions match the snapshot (AND). Empty list matches all.
	 *
	 * @param  array<int, array<string, mixed>> $conditions Condition specs.
	 * @param  array<string, mixed>             $snapshot   Package snapshot.
	 * @return bool
	 */
	private static function matches( array $conditions, array $snapshot ): bool {
		foreach ( $conditions as $condition ) {
			$field    = (string) ( $condition['field'] ?? '' );
			$operator = (string) ( $condition['operator'] ?? '' );
			$expected = $condition['value'] ?? null;

			if ( ! array_key_exists( $field, $snapshot ) ) {
				return false;
			}

			if ( ! self::compare( $snapshot[ $field ], $operator, $expected ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare a snapshot value against the expected value using an operator.
	 *
	 * @param  mixed  $actual   Snapshot value (scalar, or array for list fields).
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
			case 'contains':
				return is_array( $actual ) && in_array( (string) $expected, array_map( 'strval', $actual ), true );
			default:
				return false;
		}
	}
}
