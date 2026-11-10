<?php
/**
 * StickyNav component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A row of links to sections of the same page that sticks under the
 * header while scrolling and marks the section in view, as on long landing
 * pages ("sticky navigation", HDR-02).
 *
 * @phpstan-type StickyNavProps array{label: string, items: list<array{label: string, target: string}>, class?: string}
 */
final class StickyNav {

	/**
	 * Renders the row.
	 *
	 * @param array $props Props.
	 * @phpstan-param StickyNavProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$target = ltrim( $item['target'], '#' );
			$items .= sprintf(
				'<li class="shrink-0"><a href="#%1$s" class="relative inline-flex h-12 items-center text-sm font-medium whitespace-nowrap text-fg-2 no-underline after:absolute after:inset-x-0 after:bottom-0 after:h-0.75 after:rounded-t after:bg-primary after:opacity-0 hover:text-fg aria-[current=true]:text-primary aria-[current=true]:after:opacity-100">%2$s</a></li>',
				esc_attr( $target ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<nav%1$s><ul class="m-0 flex list-none gap-6 overflow-x-auto px-(--um-gutter) py-0 [scrollbar-width:none]">%2$s</ul></nav>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-sticky-nav sticky top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px))] z-(--um-z-sticky) border-b border-border bg-surface transition-[top] duration-300', $props['class'] ?? null ),
						'aria-label'        => $props['label'],
						'data-um-component' => 'sticky-nav',
					)
				),
				$items
			)
		);
	}
}
