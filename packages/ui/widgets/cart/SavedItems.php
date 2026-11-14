<?php
/**
 * SavedItems component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The "buy later" list (CART-02): product cards with a button that moves
 * each one back to the cart and one that removes it, and a button that
 * moves them all. Each button is a link that works without JavaScript;
 * the cart behaviour sends it in place.
 *
 * @phpstan-type SavedItem array{key: string, card: Html, restore: string, remove: string}
 * @phpstan-type SavedItemsProps array{
 *     title: string,
 *     items: list<SavedItem>,
 *     labels: array{restore: string, remove: string, all: string, empty: string},
 *     all?: string,
 *     text?: string,
 *     class?: string,
 * }
 */
final class SavedItems {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param SavedItemsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="grid min-w-0 content-start gap-2" data-um-saved="%1$s">%2$s<div class="flex gap-2"><a href="%3$s" class="inline-flex min-h-9 flex-1 items-center justify-center gap-1.5 rounded-md bg-primary-soft px-3 text-sm font-bold text-primary no-underline hover:bg-primary-soft-2 focus-visible:outline-3 focus-visible:outline-primary/40" data-um-cart-action="restore" data-key="%1$s" data-um-focus="restore-%1$s">%4$s%5$s</a><a href="%6$s" class="grid size-9 place-items-center rounded-md text-fg-3 no-underline hover:bg-surface-3 hover:text-danger-text focus-visible:outline-3 focus-visible:outline-primary/40" data-um-cart-action="forget" data-key="%1$s" aria-label="%7$s">%8$s</a></div></li>',
				esc_attr( $item['key'] ),
				$item['card'],
				esc_url( $item['restore'] ),
				Icon::render( 'cart', 'size-4' ),
				esc_html( $props['labels']['restore'] ),
				esc_url( $item['remove'] ),
				esc_attr( $props['labels']['remove'] ),
				Icon::render( 'trash', 'size-4' )
			);
		}
		$all  = isset( $props['all'] ) && array() !== $props['items'] ? sprintf(
			'<a href="%1$s" class="inline-flex min-h-9 items-center rounded-md bg-primary-soft px-3 text-sm font-bold text-primary no-underline hover:bg-primary-soft-2 focus-visible:outline-3 focus-visible:outline-primary/40" data-um-cart-action="restore-all">%2$s</a>',
			esc_url( $props['all'] ),
			esc_html( $props['labels']['all'] )
		) : '';
		$body = array() === $props['items']
			? sprintf( '<p class="m-0 py-6 text-center text-sm text-fg-2">%s</p>', esc_html( $props['labels']['empty'] ) )
			: sprintf( '<ul class="m-0 grid list-none grid-cols-2 gap-4 p-0 md:grid-cols-3 lg:grid-cols-4">%s</ul>', $items );
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="m-0 text-lg font-bold">%2$s</h2>%3$s</div>%4$s</div>%5$s</section>',
				esc_attr( Attributes::classes( 'um-scope um-saved-items flex min-w-0 flex-col gap-4 rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ) ),
				esc_html( $props['title'] ),
				isset( $props['text'] ) ? sprintf( '<p class="m-0 text-sm text-fg-2">%s</p>', esc_html( $props['text'] ) ) : '',
				$all,
				$body
			)
		);
	}
}
