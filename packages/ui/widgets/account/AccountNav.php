<?php
/**
 * AccountNav component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The account menu: dashboard, orders, lists, reviews, addresses, recent
 * views, account details and log out. The current item is marked with
 * `aria-current`; a number such as unread notifications shows at its end.
 * On phones and tablets the items sit in a row that scrolls sideways.
 *
 * @phpstan-type AccountNavItem array{label: string, href: string, icon: string, current?: bool, count?: string, out?: bool}
 * @phpstan-type AccountNavProps array{
 *     label: string,
 *     items: list<AccountNavItem>,
 *     class?: string,
 * }
 */
final class AccountNav {

	/**
	 * Renders the menu.
	 *
	 * @param array $props Props.
	 * @phpstan-param AccountNavProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$current = $item['current'] ?? false;
			$out     = $item['out'] ?? false;
			$items  .= sprintf(
				'<li class="%1$s"><a href="%2$s" class="%3$s"%4$s>%5$s<span class="flex-1">%6$s</span>%7$s</a></li>',
				$out ? 'lg:mt-1 lg:border-0 lg:border-t lg:border-solid lg:border-border lg:pt-1' : '',
				esc_url( $item['href'] ),
				esc_attr(
					Attributes::classes(
						'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium no-underline whitespace-nowrap focus-visible:outline-3 focus-visible:outline-primary/40 max-lg:border max-lg:border-border max-lg:py-2',
						$current ? 'bg-primary-soft text-primary max-lg:border-primary' : ( $out ? 'text-danger-text hover:bg-danger-soft' : 'text-fg-2 hover:bg-surface-3 hover:text-fg' )
					)
				),
				$current ? ' aria-current="page"' : '',
				Icon::render( $item['icon'], 'size-5' ),
				esc_html( $item['label'] ),
				isset( $item['count'] ) && '' !== $item['count'] ? sprintf( '<span class="grid h-5 min-w-5 place-items-center rounded-pill bg-surface-3 px-1.5 font-num text-2xs font-bold text-fg-2">%s</span>', esc_html( $item['count'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<nav class="%1$s" aria-label="%2$s"><ul class="m-0 flex list-none gap-0.5 p-0 lg:flex-col max-lg:-mx-1 max-lg:gap-2 max-lg:overflow-x-auto max-lg:px-1 max-lg:pb-1">%3$s</ul></nav>',
				esc_attr( Attributes::classes( 'um-account-nav min-w-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
