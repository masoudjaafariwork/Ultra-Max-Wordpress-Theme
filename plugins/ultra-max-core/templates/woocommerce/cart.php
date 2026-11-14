<?php
/**
 * The cart, full or empty, in our design (CART-01 to CART-05, BLD-06).
 *
 * Included by WooCommerce's cart shortcode in place of cart/cart.php and
 * cart/cart-empty.php. WooCommerce's hooks around the cart stay, so its
 * notices and other plugins work as with its own template.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Cart\Page;

defined( 'ABSPATH' ) || exit;

if ( WC()->cart->is_empty() ) {
	/** This action is documented in WooCommerce's cart/cart-empty.php template. */
	do_action( 'woocommerce_cart_is_empty' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
} else {
	/** This action is documented in WooCommerce's cart/cart.php template. */
	do_action( 'woocommerce_before_cart' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
}

Page::page()->print();

if ( ! WC()->cart->is_empty() ) {
	/** This action is documented in WooCommerce's cart/cart.php template. */
	do_action( 'woocommerce_after_cart' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
}
