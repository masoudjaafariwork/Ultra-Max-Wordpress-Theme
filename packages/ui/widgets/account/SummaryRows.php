<?php
/**
 * SummaryRows component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Label and amount rows, such as an order's payment details (ACC-02): the
 * price of the products, discounts, shipping and the total, which is bold.
 * Amounts may hold the store's price markup.
 *
 * @phpstan-type SummaryRow array{label: string, value: Html, strong?: bool}
 * @phpstan-type SummaryRowsProps array{
 *     rows: list<SummaryRow>,
 *     class?: string,
 * }
 */
final class SummaryRows {

	/**
	 * Renders the rows.
	 *
	 * @param array $props Props.
	 * @phpstan-param SummaryRowsProps $props
	 */
	public static function render( array $props ): Html {
		$rows = '';
		foreach ( $props['rows'] as $row ) {
			$strong = $row['strong'] ?? false;
			$rows  .= sprintf(
				'<div class="flex items-baseline justify-between gap-3 py-1.5 %1$s"><dt class="text-sm %2$s">%3$s</dt><dd class="m-0 font-num text-sm %4$s">%5$s</dd></div>',
				$strong ? 'mt-1 border-0 border-t border-solid border-border pt-3' : '',
				$strong ? 'font-bold' : 'text-fg-2',
				esc_html( $row['label'] ),
				$strong ? 'font-bold' : 'font-medium',
				$row['value']
			);
		}
		return Html::trusted( sprintf( '<dl class="%1$s">%2$s</dl>', esc_attr( Attributes::classes( 'um-summary-rows m-0', $props['class'] ?? null ) ), $rows ) );
	}
}
