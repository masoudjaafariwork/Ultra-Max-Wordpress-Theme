<?php
/**
 * The thank you page in our design (BLD-06, CHK-09).
 *
 * Included by WooCommerce's checkout shortcode in place of
 * checkout/thankyou.php, only after WooCommerce has checked that the
 * visitor may see this order.
 *
 * @package UltraMax\Core
 *
 * @var WC_Order $order The order, from WooCommerce.
 */

declare(strict_types=1);

use UltraMax\Core\Checkout\ThankYou;

defined( 'ABSPATH' ) || exit;

if ( isset( $order ) && $order instanceof WC_Order ) {
	/** This action is documented in WooCommerce's checkout/thankyou.php template. */
	do_action( 'woocommerce_before_thankyou', $order->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
	ThankYou::page( $order )->print();
}
