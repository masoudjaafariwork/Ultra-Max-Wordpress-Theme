<?php
/**
 * ShopBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The phone and tablet bar above a product list (PLP-06): a "Filter"
 * button with the number of chosen filters and a "Sort" button, each
 * opening a sheet from the bottom, then the number of results. It sticks
 * under the header while the list scrolls.
 *
 * @phpstan-type ShopBarProps array{
 *     filter?: array{label: string, open: string, count: int, count_label: string},
 *     sort?: array{label: string, open: string, current: string},
 *     count?: string,
 *     class?: string,
 * }
 */
final class ShopBar {

	/**
	 * Renders the bar.
	 *
	 * @param array $props Props.
	 * @phpstan-param ShopBarProps $props
	 */
	public static function render( array $props ): Html {
		$chip   = 'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-pill border border-border bg-surface px-3 text-sm font-medium text-fg hover:border-border-strong focus-visible:outline-3 focus-visible:outline-primary/40';
		$filter = '';
		if ( isset( $props['filter'] ) ) {
			$count  = $props['filter']['count'];
			$filter = sprintf(
				'<button type="button" class="%1$s" data-um-open="%2$s" aria-haspopup="dialog">%3$s%4$s%5$s</button>',
				esc_attr( $chip ),
				esc_attr( $props['filter']['open'] ),
				Icon::render( 'filter', 'size-4' ),
				esc_html( $props['filter']['label'] ),
				$count > 0 ? sprintf( '<span class="inline-grid h-5 min-w-5 place-items-center rounded-pill bg-primary px-1 font-num text-2xs font-bold text-on-primary" aria-label="%1$s">%2$s</span>', esc_attr( $props['filter']['count_label'] ), esc_html( number_format_i18n( $count ) ) ) : ''
			);
		}
		$sort = '';
		if ( isset( $props['sort'] ) ) {
			$sort = sprintf(
				'<button type="button" class="%1$s" data-um-open="%2$s" aria-haspopup="dialog"><span class="sr-only">%3$s: </span>%4$s%5$s</button>',
				esc_attr( $chip ),
				esc_attr( $props['sort']['open'] ),
				esc_html( $props['sort']['label'] ),
				Icon::render( 'sort', 'size-4' ),
				esc_html( $props['sort']['current'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s%3$s%4$s</div>',
				esc_attr( Attributes::classes( 'um-shop-bar sticky top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px))] z-3 transition-[top] duration-300 -mx-(--um-gutter) flex items-center gap-2 overflow-x-auto bg-page px-(--um-gutter) py-2 [scrollbar-width:none] lg:hidden', $props['class'] ?? null ) ),
				$filter,
				$sort,
				isset( $props['count'] ) ? sprintf( '<span class="ms-auto shrink-0 font-num text-xs whitespace-nowrap text-fg-3" data-um-shop-count>%s</span>', esc_html( $props['count'] ) ) : ''
			)
		);
	}
}
