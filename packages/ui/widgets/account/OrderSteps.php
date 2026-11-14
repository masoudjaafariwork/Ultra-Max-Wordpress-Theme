<?php
/**
 * OrderSteps component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Where an order is (ACC-02): placed, paid, being prepared, handed to the
 * carrier, delivered. Done steps carry a check and their date, the current
 * one its own icon. On phones the steps stand in a column.
 *
 * @phpstan-type OrderStep array{label: string, icon: string, state: 'done'|'current'|'todo', date?: string}
 * @phpstan-type OrderStepsProps array{
 *     label: string,
 *     steps: list<OrderStep>,
 *     class?: string,
 * }
 */
final class OrderSteps {

	/**
	 * Renders the steps.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderStepsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		$last  = count( $props['steps'] ) - 1;
		foreach ( $props['steps'] as $index => $step ) {
			$state = $step['state'];
			$dot   = match ( $state ) {
				'done'    => 'border-success bg-success text-white',
				'current' => 'border-primary bg-primary-soft text-primary',
				default   => 'border-border-strong bg-surface text-fg-3',
			};
			$line   = $index < $last ? sprintf(
				'<span class="absolute top-5 -z-1 h-0.5 w-full start-1/2 %s max-md:top-10 max-md:start-5 max-md:h-[calc(100%%-24px)] max-md:w-0.5" aria-hidden="true"></span>',
				'done' === $state ? 'bg-success' : 'bg-border'
			) : '';
			$items .= sprintf(
				'<li class="relative z-0 grid min-w-0 flex-1 justify-items-center gap-1.5 text-center max-md:grid-cols-[40px_1fr] max-md:justify-items-start max-md:gap-x-3 max-md:pb-5 max-md:text-start"%1$s>%2$s<span class="grid size-10 place-items-center rounded-full border-2 border-solid %3$s max-md:row-span-2">%4$s</span><strong class="text-sm %5$s">%6$s</strong><small class="min-h-4 font-num text-xs text-fg-3">%7$s</small></li>',
				'current' === $state ? ' aria-current="step"' : '',
				$line,
				esc_attr( $dot ),
				Icon::render( 'done' === $state ? 'check' : $step['icon'], 'size-5' ),
				'todo' === $state ? 'font-medium text-fg-3' : 'font-bold text-fg',
				esc_html( $step['label'] ),
				esc_html( $step['date'] ?? '' )
			);
		}
		return Html::trusted(
			sprintf(
				'<ol class="%1$s" aria-label="%2$s">%3$s</ol>',
				esc_attr( Attributes::classes( 'um-order-steps m-0 flex list-none gap-2 p-0 max-md:flex-col max-md:gap-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
