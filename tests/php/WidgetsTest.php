<?php
/**
 * Tests of the widgets and their helpers (HDR-02, HOME-01 to HOME-12, BLD-08, BLD-10 to BLD-12).
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Elementor\DynamicTags;
use UltraMax\Core\Elementor\Kit;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Store\Forms;
use UltraMax\Core\Store\Menus;
use UltraMax\Core\Widgets\General\Table;
use UltraMax\Core\Widgets\Header\Html as HtmlWidget;
use UltraMax\Core\Widgets\Settings;
use UltraMax\Core\Widgets\Shortcodes;
use UltraMax\Core\Widgets\Store\Posts;
use UltraMax\Core\Widgets\Store\WooHook;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widgets;

/**
 * One definition, three ways to show it.
 */
final class WidgetsTest extends WP_UnitTestCase {

	public function test_every_listed_widget_class_exists(): void {
		$this->assertCount( 102, Widgets::all(), 'Every class listed in Widgets::classes() exists.' );
	}

	public function test_every_widget_renders_its_defaults_without_notices(): void {
		foreach ( Widgets::all() as $id => $widget ) {
			$this->assertSame( $id, $widget->id() );
			$this->assertNotSame( '', $widget->title() );
			$this->assertContains( $widget->group(), array( 'header', 'store', 'shop', 'product', 'account', 'general', 'footer' ) );
			foreach ( $widget->assets() as $asset ) {
				$this->assertContains( $asset, Assets::WIDGETS, "$id asks for an unknown asset $asset." );
			}
			$html = (string) $widget->output( array() );
			$this->assertIsString( $html );
			$this->assertStringNotContainsString( '<script', $html, "$id never prints a script tag." );
		}
	}

	public function test_widget_assets_match_the_built_folders(): void {
		$found   = glob( dirname( __DIR__, 2 ) . '/packages/ui/widgets/*', GLOB_ONLYDIR );
		$folders = array_map( 'basename', is_array( $found ) ? $found : array() );
		sort( $folders );
		$listed = Assets::WIDGETS;
		sort( $listed );
		$this->assertSame( $folders, $listed );
	}

	public function test_every_widget_is_a_shortcode(): void {
		foreach ( Widgets::all() as $widget ) {
			$this->assertTrue( shortcode_exists( Shortcodes::tag( $widget ) ), $widget->id() );
		}
		$html = do_shortcode( '[um_quick_links]' );
		$this->assertStringContainsString( 'um-quick-links', $html );
		$this->assertTrue( wp_style_is( Assets::widget_handle( 'quick-links' ), 'enqueued' ), 'A shortcode loads its own styles (BLD-12).' );
	}

	public function test_settings_from_elementor_and_shortcodes_normalize_the_same(): void {
		$fields    = array(
			array(
				'id'     => 's',
				'label'  => 'S',
				'fields' => array(
					array(
						'id'      => 'on',
						'type'    => 'switch',
						'label'   => 'On',
						'default' => false,
					),
					array(
						'id'      => 'n',
						'type'    => 'number',
						'label'   => 'N',
						'default' => 4,
						'min'     => 1,
						'max'     => 8,
					),
					array(
						'id'    => 'link',
						'type'  => 'url',
						'label' => 'L',
					),
					array(
						'id'    => 'ids',
						'type'  => 'posts',
						'label' => 'P',
					),
					array(
						'id'      => 'pick',
						'type'    => 'select',
						'label'   => 'S',
						'options' => array(
							'2' => 'Two',
							'4' => 'Four',
						),
						'default' => '4',
					),
				),
			),
		);
		$elementor = Settings::normalize(
			$fields,
			array(
				'on'   => 'yes',
				'n'    => '99',
				'link' => array(
					'url'         => 'https://a.test',
					'is_external' => 'on',
				),
				'ids'  => array( '3', '7' ),
				'pick' => 2,
			)
		);
		$shortcode = Settings::normalize(
			$fields,
			array(
				'on'   => 'true',
				'n'    => '99',
				'link' => 'https://a.test',
				'ids'  => '3,7',
				'pick' => '2',
			)
		);
		$this->assertTrue( $elementor['on'] );
		$this->assertSame( 8, $elementor['n'], 'Numbers stay within their limits.' );
		$this->assertSame( array( 3, 7 ), $elementor['ids'] );
		$this->assertSame( '2', $elementor['pick'] );
		$this->assertTrue( $elementor['link']['external'] );
		$this->assertSame( $elementor['ids'], $shortcode['ids'] );
		$this->assertSame( $elementor['on'], $shortcode['on'] );
		$this->assertSame( 'https://a.test', ( new Values( $shortcode ) )->href( 'link' ) );
		$this->assertSame( '4', Settings::normalize( $fields, array( 'pick' => 'bogus' ) )['pick'] );
	}

