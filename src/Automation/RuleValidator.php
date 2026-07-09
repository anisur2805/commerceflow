<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

use InvalidArgumentException;

/**
 * Pure rule validation — no WordPress dependency.
 *
 * Sanitizes and validates a rule payload, returning a clean normalized
 * array (without id/timestamps, which are managed by the repository).
 */
class RuleValidator {

	/** @var array<int, string> */
	public const TRIGGERS = array(
		'order_created',
		'order_paid',
		'order_failed',
		'order_status_changed',
	);

	/** @var array<int, string> */
	public const ACTION_TYPES = array(
		'change_status',
		'add_order_note',
		'generate_coupon',
		'call_webhook',
	);

	/** @var array<int, string> */
	public const OPERATORS = array( 'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in' );

	/**
	 * Sanitize and validate a rule payload.
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

		$trigger = (string) ( $raw['trigger'] ?? '' );
		if ( ! in_array( $trigger, self::TRIGGERS, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown trigger "%s".', $trigger ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$trigger_config = (array) ( $raw['trigger_config'] ?? array() );

		$conditions = array();
		foreach ( (array) ( $raw['conditions'] ?? array() ) as $condition ) {
			$conditions[] = self::sanitize_condition( $condition );
		}

		$actions = array();
		foreach ( (array) ( $raw['actions'] ?? array() ) as $action ) {
			$actions[] = self::sanitize_action( $action );
		}

		if ( array() === $actions ) {
			throw new InvalidArgumentException( 'At least one action is required.' );
		}

		$enabled  = isset( $raw['enabled'] ) ? (bool) $raw['enabled'] : false;
		$priority = isset( $raw['priority'] ) ? (int) $raw['priority'] : 0;

		return array(
			'name'           => $name,
			'trigger'        => $trigger,
			'trigger_config' => $trigger_config,
			'conditions'     => $conditions,
			'actions'        => $actions,
			'enabled'        => $enabled,
			'priority'       => $priority,
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

		if ( ! isset( $condition['field'] ) ) {
			throw new InvalidArgumentException( 'Condition must have a field.' );
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
			'field'    => (string) $condition['field'],
			'operator' => $operator,
			'value'    => $condition['value'],
		);
	}

	/**
	 * @param  mixed $action Raw action.
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	private static function sanitize_action( $action ): array {
		if ( ! is_array( $action ) ) {
			throw new InvalidArgumentException( 'Action must be an array.' );
		}

		$type = (string) ( $action['type'] ?? '' );
		if ( ! in_array( $type, self::ACTION_TYPES, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Unknown action type "%s".', $type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		return array(
			'type'   => $type,
			'config' => (array) ( $action['config'] ?? array() ),
		);
	}
}
