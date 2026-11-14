<?php
/**
 * Demo shopper for the customer account (ACC-01 to ACC-09).
 *
 * Run by seed.php after the products exist, and by the account tests. Makes
 * the shopper "sara" (password "password") with a mobile number, a
 * national code and a birthday, orders in every group (current, sent with a
 * tracking code, delivered, returned and cancelled), a review and a
 * question. Every name and number is made up. Running it again changes
 * nothing that is already there.
 *
 * @package UltraMax\Seed
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- A WP-CLI script, not loaded by WordPress.

if ( ! function_exists( 'wc_create_order' ) ) {
	return;
}

$um_user = get_user_by( 'login', 'sara' );
if ( ! $um_user instanceof WP_User ) {
	$um_id   = wp_insert_user(
		array(
			'user_login'   => 'sara',
			'user_pass'    => 'password',
			'user_email'   => 'sara@example.com',
			'first_name'   => 'سارا',
			'last_name'    => 'محمدی',
			'display_name' => 'سارا محمدی',
			'role'         => 'customer',
		)
	);
	$um_user = is_int( $um_id ) ? get_user_by( 'id', $um_id ) : false;
}
if ( ! $um_user instanceof WP_User ) {
	return;
}
$um_uid     = $um_user->ID;
$um_address = array(
	'first_name' => 'سارا',
	'last_name'  => 'محمدی',
	'phone'      => '09121234567',
	'email'      => 'sara@example.com',
	'country'    => 'IR',
	'state'      => 'THR',
	'city'       => 'تهران',
	'address_1'  => 'خیابان نمونه، کوچه یکم، پلاک ۱۲',
	'postcode'   => '1234567890',
);
foreach ( $um_address as $um_key => $um_value ) {
	update_user_meta( $um_uid, 'billing_' . $um_key, $um_value );
	if ( ! in_array( $um_key, array( 'phone', 'email' ), true ) ) {
		update_user_meta( $um_uid, 'shipping_' . $um_key, $um_value );
	}
}
update_user_meta( $um_uid, 'um_national_code', '0013542419' );
update_user_meta( $um_uid, 'um_birthday', '1991-04-22' );

// Orders, once: each carries a note naming it, so a second run finds it.
$um_products = array_values(
	array_filter(
		array_map( 'wc_get_product', array( wc_get_product_id_by_sku( 'UM-001' ), wc_get_product_id_by_sku( 'UM-003' ), wc_get_product_id_by_sku( 'UM-005' ), wc_get_product_id_by_sku( 'UM-010' ) ) ),
		static fn ( $product ): bool => $product instanceof WC_Product && $product->is_type( 'simple' )
	)
);
if ( array() === $um_products ) {
	$um_products = array_values(
		array_filter(
			wc_get_products(
				array(
					'type'   => 'simple',
					'limit'  => 4,
					'status' => 'publish',
				)
			),
			static fn ( $product ): bool => $product instanceof WC_Product
		)
	);
}
$um_orders = array(
	'sent'      => array( 'processing', array( 0, 1 ), 3 ),
	'preparing' => array( 'processing', array( 2 ), 1 ),
	'awaiting'  => array( 'pending', array( 3 ), 0 ),
	'delivered' => array( 'completed', array( 0, 2, 3 ), 12 ),
	'returned'  => array( 'refunded', array( 1 ), 20 ),
	'cancelled' => array( 'cancelled', array( 3 ), 25 ),
);
foreach ( $um_orders as $um_name => list( $um_status, $um_items, $um_days ) ) {
	$um_found = wc_get_orders(
		array(
			'customer_id' => $um_uid,
			'meta_key'    => '_um_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Demo data, run by hand.
			'meta_value'  => $um_name, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Demo data, run by hand.
			'limit'       => 1,
			'status'      => 'any',
		)
	);
	if ( array() !== $um_found ) {
		continue;
	}
	$um_order = wc_create_order( array( 'customer_id' => $um_uid ) );
	if ( ! $um_order instanceof WC_Order ) {
		continue;
	}
	foreach ( $um_items as $um_index ) {
		if ( isset( $um_products[ $um_index ] ) ) {
			$um_order->add_product( $um_products[ $um_index ], 1 + ( $um_index % 2 ) );
		}
	}
	$um_order->set_address( $um_address, 'billing' );
	$um_order->set_address( $um_address, 'shipping' );
	$um_order->set_payment_method( 'cod' );
	$um_order->set_payment_method_title( 'پرداخت در محل' );
	$um_order->update_meta_data( '_um_demo', $um_name );
	$um_order->calculate_totals();
	$um_when = time() - $um_days * DAY_IN_SECONDS;
	$um_order->set_date_created( $um_when );
	if ( in_array( $um_status, array( 'processing', 'completed', 'refunded' ), true ) ) {
		$um_order->set_date_paid( $um_when + HOUR_IN_SECONDS );
	}
	if ( 'completed' === $um_status ) {
		$um_order->set_date_completed( $um_when + 4 * DAY_IN_SECONDS );
	}
	$um_order->set_status( $um_status );
	$um_order->save();
	if ( 'sent' === $um_name ) {
		$um_order->update_meta_data( '_um_tracking_carrier', 'پست' );
		$um_order->update_meta_data( '_um_tracking_code', '248910007342165501820' );
		$um_order->update_meta_data( '_um_tracking_date', $um_when + 2 * DAY_IN_SECONDS );
		$um_order->save();
		$um_order->add_order_note( 'مرسوله شما به پست تحویل شد.', 1 );
	}
}

// A review and a question by the shopper: the old ones go first, so running again makes no copies.
foreach ( get_comments(
	array(
		'user_id'   => $um_uid,
		'post_type' => 'product',
		'type'      => array( 'review', 'um_question' ),
		'status'    => 'all',
		'fields'    => 'ids',
	)
) as $um_old ) {
	wp_delete_comment( (int) $um_old, true );
}
if ( isset( $um_products[0] ) ) {
	$um_review = wp_insert_comment(
		array(
			'comment_post_ID'  => $um_products[0]->get_id(),
			'comment_author'   => 'سارا محمدی',
			'comment_content'  => 'کیفیت ساخت خوبی دارد و به موقع رسید.',
			'comment_type'     => 'review',
			'user_id'          => $um_uid,
			'comment_approved' => 1,
		)
	);
	if ( is_int( $um_review ) && $um_review > 0 ) {
		update_comment_meta( $um_review, 'rating', 4 );
		WC_Comments::clear_transients( $um_products[0]->get_id() );
	}
	wp_insert_comment(
		array(
			'comment_post_ID'  => $um_products[0]->get_id(),
			'comment_author'   => 'سارا محمدی',
			'comment_content'  => 'آیا این کالا گارانتی دارد؟',
			'comment_type'     => 'um_question',
			'user_id'          => $um_uid,
			'comment_approved' => 0,
		)
	);
}

// Two products in the shopper's wishlist.
$um_wish = array_map( static fn ( WC_Product $product ): int => $product->get_id(), array_slice( $um_products, 1, 2 ) );
if ( array() !== $um_wish ) {
	update_user_meta( $um_uid, '_um_wishlist', $um_wish );
}

WP_CLI::log( 'Demo shopper: sara / password.' );
