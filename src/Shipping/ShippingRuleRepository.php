<?php

declare(strict_types=1);

namespace CommerceFlow\Shipping;

/**
 * Shipping-rule repository — CRUD over commerceflow_shipping_rules via $wpdb.
 *
 * JSON-encodes conditions/rate columns and hydrates ShippingRule objects.
 */
class ShippingRuleRepository {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'commerceflow_shipping_rules';
	}

	/**
	 * Insert a new rule and return its ID.
	 *
	 * @param  array<string, mixed> $data Sanitized rule data (from ShippingRuleValidator).
	 * @return int Inserted rule ID, 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$now      = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			$this->table,
			array(
				'name'       => $data['name'],
				'conditions' => wp_json_encode( $data['conditions'] ?? array() ),
				'rate'       => wp_json_encode( $data['rate'] ?? array() ),
				'enabled'    => ! empty( $data['enabled'] ) ? 1 : 0,
				'priority'   => (int) ( $data['priority'] ?? 0 ),
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update an existing rule.
	 *
	 * @param  int                  $id   Rule ID.
	 * @param  array<string, mixed> $data Sanitized rule data.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$this->table,
			array(
				'name'       => $data['name'],
				'conditions' => wp_json_encode( $data['conditions'] ?? array() ),
				'rate'       => wp_json_encode( $data['rate'] ?? array() ),
				'enabled'    => ! empty( $data['enabled'] ) ? 1 : 0,
				'priority'   => (int) ( $data['priority'] ?? 0 ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
	}

	/**
	 * Delete a rule.
	 *
	 * @param  int $id Rule ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Find a single rule by ID.
	 *
	 * @param  int $id Rule ID.
	 * @return ShippingRule|null
	 */
	public function find( int $id ): ?ShippingRule {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return $this->hydrate( $row );
	}

	/**
	 * Find all rules, priority-ordered.
	 *
	 * @return array<int, ShippingRule>
	 */
	public function find_all(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT * FROM {$this->table} ORDER BY priority DESC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array_map( array( $this, 'hydrate' ), $rows ? $rows : array() );
	}

	/**
	 * Find enabled rules, priority-ordered (for live rate resolution).
	 *
	 * @return array<int, ShippingRule>
	 */
	public function find_enabled(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT * FROM {$this->table} WHERE enabled = 1 ORDER BY priority DESC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array_map( array( $this, 'hydrate' ), $rows ? $rows : array() );
	}

	/**
	 * Hydrate a raw DB row into a ShippingRule object.
	 *
	 * @param  array<string, mixed> $row Database row.
	 * @return ShippingRule
	 */
	private function hydrate( array $row ): ShippingRule {
		return ShippingRule::from_array(
			array(
				'id'         => (int) $row['id'],
				'name'       => (string) $row['name'],
				'conditions' => json_decode( (string) ( $row['conditions'] ?? '[]' ), true ) ?? array(),
				'rate'       => json_decode( (string) ( $row['rate'] ?? '[]' ), true ) ?? array(),
				'enabled'    => (bool) (int) $row['enabled'],
				'priority'   => (int) $row['priority'],
				'created_at' => (string) ( $row['created_at'] ?? '' ),
				'updated_at' => (string) ( $row['updated_at'] ?? '' ),
			)
		);
	}
}
