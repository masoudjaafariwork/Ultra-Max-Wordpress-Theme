<?php
/**
 * ProgressNote component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A line of text over a progress bar: how much of an offer is sold
 * (PDP-16), or how far the cart is from free shipping (PDP-19).
 *
 * @phpstan-type ProgressNoteProps array{
 *     text: string|Html,
 *     value: int,
 *     label: string,
 *     icon?: string,
 *     end?: string,
 *     tone?: 'success'|'accent'|'primary',
 *     class?: string,
 * }
 */
final class ProgressNote {

	/**
	 * Renders the note.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProgressNoteProps $props
	 */
	public static function render( array $props ): Html {
		$tone  = Props::one_of( 'ProgressNote', $props, 'tone', array( 'success', 'accent', 'primary' ) );
		$value = max( 0, min( 100, $props['value'] ) );
		$bar   = array(
			'success' => 'bg-success',
			'accent'  => 'bg-accent',
			'primary' => 'bg-primary',
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><div class="flex items-center justify-between gap-2 text-xs text-fg-2"><span class="inline-flex items-center gap-1.5 [&_b]:font-num [&_b]:text-fg">%2$s<span>%3$s</span></span>%4$s</div><div class="h-1.5 overflow-hidden rounded-pill bg-surface-3" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%5$d" aria-label="%6$s"><i class="block h-full rounded-pill %7$s" style="width:%5$d%%"></i></div></div>',
				esc_attr( Attributes::classes( 'um-progress-note grid gap-1.5', $props['class'] ?? null ) ),
				isset( $props['icon'] ) ? Icon::render( $props['icon'], 'size-4 text-fg-3' ) : '',
				Html::from( $props['text'] ),
				isset( $props['end'] ) && '' !== $props['end'] ? sprintf( '<span class="font-num text-fg-3">%s</span>', esc_html( $props['end'] ) ) : '',
				$value,
				esc_attr( $props['label'] ),
				$bar[ $tone ]
			)
		);
	}
}
