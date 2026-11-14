<?php
/**
 * Tests of the product page that need no WooCommerce (PDP-01 to PDP-26, BLD-04).
 *
 * WooCommerce's own flows (adding to the cart, quantity limits, reviews
 * and questions on real products, bundle discounts, private products
 * never leaking) are tested in the browser on the test site: see
 * tests/e2e/phase4-product.spec.ts.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Builder\Library;
use UltraMax\Core\Builder\Types;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\BundleBox;
use UltraMax\Core\Lib\UI\DeliveryEstimate;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\KeyFeatures;
use UltraMax\Core\Lib\UI\ProductGallery;
use UltraMax\Core\Lib\UI\ProductPrice;
use UltraMax\Core\Lib\UI\ProductSections;
use UltraMax\Core\Lib\UI\ProgressNote;
use UltraMax\Core\Lib\UI\PurchaseForm;
use UltraMax\Core\Lib\UI\QuestionList;
use UltraMax\Core\Lib\UI\ReviewList;
use UltraMax\Core\Lib\UI\SpecTable;
use UltraMax\Core\Lib\UI\Stars;
use UltraMax\Core\Lib\UI\StickyBuy;
use UltraMax\Core\Lib\UI\VariationPicker;
use UltraMax\Core\Product\Delivery;
use UltraMax\Core\Product\Gallery;
use UltraMax\Core\Product\Reviews;
use UltraMax\Core\Widgets\Widgets;

/**
 * Videos, working days, review lists, layout type and the components.
 */
final class ProductTest extends WP_UnitTestCase {

	public function tear_down(): void {
		update_option( Store::OPTION, array() );
		parent::tear_down();
	}

