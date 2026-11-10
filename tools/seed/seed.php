<?php
/**
 * Fills the development site with a small store: categories, brands,
 * products, posts, menus, header and footer layouts and a home page made
 * from the ready-made designs. Development only; never shipped.
 *
 * Run with `npm run seed`. Safe to run again: earlier seed content is
 * removed first (everything it makes carries the `_um_seed` meta).
 *
 * All names are invented; images are the product drawings made for this
 * project (design/ASSETS.fa.md), rasterised by scripts/seed.mjs.
 *
 * @package UltraMax\Tools
 */

// phpcs:ignoreFile -- Development script run by WP-CLI (eval-file allows no strict_types).

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Library;
use UltraMax\Core\Builder\LibraryStrings;
use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Store\Categories;
use UltraMax\Core\Store\Menus;

if ( ! function_exists( 'wc_get_product' ) || ! class_exists( Layouts::class ) ) {
	WP_CLI::error( 'WooCommerce and Ultra Max Core must be active.' );
}

$images_dir = WP_CONTENT_DIR . '/ultra-max-dev/.cache/seed';

/* ------------------------------------------------------------------ clean */

$old = get_posts(
	array(
		'post_type'      => array( 'product', 'post', 'page', 'attachment', Layouts::POST_TYPE, 'nav_menu_item' ),
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_um_seed',
		'fields'         => 'ids',
	)
);
// Every layout goes, including ones made by hand or by tests, so the seeded ones always win.
$old = array_merge( $old, get_posts( array( 'post_type' => Layouts::POST_TYPE, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) ) );
foreach ( array_unique( $old ) as $id ) {
	'attachment' === get_post_type( $id ) ? wp_delete_attachment( (int) $id, true ) : wp_delete_post( (int) $id, true );
}
foreach ( array( 'product_cat', 'product_brand', 'category' ) as $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		continue;
	}
	foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'meta_key' => '_um_seed' ) ) as $term ) {
		wp_delete_term( $term->term_id, $taxonomy );
	}
}
$menu = wp_get_nav_menu_object( 'UM Seed Main' );
if ( $menu ) {
	wp_delete_nav_menu( $menu->term_id );
}
WP_CLI::log( 'Removed ' . count( $old ) . ' earlier seed items.' );

/* ------------------------------------------------------------------ images */

$attachments = array();
/**
 * Imports one rasterised drawing into the media library.
 *
 * @param string $name Drawing name.
 */
$image = static function ( string $name ) use ( $images_dir, &$attachments ): int {
	if ( isset( $attachments[ $name ] ) ) {
		return $attachments[ $name ];
	}
	$source = $images_dir . '/' . $name . '.png';
	if ( ! is_readable( $source ) ) {
		return 0;
	}
	$upload = wp_upload_bits( 'um-' . $name . '.png', null, (string) file_get_contents( $source ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $name,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	update_post_meta( $id, '_um_seed', 1 );
	return $attachments[ $name ] = (int) $id;
};

/* ------------------------------------------------------------------ terms */

$term = static function ( string $name, string $taxonomy, int $parent = 0, string $icon = '' ) use ( $image ): int {
	$found = term_exists( $name, $taxonomy, $parent );
	$id    = is_array( $found ) ? (int) $found['term_id'] : 0;
	if ( 0 === $id ) {
		$made = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent ) );
		$id   = is_wp_error( $made ) ? 0 : (int) $made['term_id'];
	}
	if ( $id > 0 ) {
		update_term_meta( $id, '_um_seed', 1 );
		if ( '' !== $icon ) {
			update_term_meta( $id, 'thumbnail_id', $image( $icon ) );
		}
	}
	return $id;
};

