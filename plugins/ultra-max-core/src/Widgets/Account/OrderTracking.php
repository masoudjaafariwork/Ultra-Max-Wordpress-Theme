<?php
/**
 * Order tracking widget (BLD-07, ACC-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Account;
use UltraMax\Core\Account\Lookup;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Widgets\Values;

/**
 * The order tracking form: anyone sees an order's progress with its number and mobile number.
 */
final class OrderTracking extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'order-tracking';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Order tracking', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-search';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07', 'ACC-09' );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Modules::enabled( Account::TRACKING ) && function_exists( 'wc_get_order' ) ? Lookup::page() : Html::none();
	}
}
