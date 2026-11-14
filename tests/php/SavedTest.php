<?php
/**
 * Tests of the wishlist, compare and quick view that need no WooCommerce
 * (SAVE-01 to SAVE-05).
 *
 * Saving real products, the one-category rule, the pages and the quick
 * view window are tested in the browser on the test site: see
 * tests/e2e/phase4-account.spec.ts.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Lib\UI\CompareTable;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\QuickView;
use UltraMax\Core\Lib\UI\SaveButton;
use UltraMax\Core\Lib\UI\Toast;
use UltraMax\Core\Saved\Lists;
use UltraMax\Core\Store\Pages;

/**
 * Lists, pages and components.
 */
final class SavedTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $_COOKIE[ Lists::COOKIES[ Lists::WISHLIST ] ], $_COOKIE[ Lists::COOKIES[ Lists::COMPARE ] ] );
		wp_set_current_user( 0 );
		delete_option( Pages::OPTION );
		remove_all_filters( 'ultra_max_pages' );
		parent::tear_down();
	}

	public function test_cookie_values_keep_only_positive_unique_ids_up_to_the_limit(): void {
		$this->assertSame( array( 3, 7 ), Lists::parse( '3.7.3.-2.abc.0', Lists::WISHLIST ) );
		$this->assertSame( array( 1, 2, 3, 4 ), Lists::parse( '1.2.3.4.5.6', Lists::COMPARE ), 'At most four products are compared.' );
		$this->assertSame( array(), Lists::parse( '', Lists::WISHLIST ) );
		$this->assertFalse( Lists::known( 'later' ) );
	}

	public function test_guest_lists_live_in_cookies_and_newest_comes_first(): void {
		Lists::add( Lists::WISHLIST, 10 );
		Lists::add( Lists::WISHLIST, 20 );
		Lists::add( Lists::WISHLIST, 10 );
		$this->assertSame( array( 10, 20 ), Lists::ids( Lists::WISHLIST ) );
		$this->assertSame( '10.20', $_COOKIE['um_wishlist'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Reading what the test wrote.
		Lists::remove( Lists::WISHLIST, 10 );
		$this->assertSame( array( 20 ), Lists::ids( Lists::WISHLIST ) );
		Lists::remove( Lists::WISHLIST, 20 );
		$this->assertArrayNotHasKey( 'um_wishlist', $_COOKIE, 'An empty list removes its cookie.' );
	}

	public function test_a_shoppers_wishlist_is_kept_with_their_account(): void {
		$user = self::factory()->user->create();
		wp_set_current_user( $user );
		Lists::add( Lists::WISHLIST, 5 );
		$this->assertSame( array( 5 ), get_user_meta( $user, Lists::META, true ) );
		$this->assertArrayNotHasKey( 'um_wishlist', $_COOKIE );
		Lists::add( Lists::COMPARE, 8 );
		$this->assertSame( '8', $_COOKIE['um_compare'] ?? '', 'The comparison stays in the browser, even when logged in.' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Reading what the test wrote.
	}

	public function test_a_guests_wishlist_joins_the_account_on_login(): void {
		$user = self::factory()->user->create();
		update_user_meta( $user, Lists::META, array( 4, 5 ) );
		$_COOKIE['um_wishlist'] = '9.4';
		Lists::merge( 'shopper', get_user_by( 'id', $user ) );
		$this->assertSame( array( 9, 4, 5 ), Lists::ids( Lists::WISHLIST, $user ) );
		$this->assertArrayNotHasKey( 'um_wishlist', $_COOKIE, 'The guest list goes once it joined the account.' );
	}

	public function test_store_pages_are_made_once_and_again_from_the_bin(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter(
			'ultra_max_pages',
			static fn ( array $pages ): array => $pages + array(
				'wishlist' => array(
					'title'     => 'Wishlist',
					'slug'      => 'wishlist',
					'shortcode' => 'um_wishlist_page',
				),
			)
		);
		Pages::ensure();
		$first = Pages::id( 'wishlist' );
		$this->assertGreaterThan( 0, $first );
		$this->assertStringContainsString( '[um_wishlist_page]', (string) get_post_field( 'post_content', $first ) );
		Pages::ensure();
		$this->assertSame( $first, Pages::id( 'wishlist' ), 'An existing page is kept.' );
		wp_trash_post( $first );
		Pages::ensure();
		$this->assertNotSame( $first, Pages::id( 'wishlist' ), 'A page in the bin is made again.' );
		$this->assertSame( get_permalink( Pages::id( 'wishlist' ) ), Pages::url( 'wishlist' ) );
	}

	public function test_save_buttons_start_unpressed_so_pages_stay_cacheable(): void {
		$heart = (string) SaveButton::render(
			array(
				'list'    => 'wishlist',
				'product' => 12,
				'label'   => 'Add to wishlist',
			)
		);
		$this->assertStringContainsString( 'data-um-save="wishlist"', $heart );
		$this->assertStringContainsString( 'aria-pressed="false"', $heart );
		$this->assertStringContainsString( 'data-id="12"', $heart );
		$this->assertStringContainsString( 'aria-label="Add to wishlist"', $heart );
		$quick = (string) SaveButton::render(
			array(
				'list'    => 'quick',
				'product' => 12,
				'label'   => 'Quick view',
				'look'    => 'button',
				'text'    => 'Look',
			)
		);
		$this->assertStringContainsString( 'data-um-quick="12"', $quick );
		$this->assertStringNotContainsString( 'aria-pressed=', $quick, 'Quick view is not a toggle.' );
		$this->assertStringContainsString( '<span>Look</span>', $quick );
	}

	public function test_compare_rows_mark_differences(): void {
		$this->assertTrue( CompareTable::differs( array( '6 GB', '8 GB' ) ) );
		$this->assertFalse( CompareTable::differs( array( '6  GB', '6 GB ' ) ), 'Spaces do not make a difference.' );
		$this->assertTrue( CompareTable::differs( array( 'Black', '' ) ), 'A missing value is a difference.' );
		$html = (string) CompareTable::render(
			array(
				'caption'     => 'Side by side',
				'differences' => 'Only differences',
				'products'    => array(
					array(
						'id'     => 1,
						'title'  => 'One',
						'href'   => 'https://example.com/one',
						'image'  => Html::none(),
						'price'  => Html::text( '10' ),
						'remove' => Html::none(),
					),
					array(
						'id'     => 2,
						'title'  => 'Two',
						'href'   => 'https://example.com/two',
						'image'  => Html::none(),
						'price'  => Html::text( '20' ),
						'remove' => Html::none(),
					),
				),
				'rows'        => array(
					array(
						'label'  => 'Memory',
						'values' => array( '6 GB', '8 GB' ),
						'group'  => 'General',
					),
					array(
						'label'  => 'Colour',
						'values' => array( 'Black', 'Black' ),
						'group'  => 'General',
					),
				),
				'add'         => array(
					'label' => 'Add a product',
					'href'  => 'https://example.com/shop',
				),
			)
		);
		$this->assertSame( 1, substr_count( $html, 'data-differs' ) );
		$this->assertSame( 1, substr_count( $html, 'um-compare-group' ), 'One heading for a group.' );
		$this->assertStringContainsString( 'data-um-compare-differences', $html );
		$this->assertStringContainsString( 'Add a product', $html );
		$this->assertStringContainsString( '<caption class="sr-only">Side by side</caption>', $html );
	}

	public function test_toast_and_quick_view_shell(): void {
		$toast = (string) Toast::render(
			array(
				'close' => 'Close',
				'lists' => 'wishlist',
			)
		);
		$this->assertStringContainsString( 'role="status"', $toast );
		$this->assertStringContainsString( 'data-lists="wishlist"', $toast );
		$this->assertStringContainsString( ' hidden', $toast );
		$shell = (string) QuickView::shell(
			array(
				'title'    => 'Quick view',
				'endpoint' => '/?wc-ajax=um_quick_view',
				'loading'  => 'Loading',
				'error'    => 'Sorry',
				'assets'   => array(
					'css' => array( 'https://example.com/a.css' ),
					'js'  => array( 'https://example.com/a.js' ),
				),
			)
		);
		$this->assertStringContainsString( 'id="' . QuickView::ID . '"', $shell );
		$this->assertStringContainsString( 'data-um-component="quick-view"', $shell );
		$this->assertStringContainsString( 'a.js', html_entity_decode( $shell ) );
	}
}
