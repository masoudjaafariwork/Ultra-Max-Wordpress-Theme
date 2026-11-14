<?php
/**
 * The checkout form in our design (CHK-01 to CHK-09, BLD-06).
 *
 * Included by WooCommerce's checkout shortcode in place of
 * checkout/form-checkout.php. WooCommerce's hooks before and after the
 * form stay (notices, the login reminder, other plugins).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Checkout\Page;

defined( 'ABSPATH' ) || exit;

$ultra_max_checkout = WC()->checkout();

echo '<div class="um-checkout-page grid min-w-0 gap-4">';

/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
do_action( 'woocommerce_before_checkout_form', $ultra_max_checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

if ( ! $ultra_max_checkout->is_registration_enabled() && $ultra_max_checkout->is_registration_required() && ! is_user_logged_in() ) {
	/** This filter is documented in WooCommerce's checkout/form-checkout.php template. */
	echo esc_html( (string) apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to check out.', 'ultra-max-core' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
	echo '</div>';
	return;
}

Page::form()->print();

/** This action is documented in WooCommerce's checkout/form-checkout.php template. */
do_action( 'woocommerce_after_checkout_form', $ultra_max_checkout ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

echo '</div>';
