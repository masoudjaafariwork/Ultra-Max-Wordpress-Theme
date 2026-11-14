<?php
/**
 * Test fixture: what a purchase needs (CART, CHK, SHIP tests). Pay on
 * delivery and bank transfer; the store in Tehran; Iran Post and the city
 * courier in the "UM Iran" zone of product-extras.php; a 10% discount
 * code. The same data the demo store gets from tools/seed/seed.php.
 *
 * Run with `wp eval-file` after product-extras.php; with the argument
 * "remove" it deletes what it added.
 *
 * @package UltraMax\Tests
 */

// phpcs:ignoreFile -- Test fixture run by WP-CLI.

$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $data ) {
	if ( 'UM Iran' === $data['zone_name'] ) {
		$zone = WC_Shipping_Zones::get_zone( (int) $data['id'] );
	}
}
if ( $zone instanceof WC_Shipping_Zone ) {
	foreach ( $zone->get_shipping_methods() as $instance => $method ) {
		if ( in_array( $method->id, array( 'um_post', 'um_courier' ), true ) ) {
			$zone->delete_shipping_method( $instance );
		}
	}
}
$coupon_id = wc_get_coupon_id_by_code( 'um-test10' );
if ( $coupon_id ) {
	wp_delete_post( $coupon_id, true );
}
if ( in_array( 'remove', $args ?? array(), true ) ) {
	WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );
	WP_CLI::success( 'Removed.' );
	return;
}

update_option( 'woocommerce_default_country', 'IR:THR' );
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'IR' ) );
update_option( 'woocommerce_ship_to_countries', '' );
update_option( 'woocommerce_ship_to_destination', 'billing_only' );
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_calc_taxes', 'no' );
update_option( 'woocommerce_enable_coupons', 'yes' );
update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'      => 'yes',
		'title'        => 'پرداخت در محل',
		'description'  => 'با کارت بانکی هنگام تحویل بپردازید.',
		'instructions' => 'هزینه سفارش را هنگام تحویل با کارت بانکی بپردازید.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	)
);
update_option(
	'woocommerce_bacs_settings',
	array(
		'enabled'      => 'yes',
		'title'        => 'کارت به کارت',
		'description'  => 'مبلغ را به حساب فروشگاه واریز کنید.',
		'instructions' => 'مبلغ سفارش را به شماره حساب زیر واریز کنید و شماره سفارش را در توضیح بنویسید.',
	)
);

if ( $zone instanceof WC_Shipping_Zone ) {
	foreach ( array(
		array( 'um_post', array( 'title' => 'پست پیشتاز ایران', 'service' => 'pishtaz', 'rates' => "1 = 70000 | 95000\n3 = 110000 | 150000", 'extra' => '30000', 'um_days_min' => '2', 'um_days_max' => '4' ) ),
		array( 'um_courier', array( 'title' => 'پیک تهران', 'province' => 'THR', 'cities' => "تهران = 85000\nشهریار", 'cost' => '120000', 'um_days_min' => '0', 'um_days_max' => '1' ) ),
	) as list( $type, $options ) ) {
		$instance = $zone->add_shipping_method( $type );
		update_option( 'woocommerce_' . $type . '_' . $instance . '_settings', $options );
	}
}
WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );

// Tests buy these; keep enough in stock for every run.
foreach ( array( 'UM-003', 'UM-013', 'UM-015' ) as $sku ) {
	$stocked = wc_get_product( wc_get_product_id_by_sku( $sku ) );
	if ( $stocked ) {
		$stocked->set_stock_quantity( 50 );
		$stocked->save();
	}
}

$coupon = new WC_Coupon();
$coupon->set_code( 'um-test10' );
$coupon->set_discount_type( 'percent' );
$coupon->set_amount( 10 );
$coupon->save();

WP_CLI::success( 'Payment, shipping methods and coupon added.' );
