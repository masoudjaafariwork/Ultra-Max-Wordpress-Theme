<?php
/**
 * Tooltip component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A short hint shown on hover and on keyboard focus of its trigger.
 *
 * `children` is the trigger and must contain one focusable element, such
 * as a Button. Screen readers hear the text as the trigger's description.
 *
 * @phpstan-type TooltipProps array{
 *     id: string,
 *     text: string,
 *     children: string|Html|array<int, string|Html>,
 *     placement?: 'top'|'bottom'|'start'|'end',
 *     class?: string,
 * }
 */
final class Tooltip {

	/**
	 * Renders the tooltip.
	 *
	 * @param array $props Props.
	 * @phpstan-param TooltipProps $props
	 */
	public static function render( array $props ): Html {
		$id        = Props::id( 'Tooltip', $props );
		$placement = Props::one_of( 'Tooltip', $props, 'placement', array( 'top', 'bottom', 'start', 'end' ) );

		return Html::trusted(
			sprintf(
				'<span%1$s>%2$s<span id="%3$s" role="tooltip" class="um-tooltip invisible absolute z-(--um-z-toast) inline-block max-w-60 rounded-sm bg-tooltip px-2.5 py-1.5 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150">%4$s<span class="um-tooltip-arrow" data-popper-arrow></span></span></span>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-scope inline-flex', $props['class'] ?? null ),
						'data-um-component' => 'tooltip',
						'data-um-tooltip'   => $id,
						'data-um-placement' => $placement,
					)
				),
				Html::from( $props['children'] ),
				esc_attr( $id ),
				esc_html( $props['text'] )
			)
		);
	}
}
