<?php
/**
 * Login and register forms widget (BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Forms;
use UltraMax\Core\Widgets\Values;

/**
 * WooCommerce's login form, and its register form when the store lets shoppers register. Logged in shoppers see a link to their account. Login with a code arrives with the login plugin (phase 5).
 */
final class AccountLogin extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account-login';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Login and register forms', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-lock-user';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07' );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Forms::login();
	}
}
