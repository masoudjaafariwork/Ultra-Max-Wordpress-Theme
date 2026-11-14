<?php
/**
 * The checkout when the cart cannot be ordered as it is (CART-10, CHK-01).
 *
 * Included by WooCommerce's checkout shortcode in place of
 * checkout/cart-errors.php: each reason the cart gave, such as the order
 * limit or a product out of stock, and the way back to the cart.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Checkout\Page;

defined( 'ABSPATH' ) || exit;

Page::cart_errors()->print();

/** This action is documented in WooCommerce's checkout/cart-errors.php template. */
do_action( 'woocommerce_cart_has_errors' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
