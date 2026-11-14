<?php
/**
 * Live search suggestions (SRCH-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Search;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SearchSuggest;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Store\Format;

/**
 * `GET /wp-json/ultra-max/v1/search?q=` answers with the suggestions'
 * markup, made by the SearchSuggest component: categories, brands,
 * products and posts that match the typed words.
 *
 * Only what any visitor may see is suggested: published products that are
 * visible in search, with no password; published posts; categories and
 * brands that have products. Answers are cached for ten minutes per query
 * and dropped when products change.
 */
final class Live {

	/**
	 * Registers the route.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'route' ) );
	}

	/**
	 * The route.
	 */
	public static function route(): void {
		register_rest_route(
			AdminRest::NAMESPACE,
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'answer' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * The address of the route.
	 */
	public static function endpoint(): string {
		return rest_url( AdminRest::NAMESPACE . '/search' );
	}

	/**
	 * Answers a query.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function answer( \WP_REST_Request $request ): \WP_REST_Response {
		$query = $request->get_param( 'q' );
		$query = is_string( $query ) ? trim( mb_substr( $query, 0, 80 ) ) : '';
		if ( mb_strlen( $query ) < max( 1, Store::int( 'search_min' ) ) ) {
			return new \WP_REST_Response(
				array(
					'html'  => '',
					'count' => 0,
				)
			);
		}
		$version = get_option( Catalog::CACHE, '0' );
		$key     = 'um_live_' . md5( Normalizer::normalize( $query ) . '|' . ( is_scalar( $version ) ? (string) $version : '' ) . '|' . get_locale() );
		$cached  = get_transient( $key );
		if ( ! is_array( $cached ) ) {
			$cached = self::build( $query );
			set_transient( $key, $cached, 10 * MINUTE_IN_SECONDS );
		}
		$response = new \WP_REST_Response( $cached );
		$response->header( 'Cache-Control', 'public, max-age=300' );
		return $response;
	}

	/**
	 * The suggestions of a query.
	 *
	 * @param string $query Typed words.
	 * @return array{html: string, count: int}
	 */
	public static function build( string $query ): array {
		$products   = self::products( $query );
		$categories = self::terms( 'product_cat', $query, 4 );
		$brands     = taxonomy_exists( 'product_brand' ) ? self::terms( 'product_brand', $query, 4 ) : array();
		$posts      = Store::bool( 'search_posts' ) ? self::posts( $query ) : array();
		$html       = SearchSuggest::render(
			array(
				'id'         => 'um-suggest',
				'products'   => $products,
				'categories' => $categories,
				'brands'     => $brands,
				'posts'      => $posts,
				'labels'     => array(
					'products'   => __( 'Products', 'ultra-max-core' ),
					'categories' => __( 'Categories', 'ultra-max-core' ),
					'brands'     => __( 'Brands', 'ultra-max-core' ),
					'posts'      => __( 'From the blog', 'ultra-max-core' ),
				),
				'all'        => array(
					/* translators: %s: search words. */
					'label' => sprintf( __( 'All results for "%s"', 'ultra-max-core' ), $query ),
					'href'  => add_query_arg(
						array(
							's'         => rawurlencode( $query ),
							'post_type' => 'product',
						),
						home_url( '/' )
					),
				),
				/* translators: %s: search words. */
				'empty'      => sprintf( __( 'Nothing found for "%s".', 'ultra-max-core' ), $query ),
			)
		);
		return array(
			'html'  => (string) $html,
			'count' => count( $products ) + count( $categories ) + count( $brands ) + count( $posts ),
		);
	}

	/**
	 * Matching products, with picture and price.
	 *
	 * @param string $query Typed words.
	 * @return list<array{title: string, href: string, image: Html, price?: string, regular?: string}>
	 */
	private static function products( string $query ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$visibility = wc_get_product_visibility_term_ids();
		$hidden     = array( $visibility['exclude-from-search'] ?? 0 );
		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && isset( $visibility['outofstock'] ) ) {
			$hidden[] = $visibility['outofstock'];
		}
		$found = new \WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'has_password'   => false,
				's'              => $query,
				'posts_per_page' => max( 3, min( 10, Store::int( 'search_products' ) ) ),
				'no_found_rows'  => true,
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Cached above.
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'term_taxonomy_id',
						'terms'    => array_values( array_filter( $hidden ) ),
						'operator' => 'NOT IN',
					),
				),
			)
		);
		$out   = array();
		foreach ( is_array( $found->posts ) ? $found->posts : array() as $post ) {
			$product = wc_get_product( $post );
			if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
				continue;
			}
			$image = $product->get_image(
				'woocommerce_gallery_thumbnail',
				array(
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
			$item  = array(
				'title' => wp_strip_all_tags( $product->get_name() ),
				'href'  => (string) $product->get_permalink(),
				'image' => Html::wp( is_string( $image ) ? $image : '' ),
			);
			$price = $product->get_price();
			if ( is_numeric( $price ) && (float) $price > 0 ) {
				$item['price'] = Format::money( (float) $price ) . ' ' . Format::currency();
				$regular       = $product->get_regular_price();
				if ( $product->is_on_sale() && is_numeric( $regular ) && (float) $regular > (float) $price ) {
					$item['regular'] = Format::money( (float) $regular );
				}
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Matching categories or brands that have products. Brands match by
	 * their Latin name too.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $query    Typed words.
	 * @param int    $limit    Most terms.
	 * @return list<array{label: string, href: string, hint?: string}>
	 */
	private static function terms( string $taxonomy, string $query, int $limit ): array {
		$found = array();
		foreach ( Normalizer::variants( $query ) as $variant ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
					'name__like' => $variant,
					'number'     => $limit,
				)
			);
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				if ( $term instanceof \WP_Term ) {
					$found[ $term->term_id ] = $term;
				}
			}
			if ( 'product_brand' === $taxonomy ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => true,
						'number'     => $limit,
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Few brands; cached above.
							array(
								'key'     => Filters::LATIN_META,
								'value'   => $variant,
								'compare' => 'LIKE',
							),
						),
					)
				);
				foreach ( is_array( $terms ) ? $terms : array() as $term ) {
					if ( $term instanceof \WP_Term ) {
						$found[ $term->term_id ] = $term;
					}
				}
			}
			if ( count( $found ) >= $limit ) {
				break;
			}
		}
		$out = array();
		foreach ( array_slice( $found, 0, $limit ) as $term ) {
			$link = get_term_link( $term );
			if ( ! is_string( $link ) ) {
				continue;
			}
			$item  = array(
				'label' => $term->name,
				'href'  => $link,
			);
			$latin = get_term_meta( $term->term_id, Filters::LATIN_META, true );
			if ( is_string( $latin ) && '' !== $latin ) {
				$item['hint'] = $latin;
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Matching published posts.
	 *
	 * @param string $query Typed words.
	 * @return list<array{label: string, href: string}>
	 */
	private static function posts( string $query ): array {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'has_password'   => false,
				's'              => $query,
				'posts_per_page' => 3,
			)
		);
		$out   = array();
		foreach ( $posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$out[] = array(
					'label' => wp_strip_all_tags( get_the_title( $post ) ),
					'href'  => (string) get_permalink( $post ),
				);
			}
		}
		return $out;
	}
}
