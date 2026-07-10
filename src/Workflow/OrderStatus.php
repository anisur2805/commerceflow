<?php

declare(strict_types=1);

namespace CommerceFlow\Workflow;

/**
 * Custom fulfillment order statuses (v0.3).
 *
 * Pure definition layer — no WordPress dependency, so it is unit-testable.
 * Slugs are HPOS-compatible: WooCommerce stores them in the order status
 * column and reads them through the CRUD layer, never postmeta.
 */
class OrderStatus {

	/**
	 * Status slug: order is being packed.
	 */
	public const PACKING = 'cf-packing';

	/**
	 * Status slug: order is packed and ready to ship.
	 */
	public const READY = 'cf-ready';

	/**
	 * Status slug: order has been shipped.
	 */
	public const SHIPPED = 'cf-shipped';

	/**
	 * Custom statuses this plugin registers, slug => human label.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::PACKING => 'Packing',
			self::READY   => 'Ready to Ship',
			self::SHIPPED => 'Shipped',
		);
	}

	/**
	 * List of custom status slugs.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array_keys( self::labels() );
	}

	/**
	 * Whether a slug is one of the plugin's custom fulfillment statuses.
	 *
	 * @param string $slug Status slug (with or without the wc-/cf- prefix normalised by caller).
	 */
	public static function is_custom( string $slug ): bool {
		return in_array( $slug, self::all(), true );
	}
}
