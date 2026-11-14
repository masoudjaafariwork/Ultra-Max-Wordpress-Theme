<?php
/**
 * The smallest and largest order (CART-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Cart;

use UltraMax\Core\Lib\Settings\Store;

/**
 * The store sets the least and the most a cart may be worth, before
 * shipping. Outside those limits the cart says so clearly and how far it
 * is, and neither checkout lets the order through: the classic one
 * through WooCommerce's cart check, the block one through the Store API's
 * cart errors.
 */
final class Limits {

	/**
	 * Hooks the checks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_check_cart_items', array( self::class, 'check' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate' ), 10, 2 );
		add_action( 'woocommerce_store_api_cart_errors', array( self::class, 'store_api' ), 10, 2 );
	}

	/**
	 * Why an amount breaks the limits, or an empty text.
	 *
	 * @param float $total Cart total before shipping.
	 * @param float $min   Least, 0 for none.
	 * @param float $max   Most, 0 for none.
	 */
	public static function problem_for( float $total, float $min, float $max ): string {
		if ( $min > 0 && $total < $min ) {
			return sprintf(
				/* translators: 1: least order amount, 2: amount still missing. */
				__( 'The smallest order is %1$s. Add %2$s more to place it.', 'ultra-max-core' ),
				Totals::money( $min ),
				Totals::money( $min - $total )
			);
		}
		if ( $max > 0 && $total > $max ) {
			return sprintf(
				/* translators: 1: largest order amount, 2: amount over it. */
				__( 'The largest order is %1$s. Remove %2$s to place it.', 'ultra-max-core' ),
				Totals::money( $max ),
				Totals::money( $total - $max )
			);
		}
		return '';
	}

	/**
	 * Why the cart breaks the limits, or an empty text.
	 *
	 * @param \WC_Cart|null $cart Cart; WooCommerce's when null.
	 */
	public static function problem( ?\WC_Cart $cart = null ): string {
		$cart = $cart ?? ( function_exists( 'WC' ) ? WC()->cart : null );
		if ( ! $cart instanceof \WC_Cart || $cart->is_empty() ) {
			return '';
		}
		$amounts = Totals::of( $cart );
		return self::problem_for( $amounts['total'], (float) Store::int( 'cart_min' ), (float) Store::int( 'cart_max' ) );
	}

	/**
	 * Adds the problem to the cart's notices; the classic checkout then
	 * refuses the order.
	 */
	public static function check(): void {
		// Our cart page shows the limit in its summary already.
		if ( function_exists( 'is_cart' ) && is_cart() && Cart::ours() ) {
			return;
		}
		$problem = self::problem();
		if ( '' !== $problem && ! wc_has_notice( $problem, 'error' ) ) {
			wc_add_notice( $problem, 'error' );
		}
	}

	/**
	 * Refuses a classic checkout outside the limits.
	 *
	 * @param mixed $data   Posted data.
	 * @param mixed $errors Errors.
	 */
	public static function validate( mixed $data, mixed $errors ): void {
		$problem = self::problem();
		if ( '' !== $problem && $errors instanceof \WP_Error ) {
			$errors->add( 'ultra_max_order_limit', $problem );
		}
	}

	/**
	 * Refuses a block checkout outside the limits.
	 *
	 * @param mixed $errors Errors.
	 * @param mixed $cart   Cart.
	 */
	public static function store_api( mixed $errors, mixed $cart ): void {
		$problem = $cart instanceof \WC_Cart ? self::problem( $cart ) : '';
		if ( '' !== $problem && $errors instanceof \WP_Error ) {
			$errors->add( 'ultra_max_order_limit', $problem );
		}
	}
}
