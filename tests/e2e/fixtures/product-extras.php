<?php
/**
 * Test fixture: delivery days, free shipping, a bundle, a size guide and a
 * store tab, for the product page tests (PDP-13, PDP-14, PDP-18, PDP-19,
 * PDP-22). The same data the demo store gets from tools/seed/seed.php.
 *
 * Run with `wp eval-file`; with the argument "remove" it deletes them.
 *
 * @package UltraMax\Tests
 */

// phpcs:ignoreFile -- Test fixture run by WP-CLI.

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Product\Meta;
use UltraMax\Core\Product\SizeGuide;

$phone      = wc_get_product_id_by_sku( 'UM-001' );
$headphones = wc_get_product_id_by_sku( 'UM-003' );
$speaker    = wc_get_product_id_by_sku( 'UM-013' );
$sneaker    = wc_get_product_id_by_sku( 'UM-005' );

foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
	if ( 'UM Iran' === $zone['zone_name'] ) {
		WC_Shipping_Zones::delete_zone( (int) $zone['id'] );
	}
}
foreach ( get_posts( array( 'post_type' => SizeGuide::TYPE, 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => '_um_fixture' ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}
$shoes = get_term_by( 'name', 'کفش', 'product_cat' );
if ( $shoes instanceof WP_Term ) {
	delete_term_meta( $shoes->term_id, Meta::GUIDE );
}
if ( $phone ) {
	$product = wc_get_product( $phone );
	$product->update_meta_data( Meta::BUNDLE, '' );
	$product->update_meta_data( Meta::DISCOUNT, '0' );
	$product->save();
}
if ( in_array( 'remove', $args ?? array(), true ) ) {
	WP_CLI::success( 'Removed.' );
	return;
}

// A zone for all of Iran: courier 1 to 2 days, post 3 to 5 days, free from 5,000,000.
$zone = new WC_Shipping_Zone();
$zone->set_zone_name( 'UM Iran' );
$zone->add_location( 'IR', 'country' );
$zone->save();
foreach ( array(
	array( 'flat_rate', 'پیک فروشگاه', 1, 2, array( 'cost' => '90000' ) ),
	array( 'flat_rate', 'پست پیشتاز', 3, 5, array( 'cost' => '60000' ) ),
	array( 'free_shipping', 'ارسال رایگان', 3, 5, array( 'requires' => 'min_amount', 'min_amount' => '5000000' ) ),
) as list( $type, $title, $min, $max, $extra ) ) {
	$instance = $zone->add_shipping_method( $type );
	$options  = array_merge( array( 'title' => $title, 'um_days_min' => (string) $min, 'um_days_max' => (string) $max ), $extra );
	update_option( 'woocommerce_' . $type . '_' . $instance . '_settings', $options );
}
WC_Cache_Helper::invalidate_cache_group( 'shipping_zones' );

// The phone is bought with the headphones and the speaker, 10% off together.
if ( $phone && $headphones && $speaker ) {
	$product = wc_get_product( $phone );
	$product->update_meta_data( Meta::BUNDLE, $headphones . ',' . $speaker );
	$product->update_meta_data( Meta::DISCOUNT, '10' );
	$product->save();
}

// Shoes have a size guide.
$guide = wp_insert_post(
	array(
		'post_type'    => SizeGuide::TYPE,
		'post_status'  => 'publish',
		'post_title'   => 'راهنمای سایز کفش',
		'post_content' => '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>سایز</th><th>طول کف پا (سانتی‌متر)</th></tr></thead><tbody><tr><td>۴۰</td><td>۲۵٫۵</td></tr><tr><td>۴۱</td><td>۲۶</td></tr><tr><td>۴۲</td><td>۲۷</td></tr><tr><td>۴۳</td><td>۲۷٫۵</td></tr></tbody></table></figure><!-- /wp:table -->',
	)
);
update_post_meta( $guide, '_um_fixture', 1 );
if ( $shoes instanceof WP_Term ) {
	update_term_meta( $shoes->term_id, Meta::GUIDE, $guide );
}

// A store tab for every product.
Store::save( array( 'product_tabs' => array( array( 'title' => 'ارسال و بازگشت', 'content' => 'سفارش‌ها در روزهای کاری ارسال می‌شوند. تا هفت روز می‌توانید کالای سالم را بازگردانید.', 'categories' => '' ) ) ) );

WP_CLI::success( 'Shipping, bundle, size guide and tab added.' );
