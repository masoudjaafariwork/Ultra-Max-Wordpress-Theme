<?php
/**
 * Menu component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A navigation menu from a tree of links.
 *
 * - `bar`: the header row; sub-items open on hover and keyboard focus.
 * - `list`: a vertical list, as in the footer.
 * - `tree`: the mobile menu; sub-items fold under native disclosure.
 *
 * @phpstan-type MenuItem array{label: string, href: string, current?: bool, children?: list<array{label: string, href: string, current?: bool}>}
 * @phpstan-type MenuProps array{
 *     label: string,
 *     items: list<MenuItem>,
 *     variant?: 'bar'|'list'|'tree',
 *     id?: string,
 *     class?: string,
 * }
 */
final class Menu {

	public const VARIANTS = array( 'bar', 'list', 'tree' );

	/**
	 * Renders the menu.
	 *
	 * @param array $props Props.
	 * @phpstan-param MenuProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$variant = Props::one_of( 'Menu', $props, 'variant', self::VARIANTS );
		$items   = '';
		foreach ( $props['items'] as $item ) {
			$items .= match ( $variant ) {
				'bar'  => self::bar_item( $item ),
				'list' => self::link( $item, 'inline-block py-1 text-sm text-fg-2 no-underline hover:text-primary' ),
				'tree' => self::tree_item( $item ),
			};
		}
		$list = match ( $variant ) {
			'bar'  => 'flex h-11 items-center gap-6 text-sm text-fg-2',
			'list' => 'grid gap-1',
			'tree' => 'grid',
		};
		return Html::trusted(
			sprintf(
				'<nav%1$s><ul class="%2$s">%3$s</ul></nav>',
				Attributes::render(
					array(
						'id'         => $props['id'] ?? null,
						'aria-label' => $props['label'],
						'class'      => $props['class'] ?? null,
					)
				),
				$list,
				$items
			)
		);
	}

	/**
	 * A plain link in a list item.
	 *
	 * @param array{label: string, href: string, current?: bool} $item    Item.
	 * @param string                                             $classes Link classes.
	 */
	private static function link( array $item, string $classes ): string {
		$current = $item['current'] ?? false;
		return sprintf(
			'<li><a href="%1$s" class="%2$s"%3$s>%4$s</a></li>',
			esc_url( $item['href'] ),
			esc_attr( Attributes::classes( $classes, $current ? 'text-primary font-bold' : null ) ),
			$current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}

	/**
	 * One item of the header bar.
	 *
	 * @param array $item Item.
	 * @phpstan-param MenuItem $item
	 */
	private static function bar_item( array $item ): string {
		$current  = $item['current'] ?? false;
		$children = $item['children'] ?? array();
		$link     = sprintf(
			'<a href="%1$s" class="%2$s"%3$s>%4$s%5$s</a>',
			esc_url( $item['href'] ),
			esc_attr( Attributes::classes( 'relative inline-flex h-11 items-center gap-1 text-fg-2 no-underline hover:text-fg after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary after:opacity-0 hover:after:opacity-100', $current ? 'text-fg font-bold after:opacity-100' : null ) ),
			$current ? ' aria-current="page"' : '',
			esc_html( $item['label'] ),
			array() === $children ? '' : Icon::render( 'chevron-down', 'size-4' )
		);
		if ( array() === $children ) {
			return '<li>' . $link . '</li>';
		}
		$sub = '';
		foreach ( $children as $child ) {
			$sub .= self::link( $child, 'block rounded-sm px-3 py-2 text-sm text-fg-2 no-underline hover:bg-surface-3 hover:text-fg' );
		}
		return sprintf(
			'<li class="group relative">%1$s<ul class="invisible absolute start-0 top-full z-60 grid min-w-52 translate-y-1 gap-0.5 rounded-md border border-border bg-surface p-2 opacity-0 shadow-lg transition duration-150 ease-um group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">%2$s</ul></li>',
			$link,
			$sub
		);
	}

	/**
	 * One item of the mobile tree.
	 *
	 * @param array $item Item.
	 * @phpstan-param MenuItem $item
	 */
	private static function tree_item( array $item ): string {
		$children = $item['children'] ?? array();
		$classes  = 'flex min-h-11 items-center gap-3 border-b border-border px-1 text-md font-medium text-fg no-underline';
		if ( array() === $children ) {
			return self::link( $item, $classes );
		}
		$sub = self::link(
			array(
				'label'   => $item['label'],
				'href'    => $item['href'],
				'current' => $item['current'] ?? false,
			),
			'block py-2 text-sm text-fg-2 no-underline'
		);
		foreach ( $children as $child ) {
			$sub .= self::link( $child, 'block py-2 text-sm text-fg-2 no-underline' );
		}
		return sprintf(
			'<li><details class="group border-b border-border"><summary class="flex min-h-11 cursor-pointer list-none items-center gap-3 px-1 text-md font-medium text-fg group-open:text-primary [&::-webkit-details-marker]:hidden">%1$s%2$s</summary><ul class="grid pb-2 ps-4">%3$s</ul></details></li>',
			esc_html( $item['label'] ),
			Icon::render( 'chevron-down', 'size-5 ms-auto text-fg-3 transition-transform group-open:rotate-180' ),
			$sub
		);
	}
}
