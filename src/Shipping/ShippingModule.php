<?php

declare(strict_types=1);

namespace CommerceFlow\Shipping;

use CommerceFlow\Container\Container;
use CommerceFlow\Module\ModuleInterface;
use WC_Shipping_Rate;

/**
 * Shipping module — injects rule-based rates into WooCommerce packages.
 *
 * Enabled rules are resolved highest-priority-first against a package
 * snapshot; the winning rule's rate is added as a shipping option. Rate
 * resolution is delegated to the pure ShippingRateResolver so the live path
 * and the preview tool agree by construction (FR-SHIP-2, FR-SHIP-3).
 */
class ShippingModule implements ModuleInterface {

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
		add_filter( 'woocommerce_package_rates', array( $this, 'apply_rates' ), 20, 2 );
	}

	/**
	 * Boot.
	 */
	public function boot(): void {
		// No-op.
	}

	/**
	 * Add the winning rule's rate to a package's rates.
	 *
	 * @param  array<string, WC_Shipping_Rate> $rates   Existing package rates.
	 * @param  array<string, mixed>            $package Shipping package.
	 * @return array<string, WC_Shipping_Rate>
	 */
	public function apply_rates( array $rates, array $package ): array {
		$rules    = ( new ShippingRuleRepository() )->find_enabled();
		$snapshot = self::build_snapshot( $package );

		$winner = ShippingRateResolver::resolve(
			array_map(
				static function ( ShippingRule $rule ): array {
					return $rule->to_array();
				},
				$rules
			),
			$snapshot
		);

		if ( null === $winner ) {
			return $rates;
		}

		$id           = 'commerceflow:' . $winner['rule_id'];
		$rates[ $id ] = new WC_Shipping_Rate( $id, $winner['label'], $winner['cost'] );

		return $rates;
	}

	/**
	 * Build a package snapshot for rule matching.
	 *
	 * @param  array<string, mixed> $package Shipping package.
	 * @return array<string, mixed>
	 */
	public static function build_snapshot( array $package ): array {
		$destination = (array) ( $package['destination'] ?? array() );
		$contents    = (array) ( $package['contents'] ?? array() );

		$weight     = 0.0;
		$subtotal   = 0.0;
		$categories = array();
		$classes    = array();

		foreach ( $contents as $item ) {
			$quantity  = (int) ( $item['quantity'] ?? 0 );
			$subtotal += (float) ( $item['line_total'] ?? 0 );

			$product = $item['data'] ?? null;
			if ( ! is_object( $product ) ) {
				continue;
			}

			if ( method_exists( $product, 'get_weight' ) ) {
				$weight += (float) $product->get_weight() * $quantity;
			}
			if ( method_exists( $product, 'get_shipping_class' ) ) {
				$class = (string) $product->get_shipping_class();
				if ( '' !== $class ) {
					$classes[] = $class;
				}
			}
			if ( method_exists( $product, 'get_id' ) ) {
				foreach ( wc_get_product_cat_ids( (int) $product->get_id() ) as $term_id ) {
					$term = get_term( $term_id );
					if ( $term && ! is_wp_error( $term ) ) {
						$categories[] = $term->slug;
					}
				}
			}
		}

		return array(
			'country'        => (string) ( $destination['country'] ?? '' ),
			'state'          => (string) ( $destination['state'] ?? '' ),
			'postcode'       => (string) ( $destination['postcode'] ?? '' ),
			'weight'         => $weight,
			'subtotal'       => $subtotal,
			'category'       => array_values( array_unique( $categories ) ),
			'shipping_class' => array_values( array_unique( $classes ) ),
			'coupon'         => self::applied_coupons(),
		);
	}

	/**
	 * Applied cart coupon codes, if a cart is available.
	 *
	 * @return array<int, string>
	 */
	private static function applied_coupons(): array {
		if ( function_exists( 'WC' ) && WC()->cart ) {
			return array_map( 'strval', WC()->cart->get_applied_coupons() );
		}
		return array();
	}
}
