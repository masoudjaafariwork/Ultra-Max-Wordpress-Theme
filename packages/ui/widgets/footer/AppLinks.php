<?php
/**
 * AppLinks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The store's app links in the footer (HDR-11): dark pill buttons for app
 * stores or a direct download, with a label before them.
 *
 * @phpstan-type AppLinksProps array{label: string, items: list<array{label: string, href: string, icon: string}>, class?: string}
 */
final class AppLinks {

	/**
	 * Renders the links.
	 *
	 * @param array $props Props.
	 * @phpstan-param AppLinksProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li><a href="%1$s" class="inline-flex items-center gap-1.5 rounded-md bg-[var(--um-gray-900)] px-3 py-1.5 text-xs text-white no-underline hover:bg-[var(--um-gray-700)]">%2$s%3$s</a></li>',
				esc_url( $item['href'] ),
				Icon::render( $item['icon'], 'size-4' ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><span class="text-sm font-bold">%2$s</span><ul class="m-0 flex list-none flex-wrap gap-2 p-0">%3$s</ul></div>',
				esc_attr( Attributes::classes( 'um-app-links flex flex-wrap items-center gap-3', $props['class'] ?? null ) ),
				esc_html( $props['label'] ),
				$items
			)
		);
	}
}
