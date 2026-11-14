<?php
/**
 * Product lists: the shop, product categories, tags, brands, attributes
 * and the product search (PLP-01 to PLP-13).
 *
 * WooCommerce's own hooks around the content stay, so the theme's frame
 * and other plugins work as with WooCommerce's template.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Shop\Archive;

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/** This action is documented in WooCommerce's archive-product.php template. */
do_action( 'woocommerce_before_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

if ( function_exists( 'woocommerce_output_all_notices' ) ) {
	woocommerce_output_all_notices();
}

Archive::page()->print();

/** This action is documented in WooCommerce's archive-product.php template. */
do_action( 'woocommerce_after_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

get_footer( 'shop' );
