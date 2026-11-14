<?php
/**
 * Money component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * An amount with a smaller, quieter currency name after it, such as
 * "3,250,000 Toman" in a cart line (design/cart.html). The amount keeps the
 * look of where it is placed.
 *
 * @phpstan-type MoneyProps array{amount: string, currency: string}
 */
final class Money {

	/**
	 * Renders the amount.
	 *
	 * @param array $props Props.
	 * @phpstan-param MoneyProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted( sprintf( '%1$s <small class="text-xs font-regular text-fg-3">%2$s</small>', esc_html( $props['amount'] ), esc_html( $props['currency'] ) ) );
	}
}
