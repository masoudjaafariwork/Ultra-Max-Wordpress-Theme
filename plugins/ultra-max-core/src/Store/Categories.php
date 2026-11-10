<?php
/**
 * The product category tree for menus (HDR-07, HDR-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Store;

/**
 * Builds the tree of product categories once and keeps it until a
 * category changes, so header menus cost no query on most pages.
 *
 * @phpstan-type CategoryNode array{id: int, name: string, href: string, image: int, count: int, children: list<array<mixed>>}
 */
final class Categories {

	public const CACHE = 'ultra_max_category_tree';

	/**
	 * Clears the cached tree when categories change.
	 */
	public static function register(): void {
		foreach ( array( 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ) as $hook ) {
			add_action( $hook, array( self::class, 'flush' ) );
		}
	}

	/**
	 * Forgets the cached tree.
	 */
	public static function flush(): void {
		delete_transient( self::CACHE );
	}

	/**
	 * The tree, three levels deep.
	 *
	 * @param array $roots Only these top categories, in this order; all top categories when empty.
	 * @phpstan-param list<int> $roots
	 * @return list<CategoryNode>
	 */
	public static function tree( array $roots = array() ): array {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}
		$all = get_transient( self::CACHE );
		if ( ! is_array( $all ) ) {
			$all = self::build();
			set_transient( self::CACHE, $all, DAY_IN_SECONDS );
		}
		$nodes = array();
		foreach ( $all as $node ) {
			if ( is_array( $node ) && is_int( $node['id'] ?? null ) ) {
				$nodes[ $node['id'] ] = self::node( $node );
			}
		}
		if ( array() === $roots ) {
			return array_values( $nodes );
		}
		$out = array();
		foreach ( $roots as $id ) {
			if ( isset( $nodes[ $id ] ) ) {
				$out[] = $nodes[ $id ];
			} else {
				$found = self::find( array_values( $nodes ), $id );
				if ( null !== $found ) {
					$out[] = $found;
				}
			}
		}
		return $out;
	}

	/**
	 * Finds a node anywhere in the tree.
	 *
	 * @param array $nodes Nodes.
	 * @param int   $id    Term id.
	 * @phpstan-param list<array<mixed>> $nodes
	 * @return CategoryNode|null
	 */
	private static function find( array $nodes, int $id ): ?array {
		foreach ( $nodes as $node ) {
			if ( ( $node['id'] ?? 0 ) === $id ) {
				return self::node( $node );
			}
			$found = self::find( is_array( $node['children'] ?? null ) ? array_values( array_filter( $node['children'], 'is_array' ) ) : array(), $id );
			if ( null !== $found ) {
				return $found;
			}
		}
		return null;
	}

	/**
	 * A well-formed node.
	 *
	 * @param array<mixed> $node Raw node.
	 * @return CategoryNode
	 */
	public static function node( array $node ): array {
		$children = array();
		foreach ( is_array( $node['children'] ?? null ) ? $node['children'] : array() as $child ) {
			if ( is_array( $child ) ) {
				$children[] = $child;
			}
		}
		return array(
			'id'       => is_int( $node['id'] ?? null ) ? $node['id'] : 0,
			'name'     => is_string( $node['name'] ?? null ) ? $node['name'] : '',
			'href'     => is_string( $node['href'] ?? null ) ? $node['href'] : '#',
			'image'    => is_int( $node['image'] ?? null ) ? $node['image'] : 0,
			'count'    => is_int( $node['count'] ?? null ) ? $node['count'] : 0,
			'children' => $children,
		);
	}

	/**
	 * Reads all categories and builds the tree.
	 *
	 * @return list<CategoryNode>
	 */
	private static function build(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'menu_order',
			)
		);
		$skip  = get_option( 'default_product_cat', 0 );
		$skip  = is_numeric( $skip ) ? (int) $skip : 0;
		$by    = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( ! $term instanceof \WP_Term || $term->term_id === $skip ) {
				continue;
			}
			$link                  = get_term_link( $term );
			$thumb                 = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$by[ $term->parent ][] = array(
				'id'       => $term->term_id,
				'name'     => $term->name,
				'href'     => is_string( $link ) ? $link : '#',
				'image'    => is_numeric( $thumb ) ? (int) $thumb : 0,
				'count'    => $term->count,
				'children' => array(),
			);
		}
		$attach = static function ( int $parent_id, int $depth ) use ( &$attach, $by ): array {
			$out = array();
			foreach ( $by[ $parent_id ] ?? array() as $node ) {
				if ( $depth > 1 ) {
					$node['children'] = $attach( $node['id'], $depth - 1 );
				}
				$out[] = $node;
			}
			return $out;
		};
		$tree   = $attach( 0, 3 );
		$clean  = array();
		foreach ( $tree as $node ) {
			$clean[] = self::node( $node );
		}
		return $clean;
	}
}