	public function test_video_addresses_of_youtube_aparat_and_files(): void {
		$videos = Gallery::videos( "https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=3\nhttps://youtu.be/abcdefg1234\nhttps://www.aparat.com/v/Xy12Z\nhttps://example.com/clip.mp4\nnot a link\nhttps://example.com/page" );
		$this->assertCount( 4, $videos );
		$this->assertSame( 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', $videos[0]['src'], 'YouTube plays from its privacy-enhanced address.' );
		$this->assertSame( 'youtube', $videos[1]['provider'] );
		$this->assertSame( 'https://www.aparat.com/video/video/embed/videohash/Xy12Z/vt/frame', $videos[2]['src'] );
		$this->assertSame( 'file', $videos[3]['provider'] );
	}

	public function test_gallery_set_keys(): void {
		$this->assertSame( '', Gallery::key( array() ) );
		$this->assertSame( Gallery::key( array( 3, 4 ) ), Gallery::key( array( 3, 4 ) ) );
		$this->assertNotSame( Gallery::key( array( 3, 4 ) ), Gallery::key( array( 4, 3 ) ), 'The first picture of a set is its main one.' );
	}

	public function test_working_days_skip_fridays_holidays_and_optionally_thursdays(): void {
		$zone = new DateTimeZone( 'Asia/Tehran' );
		// Thursday 12 November 2026 (1405/08/21).
		$thursday = new DateTimeImmutable( '2026-11-12 10:00', $zone );
		$this->assertSame( '2026-11-14', Delivery::add_working_days( $thursday, 1, array() )->format( 'Y-m-d' ), 'Friday is skipped.' );
		$this->assertSame( '2026-11-15', Delivery::add_working_days( $thursday, 1, array( '2026-11-14' => true ) )->format( 'Y-m-d' ), 'Holidays are skipped.' );
		Store::save( array( 'product_thursday_off' => true ) );
		$wednesday = new DateTimeImmutable( '2026-11-11 10:00', $zone );
		$this->assertSame( '2026-11-14', Delivery::add_working_days( $wednesday, 1, array() )->format( 'Y-m-d' ), 'Thursday is skipped when the store closes then.' );
		$this->assertSame( '2026-11-11', Delivery::add_working_days( $wednesday, 0, array() )->format( 'Y-m-d' ) );
	}

	public function test_jalali_holidays_become_gregorian_dates(): void {
		Store::save( array( 'product_holidays' => "1405/01/01\nnot a date\n1405-01-13" ) );
		$this->assertSame(
			array(
				'2026-03-21' => true,
				'2026-04-02' => true,
			),
			Delivery::holidays()
		);
	}

	public function test_review_lines_are_cleaned_and_limited(): void {
		$this->assertSame( array( 'Strong battery', 'Good screen' ), Reviews::lines( "- Strong battery\n\n+ Good screen" ), 'List marks and empty lines go.' );
		$this->assertSame( array( 'Fast' ), Reviews::lines( "<script>alert(1)</script>\n* Fast" ), 'Markup goes.' );
		$this->assertCount( 5, Reviews::lines( "a\nb\nc\nd\ne\nf\ng" ) );
	}

	public function test_review_list_state_accepts_only_known_choices(): void {
		$this->assertSame(
			array(
				'sort'   => 'helpful',
				'filter' => 'all',
				'page'   => 1,
			),
			Reviews::state( array() )
		);
		$this->assertSame(
			array(
				'sort'   => 'low',
				'filter' => '4',
				'page'   => 3,
			),
			Reviews::state(
				array(
					'review_sort'   => 'low',
					'review_filter' => '4',
					'review_page'   => '3',
				)
			)
		);
		$odd = Reviews::state(
			array(
				'review_sort'   => 'random',
				'review_filter' => '9',
				'review_page'   => '-4',
			)
		);
		$this->assertSame( 'helpful', $odd['sort'] );
		$this->assertSame( 'all', $odd['filter'] );
		$this->assertSame( 1, $odd['page'] );
	}

	public function test_review_queries_filter_and_sort_by_meta(): void {
		$buyers = Reviews::query_args(
			array(
				'sort'   => 'newest',
				'filter' => 'buyers',
				'page'   => 1,
			)
		);
		$this->assertSame( 'comment_date_gmt', $buyers['orderby'] );
		$this->assertSame( 'verified', $buyers['meta_query'][0]['key'] );

		$helpful = Reviews::query_args(
			array(
				'sort'   => 'helpful',
				'filter' => 'photos',
				'page'   => 1,
			)
		);
		$this->assertSame( 'EXISTS', $helpful['meta_query'][0]['compare'] );
		$this->assertSame( 'OR', $helpful['meta_query']['votes']['relation'], 'Reviews without votes still show.' );
		$this->assertSame( 'DESC', $helpful['orderby']['up'] );

		$stars = Reviews::query_args(
			array(
				'sort'   => 'low',
				'filter' => '2',
				'page'   => 1,
			)
		);
		$this->assertSame( '2', $stars['meta_query'][0]['value'] );
		$this->assertSame( 'ASC', $stars['orderby']['stars'] );
	}

	public function test_review_criteria_come_from_the_settings(): void {
		Store::save( array( 'product_review_criteria' => "Quality\n\nValue\nQuality\n<b>Ease</b>\n5\n6\n7\n8" ) );
		$this->assertSame( array( 'Quality', 'Value', 'Ease', '5', '6', '7' ), Reviews::criteria(), 'Unique, clean and at most six.' );
	}

	public function test_single_product_layouts_are_ready_and_cover_every_product(): void {
		$this->assertTrue( Types::is_ready( 'single-product' ) );
		$this->assertSame( 'single-product', Types::all()['single-product']['location'] );
		$this->assertSame( array( array( 'target' => 'products' ) ), Layouts::defaults( 'single-product' )['include'] );
		$this->assertCount( 2, Library::all( 'single-product' ) );
		$store = Library::get( 'single-product/store' );
		$this->assertNotNull( $store );
		$types = array();
		array_walk_recursive(
			$store['content'],
			static function ( mixed $value, mixed $key ) use ( &$types ): void {
				if ( 'widgetType' === $key && is_string( $value ) ) {
					$types[] = $value;
				}
			}
		);
		foreach ( array_unique( $types ) as $type ) {
			$this->assertNotNull( Widgets::get( substr( $type, 3 ) ), "The template's widget {$type} exists." );
		}
	}

	public function test_product_widgets_draw_nothing_without_a_product(): void {
		foreach ( Widgets::all() as $widget ) {
			if ( in_array( 'BLD-04', $widget->features(), true ) ) {
				$this->assertSame( 'product', $widget->group() );
				$this->assertTrue( $widget->output( array() )->is_empty(), $widget->id() . ' needs a product.' );
			}
		}
	}

	/**
	 * A gallery item.
	 *
	 * @param string $label Label.
	 * @param string $type  image or video.
	 * @return array<string, mixed>
	 */
	private function item( string $label, string $type = 'image' ): array {
		$item = array(
			'type'  => $type,
			'image' => Html::trusted( '<img src="a.png" alt="">' ),
			'thumb' => Html::trusted( '<img src="t.png" alt="">' ),
			'label' => $label,
			'full'  => 'https://example.com/a.png',
		);
		if ( 'video' === $type ) {
			$item['video']    = 'https://www.youtube-nocookie.com/embed/x';
			$item['provider'] = 'youtube';
		}
		return $item;
	}

	public function test_gallery_thumbnails_are_links_and_sets_are_templates(): void {
		$labels = array(
			'zoom'     => 'Zoom',
			'open'     => 'Open',
			'close'    => 'Close',
			'previous' => 'Previous',
			'next'     => 'Next',
			'video'    => 'Play',
			'more'     => 'More',
			'counter'  => '%1$s of %2$s',
		);
		$items  = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$items[] = $this->item( 'P' . $i );
		}
		$items[] = $this->item( 'Clip', 'video' );
		$html    = (string) ProductGallery::render(
			array(
				'id'     => 'g1',
				'label'  => 'Pictures',
				'items'  => $items,
				'labels' => $labels,
				'sets'   => array( 'gabc' => array( $this->item( 'Red' ) ) ),
			)
		);
		$this->assertStringContainsString( 'href="#g1-slide-0"', $html, 'Thumbnails work without JavaScript.' );
		$this->assertSame( 4, substr_count( (string) strstr( $html, '<template', true ), 'data-um-gallery-thumb=' ), 'Four thumbnails, then "+N".' );
		$this->assertStringContainsString( 'data-um-gallery-open="4"', $html );
		$this->assertStringContainsString( 'data-provider="youtube"', $html );
		$this->assertStringContainsString( '<template data-um-gallery-set="gabc" data-part="track">', $html );
		$this->assertStringContainsString( 'data-um-gallery-viewer', $html );
		$this->assertTrue(
			ProductGallery::render(
				array(
					'id'     => 'g2',
					'label'  => 'x',
					'items'  => array(),
					'labels' => $labels,
				)
			)->is_empty()
		);
	}

