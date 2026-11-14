<?php
/**
 * TrackingBox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A parcel's tracking code (ACC-02, SHIP-03), with a button that copies it
 * and a link to follow the parcel on the carrier's site when there is one.
 *
 * @phpstan-type TrackingBoxProps array{
 *     title: string,
 *     code: string,
 *     copy: string,
 *     copied: string,
 *     link?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class TrackingBox {

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param TrackingBoxProps $props
	 */
	public static function render( array $props ): Html {
		$link = isset( $props['link'] )
			? sprintf(
				'<a href="%1$s" class="inline-flex h-9 items-center gap-1 rounded-sm bg-primary-soft px-3 text-sm font-bold text-primary no-underline hover:bg-primary hover:text-on-primary" target="_blank" rel="noopener noreferrer">%2$s%3$s</a>',
				esc_url( $props['link']['href'] ),
				esc_html( $props['link']['label'] ),
				Icon::render( is_rtl() ? 'chevron-left' : 'chevron-right', 'size-4' )
			)
			: '';
		return Html::trusted(
			sprintf(
				'<div%1$s><span class="grid size-12 shrink-0 place-items-center rounded-md bg-surface text-primary">%2$s</span><div class="grid min-w-0 flex-1 gap-0.5"><span class="text-xs text-fg-3">%3$s</span><b class="font-num text-md font-bold break-all" dir="ltr" data-um-code>%4$s</b></div><div class="flex flex-wrap items-center gap-2"><button type="button" class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-sm border border-border-strong bg-surface px-3 text-sm font-medium text-fg hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40" data-um-copy data-copied="%5$s">%6$s<span>%7$s</span></button>%8$s</div><span class="sr-only" role="status" aria-live="polite" data-um-copy-status></span></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-tracking-box flex flex-wrap items-center gap-3 rounded-lg bg-surface-2 p-4', $props['class'] ?? null ),
						'data-um-component' => 'copy-code',
					)
				),
				Icon::render( 'box', 'size-6' ),
				esc_html( $props['title'] ),
				esc_html( $props['code'] ),
				esc_attr( $props['copied'] ),
				Icon::render( 'copy', 'size-4' ),
				esc_html( $props['copy'] ),
				$link
			)
		);
	}
}