$tree = array(
	array( 'موبایل و تبلت', 'phone', array( 'گوشی موبایل' => array( 'گوشی اندرویدی', 'گوشی ساده' ), 'تبلت' => array(), 'قاب و محافظ' => array(), 'شارژر و کابل' => array() ) ),
	array( 'کالای دیجیتال', 'laptop', array( 'لپ‌تاپ' => array( 'لپ‌تاپ کاری', 'لپ‌تاپ گیمینگ' ), 'هدفون و هندزفری' => array(), 'ساعت هوشمند' => array(), 'اسپیکر' => array(), 'دوربین' => array() ) ),
	array( 'خانه و آشپزخانه', 'kettle', array( 'لوازم برقی کوچک' => array( 'کتری برقی', 'قهوه‌ساز' ), 'ظروف پخت' => array(), 'دکوراسیون' => array() ) ),
	array( 'مد و پوشاک', 'tshirt', array( 'لباس زنانه' => array(), 'لباس مردانه' => array(), 'کفش' => array( 'کفش ورزشی', 'کفش رسمی' ), 'کیف و کوله' => array() ) ),
	array( 'آرایشی و بهداشتی', 'cream', array( 'مراقبت پوست' => array(), 'عطر و ادکلن' => array(), 'آرایش صورت' => array() ) ),
	array( 'سوپرمارکت', 'juice', array( 'نوشیدنی' => array( 'آبمیوه', 'قهوه و چای' ), 'لبنیات' => array(), 'تنقلات' => array() ) ),
	array( 'ورزش و سفر', 'backpack', array( 'پوشاک ورزشی' => array(), 'تجهیزات کمپینگ' => array(), 'لوازم سفر' => array() ) ),
	array( 'کتاب و لوازم تحریر', 'coffee', array( 'کتاب چاپی' => array(), 'لوازم تحریر' => array() ) ),
);
$cat = array();
foreach ( $tree as $order => list( $name, $icon, $children ) ) {
	$top          = $term( $name, 'product_cat', 0, $icon );
	$cat[ $name ] = $top;
	update_term_meta( $top, 'order', $order );
	foreach ( $children as $child => $leaves ) {
		$cat[ $child ] = $term( $child, 'product_cat', $top );
		foreach ( $leaves as $leaf ) {
			$cat[ $leaf ] = $term( $leaf, 'product_cat', $cat[ $child ] );
		}
	}
}

$brands = array();
foreach ( array( 'نوویکس', 'زنترا', 'لومو', 'اوربیس', 'کاویرا', 'سپینو', 'تیرنگ' ) as $brand ) {
	$brands[ $brand ] = taxonomy_exists( 'product_brand' ) ? $term( $brand, 'product_brand' ) : 0;
}

/* ------------------------------------------------------------------ products */

