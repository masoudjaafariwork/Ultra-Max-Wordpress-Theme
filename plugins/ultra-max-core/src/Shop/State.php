<?php
/**
 * The filters and sort order chosen on a product list (PLP-01, PLP-03, PLP-12, PLP-13).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

/**
 * An immutable snapshot of the list's address. It reads WooCommerce's own
 * parameters where WooCommerce has them, so other plugins understand the
 * same addresses: `filter_<attribute>`, `min_price`, `max_price`,
 * `rating_filter` and `orderby`. Ours are `brand`, `product_cat` (outside
 * a category page), `in_stock`, `on_sale` and `per_page`.
 *
 * Addresses are written in one fixed order with sorted, comma-joined
 * values, so one choice of filters always has one address (PLP-13):
 *
 *     /product-category/audio/?brand=lumo,novix&filter_color=black&in_stock=1
 */
final class State {

	public const BRAND    = 'brand';
	public const CATEGORY = 'product_cat';
	public const PER_PAGE = array( 12, 24, 36, 48, 72, 96 );

	/**
	 * Creates the snapshot.
	 *
	 * @param array<string, list<string>> $terms     Chosen term slugs by parameter: brand, product_cat or filter_<attribute>.
	 * @param bool                        $in_stock  Only products in stock.
	 * @param bool                        $on_sale   Only discounted products.
	 * @param int                         $min_price Lowest price, 0 for none.
	 * @param int                         $max_price Highest price, 0 for none.
	 * @param int                         $rating    Lowest average rating, 0 for none.
	 * @param string                      $orderby   Sort order, empty for the store's default.
	 * @param int                         $per_page  Products per page, 0 for the store's number.
	 */
	public function __construct(
		public readonly array $terms = array(),
		public readonly bool $in_stock = false,
		public readonly bool $on_sale = false,
		public readonly int $min_price = 0,
		public readonly int $max_price = 0,
		public readonly int $rating = 0,
		public readonly string $orderby = '',
		public readonly int $per_page = 0,
	) {}

	/**
	 * The state of the current request.
	 */
	public static function current(): self {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading public filter choices of a GET list.
		return self::from_query( wp_unslash( $_GET ) );
	}

	/**
	 * The taxonomy a parameter filters, or an empty string.
	 *
	 * @param string $param Parameter.
	 */
	public static function taxonomy( string $param ): string {
		if ( self::BRAND === $param ) {
			return taxonomy_exists( 'product_brand' ) ? 'product_brand' : '';
		}
		if ( self::CATEGORY === $param ) {
			return 'product_cat';
		}
		if ( str_starts_with( $param, 'filter_' ) ) {
			$taxonomy = 'pa_' . substr( $param, 7 );
			return taxonomy_exists( $taxonomy ) ? $taxonomy : '';
		}
		return '';
	}

	/**
	 * The parameter of a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy.
	 */
	public static function param( string $taxonomy ): string {
		return match ( true ) {
			'product_brand' === $taxonomy => self::BRAND,
			'product_cat' === $taxonomy   => self::CATEGORY,
			str_starts_with( $taxonomy, 'pa_' ) => 'filter_' . substr( $taxonomy, 3 ),
			default => '',
		};
	}

	/**
	 * Reads a state from query arguments. Values may be comma lists or
	 * arrays, as a form without JavaScript sends them.
	 *
	 * @param mixed $args Query arguments.
	 */
	public static function from_query( mixed $args ): self {
		$args  = is_array( $args ) ? $args : array();
		$terms = array();
		foreach ( $args as $key => $value ) {
			if ( ! is_string( $key ) || '' === self::taxonomy( $key ) ) {
				continue;
			}
			$slugs = self::list( $value );
			if ( self::CATEGORY === $key ) {
				$slugs = array_slice( $slugs, 0, 1 );
			}
			if ( array() !== $slugs ) {
				$terms[ $key ] = $slugs;
			}
		}
		ksort( $terms );
		$rating  = 0;
		$ratings = array_filter( array_map( 'absint', self::list( $args['rating_filter'] ?? '' ) ) );
		if ( array() !== $ratings ) {
			$rating = max( 1, min( 5, min( $ratings ) ) );
		}
		$orderby  = is_string( $args['orderby'] ?? null ) ? sanitize_key( $args['orderby'] ) : '';
		$per_page = is_scalar( $args['per_page'] ?? null ) ? absint( $args['per_page'] ) : 0;
		return new self(
			$terms,
			self::flag( $args['in_stock'] ?? '' ),
			self::flag( $args['on_sale'] ?? '' ),
			self::price( $args['min_price'] ?? '' ),
			self::price( $args['max_price'] ?? '' ),
			$rating,
			$orderby,
			in_array( $per_page, self::PER_PAGE, true ) ? $per_page : 0
		);
	}

