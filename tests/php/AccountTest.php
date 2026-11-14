<?php
/**
 * Tests of the customer account that need no WooCommerce (ACC-01 to
 * ACC-09, SHIP-03, BLD-07).
 *
 * Real orders, the account pages, guest order tracking and the account
 * details form are tested in the browser on the test site: see
 * tests/e2e/phase4-account.spec.ts.
 *
 * @package UltraMax\Tests
 */

use UltraMax\Core\Account\Account;
use UltraMax\Core\Account\Endpoints;
use UltraMax\Core\Account\Lookup;
use UltraMax\Core\Account\Tracking;
use UltraMax\Core\Builder\Types;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\AccountNav;
use UltraMax\Core\Lib\UI\AddressCard;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\InfoGrid;
use UltraMax\Core\Lib\UI\OrderCard;
use UltraMax\Core\Lib\UI\OrderStats;
use UltraMax\Core\Lib\UI\OrderSteps;
use UltraMax\Core\Lib\UI\ProfileCard;
use UltraMax\Core\Lib\UI\TrackingBox;

/**
 * Menu, endpoints, carriers, tries and components.
 */
final class AccountTest extends WP_UnitTestCase {

	public function tear_down(): void {
		update_option( Store::OPTION, array() );
		parent::tear_down();
	}

	public function test_menu_follows_the_design_and_keeps_other_items_before_log_out(): void {
		$menu = Endpoints::menu(
			array(
				'dashboard'       => 'Dashboard',
				'orders'          => 'Orders',
				'downloads'       => 'Downloads',
				'edit-address'    => 'Addresses',
				'edit-account'    => 'Account details',
				'customer-logout' => 'Log out',
				'my-plugin'       => 'Plugin page',
			)
		);
		$this->assertSame( array( 'dashboard', 'orders', 'lists', 'activity', 'edit-address', 'recent', 'edit-account', 'downloads', 'my-plugin', 'customer-logout' ), array_keys( $menu ) );
		$vars = Endpoints::query_vars( array( 'orders' => 'orders' ) );
		$this->assertSame( 'lists', $vars['lists'] );
		$this->assertSame( 'activity', $vars['activity'] );
		$this->assertSame( 'recent', $vars['recent'] );
	}

	public function test_account_templates_and_layout_type(): void {
		$this->assertStringEndsWith( 'templates/account.php', (string) Account::template( 'theirs.php', 'myaccount/my-account.php' ) );
		$this->assertStringEndsWith( 'templates/account-dashboard.php', (string) Account::template( 'theirs.php', 'myaccount/dashboard.php' ) );
		$this->assertSame( 'theirs.php', Account::template( 'theirs.php', 'myaccount/form-login.php' ), 'WooCommerce keeps its forms.' );
		$this->assertTrue( Types::is_ready( 'account' ) );
		$this->assertSame( 'account', Types::all()['account']['location'] );
	}

	public function test_carrier_links_put_the_code_in_place(): void {
		Store::save(
			array(
				'tracking_carriers' => array(
					array(
						'name' => 'Fast post',
						'link' => 'https://track.example.com/?id={code}',
					),
					array(
						'name' => 'Courier',
						'link' => '',
					),
				),
			)
		);
		$this->assertSame( 'https://track.example.com/?id=AB%2012', Tracking::link( 'Fast post', 'AB 12' ) );
		$this->assertSame( '', Tracking::link( 'Courier', '123' ), 'A carrier without a link gets none.' );
		$this->assertSame( '', Tracking::link( 'Unknown', '123' ) );
	}

	public function test_order_lookup_allows_ten_tries_in_fifteen_minutes(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		for ( $i = 0; $i < Lookup::TRIES; $i++ ) {
			$this->assertTrue( Lookup::try_once() );
		}
		$this->assertFalse( Lookup::try_once(), 'The eleventh try waits.' );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$this->assertTrue( Lookup::try_once(), 'Each visitor counts on their own.' );
	}

