<?php
/**
 * Test fixture: products that no visitor may see (phase 4 exit criterion).
 * A private, a draft, a password protected and a hidden product, each in
 * the speaker category with the Novix brand, all named "leaktest".
 *
 * Run with `wp eval-file`; with the argument "remove" it deletes them.
 *
 * @package UltraMax\Tests
 */

// phpcs:ignoreFile -- Test fixture run by WP-CLI.

$existing = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_key'       => '_um_leaktest',
		'fields'         => 'ids',
	)
);
foreach ( $existing as $id ) {
	$old = wc_get_product( (int) $id );
	if ( $old ) {
		$old->delete( true );
	}
}
if ( in_array( 'remove', $args ?? array(), true ) ) {
	WP_CLI::success( 'Removed.' );
	return;
}

$category = get_term_by( 'name', 'اسپیکر', 'product_cat' );
$brand    = taxonomy_exists( 'product_brand' ) ? get_term_by( 'name', 'نوویکس', 'product_brand' ) : false;
foreach ( array(
	array( 'private', '', 'visible' ),
	array( 'draft', '', 'visible' ),
	array( 'publish', 'secret', 'visible' ),
	array( 'publish', '', 'hidden' ),
) as list( $status, $password, $visibility ) ) {
	$product = new WC_Product_Simple();
	$product->set_name( 'محصول leaktest ' . $status . ' ' . $visibility . ( '' !== $password ? ' locked' : '' ) );
	$product->set_status( $status );
	$product->set_regular_price( '999' );
	$product->set_sku( 'LEAK-' . $status . '-' . $visibility . ( '' !== $password ? '-locked' : '' ) );
	$product->set_catalog_visibility( $visibility );
	$product->set_post_password( $password );
	$product->update_meta_data( '_um_leaktest', 1 );
	if ( $category instanceof WP_Term ) {
		$product->set_category_ids( array( $category->term_id ) );
	}
	$id = $product->save();
	if ( $brand instanceof WP_Term ) {
		wp_set_object_terms( $id, array( $brand->term_id ), 'product_brand' );
	}
}
\UltraMax\Core\Store\Catalog::flush();
WP_CLI::success( 'Hidden products made.' );