$products = array(
	array( 'گوشی موبایل نوویکس مدل X12 ظرفیت ۲۵۶ گیگابایت رم ۸ گیگابایت', 'phone', 'گوشی اندرویدی', 'نوویکس', 21900000, 18490000, 2, 680, true ),
	array( 'لپ‌تاپ ۱۵.۶ اینچی زنترا مدل ProBook Z5', 'laptop', 'لپ‌تاپ کاری', 'زنترا', 59000000, 54900000, 12, 410, false ),
	array( 'هدفون بی‌سیم لومو مدل Air Pro با حذف نویز فعال', 'headphones', 'هدفون و هندزفری', 'لومو', 4600000, 3250000, 30, 840, true ),
	array( 'ساعت هوشمند اوربیس مدل Fit 4 با نمایشگر آمولد', 'watch', 'ساعت هوشمند', 'اوربیس', 6780000, 0, 25, 120, false ),
	array( 'کفش پیاده‌روی مردانه کاویرا مدل Trail', 'sneaker', 'کفش ورزشی', 'کاویرا', 2890000, 2190000, 40, 550, false ),
	array( 'تی‌شرت نخی آستین کوتاه زنانه سپینو', 'tshirt', 'لباس زنانه', 'سپینو', 690000, 489000, 60, 720, false ),
	array( 'ادو پرفیوم زنانه تیرنگ مدل Velvet حجم ۱۰۰ میلی‌لیتر', 'perfume', 'عطر و ادکلن', 'تیرنگ', 3950000, 0, 15, 96, false ),
	array( 'کرم آبرسان صورت لومو مدل Hydra حجم ۵۰ میلی‌لیتر', 'cream', 'مراقبت پوست', 'لومو', 820000, 685000, 80, 610, false ),
	array( 'قهوه اسپرسو آسیاب‌شده سپینو ۲۵۰ گرمی', 'coffee', 'قهوه و چای', 'سپینو', 475000, 412000, 120, 770, false ),
	array( 'کتری برقی زنترا مدل K20 ظرفیت ۱.۷ لیتر', 'kettle', 'کتری برقی', 'زنترا', 3100000, 2640000, 3, 490, false ),
	array( 'کوله‌پشتی لپ‌تاپ کاویرا مدل Urban ضد آب', 'backpack', 'کیف و کوله', 'کاویرا', 1480000, 0, 22, 154, false ),
	array( 'دوربین عکاسی بدون آینه اوربیس مدل M50 با لنز ۱۵-۴۵', 'camera', 'دوربین', 'اوربیس', 66000000, 62500000, 6, 230, true ),
	array( 'اسپیکر بلوتوثی قابل حمل نوویکس مدل Boom Mini', 'speaker', 'اسپیکر', 'نوویکس', 2450000, 1890000, 18, 810, false ),
	array( 'آبمیوه طبیعی پرتقال سپینو ۱ لیتری', 'juice', 'آبمیوه', 'سپینو', 115000, 98000, 200, 900, false ),
	array( 'هندزفری بلوتوثی لومو مدل Buds S', 'headphones', 'هدفون و هندزفری', 'لومو', 1890000, 1490000, 35, 330, false ),
	array( 'ساعت هوشمند کودک اوربیس مدل Kids', 'watch', 'ساعت هوشمند', 'اوربیس', 2400000, 1990000, 14, 210, false ),
	array( 'قهوه‌ساز خانگی زنترا مدل Brew', 'coffee', 'قهوه‌ساز', 'زنترا', 5400000, 4590000, 9, 140, false ),
	array( 'کفش روزمره زنانه کاویرا مدل City', 'sneaker', 'کفش ورزشی', 'کاویرا', 2390000, 0, 28, 260, false ),
);
$units = array( 'coffee' => '۲۵۰ گرم', 'juice' => '۱ لیتر', 'cream' => '۵۰ میلی‌لیتر' );
$end   = ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '+2 days' )->setTime( 23, 59 );
foreach ( $products as $index => list( $title, $picture, $category, $brand, $regular, $sale, $stock, $sales, $featured ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( $title );
	$product->set_status( 'publish' );
	$product->set_regular_price( (string) $regular );
	if ( $sale > 0 ) {
		$product->set_sale_price( (string) $sale );
		if ( 0 === $index % 3 ) {
			$product->set_date_on_sale_to( $end->getTimestamp() + $index * 3600 );
		}
	}
	$product->set_manage_stock( true );
	$product->set_stock_quantity( $stock );
	$product->set_featured( $featured );
	$product->set_category_ids( array_filter( array( $cat[ $category ] ?? 0 ) ) );
	$product->set_image_id( $image( $picture ) );
	$product->set_gallery_image_ids( array_filter( array( $image( $products[ ( $index + 4 ) % count( $products ) ][1] ) ) ) );
	$product->set_short_description( 'محصولی ساختگی برای نمایش طرح فروشگاه.' );
	$product->set_description( '<p>این محصول ساختگی است و فقط برای نمایش ظاهر فروشگاه ساخته شده است.</p>' );
	$product->set_average_rating( (string) ( 4 + ( $index % 9 ) / 10 ) );
	$product->set_rating_counts( array( 5 => 10 ) );
	$product->set_review_count( 10 + $index );
	$product->set_total_sales( $sales );
	$product->update_meta_data( '_um_seed', 1 );
	$product->update_meta_data( Catalog::VIEWS_META, 1000 - $index * 37 );
	if ( isset( $units[ $picture ] ) ) {
		$product->update_meta_data( 'um_unit', $units[ $picture ] );
	}
	$id = $product->save();
	if ( $brands[ $brand ] ?? 0 ) {
		wp_set_object_terms( $id, array( $brands[ $brand ] ), 'product_brand' );
	}
}
foreach ( $brands as $brand_id ) {
	if ( $brand_id ) {
		wp_update_term_count_now( array( $brand_id ), 'product_brand' );
	}
}
wc_delete_product_transients();
Catalog::flush();
Categories::flush();
WP_CLI::log( count( $products ) . ' products.' );

/* ------------------------------------------------------------------ posts */

$blog = $term( 'راهنمای خرید', 'category' );
foreach ( array(
	array( 'راهنمای خرید گوشی: پنج نکته پیش از انتخاب', 'phone', '-2 days' ),
	array( 'روتین ساده مراقبت پوست در پاییز', 'cream', '-4 days' ),
	array( 'کدام قهوه برای دم‌آوری خانگی مناسب است؟', 'coffee', '-7 days' ),
	array( 'عکاسی با دوربین بدون آینه برای شروع', 'camera', '-10 days' ),
) as list( $title, $picture, $when ) ) {
	$post = wp_insert_post(
		array(
			'post_title'    => $title,
			'post_status'   => 'publish',
			'post_content'  => str_repeat( '<p>این نوشته نمونه است و برای نمایش بخش خواندنی‌های صفحه اصلی ساخته شده است. متن واقعی را صاحب فروشگاه می‌نویسد.</p>', 8 ),
			'post_date'     => wp_date( 'Y-m-d H:i:s', strtotime( $when ) ),
			'post_category' => array( $blog ),
		)
	);
	set_post_thumbnail( $post, $image( $picture ) );
	update_post_meta( $post, '_um_seed', 1 );
}

/* ------------------------------------------------------------------ pages and menu */

$page = static function ( string $title, string $content = '' ): int {
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_content' => $content,
		)
	);
	update_post_meta( $id, '_um_seed', 1 );
	return (int) $id;
};
$offers = $page( 'پیشنهادهای شگفت‌انگیز', '[um_products title="همه پیشنهادها" source="sale" count="24" layout="grid" design="deal" columns="5"]' );

