<?php
/**
 * The product list page (PLP-01 to PLP-13, BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Shop;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\ActiveFilters;
use UltraMax\Core\Lib\UI\ArchiveIntro;
use UltraMax\Core\Lib\UI\Box;
use UltraMax\Core\Lib\UI\Breadcrumbs;
use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\Drawer;
use UltraMax\Core\Lib\UI\EmptyState;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\LoadMore;
use UltraMax\Core\Lib\UI\Pagination;
use UltraMax\Core\Lib\UI\ProductGrid;
use UltraMax\Core\Lib\UI\SeoText;
use UltraMax\Core\Lib\UI\ShopArea;
use UltraMax\Core\Lib\UI\ShopBar;
use UltraMax\Core\Lib\UI\ShopFilters;
use UltraMax\Core\Lib\UI\SortBar;
use UltraMax\Core\Lib\UI\Subcategories;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Lib\UI\ViewToggle;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Format;

/**
 * One renderer for every piece of a product list. The default page, the
 * shop layout widgets (BLD-05) and their shortcodes all call these
 * methods, so a list designed with Elementor behaves exactly like the
 * built-in one.
 *
 * Every piece that changes with the filters is a named part of the shop
 * area, which the shop behaviour swaps in place (PLP-01).
 *
 * Off a product list, such as while a shop layout is edited in Elementor,
 * the pieces show a sample: the newest products of the shop.
 */
final class Archive {

	public const AREA          = 'um-shop';
	public const GRID          = 'um-shop-grid';
	public const SHEET_FILTERS = 'um-shop-filters-sheet';
	public const SHEET_SORT    = 'um-shop-sort-sheet';
	public const BOTTOM_META   = 'um_bottom_description';

	/**
	 * The sample query used off a product list.
	 *
	 * @var \WP_Query|null
	 */
	private static ?\WP_Query $sample = null;