	public function test_woocommerce_hook_widget_runs_only_allowed_actions(): void {
		$this->assertSame(
			'',
			WooHook::hook(
				new Values(
					array(
						'hook'   => 'custom',
						'custom' => 'wp_head',
					)
				)
			)
		);
		$this->assertSame(
			'woocommerce_after_cart',
			WooHook::hook(
				new Values(
					array(
						'hook'   => 'custom',
						'custom' => 'woocommerce_after_cart',
					)
				)
			)
		);
		add_action( 'woocommerce_single_product_summary', static fn () => print( 'FROM A PLUGIN' ) );
		$this->assertSame( 'FROM A PLUGIN', (string) Widgets::get( 'woo-hook' )->output( array( 'hook' => 'woocommerce_single_product_summary' ) ) );
	}

	public function test_html_widget_removes_scripts(): void {
		$html = (string) ( new HtmlWidget() )->output( array( 'code' => '<b>Hi</b><script>alert(1)</script>' ) );
		$this->assertStringContainsString( '<b>Hi</b>', $html );
		$this->assertStringNotContainsString( '<script', $html );
	}

	public function test_table_rows_parse(): void {
		$this->assertSame( array( array( 'a', 'b' ), array( '1', '2' ) ), Table::parse( "a | b\n\n1|2\n" ) );
	}

	public function test_reading_time(): void {
		$this->assertSame( 1, Posts::minutes( 'One two' ) );
		$this->assertSame( 3, Posts::minutes( str_repeat( 'word ', 450 ) ) );
	}

	public function test_dynamic_tags_values(): void {
		Store::save( array( 'digits' => 'fa' ) );
		$this->assertSame( '۱۴۰۵', DynamicTags::digits( '1405' ) );
		$post = self::factory()->post->create( array( 'post_date' => '2026-11-09 10:00:00' ) );
		$this->go_to( get_permalink( $post ) );
		the_post();
		$this->assertSame( '۱۴۰۵/۰۸/۱۸', DynamicTags::date( 'published', 'Y/m/d' ) );
		update_post_meta( $post, 'size', 'XL' );
		update_post_meta( $post, '_secret', 'no' );
		$this->assertSame( 'XL', DynamicTags::field( 'size' ) );
		$this->assertSame( '', DynamicTags::field( '_secret' ), 'Private fields never show.' );
		delete_option( Store::OPTION );
	}

	public function test_forms_accept_iranian_mobiles_and_store_subscribers(): void {
		$this->assertTrue( Forms::phone( '09121234567' ) );
		$this->assertTrue( Forms::phone( '۰۹۱۲ ۱۲۳ ۴۵۶۷' ) );
		$this->assertTrue( Forms::phone( '+989121234567' ) );
		$this->assertFalse( Forms::phone( '0212223344' ) );
		delete_option( Forms::SUBSCRIBERS );
		$this->assertSame( 'subscribed', Forms::subscribe( 'A@Example.com' ) );
		$this->assertSame( 'subscribed', Forms::subscribe( 'a@example.com' ) );
		$this->assertSame( 'invalid', Forms::subscribe( 'nope' ) );
		$this->assertCount( 1, get_option( Forms::SUBSCRIBERS ) );
	}

	public function test_forms_reject_missing_token_and_bots(): void {
		$_POST = array(
			'um_nonce' => 'bad',
			'contact'  => 'a@example.com',
		);
		$this->assertSame( 'error', Forms::process( Forms::ACTION_NEWSLETTER ) );
		$_POST = array(
			'um_nonce'   => wp_create_nonce( Forms::ACTION_NEWSLETTER ),
			'contact'    => 'a@example.com',
			'um_website' => 'spam',
		);
		$this->assertSame( 'error', Forms::process( Forms::ACTION_NEWSLETTER ), 'The hidden trap field must stay empty.' );
		$_POST = array(
			'um_nonce' => wp_create_nonce( Forms::ACTION_NEWSLETTER ),
			'contact'  => '09121234567',
		);
		$this->assertSame( 'subscribed', Forms::process( Forms::ACTION_NEWSLETTER ) );
		$_POST = array();
	}

	public function test_menu_badges(): void {
		$this->assertSame( 'hot', Menus::badge( 'hot', '' )['tone'] ?? '' );
		$this->assertSame( 'Sale', Menus::badge( 'custom', 'Sale' )['label'] ?? '' );
		$this->assertNull( Menus::badge( 'custom', '' ) );
		$this->assertNull( Menus::badge( '', '' ) );
	}

	public function test_kit_follows_our_colours_fonts_and_width(): void {
		Store::save(
			array(
				'color_preset'    => 'emerald',
				'color_accent'    => '#ff0066',
				'font_body'       => 'estedad',
				'container_width' => 1200,
			)
		);
		$kit = Kit::settings();
		$this->assertSame( '#168550', $kit['system_colors'][0]['color'] );
		$this->assertSame( '#ff0066', $kit['system_colors'][1]['color'] );
		$this->assertSame( 'Estedad', $kit['system_typography'][2]['typography_font_family'] );
		$this->assertSame( 1200, $kit['container_width']['size'] );
		$this->assertSame( 1023, $kit['viewport_tablet'], 'Elementor tablets end where the theme desktop starts.' );
		$this->assertSame( 'ultra-max', Kit::fonts( array() )['Vazirmatn'] ?? '', 'Our fonts never load from Google.' );
		delete_option( Store::OPTION );
	}
}
