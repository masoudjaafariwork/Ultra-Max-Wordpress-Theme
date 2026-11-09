<?php
/**
 * Breadcrumbs component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The path from the home page to the current page (CORE-08). The last item
 * is the current page and has no link.
 *
 * @phpstan-type BreadcrumbsProps array{
 *     label: string,
 *     items: list<array{label: string, href?: string}>,
 *     class?: string,
 * }
 */
final class Breadcrumbs {

	/**
	 * Renders the trail.
	 *
	 * @param array $props Props.
	 * @phpstan-param BreadcrumbsProps $props
	 */
	public static function render( array $props ): Html {
		$items = $props['items'];
		if ( count( $items ) < 2 ) {
			return Html::none();
		}
		$last = count( $items ) - 1;
		$out  = '';
		foreach ( $items as $index => $item ) {
			$separator = 0 === $index ? '' : Icon::render( 'chevron-left', 'size-3.5 text-fg-3 ltr:rotate-180' );
			if ( $index === $last || ! isset( $item['href'] ) ) {
				$out .= sprintf( '<li class="inline-flex min-w-0 items-center gap-1.5">%s<span class="truncate text-fg-2"%s>%s</span></li>', $separator, $index === $last ? ' aria-current="page"' : '', esc_html( $item['label'] ) );
				continue;
			}
			$out .= sprintf(
				'<li class="inline-flex shrink-0 items-center gap-1.5">%s<a href="%s" class="text-fg-3 no-underline hover:text-primary">%s</a></li>',
				$separator,
				esc_url( $item['href'] ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<nav aria-label="%1$s" class="%2$s"><ol class="m-0 flex list-none flex-wrap items-center gap-1.5 p-0 text-xs">%3$s</ol></nav>',
				esc_attr( $props['label'] ),
				esc_attr( Attributes::classes( 'um-breadcrumbs min-w-0', $props['class'] ?? null ) ),
				$out
			)
		);
	}
}
