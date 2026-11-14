<?php
/**
 * ProductPrice component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The price of a product page: discount, current price, currency and the
 * old price (PDP-05). When a variation is chosen the purchase behaviour
 * writes its price into every ProductPrice of the same product, so the buy
 * box, the sticky bar and a designed layout all change together.
 *
 * `prefix` is a word before the price, such as "from" for a variable
 * product before a choice.
 *
 * @phpstan-type ProductPriceProps array{
 *     product: int,
 *     price: string,
 *     currency: string,
 *     regular?: string,
 *     discount?: string,
 *     call?: string,
 *     prefix?: string,
 *     size?: 'lg'|'md'|'sm',
 *     class?: string,
 * }
 */
final class ProductPrice {

	/**
	 * Renders the price.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductPriceProps $props
	 */
	public static function render( array $props ): Html {
		$size = Props::one_of( 'ProductPrice', $props, 'size', array( 'lg', 'md', 'sm' ) );
		$call = $props['call'] ?? '';
		$now  = match ( $size ) {
			'lg'    => 'text-2xl',
			'sm'    => 'text-md',
			default => 'text-lg',
		};
		return Html::trusted(
			sprintf(
				'<div%1$s><p class="m-0 text-md font-bold text-primary"%2$s data-um-price-call>%3$s</p><div class="flex flex-col items-end"%4$s><div class="flex w-full items-center justify-end gap-2"><span class="me-auto inline-flex h-6 items-center rounded-pill bg-primary px-2 font-num text-xs font-bold text-on-primary"%5$s data-um-price-off>%6$s</span><span class="text-xs text-fg-3"%7$s data-um-price-prefix>%8$s</span><span class="font-num font-heavy whitespace-nowrap %9$s"><span data-um-price-now>%10$s</span><span class="ms-1 text-xs font-regular text-fg-2">%11$s</span></span></div><del class="font-num text-sm text-fg-3"%12$s data-um-price-old>%13$s</del></div></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-product-price', $props['class'] ?? null ),
						'data-um-price-for' => (string) $props['product'],
					)
				),
				'' === $call ? ' hidden' : '',
				esc_html( $call ),
				'' === $call ? '' : ' hidden',
				'' === ( $props['discount'] ?? '' ) ? ' hidden' : '',
				esc_html( $props['discount'] ?? '' ),
				'' === ( $props['prefix'] ?? '' ) ? ' hidden' : '',
				esc_html( $props['prefix'] ?? '' ),
				esc_attr( $now ),
				esc_html( $props['price'] ),
				esc_html( $props['currency'] ),
				'' === ( $props['regular'] ?? '' ) ? ' hidden' : '',
				esc_html( $props['regular'] ?? '' )
			)
		);
	}
}
