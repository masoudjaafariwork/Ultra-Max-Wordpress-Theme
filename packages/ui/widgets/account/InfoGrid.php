<?php
/**
 * InfoGrid component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Labelled facts in a grid, such as the account details (ACC-07) or an
 * order's number, date and total (ACC-02). An empty value shows `blank`;
 * a badge, such as "Verified", can follow a value.
 *
 * @phpstan-type InfoItem array{label: string, value: string, num?: bool, dir?: 'ltr'|'rtl'|'auto', badge?: array{label: string, tone: 'success'|'warning'|'neutral'}}
 * @phpstan-type InfoGridProps array{
 *     items: list<InfoItem>,
 *     blank?: string,
 *     columns?: 2|4,
 *     class?: string,
 * }
 */
final class InfoGrid {

	/**
	 * Renders the grid.
	 *
	 * @param array $props Props.
	 * @phpstan-param InfoGridProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$value  = '' === trim( $item['value'] )
				? sprintf( '<span class="text-fg-3">%s</span>', esc_html( $props['blank'] ?? '—' ) )
				: sprintf( '<span%1$s>%2$s</span>', isset( $item['dir'] ) ? sprintf( ' dir="%s"', esc_attr( $item['dir'] ) ) : '', esc_html( $item['value'] ) );
			$badge  = isset( $item['badge'] ) ? Badge::render(
				array(
					'tone'     => $item['badge']['tone'],
					'children' => $item['badge']['label'],
				)
			) : '';
			$items .= sprintf(
				'<div class="grid min-w-0 content-start gap-1 border-0 border-b border-solid border-border pb-3"><dt class="text-xs text-fg-3">%1$s</dt><dd class="m-0 flex flex-wrap items-center gap-2 text-sm font-medium %2$s">%3$s%4$s</dd></div>',
				esc_html( $item['label'] ),
				( $item['num'] ?? false ) ? 'font-num' : '',
				$value,
				$badge
			);
		}
		return Html::trusted(
			sprintf(
				'<dl class="%1$s">%2$s</dl>',
				esc_attr( Attributes::classes( 'um-info-grid m-0 grid gap-x-6 gap-y-4', 4 === ( $props['columns'] ?? 2 ) ? 'grid-cols-2 lg:grid-cols-4' : 'sm:grid-cols-2', $props['class'] ?? null ) ),
				$items
			)
		);
	}
}
