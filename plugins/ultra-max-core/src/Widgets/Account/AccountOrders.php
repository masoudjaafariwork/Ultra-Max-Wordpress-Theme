<?php
/**
 * Orders by status widget (BLD-07, ACC-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Account\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Tiles of the shopper's orders by status, and the latest orders of each in tabs.
 */
final class AccountOrders extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'account-orders';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Orders by status', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-cart-medium';
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
				'id'      => 'latest',
				'type'    => 'switch',
				'label'   => __( 'Show the latest orders', 'ultra-max-core' ),
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
		return is_user_logged_in() && function_exists( 'wc_get_orders' ) ? Html::from( array( Page::stats(), $settings->bool( 'latest' ) ? Page::latest() : Html::none() ) ) : Html::none();
	}
}
