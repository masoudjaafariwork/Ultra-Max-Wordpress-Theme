<?php
/**
 * ShippingOptions component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The shipping methods of one parcel as cards with a radio (design/
 * checkout.html, SHIP-01, SHIP-02): the name, when it arrives, the price.
 *
 * The radios keep WooCommerce's names and classes (`shipping_method[0]`,
 * `.shipping_method`), so WooCommerce's checkout script recalculates the
 * order when one is chosen. A parcel with one method gets a hidden field,
 * as in WooCommerce's own template.
 *
 * @phpstan-type ShippingOption array{value: string, title: string, price: string, note?: string, icon?: string, chosen?: bool}
 * @phpstan-type ShippingOptionsProps array{
 *     index: int,
 *     options: list<ShippingOption>,
 *     label: string,
 *     empty?: string,
 *     class?: string,
 * }
 */
final class ShippingOptions {

	/**
	 * Renders the options.
	 *
	 * @param array $props Props.
	 * @phpstan-param ShippingOptionsProps $props
	 */
	public static function render( array $props ): Html {
		$index = $props['index'];
		if ( array() === $props['options'] ) {
			return Html::trusted( sprintf( '<p class="m-0 rounded-md bg-warning-soft p-3 text-sm text-warning-text">%s</p>', esc_html( $props['empty'] ?? '' ) ) );
		}
		$single = 1 === count( $props['options'] );
		$items  = '';
		foreach ( $props['options'] as $option ) {
			$id     = sprintf( 'shipping_method_%d_%s', $index, sanitize_title( $option['value'] ) );
			$input  = $single
				? sprintf( '<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method">', $index, esc_attr( $id ), esc_attr( $option['value'] ) )
				: sprintf(
					'<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="%2$s" value="%3$s" class="shipping_method size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-[1.5px] border-border-strong bg-surface checked:border-6 checked:border-primary focus-visible:outline-3 focus-visible:outline-primary/40"%4$s>',
					$index,
					esc_attr( $id ),
					esc_attr( $option['value'] ),
					( $option['chosen'] ?? false ) ? ' checked' : ''
				);
			$items .= sprintf(
				'<li><label for="%1$s" class="flex cursor-pointer items-center gap-3 rounded-md border border-border px-4 py-3 has-checked:border-primary has-checked:bg-primary-soft%2$s">%3$s%4$s<span class="grid min-w-0 flex-1"><b class="text-sm">%5$s</b>%6$s</span><b class="whitespace-nowrap font-num text-sm">%7$s</b></label></li>',
				esc_attr( $id ),
				$single ? ' border-primary bg-primary-soft' : '',
				$input,
				Icon::render( $option['icon'] ?? 'truck', 'size-6 shrink-0 text-primary' ),
				esc_html( $option['title'] ),
				isset( $option['note'] ) && '' !== $option['note'] ? sprintf( '<small class="text-xs text-fg-3">%s</small>', esc_html( $option['note'] ) ) : '',
				esc_html( $option['price'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-scope um-shipping-options woocommerce-shipping-methods m-0 grid list-none gap-2 p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
