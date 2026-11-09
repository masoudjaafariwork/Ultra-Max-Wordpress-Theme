<?php
/**
 * Pagination component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Numbered page links with previous and next.
 *
 * `base` is a URL with `%#%` where the page number goes, as in WordPress's
 * `paginate_links()`. Page 1 uses `first` when given, so it has no `/page/1/`.
 *
 * @phpstan-type PaginationProps array{
 *     label: string,
 *     current: int,
 *     total: int,
 *     base: string,
 *     first?: string,
 *     previous: string,
 *     next: string,
 *     class?: string,
 * }
 */
final class Pagination {

	/**
	 * Page numbers to show: the first, the last, and two around the current,
	 * with 0 where pages are skipped.
	 *
	 * @param int $current Current page.
	 * @param int $total   Number of pages.
	 * @return list<int>
	 */
	public static function window( int $current, int $total ): array {
		$pages = array();
		$last  = 0;
		for ( $page = 1; $page <= $total; $page++ ) {
			if ( 1 === $page || $page === $total || abs( $page - $current ) <= 2 ) {
				if ( $last > 0 && $page - $last > 1 ) {
					$pages[] = 0;
				}
				$pages[] = $page;
				$last    = $page;
			}
		}
		return $pages;
	}

	/**
	 * Renders the links.
	 *
	 * @param array $props Props.
	 * @phpstan-param PaginationProps $props
	 */
	public static function render( array $props ): Html {
		$total   = max( 1, $props['total'] );
		$current = min( max( 1, $props['current'] ), $total );
		if ( $total < 2 ) {
			return Html::none();
		}
		$url  = static fn ( int $page ): string => 1 === $page && isset( $props['first'] ) ? $props['first'] : str_replace( '%#%', (string) $page, $props['base'] );
		$cell = 'inline-flex h-10 min-w-10 items-center justify-center gap-1 rounded-md px-3 text-sm font-bold no-underline';
		$link = $cell . ' text-fg hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40';

		$out = '';
		if ( $current > 1 ) {
			$out .= sprintf( '<li><a class="%s" href="%s" rel="prev">%s<span>%s</span></a></li>', esc_attr( $link ), esc_url( $url( $current - 1 ) ), Icon::render( 'chevron-right', 'size-4 ltr:rotate-180' ), esc_html( $props['previous'] ) );
		}
		foreach ( self::window( $current, $total ) as $page ) {
			if ( 0 === $page ) {
				$out .= sprintf( '<li><span class="%s text-fg-3" aria-hidden="true">…</span></li>', esc_attr( $cell ) );
			} elseif ( $page === $current ) {
				$out .= sprintf( '<li><span class="%s bg-primary text-on-primary" aria-current="page">%s</span></li>', esc_attr( $cell ), esc_html( number_format_i18n( $page ) ) );
			} else {
				$out .= sprintf( '<li><a class="%s" href="%s">%s</a></li>', esc_attr( $link ), esc_url( $url( $page ) ), esc_html( number_format_i18n( $page ) ) );
			}
		}
		if ( $current < $total ) {
			$out .= sprintf( '<li><a class="%s" href="%s" rel="next"><span>%s</span>%s</a></li>', esc_attr( $link ), esc_url( $url( $current + 1 ) ), esc_html( $props['next'] ), Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' ) );
		}

		return Html::trusted(
			sprintf(
				'<nav aria-label="%1$s" class="%2$s"><ul class="m-0 flex list-none flex-wrap items-center justify-center gap-1 p-0">%3$s</ul></nav>',
				esc_attr( $props['label'] ),
				esc_attr( Attributes::classes( 'mt-8', $props['class'] ?? null ) ),
				$out
			)
		);
	}
}
