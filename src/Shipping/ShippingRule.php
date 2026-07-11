<?php

declare(strict_types=1);

namespace CommerceFlow\Shipping;

/**
 * Shipping rule DTO — condition(s) → rate.
 *
 * Plain data carrier with typed properties and array round-tripping so it
 * can be serialized for REST and hydrated from $wpdb rows.
 */
class ShippingRule {

	public int $id;

	public string $name;

	/** @var array<int, array<string, mixed>> */
	public array $conditions;

	/** @var array{label: string, cost: float} */
	public array $rate;

	public bool $enabled;

	public int $priority;

	public string $created_at;

	public string $updated_at;

	/**
	 * @param array<string, mixed> $data
	 */
	public static function from_array( array $data ): ShippingRule {
		$rule             = new self();
		$rule->id         = (int) ( $data['id'] ?? 0 );
		$rule->name       = (string) ( $data['name'] ?? '' );
		$rule->conditions = (array) ( $data['conditions'] ?? array() );
		$rate             = (array) ( $data['rate'] ?? array() );
		$rule->rate       = array(
			'label' => (string) ( $rate['label'] ?? '' ),
			'cost'  => (float) ( $rate['cost'] ?? 0 ),
		);
		$rule->enabled    = (bool) ( $data['enabled'] ?? false );
		$rule->priority   = (int) ( $data['priority'] ?? 0 );
		$rule->created_at = (string) ( $data['created_at'] ?? '' );
		$rule->updated_at = (string) ( $data['updated_at'] ?? '' );
		return $rule;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'         => $this->id,
			'name'       => $this->name,
			'conditions' => $this->conditions,
			'rate'       => $this->rate,
			'enabled'    => $this->enabled,
			'priority'   => $this->priority,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		);
	}
}
