<?php

declare(strict_types=1);

namespace CommerceFlow\Workflow;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;
use WC_Order;

/**
 * Order Workflow module (v0.3).
 *
 * Registers custom HPOS-stored fulfillment statuses and records every status
 * transition to the order timeline with its actor and timestamp (FR-FLOW-1,
 * FR-FLOW-2). Transition guarding lives in {@see TransitionGuard}; it is
 * enforced by the /orders REST endpoint before any status is written.
 */
class WorkflowModule implements ModuleInterface {

	/**
	 * DI container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_statuses' ) );
		add_filter( 'wc_order_statuses', array( $this, 'add_status_labels' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'record_transition' ), 20, 4 );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}

	/**
	 * Register each custom status as a post status (WooCommerce reads these
	 * through the CRUD layer under HPOS).
	 */
	public function register_statuses(): void {
		foreach ( OrderStatus::labels() as $slug => $label ) {
			register_post_status(
				'wc-' . $slug,
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
				)
			);
		}
	}

	/**
	 * Add custom status labels to the WooCommerce status list.
	 *
	 * @param  array<string, string> $statuses Existing statuses (wc-prefixed keys).
	 * @return array<string, string>
	 */
	public function add_status_labels( array $statuses ): array {
		foreach ( OrderStatus::labels() as $slug => $label ) {
			$statuses[ 'wc-' . $slug ] = _x( $label, 'Order status', 'commerceflow' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
		return $statuses;
	}

	/**
	 * Record a status transition to the order timeline (FR-FLOW-2).
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from     Previous status (no wc- prefix).
	 * @param string   $to       New status (no wc- prefix).
	 * @param WC_Order $order    Order object.
	 */
	public function record_transition( int $order_id, string $from, string $to, WC_Order $order ): void {
		( new OrderEventRepository() )->insert(
			array(
				'order_id'    => $order_id,
				'type'        => 'status_change',
				'from_status' => $from,
				'to_status'   => $to,
				'actor'       => self::current_actor(),
			)
		);
	}

	/**
	 * Resolve the actor responsible for the current change.
	 *
	 * @return string
	 */
	public static function current_actor(): string {
		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return 'automation';
		}

		$user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
		if ( $user && $user->exists() ) {
			return $user->display_name;
		}

		return 'system';
	}
}
