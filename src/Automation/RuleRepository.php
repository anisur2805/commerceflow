<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Rule repository — CRUD over the commerceflow_rules table via $wpdb.
 *
 * JSON-encodes config/conditions/actions columns and hydrates Rule objects.
 */
class RuleRepository {

	/**
	 * Table name (without prefix resolution at construct time).
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'commerceflow_rules';
	}

	/**
	 * Insert a new rule and return its ID.
	 *
	 * @param  array<string, mixed> $data Sanitized rule data (from RuleValidator).
	 * @return int Inserted rule ID, 0 on failure.
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$now      = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			$this->table,
			array(
				'name'           => $data['name'],
				'trigger'        => $data['trigger'],
				'trigger_config' => wp_json_encode( $data['trigger_config'] ?? array() ),
				'conditions'     => wp_json_encode( $data['conditions'] ?? array() ),
				'actions'        => wp_json_encode( $data['actions'] ?? array() ),
				'enabled'        => ! empty( $data['enabled'] ) ? 1 : 0,
				'priority'       => (int) ( $data['priority'] ?? 0 ),
				'created_at'     => $now,
				'updated_at'     => $now,
			)
		);

		if ( ! $inserted && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( 'CommerceFlow RuleRepository insert failed: ' . $wpdb->last_error, E_USER_WARNING ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
		}

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Update an existing rule.
	 *
	 * @param  int                   $id   Rule ID.
	 * @param  array<string, mixed>  $data Sanitized rule data.
	 * @return bool
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$this->table,
			array(
				'name'           => $data['name'],
				'trigger'        => $data['trigger'],
				'trigger_config' => wp_json_encode( $data['trigger_config'] ?? array() ),
				'conditions'     => wp_json_encode( $data['conditions'] ?? array() ),
				'actions'        => wp_json_encode( $data['actions'] ?? array() ),
				'enabled'        => ! empty( $data['enabled'] ) ? 1 : 0,
				'priority'       => (int) ( $data['priority'] ?? 0 ),
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
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
	 * @return Rule|null
	 */
	public function find( int $id ): ?Rule {
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
	 * Find all rules.
	 *
	 * @return array<int, Rule>
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
	 * Find enabled rules matching a trigger.
	 *
	 * @param  string $trigger Trigger name.
	 * @return array<int, Rule>
	 */
	public function find_enabled_by_trigger( string $trigger ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE `trigger` = %s AND enabled = 1 ORDER BY priority DESC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$trigger
			),
			ARRAY_A
		);

		return array_map( array( $this, 'hydrate' ), $rows ? $rows : array() );
	}

	/**
	 * Hydrate a raw DB row into a Rule object.
	 *
	 * @param  array<string, mixed> $row Database row.
	 * @return Rule
	 */
	private function hydrate( array $row ): Rule {
		return Rule::from_array(
			array(
				'id'             => (int) $row['id'],
				'name'           => (string) $row['name'],
				'trigger'        => (string) $row['trigger'],
				'trigger_config' => json_decode( (string) ( $row['trigger_config'] ?? '[]' ), true ) ?? array(),
				'conditions'     => json_decode( (string) ( $row['conditions'] ?? '[]' ), true ) ?? array(),
				'actions'        => json_decode( (string) ( $row['actions'] ?? '[]' ), true ) ?? array(),
				'enabled'        => (bool) (int) $row['enabled'],
				'priority'       => (int) $row['priority'],
				'created_at'     => (string) ( $row['created_at'] ?? '' ),
				'updated_at'     => (string) ( $row['updated_at'] ?? '' ),
			)
		);
	}
}
