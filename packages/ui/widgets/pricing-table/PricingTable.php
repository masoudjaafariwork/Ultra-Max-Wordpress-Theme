<?php
/**
 * PricingTable component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A plan with its price, period, list of what is included and a button
 * (HOME-12), for example for subscriptions or services. One plan can be
 * highlighted with a ribbon.
 *
 * @phpstan-type PricingTableProps array{
 *     title: string,
 *     price: string,
 *     currency?: string,
 *     period?: string,
 *     text?: string,
 *     features: list<array{text: string, included: bool}>,
 *     button?: array{label: string, href: string},
 *     ribbon?: string,
 *     featured?: bool,
 *     included_label: string,
 *     excluded_label: string,
 *     class?: string,
 * }
 */
final class PricingTable {

	/**
	 * Renders the plan.
	 *
	 * @param array $props Props.
	 * @phpstan-param PricingTableProps $props
	 */
	public static function render( array $props ): Html {
		$featured = $props['featured'] ?? false;
		$list     = '';
		foreach ( $props['features'] as $feature ) {
			$list .= sprintf(
				'<li class="flex items-start gap-2 %1$s">%2$s<span><span class="sr-only">%3$s </span>%4$s</span></li>',
				$feature['included'] ? 'text-fg' : 'text-fg-3 line-through',
				Icon::render( $feature['included'] ? 'check' : 'close', $feature['included'] ? 'mt-1 size-4 text-success-text' : 'mt-1 size-4' ),
				esc_html( $feature['included'] ? $props['included_label'] : $props['excluded_label'] ),
				esc_html( $feature['text'] )
			);
		}
		$button = isset( $props['button'] ) && '' !== $props['button']['label']
			? Button::render(
				array(
					'children' => $props['button']['label'],
					'href'     => $props['button']['href'],
					'variant'  => $featured ? 'primary' : 'outline',
					'block'    => true,
				)
			)
			: '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<h3 class="m-0 text-lg font-bold">%3$s</h3>%4$s<p class="m-0 flex items-baseline gap-1"><b class="font-num text-4xl font-heavy">%5$s</b><span class="text-sm text-fg-2">%6$s</span>%7$s</p><ul class="m-0 grid flex-1 list-none gap-2 p-0 text-sm">%8$s</ul>%9$s</div>',
				esc_attr( Attributes::classes( 'um-pricing-table relative flex h-full flex-col gap-4 overflow-hidden rounded-xl border bg-surface p-6', $featured ? 'border-primary shadow-md' : 'border-border', $props['class'] ?? null ) ),
				isset( $props['ribbon'] ) && '' !== $props['ribbon'] ? sprintf( '<span class="absolute end-4 top-4 rounded-pill bg-accent px-2.5 py-0.5 text-xs font-bold text-on-accent">%s</span>', esc_html( $props['ribbon'] ) ) : '',
				esc_html( $props['title'] ),
				isset( $props['text'] ) && '' !== $props['text'] ? sprintf( '<p class="m-0 text-sm text-fg-2">%s</p>', esc_html( $props['text'] ) ) : '',
				esc_html( $props['price'] ),
				esc_html( $props['currency'] ?? '' ),
				isset( $props['period'] ) && '' !== $props['period'] ? sprintf( '<span class="text-sm text-fg-3">/ %s</span>', esc_html( $props['period'] ) ) : '',
				$list,
				$button
			)
		);
	}
}
