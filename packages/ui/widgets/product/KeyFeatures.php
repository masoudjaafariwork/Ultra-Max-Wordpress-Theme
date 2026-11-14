<?php
/**
 * KeyFeatures component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A few specifications in tiles at the top of a product page (PDP-06),
 * with a link to the full table. Phones scroll the tiles sideways.
 *
 * @phpstan-type KeyFeaturesProps array{
 *     title: string,
 *     items: list<array{label: string, value: string}>,
 *     more?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class KeyFeatures {

	/**
	 * Renders the tiles, or nothing without items.
	 *
	 * @param array $props Props.
	 * @phpstan-param KeyFeaturesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="flex min-w-0 flex-col rounded-md bg-surface-2 px-3 py-2 text-xs max-md:w-33 max-md:shrink-0"><span class="text-fg-3">%1$s</span><b class="truncate text-sm font-bold" title="%2$s">%2$s</b></li>',
				esc_html( $item['label'] ),
				esc_html( $item['value'] )
			);
		}
		$more = isset( $props['more'] ) ? sprintf(
			'<a href="%1$s" class="inline-flex items-center gap-1 text-sm font-medium text-link no-underline hover:underline">%2$s%3$s</a>',
			esc_url( $props['more']['href'] ),
			esc_html( $props['more']['label'] ),
			Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' )
		) : '';
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><h2 class="m-0 text-md font-bold">%2$s</h2><ul class="m-0 grid list-none grid-cols-3 gap-2 p-0 max-md:flex max-md:overflow-x-auto max-md:[scrollbar-width:none]">%3$s</ul>%4$s</section>',
				esc_attr( Attributes::classes( 'um-key-features grid gap-3', $props['class'] ?? null ) ),
				esc_html( $props['title'] ),
				$items,
				$more
			)
		);
	}
}
