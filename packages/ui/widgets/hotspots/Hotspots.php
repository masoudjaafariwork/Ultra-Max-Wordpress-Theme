<?php
/**
 * Hotspots component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * An image with numbered points; each point opens a small card with a
 * title, a text, a price and a link, such as the products in a room photo
 * (HOME-12). Points are placed in percent, so they stay put at any width.
 *
 * Each point is a native disclosure (`details`), so it opens with a tap,
 * a click or the keyboard without any script; the behaviour only closes
 * the others and closes on Escape.
 *
 * @phpstan-type Hotspot array{x: float, y: float, title: string, text?: string, price?: string, href?: string, link_label?: string}
 * @phpstan-type HotspotsProps array{image: Html, label: string, spots: list<Hotspot>, class?: string}
 */
final class Hotspots {

	/**
	 * Renders the image and points.
	 *
	 * @param array $props Props.
	 * @phpstan-param HotspotsProps $props
	 */
	public static function render( array $props ): Html {
		$spots = '';
		foreach ( $props['spots'] as $index => $spot ) {
			$x      = max( 0, min( 100, $spot['x'] ) );
			$y      = max( 0, min( 100, $spot['y'] ) );
			$link   = isset( $spot['href'] ) && '' !== $spot['href']
				? sprintf( '<a href="%1$s" class="mt-1 inline-flex text-sm font-bold text-primary no-underline hover:underline">%2$s</a>', esc_url( $spot['href'] ), esc_html( $spot['link_label'] ?? $spot['title'] ) )
				: '';
			$spots .= sprintf(
				'<details class="group absolute -translate-x-1/2 -translate-y-1/2 open:z-3" style="left:%1$s%%;top:%2$s%%"><summary class="grid size-8 cursor-pointer list-none place-items-center rounded-full bg-surface font-num text-sm font-bold text-primary shadow-md ring-4 ring-white/50 transition-transform group-open:scale-110 hover:scale-110 [&::-webkit-details-marker]:hidden" aria-label="%3$s">%4$s</summary><div class="absolute top-full mt-2 w-56 left-1/2 -translate-x-1/2 rounded-md bg-surface p-3 text-start shadow-lg"><b class="block text-sm">%5$s</b>%6$s%7$s%8$s</div></details>',
				esc_attr( (string) round( $x, 2 ) ),
				esc_attr( (string) round( $y, 2 ) ),
				esc_attr( $spot['title'] ),
				esc_html( (string) ( $index + 1 ) ),
				esc_html( $spot['title'] ),
				isset( $spot['text'] ) && '' !== $spot['text'] ? sprintf( '<p class="m-0 mt-1 text-xs text-fg-2">%s</p>', esc_html( $spot['text'] ) ) : '',
				isset( $spot['price'] ) && '' !== $spot['price'] ? sprintf( '<b class="mt-1 block font-num text-sm text-fg">%s</b>', esc_html( $spot['price'] ) ) : '',
				$link
			);
		}
		return Html::trusted(
			sprintf(
				'<div%1$s><div class="[&_img]:block [&_img]:h-auto [&_img]:w-full">%2$s</div>%3$s</div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-hotspots relative overflow-visible', $props['class'] ?? null ),
						'role'              => 'group',
						'aria-label'        => $props['label'],
						'data-um-component' => 'hotspots',
					)
				),
				$props['image'],
				$spots
			)
		);
	}
}
