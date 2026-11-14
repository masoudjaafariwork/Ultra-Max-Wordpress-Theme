<?php
/**
 * OrderDone component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The top of the thank you page (BLD-06): a large mark, the thanks, a
 * line about what happens next, the order's key facts (number, date,
 * amount, payment method) and the buttons to go on.
 *
 * `tone` is success for a placed order and warning for one that still
 * waits for payment or failed.
 *
 * @phpstan-type OrderFact array{label: string, value: string, ltr?: bool}
 * @phpstan-type OrderDoneProps array{
 *     title: string,
 *     text?: string,
 *     facts?: list<OrderFact>,
 *     actions?: list<Html>,
 *     tone?: 'success'|'warning',
 *     class?: string,
 * }
 */
final class OrderDone {

	/**
	 * Renders the top.
	 *
	 * @param array $props Props.
	 * @phpstan-param OrderDoneProps $props
	 */
	public static function render( array $props ): Html {
		$tone  = Props::one_of( 'OrderDone', $props, 'tone', array( 'success', 'warning' ) );
		$facts = '';
		foreach ( $props['facts'] ?? array() as $fact ) {
			$facts .= sprintf(
				'<div class="grid gap-1 rounded-md bg-surface-2 px-4 py-3"><dt class="text-xs text-fg-3">%1$s</dt><dd class="m-0 font-num text-sm font-bold"%2$s>%3$s</dd></div>',
				esc_html( $fact['label'] ),
				( $fact['ltr'] ?? false ) ? ' dir="ltr"' : '',
				esc_html( $fact['value'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><span class="grid size-16 place-items-center rounded-pill %2$s">%3$s</span><h1 class="m-0 text-xl font-heavy">%4$s</h1>%5$s%6$s%7$s</section>',
				esc_attr( Attributes::classes( 'um-scope um-order-done grid justify-items-center gap-4 rounded-lg border border-border bg-surface p-6 text-center max-md:p-4', $props['class'] ?? null ) ),
				'success' === $tone ? 'bg-success-soft text-success-text' : 'bg-warning-soft text-warning-text',
				Icon::render( 'success' === $tone ? 'check' : 'alert', 'size-8' ),
				esc_html( $props['title'] ),
				isset( $props['text'] ) && '' !== $props['text'] ? sprintf( '<p class="m-0 max-w-140 text-sm text-fg-2">%s</p>', esc_html( $props['text'] ) ) : '',
				'' === $facts ? '' : sprintf( '<dl class="m-0 grid w-full grid-cols-2 gap-2 text-start md:grid-cols-4">%s</dl>', $facts ),
				'' === (string) Html::from( $props['actions'] ?? array() ) ? '' : sprintf( '<div class="flex flex-wrap justify-center gap-2">%s</div>', Html::from( $props['actions'] ?? array() ) )
			)
		);
	}
}
