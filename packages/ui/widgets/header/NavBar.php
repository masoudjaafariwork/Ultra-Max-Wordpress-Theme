<?php
/**
 * NavBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The header's menu row (HDR-02, HDR-06, HDR-08). Each item may have an
 * icon or a small image and a "new" or "hot" badge. An item opens either a
 * list of sub-items or a mega panel: any design made in Elementor, shown
 * full width or at the width of the content.
 *
 * Panels open on hover and on keyboard focus; Escape closes them and
 * `aria-expanded` follows (behaviour in nav-bar.behavior.ts).
 *
 * @phpstan-type NavItem array{
 *     label: string,
 *     href: string,
 *     current?: bool,
 *     icon?: string,
 *     image?: Html,
 *     badge?: array{label: string, tone: 'new'|'hot'|'brand'},
 *     mega?: Html,
 *     mega_width?: 'full'|'container'|'auto',
 *     children?: list<array{label: string, href: string, current?: bool}>,
 * }
 * @phpstan-type NavBarProps array{
 *     label: string,
 *     items: list<NavItem>,
 *     align?: 'start'|'center'|'end',
 *     size?: 'sm'|'md',
 *     class?: string,
 * }
 */
final class NavBar {

	private const BADGE = array(
		'new'   => 'bg-success-text text-white',
		'hot'   => 'bg-accent text-on-accent',
		'brand' => 'bg-primary text-on-primary',
	);

	/**
	 * Renders the menu row.
	 *
	 * @param array $props Props.
	 * @phpstan-param NavBarProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $index => $item ) {
			$items .= self::item( $item, $index );
		}
		$size  = $props['size'] ?? 'sm';
		$align = match ( $props['align'] ?? 'start' ) {
			'center' => 'justify-center',
			'end'    => 'justify-end',
			default  => 'justify-start',
		};
		return Html::trusted(
			sprintf(
				'<nav%1$s><ul class="%2$s">%3$s</ul></nav>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-nav-bar relative', $props['class'] ?? null ),
						'aria-label'        => $props['label'],
						'data-um-component' => 'nav-bar',
					)
				),
				esc_attr( Attributes::classes( 'm-0 flex list-none flex-wrap items-center gap-x-5 p-0 text-fg-2', $align, 'md' === $size ? 'h-13 text-md' : 'h-11 text-sm' ) ),
				$items
			)
		);
	}

	/**
	 * One item.
	 *
	 * @param array $item  Item.
	 * @param int   $index Position.
	 * @phpstan-param NavItem $item
	 */
	private static function item( array $item, int $index ): string {
		$current  = $item['current'] ?? false;
		$children = $item['children'] ?? array();
		$mega     = isset( $item['mega'] ) && ! $item['mega']->is_empty() ? $item['mega'] : null;
		$has_menu = null !== $mega || array() !== $children;
		$id       = 'um-nav-panel-' . $index . '-' . substr( md5( $item['label'] . $item['href'] ), 0, 6 );

		$visual = '';
		if ( isset( $item['image'] ) && ! $item['image']->is_empty() ) {
			$visual = sprintf( '<span class="inline-block size-5 shrink-0 [&_img]:size-full [&_img]:object-contain">%s</span>', $item['image'] );
		} elseif ( isset( $item['icon'] ) && '' !== $item['icon'] ) {
			$visual = Icon::render( $item['icon'], 'size-4.5' );
		}
		$badge = isset( $item['badge'] ) && '' !== $item['badge']['label']
			? sprintf( '<span class="inline-flex h-4.5 items-center rounded-pill px-1.5 text-2xs font-bold %1$s">%2$s</span>', esc_attr( self::BADGE[ $item['badge']['tone'] ] ), esc_html( $item['badge']['label'] ) )
			: '';
		$link  = sprintf(
			'<a href="%1$s" class="%2$s"%3$s%4$s>%5$s<span>%6$s</span>%7$s%8$s</a>',
			esc_url( $item['href'] ),
			esc_attr( Attributes::classes( 'relative inline-flex h-full items-center gap-1.5 whitespace-nowrap text-inherit no-underline hover:text-fg after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:bg-primary after:opacity-0 hover:after:opacity-100 focus-visible:outline-3 focus-visible:outline-primary/40', $current ? 'font-bold text-fg after:opacity-100' : null ) ),
			$current ? ' aria-current="page"' : '',
			$has_menu ? sprintf( ' aria-expanded="false" aria-controls="%s"', esc_attr( $id ) ) : '',
			$visual,
			esc_html( $item['label'] ),
			$badge,
			$has_menu ? Icon::render( 'chevron-down', 'size-4 transition-transform group-hover/item:rotate-180' ) : ''
		);
		if ( ! $has_menu ) {
			return '<li class="h-full">' . $link . '</li>';
		}
		$open = 'invisible translate-y-1 opacity-0 transition duration-150 ease-um group-hover/item:visible group-hover/item:translate-y-0 group-hover/item:opacity-100 group-focus-within/item:visible group-focus-within/item:translate-y-0 group-focus-within/item:opacity-100 group-data-open/item:visible group-data-open/item:translate-y-0 group-data-open/item:opacity-100';
		if ( null !== $mega ) {
			$width = $item['mega_width'] ?? 'container';
			$panel = sprintf(
				'<div id="%1$s" class="%2$s" data-panel>%3$s</div>',
				esc_attr( $id ),
				esc_attr(
					Attributes::classes(
						'absolute top-full z-60 overflow-hidden rounded-b-lg border border-border bg-surface shadow-lg',
						match ( $width ) {
							'full'  => 'inset-x-0',
							'auto'  => 'start-0 min-w-80',
							default => 'start-0 w-[min(var(--um-container),calc(100vw-2*var(--um-gutter)))]',
						},
						$open
					)
				),
				$mega
			);
			return sprintf( '<li class="group/item h-full %1$s">%2$s%3$s</li>', 'auto' === $width ? 'relative' : 'static', $link, $panel );
		}
		$sub = '';
		foreach ( $children as $child ) {
			$sub .= sprintf(
				'<li><a href="%1$s" class="block rounded-sm px-3 py-2 text-sm text-fg-2 no-underline hover:bg-surface-3 hover:text-fg"%2$s>%3$s</a></li>',
				esc_url( $child['href'] ),
				( $child['current'] ?? false ) ? ' aria-current="page"' : '',
				esc_html( $child['label'] )
			);
		}
		return sprintf(
			'<li class="group/item relative h-full">%1$s<ul id="%2$s" class="%3$s" data-panel>%4$s</ul></li>',
			$link,
			esc_attr( $id ),
			esc_attr( Attributes::classes( 'absolute start-0 top-full z-60 m-0 grid min-w-52 list-none gap-0.5 rounded-md border border-border bg-surface p-2 shadow-lg', $open ) ),
			$sub
		);
	}
}
