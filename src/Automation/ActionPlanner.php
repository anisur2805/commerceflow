<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Normalizes rule actions into PlannedAction objects.
 *
 * Pure — no WordPress dependency.
 */
class ActionPlanner {

	/**
	 * Plan a list of raw action specs into PlannedAction objects.
	 *
	 * @param  array<int, array<string, mixed>> $actions Raw action specs.
	 * @return array<int, PlannedAction>
	 */
	public static function plan( array $actions ): array {
		$planned = array();
		foreach ( $actions as $action ) {
			$planned[] = new PlannedAction(
				(string) ( $action['type'] ?? '' ),
				(array) ( $action['config'] ?? array() )
			);
		}
		return $planned;
	}
}
