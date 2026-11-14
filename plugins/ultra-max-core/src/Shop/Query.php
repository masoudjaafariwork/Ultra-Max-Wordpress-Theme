<?php
/**
 * Applies the chosen filters and sort order to product lists (PLP-01, PLP-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Store\Catalog;

/**
 * WooCommerce already filters by attribute, price and rating from its own
 * parameters. This adds brand, "only available" and "only discounted",
 * makes several values of one attribute mean "any of them" as on
 * Digikala, adds the "most viewed" order and sets products per page.
 *
 * Everything runs on WooCommerce's main product query only, so widgets
 * and other lists on the page are not touched.
 */
final class Query {

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_product_query', array( self::class, 'apply' ) );
		add_filter( 'loop_shop_post_in', array( self::class, 'on_sale' ) );
		add_filter( 'loop_shop_per_page', array( self::class, 'per_page' ), 20 );
		add_filter( 'woocommerce_layered_nav_default_query_type', static fn (): string => 'or' );
		add_filter( 'woocommerce_catalog_orderby', array( self::class, 'orderby_options' ) );
		add_filter( 'woocommerce_default_catalog_orderby_options', array( self::class, 'orderby_options' ) );
		add_filter( 'woocommerce_get_catalog_ordering_args', array( self::class, 'ordering' ), 10, 2 );
	}

	/**
	 * Sort orders offered on product lists, in Digikala's order. The empty
	 * key is the store's default order ("most relevant").
	 *
	 * @return array<string, string>
	 */
	public static function sorts(): array {
		return array(
			''           => __( 'Most relevant', 'ultra-max-core' ),
			'views'      => __( 'Most viewed', 'ultra-max-core' ),
			'date'       => __( 'Newest', 'ultra-max-core' ),
			'popularity' => __( 'Best selling', 'ultra-max-core' ),
			'price'      => __( 'Cheapest', 'ultra-max-core' ),
			'price-desc' => __( 'Most expensive', 'ultra-max-core' ),
		);
	}

	/**
	 * Adds the chosen brand, stock and view order to the main query.
	 *
	 * @param mixed $query The product query.
	 */
	public static function apply( mixed $query ): void {
		if ( ! $query instanceof \WP_Query ) {
			return;
		}
		// Password protected products stay out of public lists (phase 4 exit criterion).
		$query->set( 'has_password', false );
		$state = State::current();
		$tax   = $query->get( 'tax_query' );
		$tax   = is_array( $tax ) ? $tax : array();
		if ( isset( $state->terms[ State::BRAND ] ) && '' !== State::taxonomy( State::BRAND ) ) {
			$tax[] = array(
				'taxonomy' => 'product_brand',
				'field'    => 'slug',
				'terms'    => $state->terms[ State::BRAND ],
				'operator' => 'IN',
			);
		}
		if ( $state->in_stock ) {
			$visibility = wc_get_product_visibility_term_ids();
			if ( isset( $visibility['outofstock'] ) ) {
				$tax[] = array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => array( $visibility['outofstock'] ),
					'operator' => 'NOT IN',
				);
			}
		}
		$query->set( 'tax_query', $tax );

		if ( 'views' === self::current_orderby() ) {
			$meta   = $query->get( 'meta_query' );
			$meta   = is_array( $meta ) ? $meta : array();
			$meta[] = array(
				'relation' => 'OR',
				'um_views' => array(
					'key'     => Catalog::VIEWS_META,
					'type'    => 'NUMERIC',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => Catalog::VIEWS_META,
					'compare' => 'NOT EXISTS',
				),
			);
			$query->set( 'meta_query', $meta );
		}
	}

	/**
	 * Limits the list to discounted products.
	 *
	 * @param mixed $ids Product ids the list may show; empty for all.
	 * @return mixed
	 */
	public static function on_sale( mixed $ids ): mixed {
		if ( ! State::current()->on_sale ) {
			return $ids;
		}
		$sale = array_map( 'intval', wc_get_product_ids_on_sale() );
		$ids  = is_array( $ids ) ? array_map( static fn ( mixed $id ): int => is_numeric( $id ) ? (int) $id : 0, $ids ) : array();
		$ids  = array() === $ids ? $sale : array_values( array_intersect( $ids, $sale ) );
		// An empty list would mean "no limit"; an id that cannot exist means "nothing".
		return array() === $ids ? array( 0 ) : $ids;
	}

	/**
	 * Products per page, from the settings.
	 *
	 * @param mixed $count WooCommerce's number.
	 * @return mixed
	 */
	public static function per_page( mixed $count ): mixed {
		$visitor = State::current()->per_page;
		if ( $visitor > 0 ) {
			return $visitor;
		}
		$chosen = Store::int( 'shop_per_page' );
		return $chosen > 0 ? max( 4, min( 96, $chosen ) ) : $count;
	}

	/**
	 * Adds "most viewed" to WooCommerce's sort orders, so its own sorting
	 * menu and settings know it too.
	 *
	 * @param mixed $options Sort orders.
	 * @return mixed
	 */
	public static function orderby_options( mixed $options ): mixed {
		if ( is_array( $options ) && ! isset( $options['views'] ) ) {
			$options['views'] = __( 'Most viewed', 'ultra-max-core' );
		}
		return $options;
	}

	/**
	 * The query arguments of the "most viewed" order. Products never viewed
	 * stay in the list, at its end.
	 *
	 * @param mixed $args    Arguments.
	 * @param mixed $orderby Requested order.
	 * @return mixed
	 */
	public static function ordering( mixed $args, mixed $orderby = '' ): mixed {
		if ( 'views' !== $orderby || ! is_array( $args ) ) {
			return $args;
		}
		$args['orderby'] = array(
			'um_views' => 'DESC',
			'ID'       => 'DESC',
		);
		$args['order']   = 'DESC';
		unset( $args['meta_key'] );
		return $args;
	}

	/**
	 * The sort order of the request, with WooCommerce's default when none
	 * is chosen.
	 */
	public static function current_orderby(): string {
		$chosen = State::current()->orderby;
		if ( '' !== $chosen ) {
			return $chosen;
		}
		$default = is_search() ? 'relevance' : get_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
		return is_string( $default ) ? $default : 'menu_order';
	}
}
