<?php
/**
 * Tests of the cart, checkout and shipping that need no WooCommerce
 * (CART-01 to CART-10, CHK-01 to CHK-10, SHIP-01, SHIP-02, BLD-06).
 *
 * WooCommerce's own flows (a whole purchase in the classic and the block
 * checkout, the side cart, the buy later list, order limits, fields saved
 * on the order, shipping prices on a real cart) are tested in the browser
 * on the test site: see tests/e2e/phase4-checkout.spec.ts.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Builder\Types;
use UltraMax\Core\Cart\Later;
use UltraMax\Core\Cart\Limits;
use UltraMax\Core\Cart\Totals;
use UltraMax\Core\Checkout\AddressBook;
use UltraMax\Core\Checkout\Cities;
use UltraMax\Core\Checkout\Fields;
use UltraMax\Core\Checkout\Iran;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\AddressCards;
use UltraMax\Core\Lib\UI\CartLine;
use UltraMax\Core\Lib\UI\CartSummary;
use UltraMax\Core\Lib\UI\CheckoutSteps;
use UltraMax\Core\Lib\UI\CouponForm;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\OrderDone;
use UltraMax\Core\Lib\UI\PaymentMethods;
use UltraMax\Core\Lib\UI\ShippingOptions;
use UltraMax\Core\Lib\UI\SideCart;
use UltraMax\Core\Shipping\Rates;

/**
 * Iranian rules, price tables, limits, lists, fields and components.
 */
final class CheckoutTest extends WP_UnitTestCase {

	public function tear_down(): void {
		update_option( Store::OPTION, array() );
		Cities::reset();
		parent::tear_down();
	}

	public function test_mobile_numbers_are_read_however_they_are_written(): void {
		$this->assertSame( '09123456789', Iran::mobile( '۰۹۱۲ ۳۴۵ ۶۷۸۹' ) );
		$this->assertSame( '09123456789', Iran::mobile( '+98 912-345-6789' ) );
		$this->assertSame( '09123456789', Iran::mobile( '9123456789' ) );
		$this->assertNull( Iran::mobile( '02188776655' ), 'A landline is not a mobile.' );
	}

	public function test_national_code_company_id_postcode_and_economic_code(): void {
		$this->assertTrue( Iran::national_code( '0012345679' ) );
		$this->assertTrue( Iran::national_code( '۰۰۱۲۳۴۵۶۷۹' ) );
		$this->assertFalse( Iran::national_code( '0012345678' ), 'The design\'s sample code has a wrong check digit.' );
		$this->assertFalse( Iran::national_code( '2222222222' ) );
		$this->assertTrue( Iran::company_id( '10100000006' ) );
		$this->assertFalse( Iran::company_id( '10100000007' ) );
		$this->assertTrue( Iran::postcode( '1193653471' ) );
		$this->assertFalse( Iran::postcode( '1234567890' ), 'No 2 in the first five digits.' );
		$this->assertFalse( Iran::postcode( '12345' ) );
		$this->assertTrue( Iran::economic_code( '411111111111' ) );
		$this->assertFalse( Iran::economic_code( '41' ) );
		$this->assertSame( 'کیان کرج', Iran::letters( 'كيان  كرج' ) );
	}

	public function test_post_prices_by_weight_and_province(): void {
		$table = Rates::brackets( "۱ = ۷۵,۰۰۰ | ۱۰۰,۰۰۰\n0.5 = 65000 | 85000\n3 = 110000\nnot a line" );
		$this->assertCount( 3, $table );
		$this->assertSame( 0.5, $table[0]['max'], 'Lines are sorted by weight.' );
		$this->assertSame( 110000.0, $table[2]['outside'], 'One price serves both.' );
		$this->assertSame( 65000.0, Rates::post( $table, 0.4, true ) );
		$this->assertSame( 100000.0, Rates::post( $table, 0.8, false ) );
		$this->assertSame( 110000.0 + 2 * 30000.0 + 5000.0, Rates::post( $table, 4.2, true, 30000.0, 5000.0 ), 'Every kilogram started beyond the table pays the extra price.' );
		$this->assertNull( Rates::post( array(), 1.0, true ) );
	}

