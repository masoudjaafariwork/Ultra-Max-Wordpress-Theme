<?php
/**
 * SortBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Sort choices of a product list (PLP-03).
 *
 * - `bar`: the desktop row "Sort: most relevant, most viewed, ..." with the
 *   number of results and an optional end slot, such as the view switch.
 * - `list`: the same choices as a list of options, for the phone sheet.
 *
 * Choices are links, so sorting works without JavaScript; the shop
 * behaviour loads them in place. Links are `nofollow` (PLP-13).
 *
 * @phpstan-type SortOption array{label: string, href: string, current?: bool}
 * @phpstan-type SortBarProps array{
 *     label: string,
 *     options: list<SortOption>,
 *     count?: string,
 *     end?: Html,
 *     variant?: 'bar'|'list',
 *     class?: string,
 * }
 */
final class SortBar {

	/**
	 * Renders the choices.
	 *
	 * @param array $props Props.
	 * @phpstan-param SortBarProps $props
	 */
	public static function render( array $props ): Html {
		$variant = Props::one_of( 'SortBar', $props, 'variant', array( 'bar', 'list' ) );
		$links   = '';
		foreach ( $props['options'] as $option ) {
			$current = $option['current'] ?? false;
			if ( 'list' === $variant ) {
				$links .= sprintf(
					'<li><a href="%1$s" class="flex min-h-12 items-center gap-3 border-b border-border text-md no-underline %2$s" rel="nofollow"%3$s data-um-shop-link data-um-close><span class="grid size-5 shrink-0 place-items-center rounded-full border-2 %4$s" aria-hidden="true">%5$s</span>%6$s</a></li>',
					esc_url( $option['href'] ),
					$current ? 'font-bold text-primary' : 'text-fg',
					$current ? ' aria-current="true"' : '',
					$current ? 'border-primary' : 'border-border-strong',
					$current ? '<span class="size-2.5 rounded-full bg-primary"></span>' : '',
					esc_html( $option['label'] )
				);
				continue;
			}
			$links .= sprintf(
				'<li><a href="%1$s" class="inline-flex min-h-11 items-center text-sm no-underline %2$s" rel="nofollow"%3$s data-um-shop-link>%4$s</a></li>',
				esc_url( $option['href'] ),
				$current ? 'font-bold text-primary' : 'text-fg-2 hover:text-primary',
				$current ? ' aria-current="true"' : '',
				esc_html( $option['label'] )
			);
		}
		if ( 'list' === $variant ) {
			return Html::trusted( sprintf( '<ul class="m-0 list-none p-0" aria-label="%1$s">%2$s</ul>', esc_attr( $props['label'] ), $links ) );
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><span class="inline-flex shrink-0 items-center gap-1.5 text-sm font-bold" aria-hidden="true">%2$s%3$s</span><nav aria-label="%3$s" class="min-w-0"><ul class="m-0 flex list-none flex-wrap gap-x-5 p-0">%4$s</ul></nav>%5$s%6$s</div>',
				esc_attr( Attributes::classes( 'um-sort-bar flex min-h-13 items-center gap-4 rounded-lg border border-border bg-surface px-4', $props['class'] ?? null ) ),
				Icon::render( 'sort', 'size-5' ),
				esc_html( $props['label'] ),
				$links,
				isset( $props['count'] ) ? sprintf( '<span class="ms-auto shrink-0 font-num text-sm text-fg-3" data-um-shop-count>%s</span>', esc_html( $props['count'] ) ) : '',
				isset( $props['end'] ) ? sprintf( '<div class="%1$s shrink-0">%2$s</div>', isset( $props['count'] ) ? '' : 'ms-auto', $props['end'] ) : ''
			)
		);
	}
}
