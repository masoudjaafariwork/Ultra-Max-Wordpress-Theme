<?php
/**
 * The Iranian shipping module (SHIP-01, SHIP-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shipping;

use UltraMax\Core\Lib\Settings\Modules;

/**
 * Two WooCommerce shipping methods: Iran Post (express or registered,
 * priced by weight and province) and the store's city courier. Each gets
 * the "delivery from / to working days" fields of the product page's
 * delivery estimate. Off, the methods are not offered and nothing of this
 * module runs. Tracking codes of sent parcels (SHIP-03) belong to the
 * account module.
 */
final class Shipping {

	public const MODULE = 'iran-shipping';

	/**
	 * Whether the module is on and WooCommerce is active.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE ) && function_exists( 'WC' );
	}

	/**
	 * Registers the methods.
	 */
	public static function register(): void {
		if ( ! self::enabled() ) {
			return;
		}
		add_filter( 'woocommerce_shipping_methods', array( self::class, 'methods' ) );
		add_filter( 'woocommerce_shipping_methods', array( \UltraMax\Core\Product\Delivery::class, 'method_fields' ), 999 );
	}

	/**
	 * Adds our methods to WooCommerce's list.
	 *
	 * @param mixed $methods Method classes by id.
	 * @return mixed
	 */
	public static function methods( mixed $methods ): mixed {
		if ( is_array( $methods ) ) {
			$methods[ PostMethod::ID ]    = PostMethod::class;
			$methods[ CourierMethod::ID ] = CourierMethod::class;
		}
		return $methods;
	}
}
