<?php
/**
 * ItemThumbs component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The parcel at a glance (design/checkout.html, CHK-09): a line of small
 * product pictures, each with its quantity when more than one, under a
 * short title such as "Your parcel (4 items)".
 *
 * @phpstan-type ItemThumb array{image: Html, title: string, quantity: int, quantity_text?: string}
 * @phpstan-type ItemThumbsProps array{
 *     title: string,
 *     items: list<ItemThumb>,
 *     class?: string,
 * }
 */
final class ItemThumbs {

	/**
	 * Renders the pictures.
	 *
	 * @param array $props Props.
	 * @phpstan-param ItemThumbsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="relative size-18 shrink-0 rounded-md border border-border bg-surface-2 p-1.5 [&_img]:size-full [&_img]:object-contain" title="%1$s">%2$s<span class="sr-only">%1$s</span>%3$s</li>',
				esc_attr( $item['title'] ),
				$item['image'],
				$item['quantity'] > 1 ? sprintf( '<i class="absolute -bottom-1.5 -start-1.5 grid h-5.5 min-w-5.5 place-items-center rounded-pill border-2 border-surface bg-fg px-1 font-num text-2xs not-italic text-surface">%s</i>', esc_html( $item['quantity_text'] ?? (string) $item['quantity'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><span class="text-sm text-fg-2">%2$s</span><ul class="m-0 flex list-none flex-wrap gap-2 p-0">%3$s</ul></div>',
				esc_attr( Attributes::classes( 'um-scope um-item-thumbs grid gap-2', $props['class'] ?? null ) ),
				esc_html( $props['title'] ),
				$items
			)
		);
	}
}
