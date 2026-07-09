<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Produces a side-effect-free report of what would happen (FR-AUTO-7 dry-run).
 *
 * Pure — writes no order changes.
 */
class DryRunReporter {

	/**
	 * Build a dry-run report for a rule against an order snapshot.
	 *
	 * Actually evaluates conditions instead of just dumping actions.
	 *
	 * @param  array<string, mixed>             $rule     Rule data.
	 * @param  array<string, mixed>             $snapshot Order snapshot.
	 * @return array{would_match: bool, applied: bool, conditions_passed: bool, planned_actions: array<int, array{type: string, config: array<string, mixed>}>}
	 */
	public static function report( array $rule, array $snapshot ): array {
		$conditions = (array) ( $rule['conditions'] ?? array() );
		$passed     = ConditionMatcher::match( $conditions, $snapshot );

		$planned = array();
		if ( $passed ) {
			$actions = (array) ( $rule['actions'] ?? array() );
			foreach ( ActionPlanner::plan( $actions ) as $action ) {
				$planned[] = array(
					'type'   => $action->type,
					'config' => $action->config,
				);
			}
		}

		return array(
			'would_match'        => $passed,
			'applied'            => false,
			'conditions_passed'  => $passed,
			'planned_actions'    => $planned,
		);
	}
}
