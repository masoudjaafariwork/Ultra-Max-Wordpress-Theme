<?php
/**
 * ProductGrid component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Product cards in a grid (HOME-06): columns per device, and a lined look
 * where cards share thin borders, as in the classic card design.
 *
 * Each item may carry filter keys (`keys`) that a filter above the grid
 * uses to show and hide cards (HOME-09).
 *
 * @phpstan-type GridItem array{card: Html, keys?: list<string>}
 * @phpstan-type ProductGridProps array{
 *     items: list<GridItem>,
 *     label: string,
 *     columns?: array{mobile?: int, tablet?: int, desktop?: int},
 *     lined?: bool,
 *     id?: string,
 *     class?: string,
 * }
 */
final class ProductGrid {

	/**
	 * Renders the grid.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductGridProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$columns = $props['columns'] ?? array();
		$mobile  = array(
			1 => 'grid-cols-1',
			2 => 'grid-cols-2',
			3 => 'grid-cols-3',
		);
		$tablet  = array(
			2 => 'md:grid-cols-2',
			3 => 'md:grid-cols-3',
			4 => 'md:grid-cols-4',
		);
		$desktop = array(
			3 => 'lg:grid-cols-3',
			4 => 'lg:grid-cols-4',
			5 => 'lg:grid-cols-5',
			6 => 'lg:grid-cols-6',
		);
		$lined   = $props['lined'] ?? false;
		$items   = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="%1$s"%2$s>%3$s</li>',
				$lined ? 'min-w-0 border-b border-s border-border' : 'min-w-0',
				isset( $item['keys'] ) ? sprintf( ' data-keys="%s"', esc_attr( ' ' . implode( ' ', $item['keys'] ) . ' ' ) ) : '',
				$item['card']
			);
		}
		return Html::trusted(
			sprintf(
				'<ul%1$s>%2$s</ul>',
				Attributes::render(
					array(
						'id'         => $props['id'] ?? null,
						'class'      => Attributes::classes(
							'um-product-grid m-0 grid list-none p-0',
							$lined ? 'gap-0 overflow-hidden rounded-lg border-e border-t border-border bg-surface' : 'gap-3',
							$mobile[ max( 1, min( 3, $columns['mobile'] ?? 2 ) ) ],
							$tablet[ max( 2, min( 4, $columns['tablet'] ?? 3 ) ) ],
							$desktop[ max( 3, min( 6, $columns['desktop'] ?? 4 ) ) ],
							$props['class'] ?? null
						),
						'aria-label' => $props['label'],
					)
				),
				$items
			)
		);
	}
}
