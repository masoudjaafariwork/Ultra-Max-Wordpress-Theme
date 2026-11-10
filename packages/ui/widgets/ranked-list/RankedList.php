<?php
/**
 * RankedList component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A numbered list of products (HOME-08), as in "best sellers": image, a
 * large rank number and the name, in three columns filled top to bottom on
 * desktop, two on tablets and one on phones.
 *
 * @phpstan-type RankedItem array{title: string, href: string, image: Html, rank: string}
 * @phpstan-type RankedListProps array{items: list<RankedItem>, label: string, class?: string}
 */
final class RankedList {

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param RankedListProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$rows  = (int) ceil( count( $props['items'] ) / 3 );
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="min-w-0 border-b border-border"><a href="%1$s" class="flex items-center gap-3 py-3 text-sm text-fg no-underline hover:text-primary"><span class="size-18 shrink-0 [&_img]:size-full [&_img]:object-contain max-md:size-14">%2$s</span><b class="w-7 shrink-0 text-center font-num text-[28px] font-heavy text-primary">%3$s</b><span class="line-clamp-2">%4$s</span></a></li>',
				esc_url( $item['href'] ),
				$item['image'],
				esc_html( $item['rank'] ),
				esc_html( $item['title'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ol class="%1$s" style="--rows:%2$d" aria-label="%3$s">%4$s</ol>',
				esc_attr( Attributes::classes( 'um-ranked-list m-0 grid list-none grid-cols-1 gap-x-6 p-0 md:grid-cols-2 lg:grid-flow-col lg:grid-cols-3 lg:grid-rows-[repeat(var(--rows),auto)]', $props['class'] ?? null ) ),
				$rows,
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
