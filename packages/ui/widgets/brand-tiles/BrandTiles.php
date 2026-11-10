<?php
/**
 * BrandTiles component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Shop by brand (HOME-07): a scrolling row of framed tiles, each with the
 * brand's logo, or its name when there is no logo.
 *
 * @phpstan-type BrandTile array{name: string, href: string, logo?: Html, note?: string}
 * @phpstan-type BrandTilesProps array{items: list<BrandTile>, label: string, class?: string}
 */
final class BrandTiles {

	/**
	 * Renders the tiles.
	 *
	 * @param array $props Props.
	 * @phpstan-param BrandTilesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$visual = isset( $item['logo'] ) && ! $item['logo']->is_empty()
				? sprintf( '<span class="block h-12 w-[70%%] [&_img]:size-full [&_img]:object-contain">%s</span>', $item['logo'] )
				: sprintf( '<b class="text-xl font-heavy tracking-wide text-fg">%s</b>', esc_html( $item['name'] ) );
			$items .= sprintf(
				'<li class="flex-[1_0_140px] max-md:basis-30"><a href="%1$s" class="flex h-24 flex-col items-center justify-center gap-0.5 rounded-lg border border-border bg-surface no-underline transition-shadow duration-200 hover:shadow-md max-md:h-20" aria-label="%2$s">%3$s%4$s</a></li>',
				esc_url( $item['href'] ),
				esc_attr( $item['name'] ),
				$visual,
				isset( $item['note'] ) && '' !== $item['note'] ? sprintf( '<small class="text-xs text-fg-3">%s</small>', esc_html( $item['note'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-brand-tiles m-0 flex list-none gap-3 overflow-x-auto p-0 pb-1 [scrollbar-width:none]', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
