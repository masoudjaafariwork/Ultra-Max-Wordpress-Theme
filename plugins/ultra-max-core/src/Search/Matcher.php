<?php
/**
 * Product search that understands Persian spellings, codes, attributes and brands (SRCH-02, SRCH-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Search;

use UltraMax\Core\Shop\Filters;

/**
 * Replaces WordPress's search condition on product searches. Every typed
 * word must match, in any of its spellings (Normalizer), one of:
 *
 * - the title, short description or description;
 * - the product code (SKU) of the product or of one of its variations;
 * - a value of a product attribute, such as "256GB" or "red";
 * - the brand, by its Persian name or its Latin name ("novix" finds نوویکس).
 *
 * Results are still ordered by WordPress's relevance (title matches first).
 */
final class Matcher {

	/**
	 * Registers the hook.
	 */
	public static function register(): void {
		add_filter( 'posts_search', array( self::class, 'search' ), 20, 2 );
	}

	/**
	 * Whether a query is a storefront product search.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function applies( \WP_Query $query ): bool {
		if ( ! $query->is_search() || ( is_admin() && ! wp_doing_ajax() ) ) {
			return false;
		}
		$types = (array) $query->get( 'post_type' );
		return in_array( 'product', $types, true );
	}

	/**
	 * The search condition.
	 *
	 * @param mixed $search WordPress's condition.
	 * @param mixed $query  Query.
	 * @return mixed
	 */
	public static function search( mixed $search, mixed $query ): mixed {
		if ( ! $query instanceof \WP_Query || ! self::applies( $query ) ) {
			return $search;
		}
		$words = $query->get( 'search_terms' );
		if ( ! is_array( $words ) || array() === $words ) {
			return $search;
		}
		$clauses = array();
		foreach ( array_slice( $words, 0, 8 ) as $word ) {
			if ( is_string( $word ) && '' !== trim( $word ) ) {
				$clauses[] = self::clause( Normalizer::variants( ltrim( $word, '-' ) ) );
			}
		}
		if ( array() === $clauses ) {
			return $search;
		}
		global $wpdb;
		$sql = ' AND (' . implode( ' AND ', $clauses ) . ')';
		if ( ! is_user_logged_in() ) {
			$sql .= " AND ({$wpdb->posts}.post_password = '')";
		}
		return $sql;
	}

	/**
	 * The condition of one word.
	 *
	 * @param array $variants Spellings.
	 * @phpstan-param list<string> $variants
	 */
	public static function clause( array $variants ): string {
		global $wpdb;
		$lookup     = $wpdb->wc_product_meta_lookup ?? $wpdb->prefix . 'wc_product_meta_lookup';
		$taxonomies = array_values( array_filter( array_merge( array( 'product_brand' ), function_exists( 'wc_get_attribute_taxonomy_names' ) ? wc_get_attribute_taxonomy_names() : array() ), 'taxonomy_exists' ) );
		$text       = array();
		$codes      = array();
		$names      = array();
		$latin      = array();
		foreach ( $variants as $variant ) {
			$like    = '%' . $wpdb->esc_like( $variant ) . '%';
			$text[]  = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s OR {$wpdb->posts}.post_content LIKE %s", $like, $like, $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names.
			$codes[] = $wpdb->prepare( 'l.sku LIKE %s', $like );
			$names[] = $wpdb->prepare( 't.name LIKE %s', $like );
			$latin[] = $wpdb->prepare( 'tm.meta_value LIKE %s', $like );
		}
		$codes = implode( ' OR ', $codes );
		$parts = array(
			implode( ' OR ', $text ),
			// Product codes of the product and of its variations.
			"{$wpdb->posts}.ID IN ( SELECT l.product_id FROM {$lookup} l WHERE {$codes} )",
			"{$wpdb->posts}.ID IN ( SELECT v.post_parent FROM {$wpdb->posts} v INNER JOIN {$lookup} l ON l.product_id = v.ID WHERE v.post_type = 'product_variation' AND ( {$codes} ) )",
		);
		if ( array() !== $taxonomies ) {
			$in      = "'" . implode( "','", array_map( 'esc_sql', $taxonomies ) ) . "'";
			$parts[] = "{$wpdb->posts}.ID IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy IN ( {$in} ) AND ( " . implode( ' OR ', $names ) . ' ) )';
		}
		if ( taxonomy_exists( 'product_brand' ) ) {
			$parts[] = $wpdb->prepare( "{$wpdb->posts}.ID IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id INNER JOIN {$wpdb->termmeta} tm ON tm.term_id = tt.term_id AND tm.meta_key = %s WHERE tt.taxonomy = 'product_brand' AND ( ", Filters::LATIN_META ) . implode( ' OR ', $latin ) . ' ) )'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names.
		}
		return '(' . implode( ' OR ', $parts ) . ')';
	}
}
