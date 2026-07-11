<?php

declare(strict_types=1);

namespace CommerceFlow\Shipping;

use InvalidArgumentException;

/**
 * Pure shipping-rule validation — no WordPress dependency.
 *
 * Sanitizes and validates a rule payload, returning a clean normalized
 * array (without id/timestamps, which are managed by the repository).
 */
class ShippingRuleValidator {

	/** @var array<int, string> */
	public const FIELDS = array(
		'country',
		'state',
		'postcode',
		'weight',
		'subtotal',
		'category',
		'shipping_class',
		'coupon',
	);

	/** @var array<int, string> */
	public const OPERATORS = array( 'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'contains' );

	/**
	 * Sanitize and validate a shipping-rule payload.
	 *
	 * @param  array<string, mixed> $raw Raw input data.
	 * @return array<string, mixed> Sanitized rule data safe for storage.
	 * @throws InvalidArgumentException If any value is invalid.
	 */
	public static function sanitize( array $raw ): array {
		$name = trim( (string) ( $raw['name'] ?? '' ) );
		if ( '' === $name ) {
			throw new InvalidArgumentException( 'Rule name must not be empty.' );
		}

		$conditions = array();
		foreach ( (array) ( $raw['conditions'] ?? array() ) as $condition ) {
			$conditions[] = self::sanitize_condition( $condition );
		}

		$rate     = self::sanitize_rate( (array) ( $raw['rate'] ?? array() ) );
		$enabled  = isset( $raw['enabled'] ) ? (bool) $raw['enabled'] : false;
		$priority = isset( $raw['priority'] ) ? (int) $raw['priority'] : 0;

		return array(
			'name'       => $name,
			'conditions' => $conditions,
			'rate'       => $rate,
			'enabled'    => $enabled,
			'priority'   => $priority,
		);
	}

	/**
	 * @param  mixed $condition Raw condition.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	private static function sanitize_condition( $condition ): array {
		if ( ! is_array( $condition ) ) {
			throw new InvalidArgumentException( 'Condition must be an array.' );
		}

		$field = (string) ( $condition['field'] ?? '' );
		if ( ! in_array( $field, self::FIELDS, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown field "%s".', $field ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$operator = (string) ( $condition['operator'] ?? '' );
		if ( ! in_array( $operator, self::OPERATORS, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown operator "%s".', $operator ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( ! array_key_exists( 'value', $condition ) ) {
			throw new InvalidArgumentException( 'Condition must have a value.' );
		}

		return array(
			'field'    => $field,
			'operator' => $operator,
			'value'    => $condition['value'],
		);
	}

	/**
	 * @param  array<string, mixed> $rate Raw rate.
	 * @return array{label: string, cost: float}
	 * @throws InvalidArgumentException
	 */
	private static function sanitize_rate( array $rate ): array {
		$label = trim( (string) ( $rate['label'] ?? '' ) );
		if ( '' === $label ) {
			throw new InvalidArgumentException( 'Rate label must not be empty.' );
		}

		$cost = (float) ( $rate['cost'] ?? 0 );
		if ( $cost < 0 ) {
			throw new InvalidArgumentException( 'Rate cost must not be negative.' );
		}

		return array(
			'label' => $label,
			'cost'  => $cost,
		);
	}
}
