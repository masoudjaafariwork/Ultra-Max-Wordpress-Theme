<?php
/**
 * Account page content widget (BLD-07, ACC-01 to ACC-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Page;
use UltraMax\Core\Widgets\Values;

/**
 * The page of the chosen account menu item: dashboard, orders, an order, lists, reviews, addresses or details.
 */
final class AccountContent extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account-content';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Account page content', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-post-content';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07', 'ACC-01', 'ACC-02', 'ACC-04', 'ACC-05', 'ACC-06', 'ACC-07', 'ACC-08' );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return is_user_logged_in() && function_exists( 'wc_get_account_menu_items' ) ? Page::content() : Html::none();
	}
}
