<?php
/**
 * What the current request shows, for display conditions (BLD-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * A snapshot of the request: which page, which post or term, which visitor.
 *
 * Conditions are checked against this object instead of WordPress globals,
 * so the matching rules are plain functions that tests can call with any
 * made-up request.
 */
final class Context {

	/**
	 * Creates the snapshot.
	 *
	 * @param string                   $kind         One of front, blog, singular, archive, search, 404, other.
	 * @param string                   $post_type    Post type of the post shown, or of the archive's posts.
	 * @param int                      $object_id    Post id on a single post; term id on a term archive.
	 * @param string                   $taxonomy     Taxonomy of a term archive.
	 * @param array<string, list<int>> $terms        Term ids by taxonomy, with their ancestors: of the post on a single post, or the archive term.
	 * @param string                   $product_type WooCommerce product type on a single product.
	 * @param array                    $roles        Roles of the visitor; "guest" when logged out.
	 * @phpstan-param list<string> $roles
	 */
	public function __construct(
		public readonly string $kind,
		public readonly string $post_type = '',
		public readonly int $object_id = 0,
		public readonly string $taxonomy = '',
		public readonly array $terms = array(),
		public readonly string $product_type = '',
		public readonly array $roles = array( 'guest' ),
	) {}

	/**
	 * The snapshot of the current request.
	 */
	public static function current(): self {
		$roles = array( 'guest' );
		$user  = wp_get_current_user();
		if ( $user->exists() ) {
			$roles = array_values( array_filter( $user->roles, 'is_string' ) );
		}

		if ( is_front_page() ) {
			$kind = 'front';
		} elseif ( is_404() ) {
			return new self( '404', roles: $roles );
		} elseif ( is_search() ) {
			return new self( 'search', roles: $roles );
		} elseif ( is_home() ) {
			return new self( 'blog', 'post', roles: $roles );
		} elseif ( is_singular() ) {
			$kind = 'singular';
		} elseif ( is_archive() ) {
			return self::archive( $roles );
		} else {
			return new self( 'other', roles: $roles );
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return new self( $kind, roles: $roles );
		}
		$terms = array();
		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$ids = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_array( $ids ) && array() !== $ids ) {
				$terms[ $taxonomy ] = self::with_ancestors( array_values( array_map( 'intval', $ids ) ), $taxonomy );
			}
		}
		$product_type = '';
		if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
			$product      = wc_get_product( $post->ID );
			$product_type = $product instanceof \WC_Product ? $product->get_type() : '';
		}
		return new self( $kind, $post->post_type, $post->ID, '', $terms, $product_type, $roles );
	}

	/**
	 * The snapshot of an archive page.
	 *
	 * @param array $roles Roles of the visitor.
	 * @phpstan-param list<string> $roles
	 */
	private static function archive( array $roles ): self {
		$object = get_queried_object();
		if ( $object instanceof \WP_Term ) {
			$taxonomy  = get_taxonomy( $object->taxonomy );
			$types     = false === $taxonomy ? array() : $taxonomy->object_type;
			$post_type = in_array( 'product', $types, true ) ? 'product' : (string) ( $types[0] ?? 'post' );
			return new self(
				'archive',
				$post_type,
				$object->term_id,
				$object->taxonomy,
				array( $object->taxonomy => self::with_ancestors( array( $object->term_id ), $object->taxonomy ) ),
				'',
				$roles
			);
		}
		$is_shop = function_exists( 'is_shop' ) && is_shop();
		if ( $is_shop || is_post_type_archive( 'product' ) ) {
			return new self( 'archive', 'product', roles: $roles );
		}
		$post_type = get_query_var( 'post_type' );
		return new self( 'archive', is_string( $post_type ) && '' !== $post_type ? $post_type : 'post', roles: $roles );
	}

	/**
	 * Term ids with all their ancestors, so a rule for a parent category
	 * also covers its children.
	 *
	 * @param array  $ids      Term ids.
	 * @param string $taxonomy Taxonomy.
	 * @phpstan-param list<int> $ids
	 * @return list<int>
	 */
	private static function with_ancestors( array $ids, string $taxonomy ): array {
		$all = $ids;
		foreach ( $ids as $id ) {
			foreach ( get_ancestors( $id, $taxonomy, 'taxonomy' ) as $ancestor ) {
				$all[] = (int) $ancestor;
			}
		}
		return array_values( array_unique( $all ) );
	}
}
