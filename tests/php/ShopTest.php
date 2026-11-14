<?php
/**
 * Tests of product lists and search that need no WooCommerce (PLP-01 to PLP-13, SRCH-01 to SRCH-03, BLD-05).
 *
 * WooCommerce's own flows (filters on real products, private products
 * never leaking) are tested in the browser on the test site: see
 * tests/e2e/phase4-listing.spec.ts.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Builder\Context;
use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Rules;
use UltraMax\Core\Builder\Types;
use UltraMax\Core\Lib\UI\ActiveFilters;
use UltraMax\Core\Lib\UI\Drawer;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\LoadMore;
use UltraMax\Core\Lib\UI\ProductCard;
use UltraMax\Core\Lib\UI\ProductGrid;
use UltraMax\Core\Lib\UI\SearchSuggest;
use UltraMax\Core\Lib\UI\ShopArea;
use UltraMax\Core\Lib\UI\ShopFilters;
use UltraMax\Core\Lib\UI\SortBar;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Search\Live;
use UltraMax\Core\Search\Matcher;
use UltraMax\Core\Search\Normalizer;
use UltraMax\Core\Search\Search;
use UltraMax\Core\Shop\Filters;
use UltraMax\Core\Shop\Shop;
use UltraMax\Core\Shop\State;
use UltraMax\Core\Shop\Terms;

/**
 * Filter state, tidy addresses, indexing rules, Persian matching and the
 * list components.
 */
