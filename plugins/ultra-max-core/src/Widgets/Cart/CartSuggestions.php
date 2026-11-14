<?php
/**
 * Goes with your cart widget (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * Complementary products of the cart.
 */
final class CartSuggestions extends CartPartWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'cart-suggestions';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Goes with your cart', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-posts-carousel';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06', 'CART-05' );
	}

	/**
	 * The part.
	 */
	protected function part(): string {
		return 'suggestions';
	}

	/**
	 * The part's options.
	 *
	 * @param Values $settings Settings.
	 * @return array<string, mixed>
	 */
	protected function args( Values $settings ): array {
		return array( 'count' => max( 4, $settings->int( 'count' ) ) );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'count',
				'type'    => 'number',
				'label'   => __( 'Number of products', 'ultra-max-core' ),
				'default' => 10,
				'min'     => 4,
				'max'     => 24,
			),
		);
	}
}
