<?php
/**
 * OrderLines component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The products of a placed order with their pictures (CHK-09, BLD-06),
 * then its amounts: subtotal, discount, shipping, total.
 *
 * @phpstan-type OrderItem array{image: Html, title: string, quantity: string, total: string|Html, meta?: string, href?: string}
 * @phpstan-type OrderRow array{label: string, value: string|Html, strong?: bool}
 * @phpstan-type OrderLinesProps array{
 *     title: string,
 *     items: list<OrderItem>,
 *     rows?: list<OrderRow>,
 *     after?: Html,
 *     class?: string,
 * }
 */
final class OrderLines {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderLinesProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$title  = isset( $item['href'] ) && '' !== $item['href']
				? sprintf( '<a href="%1$s" class="line-clamp-2 text-sm font-bold text-fg no-underline hover:text-primary">%2$s</a>', esc_url( $item['href'] ), esc_html( $item['title'] ) )
				: sprintf( '<span class="line-clamp-2 text-sm font-bold">%s</span>', esc_html( $item['title'] ) );
			$items .= sprintf(
				'<li class="flex items-center gap-3 border-t border-border py-3 first:border-t-0"><span class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain">%1$s</span><span class="grid min-w-0 flex-1 gap-1">%2$s%3$s<small class="font-num text-xs text-fg-3">%4$s</small></span><b class="whitespace-nowrap font-num text-sm">%5$s</b></li>',
				$item['image'],
				$title,
				isset( $item['meta'] ) && '' !== $item['meta'] ? sprintf( '<small class="text-xs text-fg-2">%s</small>', esc_html( $item['meta'] ) ) : '',
				esc_html( $item['quantity'] ),
				Html::from( $item['total'] )
			);
		}
		$rows = '';
		foreach ( $props['rows'] ?? array() as $row ) {
			$strong = $row['strong'] ?? false;
			$rows  .= sprintf(
				'<dt class="%1$s">%2$s</dt><dd class="m-0 text-end font-num %3$s">%4$s</dd>',
				$strong ? 'font-bold' : 'text-fg-2',
				esc_html( $row['label'] ),
				$strong ? 'text-md font-heavy' : '',
				Html::from( $row['value'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><h2 class="m-0 text-md font-bold">%2$s</h2><ul class="m-0 list-none p-0">%3$s</ul>%4$s%5$s</section>',
				esc_attr( Attributes::classes( 'um-scope um-order-items grid gap-3 rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ) ),
				esc_html( $props['title'] ),
				$items,
				'' === $rows ? '' : sprintf( '<dl class="m-0 grid grid-cols-[1fr_auto] gap-x-2 gap-y-2 border-t border-border pt-3 text-sm">%s</dl>', $rows ),
				$props['after'] ?? ''
			)
		);
	}
}
