<?php
/**
 * MobileMenu component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The phone menu (HDR-09): a side drawer with an account strip, four quick
 * links, then categories or menu items folded in as many levels as they
 * have, using the browser's own disclosure element.
 *
 * @phpstan-type TreeItem array{label: string, href: string, current?: bool, image?: Html, children?: list<array<string, mixed>>}
 * @phpstan-type MobileMenuProps array{
 *     id: string,
 *     title: Html,
 *     label: string,
 *     items: list<TreeItem>,
 *     all_label: string,
 *     heading?: string,
 *     account?: array{href: string, title: string, text?: string},
 *     quick?: list<array{label: string, href: string, icon: string}>,
 * }
 */
final class MobileMenu {

	/**
	 * Renders the drawer.
	 *
	 * @param array $props Props.
	 * @phpstan-param MobileMenuProps $props
	 */
	public static function render( array $props ): Html {
		$account = '';
		if ( isset( $props['account'] ) ) {
			$account = sprintf(
				'<a href="%1$s" class="-mx-4 -mt-4 mb-0 flex items-center gap-3 bg-primary-soft p-4 text-primary no-underline">%2$s<span class="flex flex-1 flex-col leading-relaxed text-fg"><b>%3$s</b>%4$s</span>%5$s</a>',
				esc_url( $props['account']['href'] ),
				Icon::render( 'user', 'size-6' ),
				esc_html( $props['account']['title'] ),
				isset( $props['account']['text'] ) ? sprintf( '<small class="text-xs text-fg-2">%s</small>', esc_html( $props['account']['text'] ) ) : '',
				Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' )
			);
		}
		$quick = '';
		foreach ( $props['quick'] ?? array() as $link ) {
			$quick .= sprintf(
				'<a href="%1$s" class="flex flex-col items-center gap-1 rounded-md py-2 text-xs text-fg no-underline hover:bg-surface-3">%2$s%3$s</a>',
				esc_url( $link['href'] ),
				Icon::render( $link['icon'], 'size-5 text-primary' ),
				esc_html( $link['label'] )
			);
		}
		if ( '' !== $quick ) {
			$quick = sprintf( '<nav class="-mx-4 grid grid-cols-4 gap-1 border-b-8 border-surface-3 p-3">%s</nav>', $quick );
		}
		$heading = isset( $props['heading'] ) && '' !== $props['heading'] ? sprintf( '<h3 class="m-0 px-0 pt-4 pb-2 text-md font-bold">%s</h3>', esc_html( $props['heading'] ) ) : '';
		$tree    = '';
		foreach ( $props['items'] as $item ) {
			$tree .= self::node( $item, 0, $props['all_label'] );
		}
		return Drawer::render(
			array(
				'id'       => $props['id'],
				'title'    => $props['title'],
				'side'     => 'start',
				'class'    => 'um-mobile-drawer',
				'children' => Html::trusted( sprintf( '<div class="um-mobile-menu grid">%1$s%2$s%3$s<nav aria-label="%4$s"><ul class="-mx-4 m-0 list-none p-0">%5$s</ul></nav></div>', $account, $quick, $heading, esc_attr( $props['label'] ), $tree ) ),
			)
		);
	}

	/**
	 * One item and, folded under it, its children.
	 *
	 * @param array  $item      Item.
	 * @param int    $depth     Level, from 0.
	 * @param string $all_label "All of %s" pattern.
	 * @phpstan-param array<mixed> $item
	 */
	private static function node( array $item, int $depth, string $all_label ): string {
		$label    = is_string( $item['label'] ?? null ) ? $item['label'] : '';
		$href     = is_string( $item['href'] ?? null ) ? $item['href'] : '#';
		$children = is_array( $item['children'] ?? null ) ? $item['children'] : array();
		$image    = ( $item['image'] ?? null ) instanceof Html && ! $item['image']->is_empty()
			? sprintf( '<span class="size-7 shrink-0 [&_img]:size-full [&_img]:object-contain">%s</span>', $item['image'] )
			: '';
		$pad      = match ( $depth ) {
			0       => 'px-4',
			1       => 'ps-8 pe-4',
			default => 'ps-12 pe-4',
		};
		$text = 0 === $depth ? 'text-md font-medium text-fg' : 'text-sm text-fg-2';
		if ( array() === $children ) {
			return sprintf(
				'<li><a href="%1$s" class="%2$s"%3$s>%4$s%5$s</a></li>',
				esc_url( $href ),
				esc_attr( Attributes::classes( 'flex min-h-11 items-center gap-3 no-underline', $pad, $text, 0 === $depth ? 'border-b border-border' : null ) ),
				( $item['current'] ?? false ) === true ? ' aria-current="page"' : '',
				$image,
				esc_html( $label )
			);
		}
		$sub = sprintf( '<li><a href="%1$s" class="%2$s">%3$s</a></li>', esc_url( $href ), esc_attr( Attributes::classes( 'flex min-h-10 items-center text-sm font-bold text-primary no-underline', 0 === $depth ? 'ps-8 pe-4' : 'ps-12 pe-4' ) ), esc_html( sprintf( $all_label, $label ) ) );
		foreach ( $children as $child ) {
			if ( is_array( $child ) ) {
				$sub .= self::node( $child, $depth + 1, $all_label );
			}
		}
		return sprintf(
			'<li><details class="group/node %1$s"><summary class="%2$s">%3$s<span class="flex-1">%4$s</span>%5$s</summary><ul class="m-0 list-none p-0 pb-2">%6$s</ul></details></li>',
			0 === $depth ? 'border-b border-border' : '',
			esc_attr( Attributes::classes( 'flex min-h-11 cursor-pointer list-none items-center gap-3 group-open/node:text-primary [&::-webkit-details-marker]:hidden', $pad, $text ) ),
			$image,
			esc_html( $label ),
			Icon::render( 'chevron-down', 'size-4 text-fg-3 transition-transform group-open/node:rotate-180' ),
			$sub
		);
	}
}