	public function test_courier_goes_only_to_its_cities(): void {
		$cities = Rates::cities( "تهران = 85000\nشهريار\n" );
		$this->assertSame( 85000.0, Rates::courier( $cities, 'تهران', 120000.0 ) );
		$this->assertSame( 120000.0, Rates::courier( $cities, 'شهریار', 120000.0 ), 'Arabic yeh in the list matches Persian yeh.' );
		$this->assertNull( Rates::courier( $cities, 'کرج', 120000.0 ) );
		$this->assertSame( 50.0, Rates::courier( array(), 'هرجا', 50.0 ), 'An empty list covers the whole province.' );
	}

	public function test_order_limits_say_what_is_missing(): void {
		$this->assertSame( '', Limits::problem_for( 500.0, 0.0, 0.0 ) );
		$this->assertMatchesRegularExpression( '/(300|۳۰۰)/u', Limits::problem_for( 200.0, 500.0, 0.0 ), 'The message says how much is missing.' );
		$this->assertNotSame( '', Limits::problem_for( 900.0, 0.0, 800.0 ) );
		$this->assertSame( '', Limits::problem_for( 800.0, 500.0, 800.0 ), 'The limits themselves are allowed.' );
		$this->assertSame( 19, Totals::percent( 6570000.0, 34200000.0 ) );
		$this->assertSame( 0, Totals::percent( 10.0, 0.0 ) );
	}

	public function test_buy_later_entries_are_cleaned_and_keyed(): void {
		$list = Later::clean(
			array(
				array(
					'product' => 12,
					'values'  => array( 'attribute_pa_color' => 'black' ),
				),
				array(
					'product' => 12,
					'values'  => array( 'attribute_pa_color' => 'black' ),
				),
				array( 'product' => 0 ),
				'junk',
			)
		);
		$this->assertCount( 1, $list, 'The same product with the same options is one entry.' );
		$entry = array_values( $list )[0];
		$this->assertSame( 0, $entry['variation'] );
		$this->assertSame( Later::key( $entry ), array_key_first( $list ) );
	}

	public function test_address_book_keeps_one_entry_per_place(): void {
		$user  = self::factory()->user->create();
		$home  = array(
			'first_name' => 'سارا',
			'last_name'  => 'محمدی',
			'phone'      => '09123456789',
			'state'      => 'THR',
			'city'       => 'تهران',
			'address_1'  => 'خیابان ولیعصر، پلاک ۱۲',
			'postcode'   => '1193653471',
		);
		$first = AddressBook::add( $user, $home );
		AddressBook::add(
			$user,
			array_merge(
				$home,
				array(
					'address_1' => 'خیابان چهارباغ',
					'state'     => 'ESF',
					'city'      => 'اصفهان',
				)
			)
		);
		$again = AddressBook::add(
			$user,
			array_merge(
				$home,
				array(
					'first_name' => 'علی',
					'postcode'   => '۱۱۹۳۶۵۳۴۷۱',
				)
			)
		);
		$book  = AddressBook::of( $user );
		$this->assertSame( $first, $again, 'The same street and postal code is the same address.' );
		$this->assertCount( 2, $book );
		$this->assertSame( 'علی', $book[0]['first_name'], 'The newest use moves first.' );
		$this->assertSame( 'IR', $book[0]['country'] );
		AddressBook::remove( $user, $first );
		$this->assertCount( 1, AddressBook::of( $user ) );
	}

	public function test_cities_include_the_store_additions(): void {
		Store::save( array( 'checkout_cities' => "THR: لواسان\nبی‌معنی\nTHR: تهران" ) );
		Cities::reset();
		$tehran = Cities::of( 'THR' );
		$this->assertSame( 'تهران', $tehran[0], 'The capital comes first.' );
		$this->assertContains( 'لواسان', $tehran );
		$this->assertSame( 1, count( array_keys( $tehran, 'تهران', true ) ), 'A city already listed is not added twice.' );
		$this->assertContains( 'اصفهان', Cities::of( 'ESF' ) );
		$this->assertTrue( Cities::same( 'شهريار', 'شهریار' ) );
		$this->assertCount( 31, Cities::all(), 'Every province of Iran has a list.' );
	}