$menu_id = wp_create_nav_menu( 'UM Seed Main' );
$add     = static function ( array $args ) use ( $menu_id ): int {
	$item = wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-status' => 'publish' ), $args ) );
	update_post_meta( $item, '_um_seed', 1 );
	return (int) $item;
};
$deals = $add( array( 'menu-item-title' => 'شگفت‌انگیزها', 'menu-item-url' => get_permalink( $offers ), 'menu-item-type' => 'custom' ) );
update_post_meta( $deals, Menus::ICON, 'fire' );
$market = $add( array( 'menu-item-title' => 'سوپرمارکت', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat['سوپرمارکت'], 'menu-item-type' => 'taxonomy' ) );
update_post_meta( $market, Menus::ICON, 'store' );
$discounts = $add( array( 'menu-item-title' => 'تخفیف‌ها و پیشنهادها', 'menu-item-url' => get_permalink( $offers ), 'menu-item-type' => 'custom' ) );
update_post_meta( $discounts, Menus::ICON, 'percent' );
$best = $add( array( 'menu-item-title' => 'پرفروش‌ترین‌ها', 'menu-item-url' => home_url( '/shop/?orderby=popularity' ), 'menu-item-type' => 'custom' ) );
update_post_meta( $best, Menus::ICON, 'star' );
update_post_meta( $best, Menus::BADGE, 'hot' );
$new = $add( array( 'menu-item-title' => 'تازه‌ها', 'menu-item-url' => home_url( '/shop/?orderby=date' ), 'menu-item-type' => 'custom' ) );
update_post_meta( $new, Menus::ICON, 'sparkles' );
update_post_meta( $new, Menus::BADGE, 'new' );
$digital = $add( array( 'menu-item-title' => 'کالای دیجیتال', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat['کالای دیجیتال'], 'menu-item-type' => 'taxonomy' ) );
foreach ( array( 'لپ‌تاپ', 'هدفون و هندزفری', 'ساعت هوشمند', 'دوربین' ) as $child ) {
	$add( array( 'menu-item-title' => $child, 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $cat[ $child ], 'menu-item-type' => 'taxonomy', 'menu-item-parent-id' => $digital ) );
}
$locations            = get_theme_mod( 'nav_menu_locations', array() );
$locations['primary'] = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );

/* ------------------------------------------------------------------ layouts and home page */

$layout = static function ( string $template, array $conditions = array() ): int {
	$found = Library::get( $template );
	if ( null === $found ) {
		WP_CLI::warning( "Template $template is missing." );
		return 0;
	}
	$type     = $found['kind'];
	$defaults = Layouts::defaults( $type );
	$id       = Layouts::create( $type, $found['title'], LibraryStrings::translate( $found['content'] ), array_merge( $defaults, array( 'devices' => $found['devices'] ), $conditions ) );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_um_seed', 1 );
	return $id;
};
$layout( 'header/desktop-1' );
$layout( 'header/mobile-1' );
$layout( 'footer/store-1' );
Layouts::rebuild();

$home = $page( 'خانه' );
$found = Library::get( 'page/home-store' );
if ( null !== $found ) {
	Data::import( $home, LibraryStrings::translate( $found['content'] ) );
	update_post_meta( $home, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $home, '_elementor_template_type', 'wp-page' );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home );

if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::instance()->files_manager->clear_cache();
}
WP_CLI::success( 'Seeded. Home page: ' . get_permalink( $home ) );
