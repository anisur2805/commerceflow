<?php

declare(strict_types=1);

namespace CommerceFlow\Analytics;

use CommerceFlow\Workflow\OrderStatus;
use DateTimeImmutable;

/**
 * WooCommerce-native analytics query layer.
 *
 * All order reads go through the CRUD layer — no direct postmeta queries.
 *
 * @phpstan-type DashboardData array{orders_today: int, revenue_today: float, pending_orders: int, failed_payments: int, revenue_30d: array<int, array{date: string, revenue: float}>, top_products_30d: array<int, array{id: int, name: string, quantity: int, total: float}>, fulfillment: array<int, array{status: string, label: string, count: int}>}
 */
class DashboardQuery {

	/**
	 * Get the full dashboard dataset.
	 *
	 * @return DashboardData
	 */
	public function get_data(): array {
		return array(
			'orders_today'     => $this->get_orders_today(),
			'revenue_today'    => $this->get_revenue_today(),
			'pending_orders'   => $this->get_pending_orders(),
			'failed_payments'  => $this->get_failed_payments(),
			'revenue_30d'      => $this->get_revenue_series_30d(),
			'top_products_30d' => $this->get_top_products_30d(),
			'fulfillment'      => $this->get_fulfillment_counts(),
		);
	}

	/**
	 * Count open orders in each custom fulfillment status (v0.3 card).
	 *
	 * @return array<int, array{status: string, label: string, count: int}>
	 */
	public function get_fulfillment_counts(): array {
		$counts = array();
		foreach ( OrderStatus::labels() as $slug => $label ) {
			$orders   = wc_get_orders(
				array(
					'status' => 'wc-' . $slug,
					'limit'  => -1,
					'return' => 'ids',
				)
			);
			$counts[] = array(
				'status' => $slug,
				'label'  => $label,
				'count'  => count( $orders ),
			);
		}
		return $counts;
	}

	/**
	 * Return a date_created range string for today (start-of-today → start-of-tomorrow).
	 *
	 * WooCommerce "..." range is inclusive on both sides, so querying
	 * [2026-07-09 00:00:00...2026-07-10 00:00:00] includes today's orders
	 * up to but not including midnight tomorrow.
	 *
	 * @return string
	 */
	private function today_range(): string {
		$start = ( new DateTimeImmutable( 'today' ) )->format( 'Y-m-d' ) . ' 00:00:00';
		$end   = ( new DateTimeImmutable( 'tomorrow' ) )->format( 'Y-m-d' ) . ' 00:00:00';
		return $start . '...' . $end;
	}

	/**
	 * Return a date_created range string for the trailing 30 days.
	 *
	 * @return string
	 */
	private function month_range(): string {
		$start = ( new DateTimeImmutable( '-30 days' ) )->format( 'Y-m-d' ) . ' 00:00:00';
		$end   = ( new DateTimeImmutable( 'tomorrow' ) )->format( 'Y-m-d' ) . ' 00:00:00';
		return $start . '...' . $end;
	}

	/**
	 * Count orders placed today.
	 *
	 * @return int
	 */
	public function get_orders_today(): int {
		$orders = wc_get_orders(
			array(
				'date_created' => $this->today_range(),
				'limit'        => -1,
				'return'       => 'ids',
			)
		);

		return count( $orders );
	}

	/**
	 * Sum revenue for orders placed today (completed and processing).
	 *
	 * @return float
	 */
	public function get_revenue_today(): float {
		$orders = wc_get_orders(
			array(
				'date_created' => $this->today_range(),
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'limit'        => -1,
			)
		);

		$total = 0.0;
		foreach ( $orders as $order ) {
			$total += (float) $order->get_total();
		}

		return round( $total, 2 );
	}

	/**
	 * Count pending / on-hold orders.
	 *
	 * @return int
	 */
	public function get_pending_orders(): int {
		$orders = wc_get_orders(
			array(
				'status' => array( 'wc-pending', 'wc-on-hold' ),
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		return count( $orders );
	}

	/**
	 * Count failed / cancelled / refunded orders placed today.
	 *
	 * @return int
	 */
	public function get_failed_payments(): int {
		$orders = wc_get_orders(
			array(
				'date_created' => $this->today_range(),
				'status'       => array( 'wc-failed', 'wc-cancelled', 'wc-refunded' ),
				'limit'        => -1,
				'return'       => 'ids',
			)
		);

		return count( $orders );
	}

	/**
	 * Get daily revenue totals for the last 30 days.
	 *
	 * @return array<int, array{date: string, revenue: float}>
	 */
	public function get_revenue_series_30d(): array {
		$range  = $this->month_range();
		$parts  = explode( '...', $range );
		$period = new \DatePeriod(
			new DateTimeImmutable( $parts[0] ),
			new \DateInterval( 'P1D' ),
			new DateTimeImmutable( $parts[1] )
		);

		$orders = wc_get_orders(
			array(
				'date_created' => $range,
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'limit'        => -1,
			)
		);

		$daily = array();
		foreach ( $orders as $order ) {
			$day = $order->get_date_created()->date( 'Y-m-d' );
			if ( ! isset( $daily[ $day ] ) ) {
				$daily[ $day ] = 0.0;
			}
			$daily[ $day ] += (float) $order->get_total();
		}

		$series = array();
		foreach ( $period as $date ) {
			$key      = $date->format( 'Y-m-d' );
			$series[] = array(
				'date'    => $key,
				'revenue' => round( $daily[ $key ] ?? 0.0, 2 ),
			);
		}

		return $series;
	}

	/**
	 * Get top products by sales volume over the last 30 days.
	 *
	 * @param int $limit Number of products to return.
	 * @return array<int, array{id: int, name: string, quantity: int, total: float}>
	 */
	public function get_top_products_30d( int $limit = 5 ): array {
		$orders = wc_get_orders(
			array(
				'date_created' => $this->month_range(),
				'status'       => array( 'wc-completed', 'wc-processing' ),
				'limit'        => -1,
			)
		);

		$products = array();
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				if ( ! isset( $products[ $product_id ] ) ) {
					$product                 = $item->get_product();
					$products[ $product_id ] = array(
						'id'       => $product_id,
						'name'     => $product ? $product->get_name() : sprintf( '#%d', $product_id ),
						'quantity' => 0,
						'total'    => 0.0,
					);
				}
				$products[ $product_id ]['quantity'] += (int) $item->get_quantity();
				$products[ $product_id ]['total']    += (float) $item->get_total();
			}
		}

		usort(
			$products,
			function ( array $a, array $b ): int {
				return $b['quantity'] <=> $a['quantity'];
			}
		);

		$products = array_slice( $products, 0, $limit );
		foreach ( $products as &$p ) {
			$p['total'] = round( $p['total'], 2 );
		}
		unset( $p );

		return $products;
	}
}
