<?php
/**
 * PaymentMethods component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The payment methods of the checkout as cards (design/checkout.html).
 *
 * The markup keeps WooCommerce's ids, names and classes (`#payment`,
 * `.wc_payment_method`, `input[name=payment_method]`, `.payment_box`),
 * because WooCommerce's checkout script and every payment gateway,
 * Iranian ones included, look for them: the gateway's own fields show in
 * its box, and its button text replaces the order button's.
 *
 * `after` is printed inside `#payment`, for WooCommerce's terms, its
 * security token and other plugins' hooks.
 *
 * @phpstan-type PaymentMethod array{id: string, title: Html, chosen: bool, button?: string, box?: Html}
 * @phpstan-type PaymentMethodsProps array{
 *     methods: list<PaymentMethod>,
 *     label: string,
 *     needs_payment?: bool,
 *     empty?: Html,
 *     after?: Html,
 *     class?: string,
 * }
 */
final class PaymentMethods {

	/**
	 * Renders the methods.
	 *
	 * @param array $props Props.
	 * @phpstan-param PaymentMethodsProps $props
	 */
	public static function render( array $props ): Html {
		$items = '';
		foreach ( $props['methods'] as $method ) {
			$id     = sanitize_html_class( $method['id'] );
			$items .= sprintf(
				'<li class="wc_payment_method payment_method_%1$s rounded-md border border-border has-checked:border-primary has-checked:bg-primary-soft"><div class="flex items-center gap-3 px-4 py-3"><input id="payment_method_%1$s" type="radio" class="input-radio size-5 shrink-0 cursor-pointer appearance-none rounded-pill border-[1.5px] border-border-strong bg-surface checked:border-6 checked:border-primary focus-visible:outline-3 focus-visible:outline-primary/40" name="payment_method" value="%2$s"%3$s data-order_button_text="%4$s"><label for="payment_method_%1$s" class="flex min-w-0 flex-1 cursor-pointer flex-wrap items-center gap-2 text-sm font-bold [&_img]:inline [&_img]:max-h-7 [&_img]:w-auto">%5$s</label></div>%6$s</li>',
				esc_attr( $id ),
				esc_attr( $method['id'] ),
				$method['chosen'] ? ' checked' : '',
				esc_attr( $method['button'] ?? '' ),
				$method['title'],
				isset( $method['box'] ) ? sprintf( '<div class="payment_box payment_method_%1$s border-t border-border px-4 py-3 text-sm text-fg-2"%2$s>%3$s</div>', esc_attr( $id ), $method['chosen'] ? '' : ' style="display:none;"', $method['box'] ) : ''
			);
		}
		$list = '';
		if ( $props['needs_payment'] ?? true ) {
			$list = sprintf(
				'<ul class="wc_payment_methods payment_methods methods m-0 grid list-none gap-2 p-0" aria-label="%1$s">%2$s</ul>',
				esc_attr( $props['label'] ),
				'' === $items ? sprintf( '<li>%s</li>', $props['empty'] ?? '' ) : $items
			);
		}
		return Html::trusted(
			sprintf(
				'<div id="payment" class="%1$s">%2$s%3$s</div>',
				esc_attr( Attributes::classes( 'woocommerce-checkout-payment um-scope um-payment-methods grid gap-3', $props['class'] ?? null ) ),
				$list,
				$props['after'] ?? ''
			)
		);
	}
}
