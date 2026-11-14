<?php
/**
 * The filters of a product list (PLP-01, PLP-02, PLP-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\ShopFilters;
use UltraMax\Core\Store\Format;

/**
 * Which filters a list shows, and their groups and chips.
 *
 * The store chooses its filters and their order in the settings. A
 * category may choose its own (PLP-02): mobiles filter by memory and
 * colour, shoes by size; subcategories inherit the choice of their
 * nearest parent that made one.
 *
 * Filter keys: `in_stock`, `on_sale`, `category`, `brand`, `price`,
 * `rating`, `attributes` (every product attribute) and `attr:<taxonomy>`.
 *
 * @phpstan-import-type FilterGroup from ShopFilters
 */
final class Filters {

	public const TERM_META  = 'um_filters';
	public const COLOR_META = 'um_color';
	public const LATIN_META = 'um_latin_name';

	/**
	 * Groups per list and choice, for this request.
	 *
	 * @var array<string, list<FilterGroup>>
	 */
	private static array $memo = array();

	/**
	 * Filters the settings offer, with "every attribute".
	 *
	 * @return array<string, string>
	 */
	public static function choices(): array {
		return array_merge(
			array(
				'in_stock'   => __( 'Only available products', 'ultra-max-core' ),
				'on_sale'    => __( 'Only discounted products', 'ultra-max-core' ),
				'category'   => __( 'Category', 'ultra-max-core' ),
				'brand'      => __( 'Brand', 'ultra-max-core' ),
				'price'      => __( 'Price range', 'ultra-max-core' ),
				'rating'     => __( 'Buyers\' rating', 'ultra-max-core' ),
				'attributes' => __( 'Every product attribute', 'ultra-max-core' ),
			),
			self::attributes()
		);
	}

	/**
	 * Product attributes as filter keys.
	 *
	 * @return array<string, string>
	 */
	public static function attributes(): array {
		$out = array();
		foreach ( function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array() as $attribute ) {
			if ( is_object( $attribute ) && isset( $attribute->attribute_name, $attribute->attribute_label ) ) {
				/* translators: %s: attribute name such as Colour. */
				$out[ 'attr:pa_' . $attribute->attribute_name ] = sprintf( __( 'Attribute: %s', 'ultra-max-core' ), (string) $attribute->attribute_label );
			}
		}
		return $out;
	}

	/**
	 * The default filters of a new store.
	 *
	 * @return list<string>
	 */
	public static function defaults(): array {
		return array( 'in_stock', 'on_sale', 'category', 'brand', 'price', 'attributes', 'rating' );
	}

	/**
	 * The store's filters, from the settings.
	 *
	 * @return list<string>
	 */
	public static function store_keys(): array {
		$keys = array();
		foreach ( Store::rows( 'shop_filters' ) as $row ) {
			$key = is_array( $row ) && is_string( $row['filter'] ?? null ) ? $row['filter'] : '';
			if ( '' !== $key && ! in_array( $key, $keys, true ) ) {
				$keys[] = $key;
			}
		}
		return array() === $keys ? self::defaults() : $keys;
	}

	/**
	 * The filters a category chose for itself, or null to inherit.
	 *
	 * @param int $term_id Category id.
	 * @return list<string>|null
	 */
	public static function term_keys( int $term_id ): ?array {
		$keys = get_term_meta( $term_id, self::TERM_META, true );
		if ( ! is_array( $keys ) ) {
			return null;
		}
		return array_values( array_filter( $keys, 'is_string' ) );
	}

