<?php
/**
 * Which list of products a page shows, before any filter.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

/**
 * The shop, a product term archive (category, tag, brand or attribute) or
 * a product search. Filters, counts, addresses and canonical links all
 * start from here.
 */
final class Scope {

	/**
	 * Creates the scope.
	 *
	 * @param 'shop'|'term'|'search' $kind     Kind of list.
	 * @param string                 $taxonomy Taxonomy of a term archive.
	 * @param int                    $term_id  Term of a term archive.
	 * @param string                 $search   Search words.
	 */
	public function __construct(
		public readonly string $kind = 'shop',
		public readonly string $taxonomy = '',
		public readonly int $term_id = 0,
		public readonly string $search = '',
	) {}

	/**
	 * Whether the current request is a product list.
	 */
	public static function is_list(): bool {
		if ( ! function_exists( 'is_shop' ) ) {
			return false;
		}
		return is_shop() || is_product_taxonomy() || ( is_search() && is_post_type_archive( 'product' ) );
	}

	/**
	 * The scope of the current request; the shop when it is not a list.
	 */
	public static function current(): self {
		if ( is_search() ) {
			return new self( 'search', search: get_search_query( false ) );
		}
		$object = get_queried_object();
		if ( $object instanceof \WP_Term && function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			return new self( 'term', $object->taxonomy, $object->term_id );
		}
		return new self();
	}

	/**
	 * A key that names the list, for caches.
	 */
	public function key(): string {
		return match ( $this->kind ) {
			'term'   => 'term:' . $this->taxonomy . ':' . $this->term_id,
			'search' => 'search:' . strtolower( trim( $this->search ) ),
			default  => 'shop',
		};
	}

	/**
	 * The term of a term archive.
	 */
	public function term(): ?\WP_Term {
		if ( 'term' !== $this->kind ) {
			return null;
		}
		$term = get_term( $this->term_id, $this->taxonomy );
		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * The list's address without filters, page or sort order.
	 */
	public function url(): string {
		if ( 'search' === $this->kind ) {
			return add_query_arg(
				array(
					's'         => rawurlencode( $this->search ),
					'post_type' => 'product',
				),
				home_url( '/' )
			);
		}
		$term = $this->term();
		if ( null !== $term ) {
			$link = get_term_link( $term );
			return is_string( $link ) ? $link : home_url( '/' );
		}
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}

	/**
	 * Fields a filter form must send so the list stays the same, such as
	 * the search words.
	 *
	 * @return array<string, string>
	 */
	public function hidden(): array {
		return 'search' === $this->kind
			? array(
				's'         => $this->search,
				'post_type' => 'product',
			)
			: array();
	}

	/**
	 * Ids of the visible products of the list, before any filter. Private,
	 * draft and hidden products are never part of it.
	 *
	 * @return list<int>
	 */
	public function ids(): array {
		$visibility = wc_get_product_visibility_term_ids();
		$hidden     = array( 'search' === $this->kind ? ( $visibility['exclude-from-search'] ?? 0 ) : ( $visibility['exclude-from-catalog'] ?? 0 ) );
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && isset( $visibility['outofstock'] ) ) {
			$hidden[] = $visibility['outofstock'];
		}
		$tax = array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array_values( array_filter( $hidden ) ),
				'operator' => 'NOT IN',
			),
		);
		if ( 'term' === $this->kind ) {
			$tax[] = array(
				'taxonomy' => $this->taxonomy,
				'field'    => 'term_id',
				'terms'    => array( $this->term_id ),
			);
		}
		/**
		 * Filters how many products a list counts for its filters at most.
		 *
		 * @param int $limit Products.
		 */
		$limit = (int) apply_filters( 'ultra_max_facets_limit', 10000 );
		$args  = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'has_password'           => false,
			'fields'                 => 'ids',
			'posts_per_page'         => max( 1, $limit ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Cached by Facets.
			'orderby'                => 'ID',
			'order'                  => 'ASC',
		);
		if ( 'search' === $this->kind ) {
			$args['s'] = $this->search;
		}
		$query = new \WP_Query( $args );
		$ids   = array();
		foreach ( is_array( $query->posts ) ? $query->posts : array() as $id ) {
			if ( is_numeric( $id ) ) {
				$ids[] = (int) $id;
			}
		}
		return $ids;
	}
}
