<?php
/**
 * ViewToggle component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Two buttons that show a product grid as cards or as rows (PLP-05). The
 * shop behaviour switches the `data-view` of the grid it controls and
 * remembers the choice in this browser.
 *
 * @phpstan-type ViewToggleProps array{
 *     controls: string,
 *     label: string,
 *     grid: string,
 *     list: string,
 *     current?: 'grid'|'list',
 *     class?: string,
 * }
 */
final class ViewToggle {

	/**
	 * Renders the switch.
	 *
	 * @param array $props Props.
	 * @phpstan-param ViewToggleProps $props
	 */
	public static function render( array $props ): Html {
		$current = Props::one_of( 'ViewToggle', $props, 'current', array( 'grid', 'list' ) );
		$button  = static fn ( string $view, string $label, string $icon ): string => sprintf(
			'<button type="button" class="grid size-8 place-items-center rounded-sm text-fg-3 hover:text-fg aria-pressed:bg-surface aria-pressed:text-primary aria-pressed:shadow-xs focus-visible:outline-3 focus-visible:outline-primary/40" aria-pressed="%1$s" data-um-view="%2$s" aria-controls="%3$s"><span class="sr-only">%4$s</span>%5$s</button>',
			$view === $current ? 'true' : 'false',
			esc_attr( $view ),
			esc_attr( $props['controls'] ),
			esc_html( $label ),
			Icon::render( $icon, 'size-4.5' )
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s" role="group" aria-label="%2$s">%3$s%4$s</div>',
				esc_attr( Attributes::classes( 'um-view-toggle inline-flex gap-0.5 rounded-md bg-surface-3 p-0.5', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$button( 'grid', $props['grid'], 'grid' ),
				$button( 'list', $props['list'], 'list' )
			)
		);
	}
}