	/**
	 * The filters of a list, with "every attribute" spelled out.
	 *
	 * @param Scope $scope The list.
	 * @return list<string>
	 */
	public static function keys( Scope $scope ): array {
		$keys = null;
		if ( 'term' === $scope->kind && 'product_cat' === $scope->taxonomy ) {
			foreach ( array_merge( array( $scope->term_id ), get_ancestors( $scope->term_id, 'product_cat', 'taxonomy' ) ) as $id ) {
				$keys = self::term_keys( (int) $id );
				if ( null !== $keys ) {
					break;
				}
			}
		}
		$keys ??= self::store_keys();
		$out    = array();
		foreach ( $keys as $key ) {
			if ( 'attributes' === $key ) {
				foreach ( array_keys( self::attributes() ) as $attribute ) {
					if ( ! in_array( $attribute, $keys, true ) ) {
						$out[] = $attribute;
					}
				}
				continue;
			}
			$out[] = $key;
		}
		if ( 'term' === $scope->kind && 'product_brand' === $scope->taxonomy ) {
			$out = array_values( array_diff( $out, array( 'brand' ) ) );
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * The filter groups of a list.
	 *
	 * @param Scope $scope The list.
	 * @param State $state Chosen filters.
	 * @return list<FilterGroup>
	 */
	public static function groups( Scope $scope, State $state ): array {
		$key = $scope->key() . '?' . http_build_query( $state->args() );
		if ( ! isset( self::$memo[ $key ] ) ) {
			self::$memo[ $key ] = self::build( $scope, $state );
		}
		return self::$memo[ $key ];
	}

	/**
	 * Forgets this request's groups, for tests.
	 */
	public static function reset(): void {
		self::$memo = array();
		Facets::reset();
	}

	/**
	 * Builds the filter groups of a list.
	 *
	 * @param Scope $scope The list.
	 * @param State $state Chosen filters.
	 * @return list<FilterGroup>
	 */
	private static function build( Scope $scope, State $state ): array {
		$facets = Facets::for( $scope );
		$base   = $scope->url();
		$groups = array();
		$open   = 0;
		foreach ( self::keys( $scope ) as $key ) {
			$group = match ( true ) {
				'in_stock' === $key, 'on_sale' === $key => self::toggle( $key, $state, $facets ),
				'category' === $key => self::categories( $scope, $state, $facets, $base ),
				'brand' === $key    => self::terms( 'product_brand', __( 'Brand', 'ultra-max-core' ), $state, $facets, false ),
				'price' === $key    => self::price( $state, $facets ),
				'rating' === $key   => self::rating( $state, $facets ),
				str_starts_with( $key, 'attr:' ) => self::attribute( substr( $key, 5 ), $state, $facets ),
				default => null,
			};
			if ( null === $group ) {
				continue;
			}
			if ( 'toggle' !== $group['type'] && $open < 3 ) {
				$group['open'] = true;
				++$open;
			}
			$groups[] = $group;
		}
		return $groups;
	}

	/**
	 * A switch group.
	 *
	 * @param string $key    in_stock or on_sale.
	 * @param State  $state  Chosen filters.
	 * @param array  $facets Counts.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function toggle( string $key, State $state, array $facets ): ?array {
		$checked = 'in_stock' === $key ? $state->in_stock : $state->on_sale;
		$count   = $facets[ $key ];
		if ( 0 === $count && ! $checked ) {
			return null;
		}
		return array(
			'type'    => 'toggle',
			'key'     => $key,
			'name'    => $key,
			'label'   => 'in_stock' === $key ? __( 'Only available products', 'ultra-max-core' ) : __( 'Only discounted products', 'ultra-max-core' ),
			'checked' => $checked,
		);
	}

	/**
	 * The category tree: on a category page its parent, itself and its
	 * children as pages; elsewhere the categories of the list as a choice.
	 *
	 * @param Scope  $scope  The list.
	 * @param State  $state  Chosen filters.
	 * @param array  $facets Counts.
	 * @param string $base   List address.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function categories( Scope $scope, State $state, array $facets, string $base ): ?array {
		$counts = $facets['terms']['product_cat'] ?? array();
		$links  = array();
		$term   = $scope->term();
		if ( null !== $term && 'product_cat' === $term->taxonomy ) {
			$depth  = 0;
			$parent = $term->parent > 0 ? get_term( $term->parent, 'product_cat' ) : null;
			if ( $parent instanceof \WP_Term ) {
				$links[] = self::link( $parent, $state->url( self::term_url( $parent ) ), false, 0 );
				$depth   = 1;
			}
			$links[] = self::link( $term, $state->url( $base ), true, $depth, $facets['total'] );
			foreach ( self::children( $term->term_id, $counts ) as $child ) {
				$links[] = self::link( $child, $state->without( State::CATEGORY )->url( self::term_url( $child ) ), false, $depth + 1, $counts[ $child->term_id ] ?? 0 );
			}
		} else {
			$chosen = $state->terms[ State::CATEGORY ][0] ?? '';
			foreach ( self::children( 0, $counts ) as $top ) {
				$links[] = self::link( $top, $state->toggle( State::CATEGORY, $top->slug )->url( $base ), $top->slug === $chosen, 0, $counts[ $top->term_id ] ?? 0 );
				if ( '' === $chosen ) {
					continue;
				}
				$current = get_term_by( 'slug', $chosen, 'product_cat' );
				if ( $current instanceof \WP_Term && ( $current->term_id === $top->term_id || in_array( $top->term_id, get_ancestors( $current->term_id, 'product_cat', 'taxonomy' ), true ) ) ) {
					foreach ( self::children( $top->term_id, $counts ) as $child ) {
						$links[] = self::link( $child, $state->toggle( State::CATEGORY, $child->slug )->url( $base ), $child->slug === $chosen, 1, $counts[ $child->term_id ] ?? 0 );
					}
				}
			}
		}
		if ( count( $links ) < 2 ) {
			return null;
		}
		return array(
			'type'  => 'links',
			'key'   => 'category',
			'label' => __( 'Category', 'ultra-max-core' ),
			'links' => $links,
		);
	}

	/**
	 * Child categories that have products in the list.
	 *
	 * @param int             $parent_id Parent id; 0 for the top level.
	 * @param array<int, int> $counts Products per category.
	 * @return list<\WP_Term>
	 */
	private static function children( int $parent_id, array $counts ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => false,
				'orderby'    => 'menu_order', // WooCommerce's own category order.
			)
		);
		$out   = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( $term instanceof \WP_Term && ( $counts[ $term->term_id ] ?? 0 ) > 0 ) {
				$out[] = $term;
			}
		}
		return $out;
	}

	/**
	 * The address of a term archive.
	 *
	 * @param \WP_Term $term Term.
	 */
	private static function term_url( \WP_Term $term ): string {
		$link = get_term_link( $term );
		return is_string( $link ) ? $link : home_url( '/' );
	}

	/**
	 * One link of the category tree.
	 *
	 * @param \WP_Term $term    Category.
	 * @param string   $href    Address.
	 * @param bool     $current Whether it is the page shown.
	 * @param int      $depth   Indent.
	 * @param int|null $count   Products, or null to hide the number.
	 * @return array{label: string, href: string, current: bool, depth: int, count?: string}
	 */
	private static function link( \WP_Term $term, string $href, bool $current, int $depth, ?int $count = null ): array {
		$link = array(
			'label'   => $term->name,
			'href'    => $href,
			'current' => $current,
			'depth'   => $depth,
		);
		if ( null !== $count ) {
			$link['count'] = Format::number( $count );
		}
		return $link;
	}

	/**
	 * A check list of the terms of a taxonomy that the list has.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $label    Group title.
	 * @param State  $state    Chosen filters.
	 * @param array  $facets   Counts.
	 * @param bool   $swatch   Whether values are colours.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function terms( string $taxonomy, string $label, State $state, array $facets, bool $swatch ): ?array {
		$param  = State::param( $taxonomy );
		$counts = $facets['terms'][ $taxonomy ] ?? array();
		if ( '' === $param || ! taxonomy_exists( $taxonomy ) || array() === $counts ) {
			return null;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'include'    => array_keys( $counts ),
				'hide_empty' => false,
			)
		);
		$items = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$item  = array(
				'value'   => rawurldecode( $term->slug ),
				'label'   => $term->name,
				'count'   => Format::number( $counts[ $term->term_id ] ?? 0 ),
				'checked' => $state->has( $param, $term->slug ),
				'_n'      => $counts[ $term->term_id ] ?? 0,
			);
			$latin = get_term_meta( $term->term_id, self::LATIN_META, true );
			if ( is_string( $latin ) && '' !== $latin ) {
				$item['hint'] = $latin;
			}
			if ( $swatch ) {
				$color         = get_term_meta( $term->term_id, self::COLOR_META, true );
				$item['color'] = is_string( $color ) && 1 === preg_match( '/^#[0-9a-f]{3,8}$/i', $color ) ? $color : 'var(--um-surface-3)';
			}
			$items[] = $item;
		}
		if ( array() === $items ) {
			return null;
		}
		if ( ! $swatch ) {
			usort( $items, static fn ( array $a, array $b ): int => array( $b['checked'], $b['_n'], $a['label'] ) <=> array( $a['checked'], $a['_n'], $b['label'] ) );
		}
		return array(
			'type'   => 'checks',
			'key'    => $param,
			'name'   => $param,
			'label'  => $label,
			'swatch' => $swatch,
			'search' => 'product_brand' === $taxonomy ? __( 'Search brands, in Persian or English', 'ultra-max-core' ) : __( 'Search', 'ultra-max-core' ),
			'items'  => array_map(
				static function ( array $item ): array {
					unset( $item['_n'] );
					return $item;
				},
				$items
			),
		);
	}

	/**
	 * The group of one product attribute.
	 *
	 * @param string $taxonomy Attribute taxonomy, such as pa_color.
	 * @param State  $state    Chosen filters.
	 * @param array  $facets   Counts.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function attribute( string $taxonomy, State $state, array $facets ): ?array {
		$id = function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? wc_attribute_taxonomy_id_by_name( $taxonomy ) : 0;
		if ( 0 === $id ) {
			return null;
		}
		$attribute = wc_get_attribute( $id );
		if ( null === $attribute ) {
			return null;
		}
		return self::terms( $taxonomy, (string) $attribute->name, $state, $facets, in_array( $taxonomy, Terms::swatch_taxonomies(), true ) );
	}

	/**
	 * The price range.
	 *
	 * @param State $state  Chosen filters.
	 * @param array $facets Counts.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function price( State $state, array $facets ): ?array {
		$step = self::step( $facets['max'] - $facets['min'] );
		$min  = (int) ( floor( $facets['min'] / $step ) * $step );
		$max  = (int) ( ceil( $facets['max'] / $step ) * $step );
		if ( $max <= $min ) {
			return null;
		}
		return array(
			'type'  => 'range',
			'key'   => 'price',
			'label' => __( 'Price range', 'ultra-max-core' ),
			'range' => array(
				'min'        => $min,
				'max'        => $max,
				'from'       => $state->min_price > 0 ? max( $min, min( $max, $state->min_price ) ) : $min,
				'to'         => $state->max_price > 0 ? max( $min, min( $max, $state->max_price ) ) : $max,
				'step'       => $step,
				'unit'       => Format::currency(),
				'from_label' => __( 'From', 'ultra-max-core' ),
				'to_label'   => __( 'To', 'ultra-max-core' ),
				'min_name'   => 'min_price',
				'max_name'   => 'max_price',
			),
		);
	}

	/**
	 * A round step for a price span: about a hundred steps, in 1, 2 or 5
	 * times a power of ten.
	 *
	 * @param int $span Highest minus lowest price.
	 */
	public static function step( int $span ): int {
		if ( $span <= 100 ) {
			return 1;
		}
		$raw   = $span / 100;
		$power = 10 ** (int) floor( log10( $raw ) );
		foreach ( array( 1, 2, 5, 10 ) as $factor ) {
			if ( $raw <= $factor * $power ) {
				return (int) ( $factor * $power );
			}
		}
		return (int) ( 10 * $power );
	}

	/**
	 * The rating choice.
	 *
	 * @param State $state  Chosen filters.
	 * @param array $facets Counts.
	 * @phpstan-param array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>} $facets
	 * @return FilterGroup|null
	 */
	private static function rating( State $state, array $facets ): ?array {
		$items = array();
		foreach ( array( 4, 3 ) as $stars ) {
			$count = $facets['rated'][ $stars ] ?? 0;
			if ( 0 === $count && $state->rating !== $stars ) {
				continue;
			}
			$items[] = array(
				'value'   => implode( ',', range( $stars, 5 ) ),
				/* translators: %s: number of stars. */
				'label'   => sprintf( __( '%s stars and up', 'ultra-max-core' ), Format::number( $stars ) ),
				'count'   => Format::number( $count ),
				'checked' => $state->rating === $stars,
			);
		}
		if ( array() === $items ) {
			return null;
		}
		return array(
			'type'  => 'choice',
			'key'   => 'rating',
			'name'  => 'rating_filter',
			'label' => __( 'Buyers\' rating', 'ultra-max-core' ),
			'items' => $items,
		);
	}

	/**
	 * The chosen filters as removable chips (PLP-12).
	 *
	 * @param Scope $scope The list.
	 * @param State $state Chosen filters.
	 * @return list<array{label: string, href: string, remove: string}>
	 */
	public static function chips( Scope $scope, State $state ): array {
		$base  = $scope->url();
		$chips = array();
		/* translators: %s: name of a filter value. */
		$remove = static fn ( string $label ): string => sprintf( __( 'Remove filter %s', 'ultra-max-core' ), $label );
		foreach ( $state->terms as $param => $slugs ) {
			$taxonomy = State::taxonomy( $param );
			foreach ( $slugs as $slug ) {
				$term    = get_term_by( 'slug', $slug, $taxonomy );
				$label   = $term instanceof \WP_Term ? $term->name : rawurldecode( $slug );
				$chips[] = array(
					'label'  => $label,
					'href'   => $state->toggle( $param, $slug )->url( $base ),
					'remove' => $remove( $label ),
				);
			}
		}
		if ( $state->in_stock ) {
			$label   = __( 'Only available', 'ultra-max-core' );
			$chips[] = array(
				'label'  => $label,
				'href'   => $state->without( 'in_stock' )->url( $base ),
				'remove' => $remove( $label ),
			);
		}
		if ( $state->on_sale ) {
			$label   = __( 'Only discounted', 'ultra-max-core' );
			$chips[] = array(
				'label'  => $label,
				'href'   => $state->without( 'on_sale' )->url( $base ),
				'remove' => $remove( $label ),
			);
		}
		if ( $state->min_price > 0 || $state->max_price > 0 ) {
			$label = match ( true ) {
				/* translators: 1: lowest price, 2: highest price, 3: currency. */
				$state->min_price > 0 && $state->max_price > 0 => sprintf( __( '%1$s to %2$s %3$s', 'ultra-max-core' ), Format::money( $state->min_price ), Format::money( $state->max_price ), Format::currency() ),
				/* translators: 1: lowest price, 2: currency. */
				$state->min_price > 0 => sprintf( __( 'From %1$s %2$s', 'ultra-max-core' ), Format::money( $state->min_price ), Format::currency() ),
				/* translators: 1: highest price, 2: currency. */
				default => sprintf( __( 'Up to %1$s %2$s', 'ultra-max-core' ), Format::money( $state->max_price ), Format::currency() ),
			};
			$chips[] = array(
				'label'  => $label,
				'href'   => $state->without( 'price' )->url( $base ),
				'remove' => $remove( $label ),
			);
		}
		if ( $state->rating > 0 ) {
			/* translators: %s: number of stars. */
			$label   = sprintf( __( '%s stars and up', 'ultra-max-core' ), Format::number( $state->rating ) );
			$chips[] = array(
				'label'  => $label,
				'href'   => $state->without( 'rating' )->url( $base ),
				'remove' => $remove( $label ),
			);
		}
		return $chips;
	}
}
