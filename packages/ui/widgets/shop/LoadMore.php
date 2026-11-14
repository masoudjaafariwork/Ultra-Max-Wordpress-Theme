<?php
/**
 * LoadMore component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The end of a product list (PLP-04): how many products are shown, a
 * progress bar, a "show more" button and the page links.
 *
 * - `pages`: page links only.
 * - `more`: the button adds the next page below the list; page links stay
 *   for visitors without JavaScript and for search engines.
 * - `infinite`: like `more`, and the next page loads by itself when the
 *   button scrolls into view.
 *
 * The button is a link to the next page, so it works without JavaScript.
 *
 * @phpstan-type LoadMoreProps array{
 *     mode?: 'pages'|'more'|'infinite',
 *     shown: int,
 *     total: int,
 *     status: string,
 *     label: string,
 *     next?: string,
 *     pagination?: Html,
 *     class?: string,
 * }
 */
final class LoadMore {

	/**
	 * Renders the end of the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param LoadMoreProps $props
	 */
	public static function render( array $props ): Html {
		$mode  = Props::one_of( 'LoadMore', $props, 'mode', array( 'pages', 'more', 'infinite' ) );
		$total = max( 0, $props['total'] );
		if ( 0 === $total ) {
			return Html::none();
		}
		$share  = (int) round( min( $props['shown'], $total ) / $total * 100 );
		$button = '';
		if ( 'pages' !== $mode && isset( $props['next'] ) && '' !== $props['next'] ) {
			$button = sprintf(
				'<a href="%1$s" class="inline-flex h-11 items-center gap-1.5 rounded-md border border-border-strong bg-surface px-5 text-sm font-bold text-fg no-underline hover:border-primary hover:text-primary aria-busy:pointer-events-none aria-busy:opacity-60 focus-visible:outline-3 focus-visible:outline-primary/40" data-um-load-more="%2$s">%3$s%4$s</a>',
				esc_url( $props['next'] ),
				esc_attr( $mode ),
				esc_html( $props['label'] ),
				Icon::render( 'chevron-down', 'size-4' )
			);
		}
		$progress = 'pages' === $mode ? '' : sprintf(
			'<span class="block h-1 w-50 overflow-hidden rounded-pill bg-border" aria-hidden="true"><i class="block h-full rounded-pill bg-primary" style="width:%d%%"></i></span>',
			$share
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><span class="text-xs text-fg-3">%2$s</span>%3$s%4$s%5$s</div>',
				esc_attr( Attributes::classes( 'um-load-more my-6 flex flex-col items-center gap-3', $props['class'] ?? null ) ),
				esc_html( $props['status'] ),
				$progress,
				$button,
				$props['pagination'] ?? ''
			)
		);
	}
}
