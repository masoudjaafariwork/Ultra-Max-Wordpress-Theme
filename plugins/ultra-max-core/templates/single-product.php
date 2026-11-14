<?php
/**
 * A single product (PDP-01 to PDP-26).
 *
 * WooCommerce's own hooks around the product stay, so the theme's frame,
 * notices, view counting and other plugins work as with WooCommerce's
 * template. A password protected product shows only the password form.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Product\Page;

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

/** This action is documented in WooCommerce's single-product.php template. */
do_action( 'woocommerce_before_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

while ( have_posts() ) {
	the_post();

	/** This action is documented in WooCommerce's content-single-product.php template. */
	do_action( 'woocommerce_before_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

	if ( post_password_required() ) {
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress's own form.
		continue;
	}

	printf( '<div id="product-%d" ', (int) get_the_ID() );
	// WooCommerce's product classes, which other plugins style and look for.
	wc_product_class( 'um-product', (int) get_the_ID() );
	echo '>';
	Page::page()->print();
	echo '</div>';

	/** This action is documented in WooCommerce's content-single-product.php template. */
	do_action( 'woocommerce_after_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
}

/** This action is documented in WooCommerce's single-product.php template. */
do_action( 'woocommerce_after_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

get_footer( 'shop' );
