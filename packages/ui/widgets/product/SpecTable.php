<?php
/**
 * SpecTable component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Every specification of a product in groups such as "Display" and
 * "Camera" (PDP-06): a group title beside a list of names and values.
 *
 * @phpstan-type SpecTableProps array{
 *     groups: list<array{title: string, rows: list<array{label: string, value: string}>}>,
 *     class?: string,
 * }
 */
final class SpecTable {

	/**
	 * Renders the table, or nothing without rows.
	 *
	 * @param array $props Props.
	 * @phpstan-param SpecTableProps $props
	 */
	public static function render( array $props ): Html {
		$out = '';
		foreach ( $props['groups'] as $group ) {
			if ( array() === $group['rows'] ) {
				continue;
			}
			$rows = '';
			foreach ( $group['rows'] as $row ) {
				$rows .= sprintf(
					'<dt class="m-0 border-b border-border py-2.5 pe-4 text-sm text-fg-3">%1$s</dt><dd class="m-0 border-b border-border py-2.5 text-sm">%2$s</dd>',
					esc_html( $row['label'] ),
					nl2br( esc_html( $row['value'] ) )
				);
			}
			$out .= sprintf(
				'<div class="grid gap-4 py-3 md:grid-cols-[180px_minmax(0,1fr)] max-md:gap-1"><h3 class="m-0 text-md font-bold">%1$s</h3><dl class="m-0 grid grid-cols-[120px_minmax(0,1fr)] md:grid-cols-[200px_minmax(0,1fr)]">%2$s</dl></div>',
				esc_html( $group['title'] ),
				$rows
			);
		}
		if ( '' === $out ) {
			return Html::none();
		}
		return Html::trusted( sprintf( '<div class="%1$s">%2$s</div>', esc_attr( Attributes::classes( 'um-spec-table', $props['class'] ?? null ) ), $out ) );
	}
}
