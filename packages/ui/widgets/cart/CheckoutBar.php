<?php
/**
 * CheckoutBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The bar fixed to the bottom of phones on the cart and the checkout: the
 * amount and the button that goes on (design/cart.html). It sits above the
 * bottom navigation when the store shows one.
 *
 * @phpstan-type CheckoutBarProps array{
 *     label: string,
 *     amount: string|Html,
 *     action: Html,
 *     class?: string,
 * }
 */
final class CheckoutBar {

	/**
	 * Renders the bar.
	 *
	 * @param array $props Props.
	 * @phpstan-param CheckoutBarProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><div class="min-w-0 flex-1 [&>*]:w-full">%2$s</div><div class="grid shrink-0 text-end"><small class="text-xs text-fg-3">%3$s</small><b class="font-num text-lg font-heavy">%4$s</b></div></div>',
				esc_attr( Attributes::classes( 'um-scope um-checkout-bar fixed inset-x-0 bottom-0 z-(--um-z-sticky) flex items-center justify-between gap-3 border-t border-border bg-surface px-(--um-gutter) py-2.5 pb-[calc(10px+env(safe-area-inset-bottom))] shadow-[0_-6px_16px_rgb(21_22_28/6%)] md:hidden', $props['class'] ?? null ) ),
				$props['action'],
				esc_html( $props['label'] ),
				Html::from( $props['amount'] )
			)
		);
	}
}
