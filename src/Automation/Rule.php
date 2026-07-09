<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Automation rule DTO — trigger → condition(s) → action(s).
 *
 * Plain data carrier with typed properties and array round-tripping so it
 * can be serialized for REST and hydrated from $wpdb rows.
 */
class Rule {

	public int $id;

	public string $name;

	public string $trigger;

	/** @var array<string, mixed> */
	public array $trigger_config;

	/** @var array<int, array<string, mixed>> */
	public array $conditions;

	/** @var array<int, array<string, mixed>> */
	public array $actions;

	public bool $enabled;

	public int $priority;

	public string $created_at;

	public string $updated_at;

	/**
	 * @param array<string, mixed> $data
	 */
	public static function from_array( array $data ): Rule {
		$rule                 = new self();
		$rule->id             = (int) ( $data['id'] ?? 0 );
		$rule->name           = (string) ( $data['name'] ?? '' );
		$rule->trigger        = (string) ( $data['trigger'] ?? '' );
		$rule->trigger_config = (array) ( $data['trigger_config'] ?? array() );
		$rule->conditions     = (array) ( $data['conditions'] ?? array() );
		$rule->actions        = (array) ( $data['actions'] ?? array() );
		$rule->enabled        = (bool) ( $data['enabled'] ?? false );
		$rule->priority       = (int) ( $data['priority'] ?? 0 );
		$rule->created_at     = (string) ( $data['created_at'] ?? '' );
		$rule->updated_at     = (string) ( $data['updated_at'] ?? '' );
		return $rule;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'             => $this->id,
			'name'           => $this->name,
			'trigger'        => $this->trigger,
			'trigger_config' => $this->trigger_config,
			'conditions'     => $this->conditions,
			'actions'        => $this->actions,
			'enabled'        => $this->enabled,
			'priority'       => $this->priority,
			'created_at'     => $this->created_at,
			'updated_at'     => $this->updated_at,
		);
	}
}
