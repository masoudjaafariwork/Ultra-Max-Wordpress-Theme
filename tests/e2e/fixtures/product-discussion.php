<?php
/**
 * Test fixture: reviews and questions on the demo phone (SKU UM-001), for
 * the product page tests (PDP-07 to PDP-10). Each carries `_um_fixture`.
 *
 * Run with `wp eval-file`; with the argument "remove" it deletes them.
 *
 * @package UltraMax\Tests
 */

// phpcs:ignoreFile -- Test fixture run by WP-CLI.

$product_id = wc_get_product_id_by_sku( 'UM-001' );
foreach ( get_comments( array( 'meta_key' => '_um_fixture', 'status' => 'all' ) ) as $old ) {
	wp_delete_comment( (int) $old->comment_ID, true );
}
if ( in_array( 'remove', $args ?? array(), true ) || ! $product_id ) {
	\UltraMax\Core\Product\Reviews::flush( $product_id );
	WC_Comments::clear_transients( $product_id );
	WP_CLI::success( 'Removed.' );
	return;
}

$reviews = array(
	array( 'سارا م.', 5, true, 'باتری فوق‌العاده و صفحه روشن', 'دو هفته است استفاده می‌کنم. با استفاده معمولی دو روز شارژ نگه می‌دارد.', array( 'باتری قوی', 'صفحه‌نمایش روشن' ), array( 'شارژر داخل جعبه نیست' ), 'yes', 24, '-3 days' ),
	array( 'علی ر.', 3, true, 'نسبت به قیمت خوب است', 'کار روزمره را بدون مشکل انجام می‌دهد، ولی در بازی‌های سنگین کمی داغ می‌شود.', array( 'قیمت مناسب' ), array( 'گرم شدن در بازی' ), 'unsure', 9, '-6 days' ),
	array( 'مریم ک.', 4, false, 'دوربین خوب', 'عکس‌های روز عالی است و شب هم قابل قبول.', array( 'دوربین' ), array(), 'yes', 3, '-9 days' ),
	array( 'رضا ت.', 2, false, 'انتظار بیشتری داشتم', 'صدای بلندگو کم است.', array(), array( 'بلندگو' ), 'no', 1, '-12 days' ),
	array( 'نگار س.', 5, true, 'عالی', 'از هر نظر راضی هستم و به دوستانم هم پیشنهاد کردم.', array( 'سرعت', 'طراحی' ), array(), 'yes', 7, '-15 days' ),
	array( 'حسین الف.', 4, true, 'خوب و سریع', 'سریع است و تا الان مشکلی نداشته.', array( 'سرعت' ), array(), 'yes', 0, '-18 days' ),
);
foreach ( $reviews as list( $author, $rating, $buyer, $title, $text, $pros, $cons, $recommend, $up, $when ) ) {
	$id = wp_insert_comment(
		array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $author,
			'comment_author_email' => 'fixture@example.com',
			'comment_content'      => $text,
			'comment_type'         => 'review',
			'comment_approved'     => 1,
			'comment_date'         => wp_date( 'Y-m-d H:i:s', strtotime( $when ) ),
			'comment_date_gmt'     => gmdate( 'Y-m-d H:i:s', strtotime( $when ) ),
		)
	);
	update_comment_meta( $id, '_um_fixture', 1 );
	update_comment_meta( $id, 'rating', $rating );
	update_comment_meta( $id, 'verified', $buyer ? 1 : 0 );
	update_comment_meta( $id, 'um_title', $title );
	if ( $pros ) {
		update_comment_meta( $id, 'um_pros', $pros );
	}
	if ( $cons ) {
		update_comment_meta( $id, 'um_cons', $cons );
	}
	update_comment_meta( $id, 'um_recommend', $recommend );
	update_comment_meta( $id, 'um_criteria', array( 'Build quality' => $rating, 'Value for money' => max( 1, $rating - 1 ) ) );
	update_comment_meta( $id, 'um_up', $up );
	update_comment_meta( $id, 'um_down', 0 );
}

$question = wp_insert_comment(
	array(
		'comment_post_ID'      => $product_id,
		'comment_author'       => 'کاوه',
		'comment_author_email' => 'fixture@example.com',
		'comment_content'      => 'آیا از کارت حافظه پشتیبانی می‌کند؟',
		'comment_type'         => 'um_question',
		'comment_approved'     => 1,
	)
);
update_comment_meta( $question, '_um_fixture', 1 );
$answer = wp_insert_comment(
	array(
		'comment_post_ID'  => $product_id,
		'comment_parent'   => $question,
		'user_id'          => 1,
		'comment_author'   => 'admin',
		'comment_content'  => 'بله، تا یک ترابایت.',
		'comment_type'     => 'um_answer',
		'comment_approved' => 1,
	)
);
update_comment_meta( $answer, '_um_fixture', 1 );

WC_Comments::clear_transients( $product_id );
\UltraMax\Core\Product\Reviews::flush( $product_id );
WP_CLI::success( 'Reviews and a question added to product ' . $product_id . '.' );
