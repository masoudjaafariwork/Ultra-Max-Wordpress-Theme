<?php
/**
 * SearchSheet component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * What the phone search window shows before the visitor types (SRCH-04):
 * their recent searches, kept in this browser only, and the store's
 * popular searches. The live search behaviour fills the recent list and
 * hides this part while suggestions show.
 *
 * @phpstan-type SearchSheetProps array{
 *     recent: string,
 *     clear: string,
 *     popular: string,
 *     terms: list<array{label: string, href: string}>,
 *     search_url: string,
 *     class?: string,
 * }
 */
final class SearchSheet {

	/**
	 * Renders the part.
	 *
	 * @param array $props Props.
	 * @phpstan-param SearchSheetProps $props
	 */
	public static function render( array $props ): Html {
		$chip    = 'inline-flex h-8 items-center gap-1.5 rounded-pill border border-border bg-surface px-3 text-sm text-fg no-underline hover:border-primary';
		$popular = '';
		foreach ( $props['terms'] as $term ) {
			$popular .= sprintf( '<li><a href="%1$s" class="%2$s">%3$s</a></li>', esc_url( $term['href'] ), esc_attr( $chip ), esc_html( $term['label'] ) );
		}
		$popular = '' === $popular ? '' : sprintf(
			'<section class="grid gap-2"><h3 class="m-0 flex items-center gap-1.5 text-sm font-bold">%1$s%2$s</h3><ul class="m-0 flex list-none flex-wrap gap-2 p-0">%3$s</ul></section>',
			Icon::render( 'fire', 'size-4 text-accent-text' ),
			esc_html( $props['popular'] ),
			$popular
		);
		$recent  = sprintf(
			'<section class="grid gap-2" hidden data-um-recent><div class="flex items-center justify-between gap-2"><h3 class="m-0 flex items-center gap-1.5 text-sm font-bold">%1$s%2$s</h3><button type="button" class="cursor-pointer border-0 bg-transparent p-1 text-xs font-medium text-danger-text hover:underline" data-um-recent-clear>%3$s</button></div><ul class="m-0 flex list-none flex-wrap gap-2 p-0" data-um-recent-list></ul><template data-um-recent-item><li><a href="" class="%4$s">%5$s<span></span></a></li></template></section>',
			Icon::render( 'history', 'size-4 text-fg-3' ),
			esc_html( $props['recent'] ),
			esc_html( $props['clear'] ),
			esc_attr( $chip ),
			Icon::render( 'history', 'size-3.5 text-fg-3' )
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s" data-um-search-sheet data-search-url="%2$s">%3$s%4$s</div>',
				esc_attr( Attributes::classes( 'um-search-sheet mt-4 grid gap-5', $props['class'] ?? null ) ),
				esc_url( $props['search_url'] ),
				$recent,
				$popular
			)
		);
	}
}
