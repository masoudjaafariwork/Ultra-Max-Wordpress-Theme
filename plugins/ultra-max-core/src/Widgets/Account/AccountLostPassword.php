<?php
/**
 * Lost password form widget (BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Forms;
use UltraMax\Core\Widgets\Values;

/**
 * WooCommerce's form that sends a link to choose a new password.
 */
final class AccountLostPassword extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account-lost-password';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Lost password form', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-lock';
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
		return Forms::lost_password();
	}
}
