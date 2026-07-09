<?php

declare(strict_types=1);

namespace CommerceFlow\Automation;

use WC_Order;

/**
 * Executes PlannedActions against a WooCommerce order through the CRUD layer.
 *
 * Returns a pure result array so ExecutionPolicy can decide continue/stop.
 */
class ActionExecutor {

	/**
	 * Execute a single planned action against an order.
	 *
	 * @param  PlannedAction $action Action to execute.
	 * @param  WC_Order      $order  Target order.
	 * @return array{success: bool, message: string}
	 */
	public function execute( PlannedAction $action, WC_Order $order ): array {
		switch ( $action->type ) {
			case 'change_status':
				return $this->change_status( $action, $order );
			case 'add_order_note':
				return $this->add_order_note( $action, $order );
			case 'generate_coupon':
				return $this->generate_coupon( $action, $order );
			case 'call_webhook':
				return $this->call_webhook( $action, $order );
			default:
				return array(
					'success' => false,
					'message' => sprintf( 'Unknown action type "%s".', $action->type ),
				);
		}
	}

	/**
	 * Change the order status.
	 *
	 * @param  PlannedAction $action Action.
	 * @param  WC_Order      $order  Order.
	 * @return array{success: bool, message: string}
	 */
	private function change_status( PlannedAction $action, WC_Order $order ): array {
		$status = (string) ( $action->config['status'] ?? '' );
		if ( '' === $status ) {
			return array(
				'success' => false,
				'message' => 'Missing status in change_status config.',
			);
		}

		$order->update_status( $status, __( 'CommerceFlow automation rule.', 'commerceflow' ) );

		return array(
			'success' => true,
			'message' => sprintf( 'Order status set to %s.', $status ),
		);
	}

	/**
	 * Add a private order note.
	 *
	 * @param  PlannedAction $action Action.
	 * @param  WC_Order      $order  Order.
	 * @return array{success: bool, message: string}
	 */
	private function add_order_note( PlannedAction $action, WC_Order $order ): array {
		$note = (string) ( $action->config['note'] ?? '' );
		if ( '' === $note ) {
			return array(
				'success' => false,
				'message' => 'Missing note in add_order_note config.',
			);
		}

		$order->add_order_note( $note );

		return array(
			'success' => true,
			'message' => 'Order note added.',
		);
	}

	/**
	 * Generate a WooCommerce coupon.
	 *
	 * @param  PlannedAction $action Action.
	 * @param  WC_Order      $order  Order.
	 * @return array{success: bool, message: string}
	 */
	private function generate_coupon( PlannedAction $action, WC_Order $order ): array {
		$code   = (string) ( $action->config['code'] ?? '' );
		$amount = (float) ( $action->config['amount'] ?? 0 );
		$type   = (string) ( $action->config['type'] ?? 'percent' );

		if ( '' === $code ) {
			return array(
				'success' => false,
				'message' => 'Missing coupon code.',
			);
		}

		$coupon = new \WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_amount( $amount );
		$coupon->set_discount_type( 'fixed_cart' === $type ? 'fixed_cart' : 'percent' );

		try {
			$coupon->save();
		} catch ( \Throwable $e ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Coupon save failed: %s', $e->getMessage() ),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf( 'Coupon %s created.', $code ),
		);
	}

	/**
	 * Call an external webhook.
	 *
	 * @param  PlannedAction $action Action.
	 * @param  WC_Order      $order  Order.
	 * @return array{success: bool, message: string}
	 */
	private function call_webhook( PlannedAction $action, WC_Order $order ): array {
		$url = (string) ( $action->config['url'] ?? '' );
		if ( '' === $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid webhook URL.',
			);
		}

		$body = wp_json_encode(
			array(
				'order_id' => $order->get_id(),
				'status'   => $order->get_status(),
				'total'    => $order->get_total(),
			)
		);

		$response = wp_safe_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $body,
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Webhook failed: %s', $response->get_error_message() ),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Webhook returned HTTP %d.', $code ),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf( 'Webhook delivered (HTTP %d).', $code ),
		);
	}
}
