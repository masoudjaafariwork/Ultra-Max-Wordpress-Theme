<?php
/**
 * Cart items widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * The cart lines with steppers, the saving of each and "move to buy later".
 */
final class CartItems extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-items';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Cart items', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-related';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CART-01', 'CART-02' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'lines';
	}

	/**
	 * The part's options.
	 *
	 * @param Values $settings Settings.
	 * @return array<string, mixed>
	 */
	protected function args( Values $settings ): array {
		return array( 'heading' => $settings->string( 'heading' ) );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'heading',
				'type'    => 'select',
				'label'   => __( 'Title tag', 'ultra-max-core' ),
				'options' => array(
					'h1' => 'H1',
					'h2' => 'H2',
				),
				'default' => 'h1',
			),
		);
	}
}
