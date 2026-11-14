<?php
/**
 * Counts behind the filters of a product list (PLP-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Store\Catalog;

/**
 * For the products of a list before any filter (a category, a brand, the
 * shop or a search), how many carry each brand, attribute value and
 * category, the price range, and how many are available, discounted or
 * well rated. Filters show only values that exist in the list.
 *
 * Three small grouped queries over the list's products, cached for an
 * hour per list and dropped whenever products change (the catalog
 * version), so a busy category page counts once, not on every visit.
 *
 * @phpstan-type Counts array{total: int, terms: array<string, array<int, int>>, min: int, max: int, in_stock: int, on_sale: int, rated: array<int, int>}
 */
final class Facets {

	/**
	 * Counts per scope, for this request.
	 *
	 * @var array<string, Counts>
	 */
	private static array $memo = array();

	/**
	 * The counts of a list.
	 *
	 * @param Scope $scope The list.
	 * @return Counts
	 */
	public static function for( Scope $scope ): array {
		$key = $scope->key();
		if ( isset( self::$memo[ $key ] ) ) {
			return self::$memo[ $key ];
		}
		$version = get_option( Catalog::CACHE, '0' );
		$cache   = 'um_facets_' . md5( $key . '|' . ( is_scalar( $version ) ? (string) $version : '' ) . '|' . get_locale() );
		$found   = get_transient( $cache );
		if ( is_array( $found ) && isset( $found['total'], $found['terms'] ) ) {
			/**
			 * Counts from the cache, written by count() below.
			 *
			 * @var Counts $found
			 */
			self::$memo[ $key ] = $found;
			return $found;
		}
		$counts = self::count( $scope->ids() );
		set_transient( $cache, $counts, HOUR_IN_SECONDS );
		self::$memo[ $key ] = $counts;
		return $counts;
	}

	/**
	 * Forgets this request's counts, for tests.
	 */
	public static function reset(): void {
		self::$memo = array();
	}

	/**
	 * Counts a list of products.
	 *
	 * @param array $ids Product ids.
	 * @phpstan-param list<int> $ids
	 * @return Counts
	 */
	public static function count( array $ids ): array {
		$counts = array(
			'total'    => count( $ids ),
			'terms'    => array(),
			'min'      => 0,
			'max'      => 0,
			'in_stock' => 0,
			'on_sale'  => 0,
			'rated'    => array(),
		);
		if ( array() === $ids ) {
			return $counts;
		}
		global $wpdb;
		$taxonomies = array_values( array_filter( array_merge( array( 'product_cat', 'product_brand' ), wc_get_attribute_taxonomy_names() ), 'taxonomy_exists' ) );
		$list       = implode( ',', array_map( 'intval', $ids ) );
		$names      = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );
		// The ids are integers joined above; the taxonomy names are placeholders.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tt.taxonomy, tt.term_id, COUNT( DISTINCT tr.object_id ) AS n
				FROM {$wpdb->term_relationships} tr
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
				WHERE tt.taxonomy IN ( $names ) AND tr.object_id IN ( $list )
				GROUP BY tt.term_taxonomy_id",
				...$taxonomies
			),
			ARRAY_A
		);
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) && is_string( $row['taxonomy'] ?? null ) ) {
				$counts['terms'][ $row['taxonomy'] ][ self::int( $row['term_id'] ?? 0 ) ] = self::int( $row['n'] ?? 0 );
			}
		}
		$counts['terms']['product_cat'] = self::categories( $list );
		$lookup                         = $wpdb->wc_product_meta_lookup ?? $wpdb->prefix . 'wc_product_meta_lookup';
		$stats                          = $wpdb->get_row(
			"SELECT MIN( min_price ) AS low, MAX( max_price ) AS high,
				SUM( stock_status = 'instock' ) AS in_stock, SUM( onsale = 1 ) AS on_sale,
				SUM( average_rating >= 4 ) AS r4, SUM( average_rating >= 3 ) AS r3
			FROM {$lookup} WHERE product_id IN ( $list )",
			ARRAY_A
		);
		// phpcs:enable
		if ( is_array( $stats ) ) {
			$counts['min']      = (int) floor( is_numeric( $stats['low'] ?? null ) ? (float) $stats['low'] : 0.0 );
			$counts['max']      = (int) ceil( is_numeric( $stats['high'] ?? null ) ? (float) $stats['high'] : 0.0 );
			$counts['in_stock'] = self::int( $stats['in_stock'] ?? 0 );
			$counts['on_sale']  = self::int( $stats['on_sale'] ?? 0 );
			$counts['rated']    = array(
				4 => self::int( $stats['r4'] ?? 0 ),
				3 => self::int( $stats['r3'] ?? 0 ),
			);
		}
		return $counts;
	}

	/**
	 * Products per category, each counted once in its categories and all
	 * their parents, so "laptops" counts the products of "work laptops".
	 *
	 * @param string $id_list Product ids joined with commas.
	 * @return array<int, int>
	 */
	private static function categories( string $id_list ): array {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- Integer ids joined by the caller; cached by for().
		$rows = $wpdb->get_results(
			"SELECT tr.object_id AS product, tt.term_id AS term
			FROM {$wpdb->term_relationships} tr
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tt.taxonomy = 'product_cat' AND tr.object_id IN ( $id_list )",
			ARRAY_A
		);
		// phpcs:enable
		$sets = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$product                   = self::int( $row['product'] ?? 0 );
			$term                      = self::int( $row['term'] ?? 0 );
			$sets[ $product ][ $term ] = true;
			foreach ( get_ancestors( $term, 'product_cat', 'taxonomy' ) as $ancestor ) {
				$sets[ $product ][ (int) $ancestor ] = true;
			}
		}
		$out = array();
		foreach ( $sets as $terms ) {
			foreach ( array_keys( $terms ) as $term ) {
				$out[ $term ] = ( $out[ $term ] ?? 0 ) + 1;
			}
		}
		return $out;
	}

	/**
	 * A whole number from a database value.
	 *
	 * @param mixed $value Value.
	 */
	private static function int( mixed $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}
}
