<?php

declare(strict_types=1);

namespace CommerceFlow\Workflow;

/**
 * Persists per-order workflow events to commerceflow_order_events (v0.3).
 *
 * Feeds the order timeline (FR-FLOW-2): every status transition is recorded
 * with its actor and timestamp.
 */
class OrderEventRepository {

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
		$this->table = $wpdb->prefix . 'commerceflow_order_events';
	}

	/**
	 * Insert an event and return its ID.
	 *
	 * @param  array<string, mixed> $data Event data.
	 * @return int
	 */
	public function insert( array $data ): int {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->table,
			array(
				'order_id'    => (int) ( $data['order_id'] ?? 0 ),
				'type'        => (string) ( $data['type'] ?? 'status_change' ),
				'from_status' => (string) ( $data['from_status'] ?? '' ),
				'to_status'   => (string) ( $data['to_status'] ?? '' ),
				'actor'       => (string) ( $data['actor'] ?? '' ),
				'note'        => (string) ( $data['note'] ?? '' ),
				'created_at'  => current_time( 'mysql' ),
			)
		);

		return $inserted ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Find events for an order, newest first.
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
	 * Find recent events across all orders, newest first.
	 *
	 * @param  int $limit Maximum rows.
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
	 * Hydrate a raw DB row.
	 *
	 * @param  array<string, mixed> $row Database row.
	 * @return array<string, mixed>
	 */
	private function hydrate( array $row ): array {
		return array(
			'id'          => (int) $row['id'],
			'order_id'    => (int) $row['order_id'],
			'type'        => (string) $row['type'],
			'from_status' => (string) ( $row['from_status'] ?? '' ),
			'to_status'   => (string) ( $row['to_status'] ?? '' ),
			'actor'       => (string) ( $row['actor'] ?? '' ),
			'note'        => (string) ( $row['note'] ?? '' ),
			'created_at'  => (string) ( $row['created_at'] ?? '' ),
		);
	}
}