final class ShopTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			register_taxonomy( 'product_cat', 'product', array( 'hierarchical' => true ) );
		}
		Filters::reset();
	}

	public function test_state_reads_comma_lists_and_form_arrays_the_same(): void {
		$commas = State::from_query(
			array(
				'product_cat'   => 'b,a',
				'min_price'     => '1000',
				'max_price'     => 'x',
				'in_stock'      => '1',
				'rating_filter' => '5,4',
				'orderby'       => 'price-desc',
				'per_page'      => '48',
			)
		);
		$this->assertSame( array( 'product_cat' => array( 'a' ) ), $commas->terms, 'One category at a time: the first of the sorted list.' );
		$this->assertSame( 1000, $commas->min_price );
		$this->assertSame( 0, $commas->max_price );
		$this->assertTrue( $commas->in_stock );
		$this->assertSame( 4, $commas->rating );
		$this->assertSame( 48, $commas->per_page );

		$this->assertSame( 0, State::from_query( array( 'per_page' => '1000' ) )->per_page, 'Only the offered page sizes.' );
		$this->assertSame( array(), State::from_query( array( 'filter_nothing' => 'a' ) )->terms, 'Unknown attributes are ignored.' );
		$this->assertSame( array(), State::from_query( 'not an array' )->terms );
	}

	public function test_one_choice_has_one_address_in_a_fixed_order(): void {
		$state = State::from_query(
			array(
				'orderby'       => 'date',
				'rating_filter' => '4',
				'on_sale'       => 'yes',
				'product_cat'   => 'audio',
			)
		);
		$this->assertSame(
			array(
				'product_cat'   => 'audio',
				'on_sale'       => '1',
				'rating_filter' => '4,5',
				'orderby'       => 'date',
			),
			$state->args()
		);
		$this->assertSame( 'https://example.org/shop/?product_cat=audio&on_sale=1&rating_filter=4,5&orderby=date', $state->url( 'https://example.org/shop/' ) );
	}

	public function test_persian_slugs_are_encoded_once(): void {
		$state = State::from_query( array( 'product_cat' => 'صوتی' ) );
		$url   = $state->url( 'https://example.org/shop/' );
		$this->assertStringContainsString( 'product_cat=%D8%B5%D9%88%D8%AA%DB%8C', $url );
		$this->assertStringNotContainsString( '%25', $url, 'No double encoding.' );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $back );
		$this->assertSame( $state->terms, State::from_query( $back )->terms, 'The address reads back to the same choice.' );
	}

	public function test_state_changes_keep_everything_else(): void {
		$state = State::from_query(
			array(
				'product_cat' => 'audio',
				'in_stock'    => '1',
				'min_price'   => '10',
				'orderby'     => 'price',
			)
		);
		$this->assertSame( 3, $state->count() );
		$this->assertFalse( $state->without( 'in_stock' )->in_stock );
		$this->assertSame( 0, $state->without( 'price' )->min_price );
		$this->assertSame( 'price', $state->without( 'price' )->orderby, 'Removing a filter keeps the sort order.' );
		$this->assertSame( array( 'shoes' ), $state->toggle( 'product_cat', 'shoes' )->terms['product_cat'], 'A category replaces the other.' );
		$this->assertArrayNotHasKey( 'product_cat', $state->toggle( 'product_cat', 'audio' )->terms );
		$this->assertTrue( $state->cleared()->is_empty() );
		$this->assertSame( 'price', $state->cleared()->orderby );
		$this->assertSame( 'date', $state->sorted( 'date' )->args()['orderby'] );
		$this->assertSame( 24, $state->paged_by( 24 )->per_page );
		$this->assertSame( 0, $state->paged_by( 25 )->per_page );
	}

	public function test_only_lists_with_one_chosen_value_may_be_indexed(): void {
		$this->assertTrue( State::from_query( array() )->indexable() );
		$this->assertTrue( State::from_query( array( 'product_cat' => 'audio' ) )->indexable() );
		$this->assertFalse( State::from_query( array( 'product_cat' => 'audio' ) )->indexable( false ), 'The setting can forbid even one value.' );
		$this->assertFalse( State::from_query( array( 'product_cat' => 'audio' ) )->toggle( 'product_cat', 'b' )->with_flag( 'in_stock', true )->indexable() );
		$this->assertFalse( State::from_query( array( 'orderby' => 'price' ) )->indexable(), 'A sort order is not a page of its own.' );
		$this->assertFalse( State::from_query( array( 'min_price' => '5' ) )->indexable() );
		$this->assertFalse( State::from_query( array( 'per_page' => '48' ) )->indexable() );
	}

	public function test_persian_spellings_meet_in_one_form(): void {
		$this->assertSame( 'کیف 256 گیگ', Normalizer::normalize( '  كيف ۲۵۶  گیگ ' ) );
		$this->assertSame( 'می شود', Normalizer::normalize( "می\u{200C}شود" ) );
		$this->assertSame( 'novix', Normalizer::normalize( 'NOVIX' ) );

		$variants = Normalizer::variants( 'کیف۲۵۶' );
		$this->assertContains( 'کیف256', $variants, 'Latin digits.' );
		$this->assertContains( 'کیف۲۵۶', $variants, 'Persian digits.' );
		$this->assertContains( 'كيف256', $variants, 'Arabic letters, as older data stores them.' );
		$this->assertLessThanOrEqual( 8, count( $variants ) );
		$this->assertContains( "می\u{200C}شود", Normalizer::variants( "می\u{200C}شود" ) );
		$this->assertContains( 'میشود', Normalizer::variants( "می\u{200C}شود" ), 'Joined, without the non-joiner.' );
		$this->assertSame( array(), Normalizer::variants( '  ' ) );
	}

	public function test_a_search_word_matches_codes_attributes_and_brands(): void {
		register_taxonomy( 'product_brand', 'product' );
		$sql = Matcher::clause( Normalizer::variants( 'UM-001' ) );
		$this->assertStringContainsString( 'post_title LIKE', $sql );
		$this->assertStringContainsString( 'l.sku LIKE', $sql, 'Product codes.' );
		$this->assertStringContainsString( "post_type = 'product_variation'", $sql, 'Codes of variations.' );
		$this->assertStringContainsString( Filters::LATIN_META, $sql, 'Brand Latin names.' );
		$this->assertStringContainsString( 'product_brand', $sql, 'Brand names.' );
		unregister_taxonomy( 'product_brand' );
	}

	public function test_only_storefront_product_searches_are_changed(): void {
		$query = new WP_Query();
		$query->parse_query(
			array(
				's'         => 'x',
				'post_type' => 'product',
			)
		);
		$this->assertTrue( Matcher::applies( $query ) );
		$posts = new WP_Query();
		$posts->parse_query( array( 's' => 'x' ) );
		$this->assertFalse( Matcher::applies( $posts ), 'Blog searches keep WordPress\'s own condition.' );
		$this->assertSame( 'KEEP', Matcher::search( 'KEEP', $posts ) );
	}

	public function test_price_steps_are_round(): void {
		$this->assertSame( 1, Filters::step( 50 ) );
		$this->assertSame( 1000, Filters::step( 100000 ) );
		$this->assertSame( 500000, Filters::step( 45000000 ) );
		$this->assertSame( 200, Filters::step( 15000 ) );
	}

	public function test_categories_inherit_the_filters_of_their_nearest_parent(): void {
		$top   = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$child = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'parent'   => $top,
			)
		);
		$leaf  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'parent'   => $child,
			)
		);
		$scope = static fn ( int $id ): \UltraMax\Core\Shop\Scope => new \UltraMax\Core\Shop\Scope( 'term', 'product_cat', $id );

		$this->assertSame( array( 'in_stock', 'on_sale', 'category', 'brand', 'price', 'rating' ), Filters::keys( $scope( $leaf ) ), 'The store\'s filters by default.' );
		update_term_meta( $top, Filters::TERM_META, array( 'price', 'brand' ) );
		$this->assertSame( array( 'price', 'brand' ), Filters::keys( $scope( $leaf ) ), 'The nearest parent that chose.' );
		update_term_meta( $child, Filters::TERM_META, array( 'in_stock' ) );
		$this->assertSame( array( 'in_stock' ), Filters::keys( $scope( $leaf ) ) );
		$this->assertSame( array( 'price', 'brand' ), Filters::keys( $scope( $top ) ) );
		$this->assertNotContains( 'brand', Filters::keys( new \UltraMax\Core\Shop\Scope( 'term', 'product_brand', 1 ) ), 'A brand page has no brand filter.' );
	}

	public function test_the_shop_turns_on_its_layout_types(): void {
		$types = Shop::types( Types::all() );
		$this->assertTrue( $types['shop-archive']['ready'] );
		$this->assertTrue( $types['product-card']['ready'] );
		$this->assertFalse( $types['cart']['ready'], 'Other store types wait for their widgets.' );
		$this->assertSame( 'shop-archive', Types::all()['shop-archive']['location'], 'Its own place, never the blog archive.' );
		$this->assertSame( array( array( 'target' => 'all' ) ), Layouts::defaults( 'shop-archive' )['include'] );
		$this->assertSame( array(), Layouts::defaults( 'product-card' )['include'] );
	}

	public function test_a_product_search_counts_as_a_shop_page(): void {
		$rule = array(
			'target' => 'shop',
			'values' => array(),
		);
		$this->assertTrue( Rules::matches( $rule, new Context( 'search', 'product' ) ) );
		$this->assertFalse( Rules::matches( $rule, new Context( 'search' ) ) );
		$this->assertTrue( Rules::matches( $rule, new Context( 'archive', 'product' ) ) );
	}

	public function test_colour_swatch_attributes_are_remembered(): void {
		Terms::set_swatch( 'pa_color', true );
		Terms::set_swatch( 'pa_color', true );
		$this->assertSame( array( 'pa_color' ), Terms::swatch_taxonomies() );
		Terms::set_swatch( 'pa_color', false );
		$this->assertSame( array(), Terms::swatch_taxonomies() );
	}

	public function test_filter_panel_is_a_get_form_with_named_fields(): void {
		$html = (string) ShopFilters::render(
			array(
				'id'     => 'f',
				'action' => 'https://example.org/shop/',
				'title'  => 'Filters',
				'apply'  => 'Apply',
				'hidden' => array( 's' => 'x' ),
				'groups' => array(
					array(
						'type'    => 'toggle',
						'key'     => 'in_stock',
						'name'    => 'in_stock',
						'label'   => 'Available',
						'checked' => true,
					),
					array(
						'type'   => 'checks',
						'key'    => 'brand',
						'name'   => 'brand',
						'label'  => 'Brand',
						'search' => 'Search brands',
						'items'  => array(
							array(
								'value'   => 'novix',
								'label'   => '<b>Novix</b>',
								'hint'    => 'NVX',
								'checked' => true,
							),
						),
					),
					array(
						'type'   => 'checks',
						'key'    => 'filter_color',
						'label'  => 'Colour',
						'swatch' => true,
						'items'  => array(
							array(
								'value' => 'black',
								'label' => 'Black',
								'color' => '#000',
							),
						),
					),
					array(
						'type'  => 'choice',
						'key'   => 'rating',
						'name'  => 'rating_filter',
						'label' => 'Rating',
						'items' => array(
							array(
								'value' => '4,5',
								'label' => '4 and up',
							),
						),
					),
					array(
						'type'  => 'range',
						'key'   => 'price',
						'label' => 'Price',
						'range' => array(
							'min'        => 0,
							'max'        => 1000,
							'from'       => 100,
							'to'         => 1000,
							'step'       => 10,
							'unit'       => 'T',
							'from_label' => 'From',
							'to_label'   => 'To',
							'min_name'   => 'min_price',
							'max_name'   => 'max_price',
						),
					),
				),
			)
		);
		$this->assertStringContainsString( 'method="get"', $html );
		$this->assertStringContainsString( 'data-um-shop-form', $html );
		$this->assertStringContainsString( '<input type="hidden" name="s" value="x">', $html, 'A search stays a search.' );
		$this->assertStringContainsString( 'role="switch" name="in_stock" value="1" class="peer sr-only" checked', $html );
		$this->assertStringContainsString( 'name="brand[]" value="novix"', $html );
		$this->assertStringContainsString( '&lt;b&gt;Novix&lt;/b&gt;', $html, 'Names are escaped.' );
		$this->assertStringContainsString( 'type="radio" name="rating_filter" value="4,5"', $html );
		$this->assertStringContainsString( 'style="background:#000"', $html );
		$this->assertStringContainsString( 'name="min_price"', $html );
		$this->assertStringContainsString( 'data-bound="1000"', $html, 'The untouched upper bound is left out of the address.' );
		$this->assertStringContainsString( 'data-um-shop-apply', $html, 'Without JavaScript a button applies the form.' );
		$this->assertSame( 2, substr_count( $html, 'data-um-component="price-range"' ) + substr_count( $html, 'role="radiogroup"' ) );
	}

	public function test_list_pieces_render_with_their_hooks(): void {
		$this->assertTrue(
			ActiveFilters::render(
				array(
					'label' => 'x',
					'items' => array(),
				)
			)->is_empty()
		);
		$chips = (string) ActiveFilters::render(
			array(
				'label' => 'Chosen',
				'items' => array(
					array(
						'label'  => 'Novix',
						'href'   => '/shop/',
						'remove' => 'Remove Novix',
					),
				),
			)
		);
		$this->assertStringContainsString( 'rel="nofollow" data-um-shop-link', $chips );

		$sort = (string) SortBar::render(
			array(
				'label'   => 'Sort',
				'options' => array(
					array(
						'label'   => 'Newest',
						'href'    => '/?orderby=date',
						'current' => true,
					),
				),
				'count'   => '5 products',
			)
		);
		$this->assertStringContainsString( 'aria-current="true"', $sort );
		$this->assertStringContainsString( 'data-um-shop-count', $sort );

		$more = (string) LoadMore::render(
			array(
				'mode'   => 'infinite',
				'shown'  => 12,
				'total'  => 30,
				'status' => '12 of 30',
				'label'  => 'More',
				'next'   => '/shop/page/2/',
			)
		);
		$this->assertStringContainsString( 'data-um-load-more="infinite"', $more );
		$this->assertStringContainsString( 'href="/shop/page/2/"', $more, 'Works without JavaScript too.' );
		$this->assertStringContainsString( 'width:40%', $more );
		$this->assertStringNotContainsString(
			'data-um-load-more',
			(string) LoadMore::render(
				array(
					'mode'   => 'pages',
					'shown'  => 1,
					'total'  => 3,
					'status' => '',
					'label'  => 'More',
					'next'   => '/2/',
				)
			)
		);

		$this->assertSame( '<div data-um-shop-part="products">x</div>', (string) ShopArea::part( 'products', Html::text( 'x' ) ) );
		$area = (string) ShopArea::render(
			array(
				'id'       => 'um-shop',
				'children' => Html::text( 'x' ),
			)
		);
		$this->assertStringContainsString( 'data-um-component="shop"', $area );
		$this->assertStringContainsString( 'aria-live="polite"', $area );
	}

	public function test_bottom_sheet_and_full_screen_window(): void {
		$sheet = (string) Drawer::render(
			array(
				'id'       => 'sheet',
				'title'    => 'Filters',
				'side'     => 'bottom',
				'children' => 'x',
			)
		);
		$this->assertStringContainsString( 'um-drawer-bottom', $sheet );
		$this->assertStringContainsString( 'bottom-0', $sheet );
		$window = (string) UI::dialog(
			array(
				'id'       => 'w',
				'title'    => 'Search',
				'size'     => 'full',
				'children' => 'x',
			)
		);
		$this->assertStringContainsString( 'max-md:h-dvh', $window );
	}

	public function test_the_six_card_designs_and_the_row_view(): void {
		$base = array(
			'title' => 'Shirt',
			'href'  => '/p/',
			'image' => Html::trusted( '<img src="a.png" alt="">' ),
			'price' => '100',
			'add'   => array(
				'href'  => '/p/',
				'label' => 'Choose options',
				'id'    => 7,
				'ajax'  => false,
			),
		);
		$this->assertCount( 6, ProductCard::VARIANTS );
		$swatch = (string) ProductCard::render(
			array_merge(
				$base,
				array(
					'variant'  => 'swatch',
					'swatches' => array(
						array(
							'color' => '#f00',
							'label' => 'Red',
							'src'   => 'https://example.org/red.png',
						),
						array(
							'color' => '#00f',
							'label' => 'Blue',
						),
					),
					'sizes'    => array(
						array(
							'label' => 'S',
							'out'   => true,
						),
						array( 'label' => 'M' ),
					),
				)
			)
		);
		$this->assertStringContainsString( 'data-um-component="product-card"', $swatch, 'Colour buttons need the card behaviour.' );
		$this->assertStringContainsString( 'aria-pressed="true" aria-label="Red" data-um-swatch data-src="https://example.org/red.png"', $swatch );
		$this->assertStringContainsString( 'line-through', $swatch, 'A sold-out size is struck through.' );

		$list = (string) ProductCard::render(
			array_merge(
				$base,
				array(
					'variant' => 'list',
					'details' => array(
						'line'     => 'Brand: Lumo',
						'features' => array( 'Memory: 256GB', 'Colour: red', 'Size: M', 'Too many' ),
					),
				)
			)
		);
		$this->assertStringContainsString( 'data-layout="list"', $list );
		$this->assertSame( 3, substr_count( $list, '<li class="relative ps-3' ), 'Three features at most.' );
		$this->assertStringContainsString( 'um-pc-buy', $list );

		$classic = (string) ProductCard::render(
			array_merge(
				$base,
				array(
					'colors'       => array(
						array(
							'color' => '#000',
							'label' => 'Black',
						),
					),
					'colors_label' => 'In 1 colour',
					'actions'      => array( Html::trusted( '<button>W</button>' ) ),
				)
			)
		);
		$this->assertStringContainsString( 'aria-label="In 1 colour"', $classic );
		$this->assertStringContainsString( '<button>W</button>', $classic, 'Modules add action buttons.' );

		$grid = (string) ProductGrid::render(
			array(
				'label'  => 'Products',
				'items'  => array( array( 'card' => Html::text( 'x' ) ) ),
				'view'   => 'list',
				'mobile' => 'list',
			)
		);
		$this->assertStringContainsString( 'data-view="list"', $grid );
		$this->assertStringContainsString( 'data-mobile="list"', $grid );

		foreach ( ProductCard::PARTS as $part ) {
			$this->assertInstanceOf( Html::class, ProductCard::part( $part, $base ) );
		}
		$this->assertStringContainsString( '<h2 ', (string) ProductCard::part( 'title', $base, array( 'tag' => 'h2' ) ) );
		$this->assertStringContainsString( 'data-um-component="product-card"', (string) ProductCard::shell( Html::text( 'x' ) ) );
	}

	public function test_live_search_suggests_only_what_visitors_may_see(): void {
		Search::reset();
		$public  = self::factory()->post->create( array( 'post_title' => 'قهوه دمی خانگی' ) );
		$private = self::factory()->post->create(
			array(
				'post_title'  => 'قهوه خصوصی',
				'post_status' => 'private',
			)
		);
		$locked  = self::factory()->post->create(
			array(
				'post_title'    => 'قهوه رمزدار',
				'post_password' => 'secret',
			)
		);
		$draft   = self::factory()->post->create(
			array(
				'post_title'  => 'قهوه پیش‌نویس',
				'post_status' => 'draft',
			)
		);
		wp_set_current_user( 0 );
		$answer = Live::build( 'قهوه' );
		$this->assertStringContainsString( (string) get_permalink( $public ), $answer['html'] );
		foreach ( array( $private, $locked, $draft ) as $hidden ) {
			$this->assertStringNotContainsString( 'p=' . $hidden, $answer['html'] );
			$this->assertStringNotContainsString( get_the_title( $hidden ), $answer['html'] );
		}
		$this->assertStringContainsString( 'role="option"', $answer['html'] );

		$none = Live::build( 'zzzzqqq' );
		$this->assertSame( 0, $none['count'] );
		$this->assertStringContainsString( 'role="status"', $none['html'] );

		$suggest = (string) SearchSuggest::render(
			array(
				'id'       => 's',
				'products' => array(),
				'posts'    => array(
					array(
						'label' => 'A',
						'href'  => '/a/',
					),
					array(
						'label' => 'B',
						'href'  => '/b/',
					),
				),
				'labels'   => array(
					'products'   => 'P',
					'categories' => 'C',
					'brands'     => 'B',
					'posts'      => 'Blog',
				),
				'all'      => array(
					'label' => 'All',
					'href'  => '/?s=a',
				),
				'empty'    => 'None',
			)
		);
		$this->assertStringContainsString( 'id="s-o0"', $suggest );
		$this->assertStringContainsString( 'id="s-o2"', $suggest, 'Every option has its own id for arrow keys.' );
	}

	public function test_the_search_route_is_public_and_short_queries_ask_nothing(): void {
		$request = new WP_REST_Request( 'GET', '/ultra-max/v1/search' );
		$request->set_param( 'q', 'a' );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '', $response->get_data()['html'] );
	}

	public function test_the_theme_search_form_gets_live_suggestions(): void {
		$props = Search::form( array( 'id' => 'x' ) );
		$this->assertIsArray( $props );
		$this->assertSame( Live::endpoint(), $props['live']['endpoint'] );
		$html = (string) \UltraMax\Theme\Template::search_component( 'um-test-search', 'soft' );
		$this->assertStringContainsString( 'data-um-component="live-search"', $html );
		$this->assertStringContainsString( 'role="combobox"', $html );
	}
}
