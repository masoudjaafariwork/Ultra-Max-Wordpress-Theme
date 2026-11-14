<?php
/**
 * Subcategories component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The child categories of a category, with their pictures, in a row that
 * scrolls sideways on small screens (PLP-09).
 *
 * @phpstan-type SubcategoriesProps array{
 *     label: string,
 *     items: list<array{label: string, href: string, image?: Html, count?: string}>,
 *     class?: string,
 * }
 */
final class Subcategories {

	/**
	 * Renders the row, or nothing without children.
	 *
	 * @param array $props Props.
	 * @phpstan-param SubcategoriesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$image  = isset( $item['image'] ) && ! $item['image']->is_empty()
				? sprintf( '<span class="block size-14 max-md:size-11 [&_img]:size-full [&_img]:object-contain">%s</span>', $item['image'] )
				: sprintf( '<span class="grid size-14 place-items-center rounded-full bg-surface-3 text-fg-3 max-md:size-11">%s</span>', Icon::render( 'folder', 'size-6' ) );
			$items .= sprintf(
				'<li class="shrink-0"><a href="%1$s" class="flex w-28 flex-col items-center gap-1.5 rounded-lg border border-border bg-surface px-2 py-3 text-center text-xs font-medium text-fg no-underline transition-colors duration-150 hover:border-primary max-md:w-22 max-md:p-2">%2$s<span class="line-clamp-2">%3$s</span>%4$s</a></li>',
				esc_url( $item['href'] ),
				$image,
				esc_html( $item['label'] ),
				isset( $item['count'] ) ? sprintf( '<span class="font-num text-2xs text-fg-3">%s</span>', esc_html( $item['count'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<nav class="%1$s" aria-label="%2$s"><ul class="m-0 flex list-none gap-3 overflow-x-auto p-0 pb-1 [scrollbar-width:none]">%3$s</ul></nav>',
				esc_attr( Attributes::classes( 'um-subcategories min-w-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
