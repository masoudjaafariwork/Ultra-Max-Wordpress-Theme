<?php
/**
 * Product lists for the store widgets (HOME-03, HOME-06, HOME-08, HOME-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

/**
 * One function returns products from any source a widget offers: newest,
 * best selling, on sale, most viewed, featured, top rated, a category or a
 * hand-picked list. Results are cached for a few minutes per source, so a
 * busy home page runs its product queries once, not on every visit.
 *
 * @phpstan-type Query array{source: string, count: int, categories?: list<int>, ids?: list<int>, in_stock?: bool}
 */
final class Catalog {

	public const VIEWS_META = 'um_views';
	public const CACHE      = 'ultra_max_catalog';

	/**
	 * Sources a widget can choose.
	 *
	 * @return array<string, string>
	 */
	public static function sources(): array {
		return array(
			'recent'       => __( 'Newest', 'ultra-max-core' ),
			'best_selling' => __( 'Best selling', 'ultra-max-core' ),
			'sale'         => __( 'On sale', 'ultra-max-core' ),
			'viewed'       => __( 'Most viewed', 'ultra-max-core' ),
			'featured'     => __( 'Featured', 'ultra-max-core' ),
			'top_rated'    => __( 'Top rated', 'ultra-max-core' ),
			'category'     => __( 'From categories', 'ultra-max-core' ),
			'manual'       => __( 'Hand-picked', 'ultra-max-core' ),
		);
	}

	/**
	 * Registers the view counter and cache clearing.
	 */
	public static function register(): void {
		add_action( 'woocommerce_after_single_product', array( self::class, 'count_view' ) );
		foreach ( array( 'woocommerce_update_product', 'woocommerce_new_product', 'woocommerce_delete_product', 'woocommerce_order_status_completed' ) as $hook ) {
			add_action( $hook, array( self::class, 'flush' ) );
		}
	}

	/**
	 * Counts a product view, for the "most viewed" source.
	 */
	public static function count_view(): void {
		$id = (int) get_the_ID();
		if ( $id > 0 && ! current_user_can( 'edit_post', $id ) ) {
			$views = get_post_meta( $id, self::VIEWS_META, true );
			update_post_meta( $id, self::VIEWS_META, ( is_numeric( $views ) ? (int) $views : 0 ) + 1 );
		}
	}

	/**
	 * Forgets cached lists after products change.
	 */
	public static function flush(): void {
		update_option( self::CACHE, (string) time(), false );
	}

	/**
	 * Product ids for a query, newest cache first.
	 *
	 * @param array $query Query.
	 * @phpstan-param Query $query
	 * @return list<int>
	 */
	public static function ids( array $query ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$query['count'] = max( 1, min( 48, $query['count'] ) );
		if ( 'manual' === $query['source'] ) {
			return array_slice( $query['ids'] ?? array(), 0, $query['count'] );
		}
		$version = get_option( self::CACHE, '0' );
		$key     = 'um_products_' . md5( (string) wp_json_encode( $query ) . ( is_scalar( $version ) ? (string) $version : '' ) );
		$cached  = get_transient( $key );
		if ( is_array( $cached ) ) {
			return array_values( array_filter( $cached, 'is_int' ) );
		}
		$ids = self::query( $query );
		set_transient( $key, $ids, 10 * MINUTE_IN_SECONDS );
		return $ids;
	}

	/**
	 * Runs a query.
	 *
	 * @param array $query Query.
	 * @phpstan-param Query $query
	 * @return list<int>
	 */
	private static function query( array $query ): array {
		$args = array(
			'status'     => 'publish',
			'limit'      => $query['count'],
			'return'     => 'ids',
			'visibility' => 'catalog',
		);
		if ( $query['in_stock'] ?? false ) {
			$args['stock_status'] = 'instock';
		}
		if ( array() !== ( $query['categories'] ?? array() ) ) {
			$slugs = array();
			foreach ( $query['categories'] ?? array() as $term_id ) {
				$term = get_term( $term_id, 'product_cat' );
				if ( $term instanceof \WP_Term ) {
					$slugs[] = $term->slug;
				}
			}
			$args['category'] = $slugs;
		}
		switch ( $query['source'] ) {
			case 'best_selling':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Indexed WooCommerce meta, cached above.
				$args['order']    = 'DESC';
				break;
			case 'viewed':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = self::VIEWS_META; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cached above.
				$args['order']    = 'DESC';
				break;
			case 'top_rated':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cached above.
				$args['order']    = 'DESC';
				break;
			case 'featured':
				$args['featured'] = true;
				break;
			case 'sale':
				$sale = wc_get_product_ids_on_sale();
				if ( array() === $sale ) {
					return array();
				}
				$args['include'] = array_map( 'intval', $sale );
				break;
			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}
		$found = wc_get_products( $args );
		$ids   = array();
		foreach ( is_array( $found ) ? $found : array() as $id ) {
			if ( is_numeric( $id ) ) {
				$ids[] = (int) $id;
			}
		}
		return $ids;
	}

	/**
	 * Products for a query.
	 *
	 * @param array $query Query.
	 * @phpstan-param Query $query
	 * @return list<\WC_Product>
	 */
	public static function products( array $query ): array {
		$out = array();
		$ids = self::ids( $query );
		if ( array() === $ids ) {
			return $out;
		}
		_prime_post_caches( $ids );
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product instanceof \WC_Product && $product->is_visible() ) {
				$out[] = $product;
			}
		}
		return $out;
	}
}