	public function test_account_menu_marks_the_current_page(): void {
		$html = (string) AccountNav::render(
			array(
				'label' => 'Account menu',
				'items' => array(
					array(
						'label'   => 'Orders',
						'href'    => 'https://example.com/my-account/orders/',
						'icon'    => 'box',
						'current' => true,
					),
					array(
						'label' => 'Log out',
						'href'  => 'https://example.com/my-account/customer-logout/',
						'icon'  => 'logout',
						'out'   => true,
						'count' => '۲',
					),
				),
			)
		);
		$this->assertSame( 1, substr_count( $html, 'aria-current="page"' ) );
		$this->assertStringContainsString( 'aria-label="Account menu"', $html );
		$this->assertStringContainsString( '۲', $html );
		$profile = (string) ProfileCard::render(
			array(
				'name' => 'سارا محمدی',
				'line' => '09121234567',
				'edit' => array(
					'label' => 'Edit',
					'href'  => 'https://example.com/edit',
				),
			)
		);
		$this->assertStringContainsString( '>س<', $profile, 'The first letter stands for the shopper.' );
		$this->assertStringContainsString( 'aria-label="Edit"', $profile );
	}

	public function test_order_parts_render(): void {
		$stats = (string) OrderStats::render(
			array(
				'label' => 'Orders by status',
				'items' => array(
					array(
						'label' => 'Current',
						'count' => '2 orders',
						'icon'  => 'clock',
						'tone'  => 'warning',
						'href'  => 'https://example.com/orders/?status=current',
					),
				),
			)
		);
		$this->assertStringContainsString( 'status=current', $stats );
		$card = (string) OrderCard::render(
			array(
				'href'   => 'https://example.com/view-order/5/',
				'label'  => 'Order 5',
				'status' => array(
					'label' => 'Sent',
					'tone'  => 'info',
					'icon'  => 'truck',
				),
				'meta'   => array(
					array(
						'label' => 'Total',
						'value' => '1,000',
						'num'   => true,
					),
				),
				'images' => array( Html::none(), Html::none() ),
				'more'   => '+3',
			)
		);
		$this->assertStringContainsString( '<span class="sr-only">Order 5</span>', $card, 'The card names its order for screen readers without hiding its text.' );
		$this->assertStringContainsString( '+3', $card );
		$steps = (string) OrderSteps::render(
			array(
				'label' => 'Progress',
				'steps' => array(
					array(
						'label' => 'Placed',
						'icon'  => 'file',
						'state' => 'done',
						'date'  => '1 Aban',
					),
					array(
						'label' => 'Sent',
						'icon'  => 'truck',
						'state' => 'current',
					),
					array(
						'label' => 'Delivered',
						'icon'  => 'home',
						'state' => 'todo',
					),
				),
			)
		);
		$this->assertSame( 1, substr_count( $steps, 'aria-current="step"' ) );
		$this->assertStringStartsWith( '<ol', $steps );
		$box = (string) TrackingBox::render(
			array(
				'title'  => 'Post tracking code',
				'code'   => '248910007342',
				'copy'   => 'Copy',
				'copied' => 'Copied',
				'link'   => array(
					'label' => 'Follow',
					'href'  => 'https://track.example.com/?id=248910007342',
				),
			)
		);
		$this->assertStringContainsString( 'data-um-component="copy-code"', $box );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $box );
		$this->assertStringContainsString( 'dir="ltr"', $box );
	}

	public function test_details_and_addresses_show_blanks(): void {
		$grid = (string) InfoGrid::render(
			array(
				'items' => array(
					array(
						'label' => 'National code',
						'value' => '',
					),
					array(
						'label' => 'Mobile',
						'value' => '09121234567',
						'dir'   => 'ltr',
						'badge' => array(
							'label' => 'Verified',
							'tone'  => 'success',
						),
					),
				),
				'blank' => 'Not given',
			)
		);
		$this->assertStringContainsString( 'Not given', $grid );
		$this->assertStringContainsString( 'Verified', $grid );
		$card = (string) AddressCard::render(
			array(
				'title' => 'Shipping address',
				'lines' => array(),
				'empty' => 'Not saved yet',
			)
		);
		$this->assertStringContainsString( 'Not saved yet', $card );
		$this->assertStringContainsString( '<address', $card );
	}
}
