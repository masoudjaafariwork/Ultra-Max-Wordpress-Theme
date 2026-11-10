<?php
/**
 * DataTable component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A simple table of rows and columns with a caption (HOME-12), such as a
 * size chart or shipping rates. Wide tables scroll sideways on phones
 * instead of breaking the page.
 *
 * @phpstan-type DataTableProps array{caption: string, head: list<string>, rows: list<list<string>>, striped?: bool, show_caption?: bool, class?: string}
 */
final class DataTable {

	/**
	 * Renders the table.
	 *
	 * @param array $props Props.
	 * @phpstan-param DataTableProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['rows'] ) {
			return Html::none();
		}
		$head = '';
		foreach ( $props['head'] as $cell ) {
			$head .= sprintf( '<th scope="col" class="bg-surface-3 px-4 py-3 text-start font-bold">%s</th>', esc_html( $cell ) );
		}
		$rows = '';
		foreach ( $props['rows'] as $row ) {
			$cells = '';
			foreach ( $row as $index => $cell ) {
				$cells .= 0 === $index
					? sprintf( '<th scope="row" class="px-4 py-3 text-start font-medium">%s</th>', esc_html( $cell ) )
					: sprintf( '<td class="px-4 py-3">%s</td>', esc_html( $cell ) );
			}
			$rows .= sprintf( '<tr class="border-t border-border %1$s">%2$s</tr>', ( $props['striped'] ?? true ) ? 'even:bg-surface-2' : '', $cells );
		}
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><table class="w-full border-collapse text-sm"><caption class="%2$s">%3$s</caption>%4$s<tbody>%5$s</tbody></table></div>',
				esc_attr( Attributes::classes( 'um-data-table overflow-x-auto rounded-lg border border-border bg-surface', $props['class'] ?? null ) ),
				( $props['show_caption'] ?? false ) ? 'px-4 py-3 text-start font-bold' : 'sr-only',
				esc_html( $props['caption'] ),
				'' === $head ? '' : sprintf( '<thead><tr>%s</tr></thead>', $head ),
				$rows
			)
		);
	}
}
