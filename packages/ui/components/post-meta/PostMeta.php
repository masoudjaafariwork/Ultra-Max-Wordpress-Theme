<?php
/**
 * PostMeta component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A line of facts about a post: date, author, category, comments. Each
 * item has an icon; a date item renders `time` with a machine-readable
 * Gregorian `datetime` and the shown (Jalali) text.
 *
 * @phpstan-type PostMetaItem array{icon: string, label: string, href?: string, datetime?: string, hidden_label?: string}
 * @phpstan-type PostMetaProps array{
 *     items: list<PostMetaItem>,
 *     class?: string,
 * }
 */
final class PostMeta {

	/**
	 * Renders the line.
	 *
	 * @param array $props Props.
	 * @phpstan-param PostMetaProps $props
	 */
	public static function render( array $props ): Html {
		$out = '';
		foreach ( $props['items'] as $item ) {
			$text = esc_html( $item['label'] );
			if ( isset( $item['datetime'] ) ) {
				$text = sprintf( '<time datetime="%s">%s</time>', esc_attr( $item['datetime'] ), $text );
			}
			if ( isset( $item['hidden_label'] ) ) {
				$text = sprintf( '<span class="sr-only">%s </span>%s', esc_html( $item['hidden_label'] ), $text );
			}
			if ( isset( $item['href'] ) ) {
				$text = sprintf( '<a href="%s" class="text-inherit no-underline hover:text-primary">%s</a>', esc_url( $item['href'] ), $text );
			}
			$out .= sprintf( '<li class="inline-flex items-center gap-1.5">%s%s</li>', Icon::render( $item['icon'], 'size-4 text-fg-3' ), $text );
		}
		if ( '' === $out ) {
			return Html::none();
		}
		return Html::trusted(
			sprintf(
				'<ul class="%s">%s</ul>',
				esc_attr( Attributes::classes( 'm-0 flex list-none flex-wrap items-center gap-x-4 gap-y-1 p-0 text-xs text-fg-2', $props['class'] ?? null ) ),
				$out
			)
		);
	}
}
