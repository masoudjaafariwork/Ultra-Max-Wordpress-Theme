<?php
/**
 * Tests of the theme core.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Theme\Breadcrumbs;
use UltraMax\Theme\ContactLinks;
use UltraMax\Theme\Dates;
use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\UI;
use UltraMax\Theme\Tokens;

/**
 * Theme core (CORE-01 to CORE-13).
 */
final class ThemeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		delete_option( Store::OPTION );
	}

	public function test_theme_is_active_and_declares_supports(): void {
		$this->assertSame( 'ultra-max', get_template() );
		$this->assertTrue( current_theme_supports( 'woocommerce' ) );
		$this->assertTrue( current_theme_supports( 'title-tag' ) );
		$this->assertArrayHasKey( 'primary', get_registered_nav_menus() );
	}

	public function test_default_tokens_print_nothing(): void {
		$this->assertSame( '', Tokens::css() );
	}

	public function test_preset_custom_colour_radius_and_width_become_variables(): void {
		Store::save( array( 'color_preset' => 'emerald' ) );
		$this->assertStringContainsString( '--um-brand-600:#168550', Tokens::css() );

		Store::save(
			array(
				'color_preset'    => 'custom',
				'color_primary'   => '#ffd400',
				'radius'          => 0,
				'container_width' => 1200,
				'shadow'          => 'none',
			)
		);
		$css = Tokens::css();
		$this->assertStringContainsString( '--um-brand-600:#ffd400', $css );
		$this->assertStringContainsString( '--um-on-primary:#15161c', $css );
		$this->assertStringContainsString( '--um-radius-lg:0px', $css );
		$this->assertStringContainsString( '--um-container:1200px', $css );
		$this->assertStringContainsString( '--um-shadow-md:none', $css );
	}

	public function test_estedad_font_declares_its_face(): void {
		Store::save(
			array(
				'font_body'    => 'estedad',
				'font_numbers' => 'vazirmatn',
			)
		);
		$css = Tokens::css();
		$this->assertStringContainsString( '@font-face{font-family:"Estedad"', $css );
		$this->assertStringContainsString( '--um-font-body:"Estedad"', $css );
		$this->assertStringContainsString( '--um-font-num:"Vazirmatn"', $css );
	}

	public function test_dates_are_jalali_with_persian_digits_on_the_storefront(): void {
		$timestamp = ( new DateTimeImmutable( '2026-11-09 12:00:00', wp_timezone() ) )->getTimestamp();
		$this->assertSame( '۱۴۰۵/۰۸/۱۸', wp_date( 'Y/m/d', $timestamp ) );
		Store::save( array( 'digits' => 'en' ) );
		$this->assertSame( '1405/08/18', wp_date( 'Y/m/d', $timestamp ) );
		$this->assertSame( '2026-11-09T12:00:00+00:00', wp_date( 'c', $timestamp ) );
	}

	public function test_dates_stay_gregorian_in_the_admin(): void {
		set_current_screen( 'dashboard' );
		$timestamp = ( new DateTimeImmutable( '2026-11-09 12:00:00', wp_timezone() ) )->getTimestamp();
		$this->assertSame( '2026/11/09', wp_date( 'Y/m/d', $timestamp ) );
		set_current_screen( 'front' );
	}

	public function test_number_format_uses_persian_digits(): void {
		$this->assertSame( '۱,۲۳۴', number_format_i18n( 1234 ) );
		$this->assertTrue( Dates::persian_digits() );
	}

	public function test_contact_links(): void {
		$this->assertSame( 'https://wa.me/989121234567', ContactLinks::href( 'whatsapp', '۰۹۱۲ ۱۲۳ ۴۵۶۷' ) );
		$this->assertSame( 'https://t.me/shop', ContactLinks::href( 'telegram', '@shop' ) );
		$this->assertSame( 'tel:02112345678', ContactLinks::href( 'phone', '021-1234 5678' ) );
		$this->assertSame( '', ContactLinks::href( 'email', 'not an email' ) );
		Store::save(
			array(
				'contact_items' => array(
					array(
						'type'  => 'eitaa',
						'value' => 'store',
						'label' => '',
					),
					array(
						'type'  => 'phone',
						'value' => '',
						'label' => 'Empty',
					),
				),
			)
		);
		$items = ContactLinks::items();
		$this->assertCount( 1, $items );
		$this->assertSame( 'https://eitaa.com/store', $items[0]['href'] );
	}

	public function test_breadcrumbs_of_a_post(): void {
		$parent = self::factory()->category->create( array( 'name' => 'Parent' ) );
		$child  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'parent' => $parent,
			)
		);
		$post   = self::factory()->post->create(
			array(
				'post_title'    => 'Article',
				'post_category' => array( $child ),
			)
		);
		$this->go_to( get_permalink( $post ) );
		$labels = array_column( Breadcrumbs::trail(), 'label' );
		$this->assertSame( array( 'Home', 'Parent', 'Child', 'Article' ), $labels );
	}

	public function test_visibility_classes(): void {
		$this->assertSame( '', UI::visibility( array() ) );
		$this->assertSame(
			'max-md:hidden lg:hidden',
			UI::visibility(
				array(
					'desktop' => false,
					'tablet'  => true,
					'mobile'  => false,
				)
			)
		);
	}

	public function test_pagination_window(): void {
		$this->assertSame( array( 1, 0, 4, 5, 6, 7, 8, 0, 20 ), \UltraMax\Theme\Lib\UI\Pagination::window( 6, 20 ) );
		$this->assertSame( array( 1, 2, 3 ), \UltraMax\Theme\Lib\UI\Pagination::window( 1, 3 ) );
	}

	public function test_notice_bar_and_contact_buttons_render_accessibly(): void {
		$bar = (string) UI::notice_bar(
			array(
				'id'          => 'n',
				'text'        => 'Sale',
				'close_label' => 'Close',
				'start'       => 100,
			)
		);
		$this->assertStringContainsString( 'data-um-component="notice-bar"', $bar );
		$this->assertStringContainsString( 'data-start="100"', $bar );
		$this->assertStringContainsString( 'aria-label="Close"', $bar );

		$buttons = (string) UI::contact_buttons(
			array(
				'id'    => 'c',
				'label' => 'Contact',
				'items' => array(
					array(
						'label' => 'A',
						'href'  => 'tel:1',
						'icon'  => 'phone',
					),
					array(
						'label' => 'B',
						'href'  => 'https://t.me/x',
						'icon'  => 'send',
					),
				),
			)
		);
		$this->assertStringContainsString( 'aria-expanded="false"', $buttons );
		$this->assertStringContainsString( 'href="tel:1"', $buttons );
		$this->assertStringContainsString( 'target="_blank" rel="noopener"', $buttons );
	}

	public function test_comment_form_reads_wordpress_commenter_keys(): void {
		$args = \UltraMax\Theme\Lib\UI\Comments::form_args( wp_get_current_commenter(), true );
		$this->assertStringContainsString( 'name="author"', $args['fields']['author'] );
		$args = \UltraMax\Theme\Lib\UI\Comments::form_args(
			array(
				'comment_author'       => 'Sara',
				'comment_author_email' => 'a@b.c',
				'comment_author_url'   => '',
			),
			true
		);
		$this->assertStringContainsString( 'value="Sara"', $args['fields']['author'] );
		$this->assertStringContainsString( ' checked', $args['fields']['cookies'] );
	}
}