	public function test_variation_choices_are_radio_buttons_of_the_form(): void {
		$html = (string) VariationPicker::render(
			array(
				'form'       => 'um-purchase-7',
				'attributes' => array(
					array(
						'name'     => 'attribute_pa_color',
						'label'    => 'Colour',
						'display'  => 'color',
						'choose'   => 'Choose',
						'selected' => 'black',
						'options'  => array(
							array(
								'value' => 'black',
								'label' => 'Black',
								'color' => '#000000',
							),
							array(
								'value' => 'cream',
								'label' => 'Cream',
								'color' => '#eeeeee',
								'out'   => true,
							),
						),
					),
					array(
						'name'    => 'attribute_size',
						'label'   => 'Size',
						'display' => 'select',
						'choose'  => 'Choose',
						'options' => array(
							array(
								'value' => 'M',
								'label' => 'M',
							),
						),
					),
				),
			)
		);
		$this->assertSame( 2, substr_count( $html, 'type="radio"' ) );
		$this->assertSame( 3, substr_count( $html, 'form="um-purchase-7"' ), 'Every choice belongs to the form.' );
		$this->assertStringContainsString( 'value="black" form="um-purchase-7" checked', $html );
		$this->assertStringContainsString( 'data-out', $html, 'Sold out values are marked.' );
		$this->assertStringContainsString( 'background:#000000', $html );
		$this->assertStringContainsString( '<select', $html );
	}

	public function test_purchase_form_posts_woocommerce_fields_and_variation_data(): void {
		$messages = array(
			'choose'      => 'c',
			'unavailable' => 'u',
			'out'         => 'o',
			'added'       => 'a',
			'cart'        => 'Cart',
			'cart_url'    => '/cart/',
			'error'       => 'e',
		);
		$html     = (string) PurchaseForm::render(
			array(
				'id'         => 'um-purchase-9',
				'product'    => 9,
				'action'     => 'https://example.com/p/',
				'add'        => array( 'label' => 'Add' ),
				'messages'   => $messages,
				'quick'      => 'Buy now',
				'quantity'   => array(
					'min'      => 6,
					'max'      => 30,
					'step'     => 6,
					'value'    => 6,
					'label'    => 'Quantity',
					'increase' => '+',
					'decrease' => '-',
				),
				'variations' => array(
					array(
						'id'         => 11,
						'attributes' => array( 'attribute_size' => '</script><script>' ),
						'price'      => '100',
						'regular'    => '',
						'discount'   => '',
						'in_stock'   => true,
						'stock'      => '',
						'stock_tone' => 'success',
						'min'        => 1,
						'max'        => 0,
						'step'       => 1,
						'gallery'    => '',
						'sku'        => '',
						'summary'    => 'M',
					),
				),
			)
		);
		$this->assertStringContainsString( 'name="add-to-cart" value="9"', $html, 'WooCommerce\'s own form fields.' );
		$this->assertStringContainsString( 'name="variation_id" value="0"', $html );
		$this->assertStringContainsString( 'min="6" max="30" step="6" value="6"', $html );
		$this->assertStringContainsString( 'name="um-buy-now" value="1"', $html );
		$this->assertStringNotContainsString( '</script><script>', $html, 'Variation data cannot close its script element.' );
		$this->assertStringContainsString( 'data-um-component="purchase"', $html );
	}