	/**
	 * Clean, sorted, unique slugs from a comma list or an array.
	 *
	 * @param mixed $value Value.
	 * @return list<string>
	 */
	private static function list( mixed $value ): array {
		$parts = is_array( $value ) ? $value : explode( ',', is_scalar( $value ) ? (string) $value : '' );
		$out   = array();
		foreach ( $parts as $part ) {
			$slug = is_scalar( $part ) ? sanitize_title( (string) $part ) : '';
			if ( '' !== $slug ) {
				$out[ $slug ] = true;
			}
		}
		$out = array_keys( $out );
		sort( $out, SORT_STRING );
		return array_map( 'strval', $out );
	}

	/**
	 * Whether a switch parameter is on.
	 *
	 * @param mixed $value Value.
	 */
	private static function flag( mixed $value ): bool {
		$value = is_array( $value ) ? end( $value ) : $value;
		return in_array( $value, array( '1', 1, 'yes', 'on', true ), true );
	}

	/**
	 * A price parameter as a whole amount, 0 when empty.
	 *
	 * @param mixed $value Value.
	 */
	private static function price( mixed $value ): int {
		$value = is_array( $value ) ? end( $value ) : $value;
		return is_numeric( $value ) ? max( 0, (int) round( (float) $value ) ) : 0;
	}

	/**
	 * The query arguments of this state, in the fixed order.
	 *
	 * @return array<string, string>
	 */
	public function args(): array {
		$out = array();
		if ( isset( $this->terms[ self::CATEGORY ] ) ) {
			$out[ self::CATEGORY ] = implode( ',', $this->terms[ self::CATEGORY ] );
		}
		if ( isset( $this->terms[ self::BRAND ] ) ) {
			$out[ self::BRAND ] = implode( ',', $this->terms[ self::BRAND ] );
		}
		foreach ( $this->terms as $param => $slugs ) {
			if ( str_starts_with( $param, 'filter_' ) ) {
				$out[ $param ] = implode( ',', $slugs );
			}
		}
		if ( $this->in_stock ) {
			$out['in_stock'] = '1';
		}
		if ( $this->on_sale ) {
			$out['on_sale'] = '1';
		}
		if ( $this->min_price > 0 ) {
			$out['min_price'] = (string) $this->min_price;
		}
		if ( $this->max_price > 0 ) {
			$out['max_price'] = (string) $this->max_price;
		}
		if ( $this->rating > 0 ) {
			$out['rating_filter'] = implode( ',', range( $this->rating, 5 ) );
		}
		if ( '' !== $this->orderby ) {
			$out['orderby'] = $this->orderby;
		}
		if ( $this->per_page > 0 ) {
			$out['per_page'] = (string) $this->per_page;
		}
		return $out;
	}

	/**
	 * The address of this state on a list.
	 *
	 * @param string $base The list's address without filters.
	 */
	public function url( string $base ): string {
		$args = array();
		foreach ( $this->args() as $key => $value ) {
			// Slugs of Persian terms are stored percent-encoded; encode each value once and keep the commas readable.
			$args[ $key ] = implode( ',', array_map( static fn ( string $part ): string => rawurlencode( rawurldecode( $part ) ), explode( ',', $value ) ) );
		}
		return array() === $args ? $base : add_query_arg( $args, $base );
	}

	/**
	 * Number of chosen filters, for the badge of the phone filter button.
	 * The sort order is not a filter.
	 */
	public function count(): int {
		$count = 0;
		foreach ( $this->terms as $slugs ) {
			$count += count( $slugs );
		}
		return $count + (int) $this->in_stock + (int) $this->on_sale + (int) ( $this->min_price > 0 || $this->max_price > 0 ) + (int) ( $this->rating > 0 );
	}

