<?php
/**
 * OrderItems component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The products of an order (ACC-02): picture, name, details such as the
 * quantity and the chosen colour, the line total and buttons such as
 * "Write a review" and "Buy again".
 *
 * @phpstan-type OrderItem array{title: string, href?: string, image: Html, details: list<string>, total: string, actions?: list<Html>}
 * @phpstan-type OrderItemsProps array{
 *     label: string,
 *     items: list<OrderItem>,
 *     class?: string,
 * }
 */
final class OrderItems {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderItemsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$title   = isset( $item['href'] ) && '' !== $item['href']
				? sprintf( '<a href="%1$s" class="text-sm font-bold text-fg no-underline hover:text-primary">%2$s</a>', esc_url( $item['href'] ), esc_html( $item['title'] ) )
				: sprintf( '<span class="text-sm font-bold">%s</span>', esc_html( $item['title'] ) );
			$details = array() === $item['details'] ? '' : sprintf( '<span class="text-xs text-fg-3">%s</span>', esc_html( implode( ' · ', $item['details'] ) ) );
			$actions = implode( '', array_map( 'strval', $item['actions'] ?? array() ) );
			$items  .= sprintf(
				'<li class="flex gap-4 border-0 border-t border-solid border-border py-4 first:border-t-0 first:pt-0 max-md:flex-wrap max-md:gap-3"><span class="size-20 shrink-0 overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain max-md:size-16">%1$s</span><div class="grid min-w-0 flex-1 content-start gap-1">%2$s%3$s<b class="font-num text-sm font-bold">%4$s</b></div>%5$s</li>',
				$item['image'],
				$title,
				$details,
				esc_html( $item['total'] ),
				'' === $actions ? '' : sprintf( '<div class="flex flex-wrap items-start gap-2 max-md:w-full">%s</div>', $actions )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-order-items m-0 list-none p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
