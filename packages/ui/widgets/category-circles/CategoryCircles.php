<?php
/**
 * CategoryCircles component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Round category tiles with an image or icon and a name (HOME-04): eight in
 * a row on desktop, four on tablets and phones, or one scrolling row.
 *
 * @phpstan-type CategoryCircle array{label: string, href: string, image?: Html, icon?: string, count?: string}
 * @phpstan-type CategoryCirclesProps array{
 *     items: list<CategoryCircle>,
 *     label: string,
 *     layout?: 'grid'|'row',
 *     shape?: 'circle'|'rounded',
 *     class?: string,
 * }
 */
final class CategoryCircles {

	/**
	 * Renders the tiles.
	 *
	 * @param array $props Props.
	 * @phpstan-param CategoryCirclesProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$row    = 'row' === ( $props['layout'] ?? 'grid' );
		$circle = 'circle' === ( $props['shape'] ?? 'circle' );
		$items  = '';
		foreach ( $props['items'] as $item ) {
			$visual = isset( $item['image'] ) && ! $item['image']->is_empty()
				? sprintf( '<span class="block w-[68%%] [&_img]:h-auto [&_img]:w-full [&_img]:object-contain">%s</span>', $item['image'] )
				: Icon::render( $item['icon'] ?? 'grid', 'size-10 text-primary max-md:size-7' );
			$items .= sprintf(
				'<li class="%1$s"><a href="%2$s" class="group flex flex-col items-center gap-2 text-center text-sm leading-normal font-medium text-fg no-underline max-md:text-2xs"><span class="%3$s">%4$s</span><span>%5$s</span>%6$s</a></li>',
				$row ? 'w-26 shrink-0 max-md:w-20' : 'min-w-0',
				esc_url( $item['href'] ),
				esc_attr( Attributes::classes( 'grid size-26 place-items-center border border-border bg-surface transition duration-200 group-hover:-translate-y-0.5 group-hover:shadow-md max-md:size-18', $circle ? 'rounded-full' : 'rounded-xl' ) ),
				$visual,
				esc_html( $item['label'] ),
				isset( $item['count'] ) ? sprintf( '<small class="text-xs text-fg-3">%s</small>', esc_html( $item['count'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-category-circles m-0 list-none p-0', $row ? 'flex gap-4 overflow-x-auto pb-1 [scrollbar-width:none]' : 'grid grid-cols-4 gap-x-3 gap-y-6 md:gap-4 xl:grid-cols-8', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
