<?php
/**
 * BottomNav component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The app-like bar at the bottom of phones and tablets (HDR-10): up to six
 * icons with labels, the current page marked, and number badges such as
 * the cart count. An item with `opens` opens a drawer, for example the
 * categories menu, and falls back to its link when the page has none.
 *
 * @phpstan-type BottomNavItem array{label: string, href: string, icon: string, current?: bool, count?: int, count_text?: string, count_key?: string, opens?: string}
 * @phpstan-type BottomNavProps array{label: string, items: list<BottomNavItem>, class?: string}
 */
final class BottomNav {

	/**
	 * Renders the bar.
	 *
	 * @param array $props Props.
	 * @phpstan-param BottomNavProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( array_slice( $props['items'], 0, 6 ) as $item ) {
			$current = $item['current'] ?? false;
			$badge   = isset( $item['count_key'] ) ? (string) HeaderAction::badge( $item['count_key'], $item['count'] ?? 0, $item['count_text'] ?? null ) : '';
			$items  .= sprintf(
				'<li class="flex-1"><a href="%1$s" class="%2$s"%3$s%4$s><span class="relative inline-flex">%5$s%6$s</span>%7$s</a></li>',
				esc_url( $item['href'] ),
				esc_attr( Attributes::classes( 'flex h-full flex-col items-center justify-center gap-0.5 text-2xs no-underline', $current ? 'font-bold text-fg [&_svg]:stroke-[2.2] [&_svg]:text-primary' : 'text-fg-3 hover:text-fg' ) ),
				$current ? ' aria-current="page"' : '',
				isset( $item['opens'] ) ? sprintf( ' data-opens="%s"', esc_attr( $item['opens'] ) ) : '',
				Icon::render( $item['icon'], 'size-6' ),
				$badge,
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<nav%1$s><ul class="m-0 flex h-(--um-bottom-nav-h) list-none p-0">%2$s</ul></nav>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-scope um-bottom-nav fixed inset-x-0 bottom-0 z-(--um-z-sticky) border-t border-border bg-surface pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_16px_rgb(21_22_28/5%)] lg:hidden', $props['class'] ?? null ),
						'aria-label'        => $props['label'],
						'data-um-component' => 'bottom-nav',
					)
				),
				$items
			)
		);
	}
}