	public function test_field_editor_rules_and_our_fields(): void {
		$rules = Fields::rules();
		$this->assertSame( 'hidden', $rules['company']['show'] );
		$this->assertLessThan( $rules['state']['priority'], $rules['phone']['priority'], 'The default order asks for the mobile before the province.' );
		Store::save(
			array(
				'checkout_fields'        => array(
					array(
						'field' => 'postcode',
						'label' => 'کد پستی ده رقمی',
						'show'  => 'optional',
					),
				),
				'checkout_national_code' => 'required',
				'checkout_custom'        => array(
					array(
						'label'    => 'Gift note',
						'key'      => 'gift',
						'type'     => 'select',
						'options'  => 'A, B',
						'place'    => 'order',
						'when'     => 'company',
						'required' => true,
					),
				),
			)
		);
		$rules = Fields::rules();
		$this->assertSame( 10, $rules['postcode']['priority'] );
		$this->assertSame( 'کد پستی ده رقمی', $rules['postcode']['label'] );
		$this->assertSame( '', $rules['phone']['show'], 'Fields left out of the editor keep WooCommerce\'s way.' );
		$custom = Fields::find( 'c-gift' );
		$this->assertNotNull( $custom );
		$this->assertSame(
			array(
				'A' => 'A',
				'B' => 'B',
			),
			$custom['options']
		);
		$this->assertSame( '_wc_other/ultra-max/c-gift', Fields::meta_key( $custom ) );
		$code = Fields::find( 'national-code' );
		$this->assertNotNull( $code );
		$this->assertTrue( $code['required'] );
		$this->assertSame( '', Fields::problem( $code, '0012345679' ) );
		$this->assertNotSame( '', Fields::problem( $code, '0012345678' ) );
		$this->assertTrue( Fields::applies( $code, 'person' ) );
		$this->assertFalse( Fields::applies( $code, 'company' ) );
		$this->assertSame( '09123456789', Fields::latin( '۰۹۱۲۳۴۵۶۷۸۹' ) );
	}

	public function test_cart_checkout_and_thank_you_layout_types_are_ready_with_locations(): void {
		$types = Types::all();
		$this->assertSame( 'cart', $types['cart']['location'] );
		$this->assertSame( 'checkout', $types['checkout-form']['location'] );
		$this->assertSame( 'thank-you', $types['thank-you']['location'] );
		$this->assertArrayNotHasKey( 'checkout-content', $types, 'The checkout is one layout, as the whole of it is one form.' );
	}

	public function test_cart_line_posts_like_woocommerce_and_turns_minus_into_remove(): void {
		$html = (string) CartLine::render(
			array(
				'key'      => 'k1',
				'title'    => 'هدفون',
				'image'    => Html::trusted( '<img alt="">' ),
				'total'    => '3,250,000',
				'labels'   => array(
					'quantity' => 'Q',
					'increase' => 'Up',
					'decrease' => 'Down',
					'remove'   => 'Remove',
					'later'    => 'Later',
				),
				'remove'   => 'https://shop.test/?remove_item=k1',
				'later'    => 'https://shop.test/?um-cart=later&key=k1',
				'quantity' => array(
					'value' => 1,
					'min'   => 1,
					'max'   => 3,
					'step'  => 1,
				),
				'saving'   => '1,350,000 off',
				'facts'    => array(
					array(
						'text'  => 'مشکی',
						'color' => '#23252e',
					),
				),
			)
		);
		$this->assertStringContainsString( 'name="cart[k1][qty]"', $html );
		$this->assertStringContainsString( 'data-um-cart-action="remove"', $html, 'At the minimum the minus button removes the line.' );
		$this->assertStringContainsString( 'data-um-cart-action="later"', $html );
		$this->assertStringContainsString( 'background:#23252e', $html );
		$this->assertStringContainsString( 'max="3"', $html );
	}

