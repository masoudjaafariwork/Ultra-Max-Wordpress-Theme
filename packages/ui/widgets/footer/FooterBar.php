<?php
/**
 * FooterBar component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The thin band at the very bottom of the footer: the copyright line, and
 * optionally a "back to top" button at its end.
 *
 * @phpstan-type FooterBarProps array{text: string, top?: string, class?: string}
 */
final class FooterBar {

	/**
	 * Renders the band.
	 *
	 * @param array $props Props.
	 * @phpstan-param FooterBarProps $props
	 */
	public static function render( array $props ): Html {
		$top = isset( $props['top'] ) && '' !== $props['top']
			? sprintf( '<a href="#page" class="inline-flex shrink-0 items-center gap-1 text-xs text-fg-2 no-underline hover:text-primary">%1$s%2$s</a>', esc_html( $props['top'] ), Icon::render( 'chevron-up', 'size-4' ) )
			: '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><p class="m-0 flex-1">%2$s</p>%3$s</div>',
				esc_attr( Attributes::classes( 'um-footer-bar flex flex-wrap items-center justify-center gap-3 bg-surface-3 px-(--um-gutter) py-3 text-center text-xs text-fg-3', $props['class'] ?? null ) ),
				esc_html( $props['text'] ),
				$top
			)
		);
	}
}
