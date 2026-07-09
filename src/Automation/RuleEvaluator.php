<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Pure rule matching — returns enabled rules matching a trigger + snapshot,
 * ordered by priority (descending).
 */
class RuleEvaluator {

	/**
	 * Evaluate rules against a fired trigger and order snapshot.
	 *
	 * @param  array<int, Rule>      $rules          All rules (enabled + disabled).
	 * @param  string                $trigger        Fired trigger.
	 * @param  array<string, mixed>  $trigger_config Fired trigger context.
	 * @param  array<string, mixed>  $snapshot       Order snapshot.
	 * @return array<int, Rule> Matching rules, priority-desc.
	 */
	public function evaluate( array $rules, string $trigger, array $trigger_config, array $snapshot ): array {
		$matches = array();

		foreach ( $rules as $rule ) {
			if ( ! $rule->enabled ) {
				continue;
			}

			if ( $rule->trigger !== $trigger ) {
				continue;
			}

			if ( ! $this->trigger_config_matches( $rule->trigger_config, $trigger_config ) ) {
				continue;
			}

			if ( ! ConditionMatcher::match( $rule->conditions, $snapshot ) ) {
				continue;
			}

			$matches[] = $rule;
		}

		usort(
			$matches,
			static function ( Rule $a, Rule $b ): int {
				return $b->priority <=> $a->priority;
			}
		);

		return $matches;
	}

	/**
	 * Check whether the rule's trigger config is satisfied by the fired context.
	 *
	 * An empty rule trigger_config matches any context (wildcard).
	 *
	 * @param  array<string, mixed> $rule_config    Rule's configured trigger config.
	 * @param  array<string, mixed> $fired_config   Fired trigger context.
	 * @return bool
	 */
	private function trigger_config_matches( array $rule_config, array $fired_config ): bool {
		if ( array() === $rule_config ) {
			return true;
		}

		foreach ( $rule_config as $key => $value ) {
			if ( ! isset( $fired_config[ $key ] ) ) {
				return false;
			}
			if ( (string) $fired_config[ $key ] !== (string) $value ) {
				return false;
			}
		}

		return true;
	}
}
