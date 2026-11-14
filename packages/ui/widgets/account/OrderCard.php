<?php
/**
 * OrderCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One order in a list (ACC-01): its status, date, number and total, and
 * small pictures of its products. The whole card links to the order.
 *
 * @phpstan-type OrderStatus array{label: string, tone: 'warning'|'success'|'info'|'danger'|'neutral', icon: string}
 * @phpstan-type OrderCardProps array{
 *     href: string,
 *     label: string,
 *     status: OrderStatus,
 *     meta: list<array{label: string, value: string, num?: bool}>,
 *     images: list<Html>,
 *     more?: string,
 *     class?: string,
 * }
 */
final class OrderCard {

	public const TONES = array(
		'warning' => 'warning',
		'success' => 'success',
		'info'    => 'info',
		'danger'  => 'danger',
		'neutral' => 'neutral',
	);

	/**
	 * The status badge of an order.
	 *
	 * @param array $status Status.
	 * @phpstan-param OrderStatus $status
	 */
	public static function status( array $status ): Html {
		return Badge::render(
			array(
				'tone'     => self::TONES[ $status['tone'] ],
				'children' => Html::trusted( Icon::render( $status['icon'], 'size-3.5' ) . esc_html( $status['label'] ) ),
			)
		);
	}

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderCardProps $props
	 */
	public static function render( array $props ): Html {
		$meta = '';
		foreach ( $props['meta'] as $item ) {
			$meta .= sprintf(
				'<span class="inline-flex items-center gap-1">%1$s <b class="%2$s">%3$s</b></span>',
				esc_html( $item['label'] ),
				( $item['num'] ?? false ) ? 'font-num font-bold text-fg' : 'font-bold text-fg',
				esc_html( $item['value'] )
			);
		}
		$images = '';
		foreach ( $props['images'] as $image ) {
			$images .= sprintf( '<li class="size-16 shrink-0 overflow-hidden rounded-md border border-border bg-surface-2 [&_img]:size-full [&_img]:object-contain max-md:size-14">%s</li>', $image );
		}
		if ( isset( $props['more'] ) && '' !== $props['more'] ) {
			$images .= sprintf( '<li class="grid size-16 shrink-0 place-items-center rounded-md border border-border bg-surface-2 font-num text-sm font-bold text-fg-2 max-md:size-14">%s</li>', esc_html( $props['more'] ) );
		}
		return Html::trusted(
			sprintf(
				'<a href="%1$s" class="%2$s" aria-label="%3$s"><div class="flex items-center gap-2">%4$s<span class="flex-1"></span>%5$s</div><div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-fg-3">%6$s</div>%7$s</a>',
				esc_url( $props['href'] ),
				esc_attr( Attributes::classes( 'um-order-card grid gap-3 rounded-lg border border-border bg-surface p-4 text-fg no-underline hover:border-border-strong hover:shadow-sm focus-visible:outline-3 focus-visible:outline-primary/40', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				self::status( $props['status'] ),
				Icon::render( is_rtl() ? 'chevron-left' : 'chevron-right', 'size-4 text-fg-3' ),
				$meta,
				'' === $images ? '' : sprintf( '<ul class="m-0 flex list-none gap-2 overflow-hidden p-0">%s</ul>', $images )
			)
		);
	}
}
