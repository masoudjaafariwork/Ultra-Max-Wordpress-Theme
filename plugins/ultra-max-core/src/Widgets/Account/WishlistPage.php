<?php
/**
 * Wishlist widget (BLD-07, SAVE-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Saved\Wishlist;
use UltraMax\Core\Widgets\Values;

/**
 * The shopper's wishlist, as on the wishlist page.
 */
final class WishlistPage extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'wishlist-page';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Wishlist products', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-heart-o';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07', 'SAVE-01' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'saved', 'product-card' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'columns',
				'type'    => 'number',
				'label'   => __( 'Cards in a row on desktop', 'ultra-max-core' ),
				'default' => 5,
				'min'     => 2,
				'max'     => 6,
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Wishlist::page( $settings->int( 'columns' ) );
	}
}
