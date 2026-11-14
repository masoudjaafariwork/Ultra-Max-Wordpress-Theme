<?php
/**
 * The cart and checkout pages in our design (CART-01, CHK-01).
 *
 * WooCommerce's own cart and checkout run here, whatever blocks the page
 * holds: they handle the forms, the notices, the order pay and order
 * received addresses and their checks. Only their inner templates are ours.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/** This action is documented in WooCommerce's archive-product.php template. */
do_action( 'woocommerce_before_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

echo do_shortcode( is_cart() ? '[woocommerce_cart]' : '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's own output.

/** This action is documented in WooCommerce's archive-product.php template. */
do_action( 'woocommerce_after_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

get_footer( 'shop' );