	public function test_price_hides_empty_parts(): void {
		$html = (string) ProductPrice::render(
			array(
				'product'  => 5,
				'price'    => '1,000',
				'currency' => 'Toman',
			)
		);
		$this->assertStringContainsString( 'data-um-price-for="5"', $html );
		$this->assertMatchesRegularExpression( '/hidden data-um-price-off/', $html );
		$this->assertMatchesRegularExpression( '/hidden data-um-price-old/', $html );
		$this->assertMatchesRegularExpression( '/hidden data-um-price-call/', $html );
	}

	public function test_sections_drop_empty_ones_and_offer_three_looks(): void {
		$items = array(
			array(
				'id'      => 'intro',
				'title'   => 'Intro',
				'content' => Html::text( 'Hello' ),
			),
			array(
				'id'      => 'specs',
				'title'   => 'Specs',
				'content' => Html::none(),
			),
			array(
				'id'      => 'reviews',
				'title'   => 'Reviews',
				'content' => Html::text( 'Good' ),
				'count'   => '3',
			),
		);
		$long  = (string) ProductSections::render(
			array(
				'id'    => 's1',
				'label' => 'Parts',
				'items' => $items,
			)
		);
		$this->assertStringContainsString( 'href="#intro"', $long );
		$this->assertStringNotContainsString( 'href="#specs"', $long, 'Empty sections have no link.' );
		$this->assertStringContainsString( 'data-um-component="product-sections"', $long );
		$tabs = (string) ProductSections::render(
			array(
				'id'      => 's2',
				'label'   => 'Parts',
				'items'   => $items,
				'variant' => 'tabs',
			)
		);
		$this->assertStringContainsString( 'role="tablist"', $tabs );
		$this->assertTrue(
			ProductSections::render(
				array(
					'id'    => 's3',
					'label' => 'x',
					'items' => array(),
				)
			)->is_empty()
		);
	}

	public function test_sticky_buy_submits_the_main_form(): void {
		$html = (string) StickyBuy::render(
			array(
				'form'    => 'um-purchase-3',
				'product' => 3,
				'price'   => Html::text( '100' ),
				'label'   => 'Add',
				'watch'   => 'um-buy-box-3',
			)
		);
		$this->assertStringContainsString( 'type="submit" form="um-purchase-3"', $html );
		$this->assertStringContainsString( 'data-watch="um-buy-box-3"', $html );
	}

	public function test_review_list_links_work_without_javascript(): void {
		$labels = array(
			'filters' => 'f',
			'sort'    => 's',
			'helpful' => 'h',
			'up'      => 'u',
			'down'    => 'd',
			'pros'    => 'p',
			'cons'    => 'c',
			'reply'   => 'r',
			'empty'   => 'Nothing yet',
			'pending' => 'w',
		);
		$html   = (string) ReviewList::render(
			array(
				'id'      => 'r1',
				'product' => 4,
				'labels'  => $labels,
				'items'   => array(
					array(
						'id'           => 12,
						'author'       => 'Sara',
						'date'         => '1 Aban',
						'datetime'     => '2026-10-23T00:00:00+00:00',
						'rating'       => 5,
						'rating_text'  => '5.0',
						'rating_label' => 'Rated 5',
						'text'         => Html::text( 'Great <b>phone</b>' ),
						'pros'         => array( 'Battery' ),
						'cons'         => array( 'Charger' ),
						'buyer'        => 'Buyer',
						'votes'        => array(
							'up'   => '4',
							'down' => '0',
						),
					),
				),
				'filters' => array(
					array(
						'label'   => 'Buyers',
						'value'   => 'buyers',
						'href'    => '/p/?review_filter=buyers#reviews',
						'current' => false,
					),
				),
				'more'    => array(
					'label' => 'More',
					'href'  => '/p/?review_page=2#reviews',
				),
			)
		);
		$this->assertStringContainsString( 'id="review-12"', $html );
		$this->assertStringContainsString( 'rel="nofollow" data-um-reviews-link', $html, 'Sorted and filtered lists are not followed.' );
		$this->assertStringContainsString( 'data-um-reviews-more', $html );
		$this->assertStringContainsString( 'Great &lt;b&gt;phone&lt;/b&gt;', $html );
		$this->assertStringContainsString( 'data-um-vote="up"', $html );
		$empty = (string) ReviewList::render(
			array(
				'id'      => 'r2',
				'product' => 4,
				'labels'  => $labels,
				'items'   => array(),
			)
		);
		$this->assertStringContainsString( 'Nothing yet', $empty );
	}

