<?php
/**
 * CompareTable component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Up to four products side by side (SAVE-02): a column per product with its
 * picture, price and buttons, then a row per specification. A row whose
 * values are not all the same is marked, and "Only differences" hides the
 * rest. On phones the table scrolls sideways inside its box while the names
 * of the rows stay in place.
 *
 * `add` is an extra column inviting the shopper to add another product
 * while there is room.
 *
 * @phpstan-type CompareProduct array{id: int, title: string, href: string, image: Html, price: Html, actions?: list<Html>, remove: Html}
 * @phpstan-type CompareRow array{label: string, values: list<string>, group?: string}
 * @phpstan-type CompareTableProps array{
 *     caption: string,
 *     products: list<CompareProduct>,
 *     rows: list<CompareRow>,
 *     differences: string,
 *     blank?: string,
 *     add?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class CompareTable {

	/**
	 * Whether the values of a row are not all the same.
	 *
	 * @param array $values Values.
	 * @phpstan-param list<string> $values
	 */
	public static function differs( array $values ): bool {
		$normal = array_map( static fn ( string $value ): string => trim( (string) preg_replace( '/\s+/u', ' ', $value ) ), $values );
		return count( array_unique( $normal ) ) > 1;
	}

	/**
	 * Renders the table.
	 *
	 * @param array $props Props.
	 * @phpstan-param CompareTableProps $props
	 */
	public static function render( array $props ): Html {
		$products = $props['products'];
		$columns  = count( $products ) + ( isset( $props['add'] ) ? 1 : 0 );
		$blank    = $props['blank'] ?? '—';
		$head     = '';
		foreach ( $products as $product ) {
			$head .= sprintf(
				'<th scope="col" class="w-56 min-w-44 max-w-64 border-0 border-s border-solid border-border p-3 align-top font-regular"><div class="relative grid gap-2 text-start"><span class="absolute -end-1 -top-1 z-1">%1$s</span><a href="%2$s" class="block aspect-square overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain" tabindex="-1" aria-hidden="true">%3$s</a><a href="%2$s" class="line-clamp-2 min-h-[3.4em] text-sm font-medium leading-body text-fg no-underline hover:text-primary">%4$s</a>%5$s<div class="flex flex-wrap gap-1.5">%6$s</div></div></th>',
				$product['remove'],
				esc_url( $product['href'] ),
				$product['image'],
				esc_html( $product['title'] ),
				$product['price'],
				implode( '', array_map( 'strval', $product['actions'] ?? array() ) )
			);
		}
		if ( isset( $props['add'] ) ) {
			$head .= sprintf(
				'<th scope="col" class="w-56 min-w-44 max-w-64 border-0 border-s border-solid border-border p-3 align-top font-regular"><a href="%1$s" class="grid aspect-square place-items-center content-center gap-2 rounded-md border-2 border-dashed border-border-strong text-sm font-medium text-fg-2 no-underline hover:border-primary hover:text-primary">%2$s<span>%3$s</span></a></th>',
				esc_url( $props['add']['href'] ),
				Icon::render( 'plus', 'size-7' ),
				esc_html( $props['add']['label'] )
			);
		}

		$body  = '';
		$group = '';
		foreach ( $props['rows'] as $row ) {
			$row_group = $row['group'] ?? '';
			if ( '' !== $row_group && $row_group !== $group ) {
				$body .= sprintf( '<tr class="um-compare-group"><th scope="colgroup" colspan="%1$d" class="sticky start-0 bg-surface-2 px-3 py-2 text-start text-sm font-bold">%2$s</th></tr>', $columns + 1, esc_html( $row_group ) );
			}
			$group   = $row_group;
			$values  = array_map( static fn ( string $value ): string => '' === trim( $value ) ? '' : $value, $row['values'] );
			$differs = self::differs( $values );
			$cells   = '';
			foreach ( $values as $value ) {
				$cells .= sprintf(
					'<td class="border-0 border-s border-t border-solid border-border px-3 py-2.5 align-top text-sm %1$s">%2$s</td>',
					$differs ? 'bg-warning-soft/40' : '',
					'' === $value ? sprintf( '<span class="text-fg-3">%s</span>', esc_html( $blank ) ) : esc_html( $value )
				);
			}
			if ( isset( $props['add'] ) ) {
				$cells .= '<td class="border-0 border-s border-t border-solid border-border"></td>';
			}
			$body .= sprintf(
				'<tr%1$s><th scope="row" class="sticky start-0 z-1 w-36 min-w-28 border-0 border-t border-solid border-border bg-surface px-3 py-2.5 text-start align-top text-xs font-medium text-fg-2">%2$s</th>%3$s</tr>',
				$differs ? ' data-differs' : ' class="[[data-only-differences]_&]:hidden"',
				esc_html( $row['label'] ),
				$cells
			);
		}

		return Html::trusted(
			sprintf(
				'<div%1$s><label class="mb-3 inline-flex cursor-pointer items-center gap-2 text-sm font-medium"><input type="checkbox" class="size-4 accent-primary" data-um-compare-differences>%2$s</label><div class="w-fit max-w-full overflow-x-auto rounded-lg border border-border bg-surface"><table class="border-collapse text-fg"><caption class="sr-only">%3$s</caption><thead><tr><td class="sticky start-0 z-1 w-36 min-w-28 bg-surface"></td>%4$s</tr></thead><tbody>%5$s</tbody></table></div></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-scope um-compare min-w-0', $props['class'] ?? null ),
						'data-um-component' => 'compare',
					)
				),
				esc_html( $props['differences'] ),
				esc_html( $props['caption'] ),
				$head,
				$body
			)
		);
	}
}
