<?php
/**
 * PostTiles component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Latest articles on the home page (HOME-10): four framed cards with an
 * image, title, Jalali date and reading time; a scrolling row on phones.
 *
 * @phpstan-type PostTile array{title: string, href: string, image: Html, date: string, datetime: string, meta?: string}
 * @phpstan-type PostTilesProps array{items: list<PostTile>, label: string, columns?: 2|3|4, class?: string}
 */
final class PostTiles {

	/**
	 * Renders the cards.
	 *
	 * @param array $props Props.
	 * @phpstan-param PostTilesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$columns = match ( $props['columns'] ?? 4 ) {
			2       => 'md:grid-cols-2',
			3       => 'md:grid-cols-2 lg:grid-cols-3',
			default => 'md:grid-cols-2 xl:grid-cols-4',
		};
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="min-w-0 max-md:w-60 max-md:shrink-0"><a href="%1$s" class="flex h-full flex-col overflow-hidden rounded-lg border border-border bg-surface text-fg no-underline transition-shadow duration-200 hover:shadow-md"><span class="block aspect-[16/10] overflow-hidden bg-surface-3 [&_img]:size-full [&_img]:object-cover">%2$s</span><span class="flex flex-col gap-2 p-4"><b class="line-clamp-2 text-sm leading-[1.8]">%3$s</b><small class="text-xs text-fg-3"><time datetime="%4$s">%5$s</time>%6$s</small></span></a></li>',
				esc_url( $item['href'] ),
				$item['image'],
				esc_html( $item['title'] ),
				esc_attr( $item['datetime'] ),
				esc_html( $item['date'] ),
				isset( $item['meta'] ) && '' !== $item['meta'] ? ' · ' . esc_html( $item['meta'] ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-post-tiles m-0 list-none gap-4 p-0 max-md:flex max-md:overflow-x-auto max-md:[scrollbar-width:none] md:grid', $columns, $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
