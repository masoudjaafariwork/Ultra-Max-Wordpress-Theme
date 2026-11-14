<?php
/**
 * CheckoutSteps component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The steps above the checkout (design/checkout.html, CHK-01): cart,
 * shipping details, payment. A done step shows a tick; the checkout
 * behaviour moves the current step as the shopper goes on.
 *
 * @phpstan-type CheckoutStep array{key: string, label: string, icon: string, state: 'done'|'current'|'todo', href?: string}
 * @phpstan-type CheckoutStepsProps array{
 *     steps: list<CheckoutStep>,
 *     label: string,
 *     class?: string,
 * }
 */
final class CheckoutSteps {

	/**
	 * Renders the steps.
	 *
	 * @param array $props Props.
	 * @phpstan-param CheckoutStepsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['steps'] as $step ) {
			$dot    = sprintf(
				'<span class="relative z-1 grid size-10 place-items-center rounded-pill border-2 border-border-strong bg-surface text-fg-3 group-data-[state=current]:border-primary group-data-[state=current]:text-primary group-data-[state=done]:border-primary group-data-[state=done]:bg-primary group-data-[state=done]:text-on-primary max-md:size-8">%1$s%2$s</span>',
				Icon::render( $step['icon'], 'size-5 group-data-[state=done]:hidden max-md:size-4' ),
				Icon::render( 'check', 'hidden size-5 group-data-[state=done]:block max-md:size-4' )
			);
			$label  = sprintf( '<strong class="text-sm font-bold text-fg-3 group-data-[state=current]:text-fg group-data-[state=done]:text-fg max-md:text-xs">%s</strong>', esc_html( $step['label'] ) );
			$inner  = isset( $step['href'] ) && 'done' === $step['state']
				? sprintf( '<a href="%1$s" class="grid justify-items-center gap-2 no-underline focus-visible:outline-3 focus-visible:outline-primary/40">%2$s%3$s</a>', esc_url( $step['href'] ), $dot, $label )
				: sprintf( '<span class="grid justify-items-center gap-2">%1$s%2$s</span>', $dot, $label );
			$items .= sprintf(
				'<li class="group relative flex-1 text-center before:absolute before:top-5 before:start-[-50%%] before:h-0.5 before:w-full before:bg-border-strong first:before:hidden data-[state=current]:before:bg-primary data-[state=done]:before:bg-primary max-md:before:top-4" data-state="%1$s" data-um-step-item="%2$s"%3$s>%4$s</li>',
				esc_attr( $step['state'] ),
				esc_attr( $step['key'] ),
				'current' === $step['state'] ? ' aria-current="step"' : '',
				$inner
			);
		}
		return Html::trusted(
			sprintf(
				'<nav class="%1$s" aria-label="%2$s"><ol class="m-0 flex list-none p-0">%3$s</ol></nav>',
				esc_attr( Attributes::classes( 'um-scope um-checkout-steps mx-auto w-full max-w-160', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