	/**
	 * The query whose products the list shows.
	 */
	public static function query(): \WP_Query {
		global $wp_query;
		if ( Scope::is_list() && $wp_query instanceof \WP_Query ) {
			return $wp_query;
		}
		if ( null === self::$sample ) {
			self::$sample = new \WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => 8,
					'has_password'   => false,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Only while designing.
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => array( 'exclude-from-catalog' ),
							'operator' => 'NOT IN',
						),
					),
				)
			);
		}
		return self::$sample;
	}

	/**
	 * The posts of the list's query.
	 *
	 * @return list<\WP_Post|int>
	 */
	private static function posts(): array {
		$posts = self::query()->posts;
		return is_array( $posts ) ? array_values( $posts ) : array();
	}

	/**
	 * A whole number from a value WordPress returns as mixed.
	 *
	 * @param mixed $value Value.
	 */
	private static function number( mixed $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Products per page of the list.
	 */
	private static function per_page(): int {
		$query = self::query();
		$per   = self::number( $query->get( 'posts_per_page' ) );
		return $per > 0 ? $per : max( 1, count( self::posts() ) );
	}

	/**
	 * The current page number.
	 */
	private static function paged(): int {
		return max( 1, self::number( self::query()->get( 'paged' ) ) );
	}

	/**
	 * "1,248 products".
	 */
	public static function count_text(): string {
		$total = Scope::is_list() ? self::query()->found_posts : count( self::posts() );
		/* translators: %s: number of products. */
		return sprintf( _n( '%s product', '%s products', $total, 'ultra-max-core' ), Format::number( $total ) );
	}

	/**
	 * The whole page: a layout designed with Elementor (Pro first, then
	 * ours) or the built-in arrangement, inside the shop area, with the
	 * phone sheets.
	 */
	public static function page(): Html {
		ob_start();
		$designed = function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'archive' );
		$left     = array(
			'desktop' => ! $designed,
			'tablet'  => ! $designed,
			'mobile'  => ! $designed,
		);
		if ( ! $designed && Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters(
				'ultra_max_do_location',
				array(
					'desktop' => false,
					'tablet'  => false,
					'mobile'  => false,
				),
				'shop-archive'
			);
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$layout = Html::wp( (string) ob_get_clean() );
		$parts  = array( $layout );
		if ( in_array( true, $left, true ) ) {
			$parts[] = self::arrangement( UI::visibility( $left ) );
		}
		$parts[] = self::sheets();
		return ShopArea::render(
			array(
				'id'       => self::AREA,
				'children' => $parts,
				'status'   => '',
			)
		);
	}

	/**
	 * The built-in arrangement of design/plp.html.
	 *
	 * @param string $classes Visibility classes for the devices it covers.
	 */
	public static function arrangement( string $classes = '' ): Html {
		$aside = self::filters( 'sidebar' );
		$main  = array(
			self::sorting(),
			self::bar(),
			self::active( 'lg:hidden' ),
			self::products(),
			self::more(),
			self::description( 'bottom' ),
		);
		$body  = $aside->is_empty()
			? Html::trusted( sprintf( '<div class="grid min-w-0 gap-3">%s</div>', Html::from( $main ) ) )
			: Html::trusted(
				sprintf(
					'<div class="grid items-start gap-5 lg:grid-cols-[var(--um-sidebar,280px)_minmax(0,1fr)]"><aside class="sticky top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+16px)] max-h-[calc(100dvh-120px)] overflow-y-auto max-lg:hidden">%1$s</aside><div class="grid min-w-0 gap-3">%2$s</div></div>',
					$aside,
					Html::from( $main )
				)
			);
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><div class="mb-5 grid gap-4">%2$s%3$s</div>%4$s</div>',
				esc_attr( trim( 'grid min-w-0 ' . $classes ) ),
				self::intro(),
				self::subcategories(),
				$body
			)
		);
	}

	/**
	 * Breadcrumbs from WooCommerce's trail.
	 */
	public static function breadcrumbs(): Html {
		if ( ! class_exists( '\WC_Breadcrumb' ) ) {
			return Html::none();
		}
		$trail = new \WC_Breadcrumb();
		$trail->add_crumb( _x( 'Home', 'breadcrumb', 'ultra-max-core' ), home_url( '/' ) );
		$items = array();
		foreach ( $trail->generate() as $crumb ) {
			if ( is_array( $crumb ) && is_string( $crumb[0] ?? null ) ) {
				$item = array( 'label' => wp_strip_all_tags( $crumb[0] ) );
				if ( is_string( $crumb[1] ?? null ) && '' !== $crumb[1] ) {
					$item['href'] = $crumb[1];
				}
				$items[] = $item;
			}
		}
		if ( array() !== $items ) {
			unset( $items[ count( $items ) - 1 ]['href'] );
		}
		return Breadcrumbs::render(
			array(
				'label' => __( 'Breadcrumbs', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The title of the list.
	 */
	public static function title(): string {
		$scope = Scope::current();
		if ( 'search' === $scope->kind ) {
			/* translators: %s: search words. */
			return sprintf( __( 'Search results for "%s"', 'ultra-max-core' ), $scope->search );
		}
		$term = $scope->term();
		if ( null !== $term ) {
			return $term->name;
		}
		if ( ! Scope::is_list() ) {
			return __( 'Shop', 'ultra-max-core' );
		}
		$page = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		return $page > 0 ? get_the_title( $page ) : __( 'Shop', 'ultra-max-core' );
	}

	/**
	 * The top description: the term's description, or the shop page's text.
	 * Only on the first page, as WooCommerce does.
	 */
	public static function top_description(): Html {
		if ( self::paged() > 1 ) {
			return Html::none();
		}
		$term = Scope::current()->term();
		if ( null !== $term ) {
			return Html::wp( wc_format_content( wp_kses_post( $term->description ) ) );
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$page = get_post( wc_get_page_id( 'shop' ) );
			if ( $page instanceof \WP_Post && '' !== trim( $page->post_content ) ) {
				return Html::wp( wc_format_content( wp_kses_post( $page->post_content ) ) );
			}
		}
		return Html::none();
	}

	/**
	 * Breadcrumbs, title, number of products and the top description; on a
	 * brand page with the brand's logo (PLP-10, PLP-11).
	 *
	 * @param bool $crumbs Whether to show breadcrumbs.
	 */
	public static function intro( bool $crumbs = true ): Html {
		$term  = Scope::current()->term();
		$props = array(
			'title'       => self::title(),
			'breadcrumbs' => $crumbs ? self::breadcrumbs() : Html::none(),
			'description' => self::top_description(),
			'meta'        => self::count_text(),
		);
		if ( null !== $term && 'product_brand' === $term->taxonomy ) {
			$props['variant'] = 'brand';
			$logo             = self::number( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
			if ( $logo > 0 ) {
				$props['logo'] = Image::render(
					array(
						'id'       => $logo,
						'size'     => 'medium',
						'priority' => true,
					)
				);
			}
		}
		return ShopArea::part( 'intro', ArchiveIntro::render( $props ) );
	}

	/**
	 * The child categories with pictures (PLP-09): of the category shown,
	 * or the top categories on the shop page.
	 */
	public static function subcategories(): Html {
		if ( ! Store::bool( 'shop_subcategories' ) ) {
			return Html::none();
		}
		$scope  = Scope::current();
		$parent = -1;
		if ( 'term' === $scope->kind && 'product_cat' === $scope->taxonomy ) {
			$parent = $scope->term_id;
		} elseif ( 'shop' === $scope->kind ) {
			$parent = 0;
		}
		if ( $parent < 0 ) {
			return Html::none();
		}
		$terms  = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent,
				'hide_empty' => false,
				'orderby'    => 'menu_order', // WooCommerce's own category order.
				'number'     => 24,
			)
		);
		$counts = Facets::for( $scope )['terms']['product_cat'] ?? array();
		$items  = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( ! $term instanceof \WP_Term || ( $counts[ $term->term_id ] ?? 0 ) < 1 ) {
				continue;
			}
			$link  = get_term_link( $term );
			$item  = array(
				'label' => $term->name,
				'href'  => is_string( $link ) ? $link : '',
			);
			$image = self::number( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
			if ( $image > 0 ) {
				$item['image'] = Image::render(
					array(
						'id'   => $image,
						'size' => 'thumbnail',
					)
				);
			}
			$items[] = $item;
		}
		return Subcategories::render(
			array(
				'label' => __( 'Subcategories', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The filter panel (PLP-01, PLP-02): the desktop sidebar or the phone
	 * sheet's form.
	 *
	 * @param 'sidebar'|'sheet' $variant Where it shows.
	 */
	public static function filters( string $variant = 'sidebar' ): Html {
		$scope  = Scope::current();
		$state  = State::current();
		$groups = Filters::groups( $scope, $state );
		if ( array() === $groups ) {
			return Html::none();
		}
		$props = array(
			'id'      => 'sheet' === $variant ? 'um-shop-filters-m' : 'um-shop-filters',
			'action'  => $scope->url(),
			'hidden'  => array_merge( $scope->hidden(), '' !== $state->orderby ? array( 'orderby' => $state->orderby ) : array() ),
			'title'   => __( 'Filters', 'ultra-max-core' ),
			'groups'  => $groups,
			'apply'   => __( 'Apply filters', 'ultra-max-core' ),
			'variant' => $variant,
		);
		if ( ! $state->is_empty() ) {
			$props['clear'] = array(
				'label' => __( 'Remove all', 'ultra-max-core' ),
				'href'  => $state->cleared()->url( $scope->url() ),
			);
			if ( 'sidebar' === $variant ) {
				$props['active'] = self::chips();
			}
		}
		$form = ShopFilters::render( $props );
		if ( 'sheet' === $variant ) {
			return ShopArea::part( 'filters-sheet', $form );
		}
		return ShopArea::part(
			'filters',
			Box::render(
				array(
					'tag'      => 'div',
					'padding'  => 'md',
					'children' => $form,
				)
			)
		);
	}

	/**
	 * The chosen filters as chips.
	 */
	private static function chips(): Html {
		$scope = Scope::current();
		$state = State::current();
		return ActiveFilters::render(
			array(
				'label' => __( 'Chosen filters', 'ultra-max-core' ),
				'items' => Filters::chips( $scope, $state ),
				'clear' => array(
					'label' => __( 'Remove all', 'ultra-max-core' ),
					'href'  => $state->cleared()->url( $scope->url() ),
				),
			)
		);
	}

	/**
	 * The chosen filters as a part of their own (PLP-12).
	 *
	 * @param string $classes Layout classes.
	 */
	public static function active( string $classes = '' ): Html {
		return ShopArea::part( 'active', self::chips(), $classes );
	}

	/**
	 * The sort choices as links.
	 *
	 * @return list<array{label: string, href: string, current: bool}>
	 */
	private static function sort_options(): array {
		$scope   = Scope::current();
		$state   = State::current();
		$current = $state->orderby;
		$options = array();
		foreach ( Query::sorts() as $key => $label ) {
			$options[] = array(
				'label'   => $label,
				'href'    => $state->sorted( $key )->url( $scope->url() ),
				'current' => $key === $current,
			);
		}
		return $options;
	}

	/**
	 * The desktop sort bar with the number of products and the view switch
	 * (PLP-03, PLP-05).
	 *
	 * @param bool $count Whether to show the number of products.
	 * @param bool $view  Whether to show the view switch.
	 */
	public static function sorting( bool $count = true, bool $view = true ): Html {
		$props = array(
			'label'   => __( 'Sort by', 'ultra-max-core' ),
			'options' => self::sort_options(),
			'class'   => 'max-lg:hidden',
		);
		if ( $count ) {
			$props['count'] = self::count_text();
		}
		if ( $view && Store::bool( 'shop_view_toggle' ) ) {
			$props['end'] = self::view_toggle();
		}
		return ShopArea::part( 'sort', SortBar::render( $props ) );
	}

	/**
	 * The grid and row switch (PLP-05).
	 */
	public static function view_toggle(): Html {
		return ViewToggle::render(
			array(
				'controls' => self::GRID,
				'label'    => __( 'Show products as', 'ultra-max-core' ),
				'grid'     => __( 'Grid', 'ultra-max-core' ),
				'list'     => __( 'Rows', 'ultra-max-core' ),
				'current'  => 'list' === Store::string( 'shop_view' ) ? 'list' : 'grid',
			)
		);
	}

	/**
	 * The number of products.
	 */
	public static function result_count(): Html {
		return ShopArea::part( 'count', Html::trusted( sprintf( '<span data-um-shop-count>%s</span>', esc_html( self::count_text() ) ) ) );
	}

	/**
	 * Products per page choices.
	 *
	 * @param array $choices Numbers.
	 * @phpstan-param list<int> $choices
	 */
	public static function per_page_links( array $choices ): Html {
		$scope   = Scope::current();
		$state   = State::current();
		$current = self::per_page();
		$options = array();
		foreach ( $choices as $number ) {
			$options[] = array(
				'label'   => Format::number( $number ),
				'href'    => $state->paged_by( $number )->url( $scope->url() ),
				'current' => $number === $current,
			);
		}
		return ShopArea::part(
			'per-page',
			SortBar::render(
				array(
					'label'   => __( 'Products per page', 'ultra-max-core' ),
					'options' => $options,
				)
			)
		);
	}

	/**
	 * The phone and tablet bar (PLP-06).
	 *
	 * @param bool $filter Whether to show the filter button.
	 * @param bool $sort   Whether to show the sort button.
	 */
	public static function bar( bool $filter = true, bool $sort = true ): Html {
		$state = State::current();
		$props = array( 'count' => self::count_text() );
		if ( $filter && array() !== Filters::groups( Scope::current(), $state ) ) {
			$props['filter'] = array(
				'label'       => __( 'Filter', 'ultra-max-core' ),
				'open'        => self::SHEET_FILTERS,
				'count'       => $state->count(),
				/* translators: %s: number of chosen filters. */
				'count_label' => sprintf( _n( '%s filter chosen', '%s filters chosen', $state->count(), 'ultra-max-core' ), Format::number( $state->count() ) ),
			);
		}
		if ( $sort ) {
			$sorts         = Query::sorts();
			$props['sort'] = array(
				'label'   => __( 'Sort by', 'ultra-max-core' ),
				'open'    => self::SHEET_SORT,
				'current' => $sorts[ $state->orderby ] ?? $sorts[''],
			);
		}
		return ShopArea::part( 'bar', ShopBar::render( $props ) );
	}

	/**
	 * The card design of the list: a built-in design or a card layout.
	 */
	public static function design(): string {
		$design = Store::string( 'shop_card' );
		return '' === $design ? 'classic' : $design;
	}

	/**
	 * The products (PLP-04, PLP-05, PLP-07, PLP-08).
	 *
	 * @param array $options Design and columns; the settings when missing.
	 * @phpstan-param array{design?: string, columns?: int, columns_tablet?: int, columns_mobile?: int} $options
	 */
	public static function products( array $options = array() ): Html {
		$query  = self::query();
		$design = $options['design'] ?? self::design();
		$cards  = array();
		$images = array();
		$items  = array();
		foreach ( self::posts() as $post ) {
			$product = wc_get_product( $post );
			if ( $product instanceof \WC_Product && $product->is_visible() ) {
				$items[]  = $product;
				$images[] = (int) $product->get_image_id();
				$gallery  = $product->get_gallery_image_ids();
				$images[] = isset( $gallery[0] ) ? (int) $gallery[0] : 0;
			}
		}
		$images = array_values( array_filter( array_unique( $images ) ) );
		if ( array() !== $images ) {
			// One query for every picture of the page instead of one per card.
			_prime_post_caches( $images );
		}
		$index = 0;
		foreach ( $items as $product ) {
			$cards[] = array(
				'card' => Cards::card(
					$product,
					$design,
					array(
						'priority' => $index < 2 && 1 === self::paged(),
						'rows'     => true,
					)
				),
			);
			++$index;
		}
		if ( array() === $cards ) {
			return ShopArea::part( 'products', self::nothing() );
		}
		$mobile = Store::string( 'shop_mobile' );
		return ShopArea::part(
			'products',
			ProductGrid::render(
				array(
					'id'      => self::GRID,
					'label'   => self::title(),
					'items'   => $cards,
					'columns' => array(
						'desktop' => $options['columns'] ?? max( 3, Store::int( 'shop_columns' ) ),
						'tablet'  => $options['columns_tablet'] ?? max( 2, Store::int( 'shop_columns_tablet' ) ),
						'mobile'  => $options['columns_mobile'] ?? ( 'one' === $mobile ? 1 : 2 ),
					),
					'lined'   => 'classic' === $design,
					'view'    => 'list' === Store::string( 'shop_view' ) ? 'list' : 'grid',
					'mobile'  => 'list' === $mobile || '' === $mobile ? 'list' : 'cards',
				)
			)
		);
	}

	/**
	 * The message of an empty list, with a way out.
	 */
	private static function nothing(): Html {
		$scope = Scope::current();
		$state = State::current();
		if ( ! $state->is_empty() ) {
			return EmptyState::render(
				array(
					'icon'     => 'filter',
					'title'    => __( 'No product matches these filters', 'ultra-max-core' ),
					'text'     => __( 'Remove a filter or two to see more products.', 'ultra-max-core' ),
					'children' => Button::render(
						array(
							'href'     => $state->cleared()->url( $scope->url() ),
							'variant'  => 'outline',
							'children' => __( 'Remove all filters', 'ultra-max-core' ),
							'attrs'    => array(
								'data-um-shop-link' => true,
								'rel'               => 'nofollow',
							),
						)
					),
				)
			);
		}
		return EmptyState::render(
			array(
				'icon'  => 'search' === $scope->kind ? 'search' : 'box',
				'title' => 'search' === $scope->kind ? __( 'Nothing matched your search', 'ultra-max-core' ) : __( 'No products here yet', 'ultra-max-core' ),
				'text'  => 'search' === $scope->kind ? __( 'Check the spelling or try fewer or more general words.', 'ultra-max-core' ) : '',
			)
		);
	}

	/**
	 * The end of the list: count, "show more" and page links (PLP-04).
	 */
	public static function more(): Html {
		$query = self::query();
		$total = Scope::is_list() ? $query->found_posts : count( self::posts() );
		$pages = Scope::is_list() ? $query->max_num_pages : 1;
		$paged = self::paged();
		$per   = self::per_page();
		$mode  = Store::string( 'shop_pagination' );
		$big   = 999999999;
		$props = array(
			'mode'       => in_array( $mode, array( 'pages', 'more', 'infinite' ), true ) ? $mode : 'more',
			'shown'      => min( $total, $paged * $per ),
			'total'      => $total,
			/* translators: 1: products shown, 2: all products. */
			'status'     => sprintf( __( '%1$s of %2$s products shown', 'ultra-max-core' ), Format::number( min( $total, $paged * $per ) ), Format::number( $total ) ),
			'label'      => __( 'Show more products', 'ultra-max-core' ),
			'pagination' => Pagination::render(
				array(
					'label'    => __( 'Pages', 'ultra-max-core' ),
					'current'  => $paged,
					'total'    => $pages,
					'base'     => str_replace( (string) $big, '%#%', get_pagenum_link( $big, false ) ),
					'first'    => get_pagenum_link( 1, false ),
					'previous' => __( 'Previous', 'ultra-max-core' ),
					'next'     => __( 'Next', 'ultra-max-core' ),
					'class'    => 'mt-0',
				)
			),
		);
		if ( $paged < $pages ) {
			$props['next'] = get_pagenum_link( $paged + 1, false );
		}
		return ShopArea::part( 'more', LoadMore::render( $props ) );
	}

	/**
	 * The top or bottom description (PLP-10). The bottom text is written for
	 * each category, tag or brand in its edit screen.
	 *
	 * @param 'top'|'bottom' $where Which one.
	 */
	public static function description( string $where = 'bottom' ): Html {
		if ( 'top' === $where ) {
			return self::top_description();
		}
		$term = Scope::current()->term();
		if ( null === $term || self::paged() > 1 ) {
			return Html::none();
		}
		$text = get_term_meta( $term->term_id, self::BOTTOM_META, true );
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			return Html::none();
		}
		return SeoText::render(
			array(
				'id'       => 'um-shop-seo',
				'children' => Html::wp( wc_format_content( wp_kses_post( $text ) ) ),
				'more'     => __( 'Show more', 'ultra-max-core' ),
				'less'     => __( 'Show less', 'ultra-max-core' ),
			)
		);
	}

	/**
	 * The phone sheets for filters and sorting (PLP-06).
	 */
	public static function sheets(): Html {
		$scope = Scope::current();
		$state = State::current();
		$out   = array();
		$form  = self::filters( 'sheet' );
		if ( ! $form->is_empty() ) {
			$out[] = Drawer::render(
				array(
					'id'       => self::SHEET_FILTERS,
					'title'    => __( 'Filters', 'ultra-max-core' ),
					'side'     => 'bottom',
					'class'    => 'lg:hidden',
					'children' => $form,
					'footer'   => ShopArea::part(
						'sheet-foot',
						Html::from(
							array(
								Button::render(
									array(
										'children' => self::count_text(),
										'block'    => true,
										'attrs'    => array( 'data-um-close' => true ),
									)
								),
								$state->is_empty() ? Html::none() : Button::render(
									array(
										'href'     => $state->cleared()->url( $scope->url() ),
										'variant'  => 'ghost',
										'children' => __( 'Remove filters', 'ultra-max-core' ),
										'attrs'    => array(
											'data-um-shop-link' => true,
											'rel' => 'nofollow',
										),
									)
								),
							)
						),
						'flex w-full items-center gap-2'
					),
				)
			);
		}
		$out[] = Drawer::render(
			array(
				'id'       => self::SHEET_SORT,
				'title'    => __( 'Sort by', 'ultra-max-core' ),
				'side'     => 'bottom',
				'class'    => 'lg:hidden',
				'children' => ShopArea::part(
					'sort-sheet',
					SortBar::render(
						array(
							'label'   => __( 'Sort by', 'ultra-max-core' ),
							'options' => self::sort_options(),
							'variant' => 'list',
						)
					)
				),
			)
		);
		return Html::from( $out );
	}
}
