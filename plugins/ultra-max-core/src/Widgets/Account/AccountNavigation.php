<?php
/**
 * Account menu widget (BLD-07, ACC-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Page;
use UltraMax\Core\Widgets\Values;

/**
 * The account menu with the shopper's name, for an account designed with Elementor.
 */
final class AccountNavigation extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account-navigation';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Account menu', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-nav-menu';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07', 'ACC-01' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'profile',
				'type'    => 'switch',
				'label'   => __( 'Show the shopper\'s name', 'ultra-max-core' ),
				'default' => true,
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return is_user_logged_in() && function_exists( 'wc_get_account_menu_items' ) ? Html::from( array( $settings->bool( 'profile' ) ? Page::profile() : Html::none(), Page::nav() ) ) : Html::none();
	}
}
