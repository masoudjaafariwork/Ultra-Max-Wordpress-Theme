<?php
/**
 * ActiveFilters component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The chosen filters as chips, each a link that removes it, and a link
 * that removes them all (PLP-12). Links are `nofollow`: their addresses
 * are filter combinations search engines should not index (PLP-13).
 *
 * @phpstan-type ActiveFiltersProps array{
 *     label: string,
 *     items: list<array{label: string, href: string, remove: string}>,
 *     clear?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class ActiveFilters {

	/**
	 * Renders the chips, or nothing when no filter is chosen.
	 *
	 * @param array $props Props.
	 * @phpstan-param ActiveFiltersProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$chips = '';
		foreach ( $props['items'] as $item ) {
			$chips .= sprintf(
				'<li><a href="%1$s" class="inline-flex h-8 items-center gap-1.5 rounded-pill border border-primary/30 bg-primary-soft ps-3 pe-2 text-xs font-medium text-primary no-underline hover:border-primary" rel="nofollow" data-um-shop-link><span>%2$s</span>%3$s<span class="sr-only">%4$s</span></a></li>',
				esc_url( $item['href'] ),
				esc_html( $item['label'] ),
				Icon::render( 'close', 'size-3.5' ),
				esc_html( $item['remove'] )
			);
		}
		if ( isset( $props['clear'] ) ) {
			$chips .= sprintf(
				'<li><a href="%1$s" class="inline-flex h-8 items-center px-2 text-xs font-bold text-danger-text no-underline hover:underline" rel="nofollow" data-um-shop-link>%2$s</a></li>',
				esc_url( $props['clear']['href'] ),
				esc_html( $props['clear']['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-active-filters m-0 flex list-none flex-wrap items-center gap-1.5 p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$chips
			)
		);
	}
}
