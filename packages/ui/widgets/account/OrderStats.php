<?php
/**
 * OrderStats component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The shopper's orders by status (ACC-01): current, delivered, returned and
 * cancelled, each a tile with its number that links to those orders.
 *
 * @phpstan-type OrderStat array{label: string, count: string, icon: string, tone: 'warning'|'success'|'info'|'danger'|'neutral', href: string}
 * @phpstan-type OrderStatsProps array{
 *     label: string,
 *     items: list<OrderStat>,
 *     class?: string,
 * }
 */
final class OrderStats {

	private const TONES = array(
		'warning' => 'bg-warning-soft text-warning-text',
		'success' => 'bg-success-soft text-success-text',
		'info'    => 'bg-info-soft text-info-text',
		'danger'  => 'bg-danger-soft text-danger-text',
		'neutral' => 'bg-surface-3 text-fg-2',
	);

	/**
	 * Renders the tiles.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderStatsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="min-w-0"><a href="%1$s" class="flex h-full items-center gap-3 rounded-md px-3 py-3 text-fg no-underline hover:bg-surface-2 focus-visible:outline-3 focus-visible:outline-primary/40 max-md:flex-col max-md:gap-1.5 max-md:text-center"><span class="grid size-12 shrink-0 place-items-center rounded-full %2$s max-md:size-10">%3$s</span><span class="grid gap-0.5"><b class="font-num text-md font-bold max-md:text-sm">%4$s</b><small class="text-xs text-fg-3">%5$s</small></span></a></li>',
				esc_url( $item['href'] ),
				esc_attr( self::TONES[ $item['tone'] ] ),
				Icon::render( $item['icon'], 'size-6 max-md:size-5' ),
				esc_html( $item['count'] ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-order-stats m-0 grid list-none grid-cols-4 gap-2 p-0 max-md:gap-1', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