	public function test_cart_summary_side_cart_and_coupon(): void {
		$summary = (string) CartSummary::render(
			array(
				'rows'  => array(
					array(
						'label' => 'Saving',
						'value' => '(19%) 6,570,000',
						'tone'  => 'save',
						'key'   => 'saving',
					),
				),
				'class' => 'woocommerce-checkout-review-order-table',
			)
		);
		$this->assertStringContainsString( 'data-um-row="saving"', $summary );
		$this->assertStringContainsString( 'woocommerce-checkout-review-order-table', $summary, 'WooCommerce\'s script finds the checkout summary by its class.' );
		$shell = (string) SideCart::shell(
			array(
				'endpoint' => 'https://shop.test/?wc-ajax=um_cart',
				'loading'  => 'Loading',
				'error'    => 'Error',
			)
		);
		$this->assertStringContainsString( 'data-um-component="side-cart"', $shell );
		$this->assertStringContainsString( 'data-open-on-add', $shell );
		$this->assertStringNotContainsString( '<li', $shell, 'Cached pages carry no cart lines.' );
		$form   = (string) CouponForm::render(
			array(
				'id'     => 'c',
				'label'  => 'Code',
				'submit' => 'Apply',
				'remove' => 'Remove %s',
				'action' => 'https://shop.test/cart/',
			)
		);
		$inline = (string) CouponForm::render(
			array(
				'id'     => 'c2',
				'label'  => 'Code',
				'submit' => 'Apply',
				'remove' => 'Remove %s',
				'inline' => true,
			)
		);
		$this->assertStringContainsString( '<form', $form );
		$this->assertStringContainsString( 'name="apply_coupon"', $form, 'Without JavaScript the cart\'s handler applies the code.' );
		$this->assertStringNotContainsString( '<form', $inline, 'Inside the checkout form a second form is not allowed.' );
	}

	public function test_checkout_pieces_keep_woocommerce_names(): void {
		$payment = (string) PaymentMethods::render(
			array(
				'methods' => array(
					array(
						'id'     => 'cod',
						'title'  => Html::text( 'Pay on delivery' ),
						'chosen' => true,
						'box'    => Html::text( 'Pay by card on delivery.' ),
					),
					array(
						'id'     => 'bacs',
						'title'  => Html::text( 'Transfer' ),
						'chosen' => false,
						'box'    => Html::text( 'To our account.' ),
					),
				),
				'label'   => 'Payment methods',
			)
		);
		$this->assertStringContainsString( 'id="payment"', $payment );
		$this->assertStringContainsString( 'id="payment_method_cod"', $payment );
		$this->assertStringContainsString( 'name="payment_method"', $payment );
		$this->assertStringContainsString( 'class="payment_box payment_method_bacs', $payment );
		$this->assertStringContainsString( 'style="display:none;"', $payment, 'A method not chosen hides its box, as WooCommerce\'s template does.' );
		$one = (string) ShippingOptions::render(
			array(
				'index'   => 0,
				'options' => array(
					array(
						'value' => 'um_post:3',
						'title' => 'Post',
						'price' => '70,000',
					),
				),
				'label'   => 'Shipping',
			)
		);
		$this->assertStringContainsString( 'type="hidden" name="shipping_method[0]"', $one, 'A single method is sent as a hidden field.' );
		$steps = (string) CheckoutSteps::render(
			array(
				'label' => 'Steps',
				'steps' => array(
					array(
						'key'   => 'cart',
						'label' => 'Cart',
						'icon'  => 'cart',
						'state' => 'done',
						'href'  => '/cart/',
					),
					array(
						'key'   => 'shipping',
						'label' => 'Shipping',
						'icon'  => 'truck',
						'state' => 'current',
					),
				),
			)
		);
		$this->assertStringContainsString( 'aria-current="step"', $steps );
		$this->assertStringContainsString( 'href="/cart/"', $steps );
		$cards = (string) AddressCards::render(
			array(
				'label'     => 'Addresses',
				'addresses' => array(
					array(
						'id'       => 'a1',
						'title'    => 'تهران',
						'text'     => 'سارا',
						'fields'   => array( 'state' => 'THR' ),
						'selected' => true,
					),
				),
				'new'       => 'New',
			)
		);
		$this->assertStringContainsString( 'aria-pressed="true"', $cards );
		$this->assertStringContainsString( 'data-um-address-new', $cards );
		$this->assertStringContainsString( '&quot;state&quot;:&quot;THR&quot;', $cards );
		$done = (string) OrderDone::render(
			array(
				'title' => 'Thanks',
				'tone'  => 'warning',
				'facts' => array(
					array(
						'label' => 'Number',
						'value' => '۱۲۳',
					),
				),
			)
		);
		$this->assertStringContainsString( 'bg-warning-soft', $done );
	}
}
