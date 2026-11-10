<?php
/**
 * Timeline component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Steps or events along a line, each with a date or label, a title and a
 * text (HOME-12): the story of the store, or how ordering works.
 *
 * @phpstan-type TimelineItem array{title: string, label?: string, text?: string, icon?: string}
 * @phpstan-type TimelineProps array{items: list<TimelineItem>, label: string, class?: string}
 */
final class Timeline {

	/**
	 * Renders the timeline.
	 *
	 * @param array $props Props.
	 * @phpstan-param TimelineProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="relative grid gap-1 ps-12 pb-8 last:pb-0 before:absolute before:start-4.75 before:top-10 before:bottom-0 before:w-0.5 before:bg-border last:before:hidden"><span class="absolute start-0 top-0 grid size-10 place-items-center rounded-full border-2 border-primary bg-surface text-primary">%1$s</span>%2$s<h3 class="m-0 text-md font-bold">%3$s</h3>%4$s</li>',
				Icon::render( $item['icon'] ?? 'check', 'size-5' ),
				isset( $item['label'] ) && '' !== $item['label'] ? sprintf( '<span class="text-xs font-bold text-primary">%s</span>', esc_html( $item['label'] ) ) : '',
				esc_html( $item['title'] ),
				isset( $item['text'] ) && '' !== $item['text'] ? sprintf( '<p class="m-0 text-sm leading-body text-fg-2">%s</p>', esc_html( $item['text'] ) ) : ''
			);
		}
		return Html::trusted(
			sprintf(
				'<ol class="%1$s" aria-label="%2$s">%3$s</ol>',
				esc_attr( Attributes::classes( 'um-timeline m-0 list-none p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