	public function test_star_input_is_five_named_radios(): void {
		$html = (string) Stars::input( 'rating', 'Your rating', array( 'Bad', 'Poor', 'Fair', 'Good', 'Great' ), true );
		$this->assertSame( 5, substr_count( $html, 'name="rating"' ) );
		$this->assertSame( 1, substr_count( $html, ' required' ) );
		$this->assertStringContainsString( '<legend', $html );
	}

	public function test_question_forms_carry_their_hidden_fields(): void {
		$html = (string) QuestionList::form(
			array(
				'action'      => '/wp-admin/admin-post.php',
				'hidden'      => array(
					'action'  => 'ultra_max_question',
					'product' => '8',
				),
				'label'       => 'Question',
				'placeholder' => 'Ask',
				'submit'      => 'Send',
				'notify'      => 'Email me',
			)
		);
		$this->assertStringContainsString( 'name="action" value="ultra_max_question"', $html );
		$this->assertStringContainsString( 'name="um_notify"', $html );
		$this->assertStringContainsString( 'data-um-qa-form', $html );
	}

	public function test_bundle_needs_two_products_and_carries_every_total(): void {
		$item  = static fn ( int $id ): array => array(
			'id'    => $id,
			'title' => 'P' . $id,
			'href'  => '/p' . $id,
			'image' => Html::none(),
			'price' => '100',
		);
		$props = array(
			'id'       => 'b1',
			'title'    => 'Together',
			'items'    => array( $item( 1 ) ),
			'totals'   => array(),
			'currency' => 'T',
			'labels'   => array(
				'total'   => 'Total',
				'add'     => 'Add',
				'include' => 'Include %s',
			),
			'endpoint' => '/?wc-ajax=um_add_to_cart',
			'messages' => array(
				'added'    => 'a',
				'cart'     => 'c',
				'cart_url' => '/cart/',
				'error'    => 'e',
			),
		);
		$this->assertTrue( BundleBox::render( $props )->is_empty() );
		$props['items']  = array( array_merge( $item( 1 ), array( 'fixed' => true ) ), $item( 2 ) );
		$props['totals'] = array(
			'1'   => array(
				'old'  => '',
				'now'  => '100',
				'save' => '',
			),
			'1,2' => array(
				'old'  => '200',
				'now'  => '180',
				'save' => 'You save 20',
			),
		);
		$html            = (string) BundleBox::render( $props );
		$this->assertStringContainsString( 'data-um-bundle-now>180<', $html, 'The total of all products shows first.' );
		$this->assertStringContainsString( '"1,2"', $html );
		$this->assertSame( 1, substr_count( $html, ' disabled' ), 'The product itself is always in the bundle.' );
	}

	public function test_small_parts_render_or_stay_empty(): void {
		$this->assertTrue(
			KeyFeatures::render(
				array(
					'title' => 'Key',
					'items' => array(),
				)
			)->is_empty()
		);
		$this->assertTrue(
			SpecTable::render(
				array(
					'groups' => array(
						array(
							'title' => 'General',
							'rows'  => array(),
						),
					),
				)
			)->is_empty()
		);
		$table = (string) SpecTable::render(
			array(
				'groups' => array(
					array(
						'title' => 'Display',
						'rows'  => array(
							array(
								'label' => 'Size',
								'value' => '6.7"',
							),
						),
					),
				),
			)
		);
		$this->assertStringContainsString( '<dt', $table );
		$bar = (string) ProgressNote::render(
			array(
				'text'  => 'Almost',
				'value' => 140,
				'label' => 'Progress',
			)
		);
		$this->assertStringContainsString( 'aria-valuenow="100"', $bar, 'The bar never goes past full.' );
		$rows = DeliveryEstimate::rows(
			array(
				array(
					'title' => 'Courier',
					'text'  => '25 to 26 Aban',
				),
			)
		);
		$this->assertStringContainsString( 'Courier: <b', $rows );
	}
}
