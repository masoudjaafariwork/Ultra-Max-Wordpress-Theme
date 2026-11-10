<?php
/**
 * IconBox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * An icon or image with a title and a short text, optionally a link
 * (HOME-12). Stacked, or with the icon at the start of the row.
 *
 * @phpstan-type IconBoxProps array{
 *     title: string,
 *     text?: string,
 *     icon?: string,
 *     image?: Html,
 *     href?: string,
 *     layout?: 'stacked'|'inline',
 *     framed?: bool,
 *     align?: 'start'|'center',
 *     class?: string,
 * }
 */
final class IconBox {

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param IconBoxProps $props
	 */
	public static function render( array $props ): Html {
		$inline = 'inline' === ( $props['layout'] ?? 'stacked' );
		$center = ! $inline && 'center' === ( $props['align'] ?? 'start' );
		$visual = isset( $props['image'] ) && ! $props['image']->is_empty()
			? sprintf( '<span class="block size-14 shrink-0 [&_img]:size-full [&_img]:object-contain">%s</span>', $props['image'] )
			: sprintf( '<span class="grid size-13 shrink-0 place-items-center rounded-full bg-primary-soft text-primary">%s</span>', Icon::render( $props['icon'] ?? 'star', 'size-6' ) );
		$title  = isset( $props['href'] ) && '' !== $props['href']
			? sprintf( '<a href="%1$s" class="text-fg no-underline after:absolute after:inset-0 hover:text-primary">%2$s</a>', esc_url( $props['href'] ), esc_html( $props['title'] ) )
			: esc_html( $props['title'] );
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<div class="grid gap-1"><h3 class="m-0 text-md font-bold">%3$s</h3>%4$s</div></div>',
				esc_attr(
					Attributes::classes(
						'um-icon-box relative flex gap-3',
						$inline ? 'items-start' : 'flex-col',
						$center ? 'items-center text-center' : null,
						( $props['framed'] ?? false ) ? 'rounded-lg border border-border bg-surface p-5 transition-shadow hover:shadow-md' : null,
						$props['class'] ?? null
					)
				),
				$visual,
				$title,
				isset( $props['text'] ) && '' !== $props['text'] ? sprintf( '<p class="m-0 text-sm leading-body text-fg-2">%s</p>', esc_html( $props['text'] ) ) : ''
			)
		);
	}
}
