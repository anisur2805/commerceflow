<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

/**
 * Rule-log repository — persists execution results to commerceflow_rule_logs.
 */
class RuleLogRepository {

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
		$this->table = $wpdb->prefix . 'commerceflow_rule_logs';
	}

	/**
	 * Insert a log entry and return its ID.
	 *
	 * @param  array<string, mixed> $data Log data.
	 * @return int
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table,
			array(
				'rule_id'    => (int) ( $data['rule_id'] ?? 0 ),
				'order_id'   => (int) ( $data['order_id'] ?? 0 ),
				'trigger'    => (string) ( $data['trigger'] ?? '' ),
				'status'     => (string) ( $data['status'] ?? 'unknown' ),
				'run_id'     => (string) ( $data['run_id'] ?? '' ),
				'detail'     => wp_json_encode( $data['detail'] ?? array() ),
				'created_at' => current_time( 'mysql' ),
			)
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Find recent log entries.
	 *
	 * @param  int $limit Maximum number of entries.
	 * @return array<int, array<string, mixed>>
	 */
	public function find_recent( int $limit = 20 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $rows ? array_map( array( $this, 'hydrate' ), $rows ) : array();
	}

	/**
	 * Find log entries for an order.
	 *
	 * @param  int $order_id Order ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function find_by_order( int $order_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table} WHERE order_id = %d ORDER BY id DESC", $order_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $rows ? array_map( array( $this, 'hydrate' ), $rows ) : array();
	}

	/**
	 * Hydrate a raw DB row.
	 *
	 * @param  array<string, mixed> $row Database row.
	 * @return array<string, mixed>
	 */
	private function hydrate( array $row ): array {
		return array(
			'id'         => (int) $row['id'],
			'rule_id'    => (int) $row['rule_id'],
			'order_id'   => (int) $row['order_id'],
			'trigger'    => (string) $row['trigger'],
			'status'     => (string) $row['status'],
			'run_id'     => (string) ( $row['run_id'] ?? '' ),
			'detail'     => json_decode( (string) ( $row['detail'] ?? '[]' ), true ) ?? array(),
			'created_at' => (string) ( $row['created_at'] ?? '' ),
		);
	}
}