	/**
	 * Whether a term is chosen.
	 *
	 * @param string $param Parameter.
	 * @param string $slug  Term slug.
	 */
	public function has( string $param, string $slug ): bool {
		return in_array( $slug, $this->terms[ $param ] ?? array(), true );
	}

	/**
	 * The state with a term added or removed.
	 *
	 * @param string $param Parameter.
	 * @param string $slug  Term slug.
	 */
	public function toggle( string $param, string $slug ): self {
		$terms = $this->terms;
		$list  = $terms[ $param ] ?? array();
		if ( in_array( $slug, $list, true ) ) {
			$list = array_values( array_diff( $list, array( $slug ) ) );
		} elseif ( self::CATEGORY === $param ) {
			$list = array( $slug );
		} else {
			$list[] = $slug;
			sort( $list, SORT_STRING );
		}
		if ( array() === $list ) {
			unset( $terms[ $param ] );
		} else {
			$terms[ $param ] = $list;
		}
		ksort( $terms );
		return new self( $terms, $this->in_stock, $this->on_sale, $this->min_price, $this->max_price, $this->rating, $this->orderby, $this->per_page );
	}

	/**
	 * The state without one filter: a parameter, or `in_stock`, `on_sale`,
	 * `price` or `rating`.
	 *
	 * @param string $what What to remove.
	 */
	public function without( string $what ): self {
		$terms = $this->terms;
		unset( $terms[ $what ] );
		return new self(
			$terms,
			'in_stock' === $what ? false : $this->in_stock,
			'on_sale' === $what ? false : $this->on_sale,
			'price' === $what ? 0 : $this->min_price,
			'price' === $what ? 0 : $this->max_price,
			'rating' === $what ? 0 : $this->rating,
			$this->orderby,
			$this->per_page
		);
	}

	/**
	 * The state with a switch set.
	 *
	 * @param 'in_stock'|'on_sale' $what Switch.
	 * @param bool                 $on   New value.
	 */
	public function with_flag( string $what, bool $on ): self {
		return new self(
			$this->terms,
			'in_stock' === $what ? $on : $this->in_stock,
			'on_sale' === $what ? $on : $this->on_sale,
			$this->min_price,
			$this->max_price,
			$this->rating,
			$this->orderby,
			$this->per_page
		);
	}

	/**
	 * The state with another sort order.
	 *
	 * @param string $orderby Sort order; empty for the default.
	 */
	public function sorted( string $orderby ): self {
		return new self( $this->terms, $this->in_stock, $this->on_sale, $this->min_price, $this->max_price, $this->rating, $orderby, $this->per_page );
	}

	/**
	 * The state without any filter, keeping the sort order.
	 */
	public function cleared(): self {
		return new self( orderby: $this->orderby, per_page: $this->per_page );
	}

	/**
	 * The state with another number of products per page.
	 *
	 * @param int $per_page Products per page; 0 for the store's number.
	 */
	public function paged_by( int $per_page ): self {
		return new self( $this->terms, $this->in_stock, $this->on_sale, $this->min_price, $this->max_price, $this->rating, $this->orderby, in_array( $per_page, self::PER_PAGE, true ) ? $per_page : 0 );
	}

	/**
	 * Whether no filter is chosen.
	 */
	public function is_empty(): bool {
		return 0 === $this->count();
	}

	/**
	 * Whether search engines may index this choice (PLP-13). A category or
	 * brand list with one chosen value, such as "audio, brand Lumo", is a
	 * page people search for. Two values, a price, a rating, a switch or a
	 * sort order make a combination with no value of its own.
	 *
	 * @param bool $single Whether one chosen value may be indexed at all.
	 */
	public function indexable( bool $single = true ): bool {
		if ( '' !== $this->orderby || $this->per_page > 0 || $this->in_stock || $this->on_sale || $this->min_price > 0 || $this->max_price > 0 || $this->rating > 0 ) {
			return false;
		}
		$values = 0;
		foreach ( $this->terms as $slugs ) {
			$values += count( $slugs );
		}
		return 0 === $values || ( $single && 1 === $values );
	}
}
