<?php
/**
 * CouponForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The discount code field with its button, and the codes already applied
 * as chips with a remove link.
 *
 * On the cart it is a form that posts to WooCommerce's own cart handler,
 * so it works without JavaScript. Inside the checkout form a second form
 * is not allowed: `inline` renders the same field in a plain block, and
 * the checkout behaviour sends it.
 *
 * @phpstan-type CouponChip array{code: string, remove: string, amount?: string}
 * @phpstan-type CouponFormProps array{
 *     id: string,
 *     label: string,
 *     submit: string,
 *     remove: string,
 *     action?: string,
 *     hidden?: array<string, string>,
 *     placeholder?: string,
 *     coupons?: list<CouponChip>,
 *     inline?: bool,
 *     title?: string,
 *     class?: string,
 * }
 */
final class CouponForm {

	/**
	 * Renders the field.
	 *
	 * @param array $props Props.
	 * @phpstan-param CouponFormProps $props
	 */
	public static function render( array $props ): Html {
		$id     = Props::id( 'CouponForm', $props );
		$inline = $props['inline'] ?? false;
		$hidden = '';
		foreach ( $props['hidden'] ?? array() as $name => $value ) {
			$hidden .= sprintf( '<input type="hidden" name="%1$s" value="%2$s">', esc_attr( $name ), esc_attr( $value ) );
		}
		$chips = '';
		foreach ( $props['coupons'] ?? array() as $coupon ) {
			$chips .= sprintf(
				'<li class="inline-flex items-center gap-1 rounded-pill bg-success-soft py-1 ps-3 pe-1 text-sm font-bold text-success-text"><span dir="ltr">%1$s</span>%2$s<a href="%3$s" class="grid size-6 place-items-center rounded-pill text-success-text no-underline hover:bg-surface focus-visible:outline-3 focus-visible:outline-primary/40" data-um-coupon-remove="%4$s" aria-label="%5$s">%6$s</a></li>',
				esc_html( strtoupper( $coupon['code'] ) ),
				isset( $coupon['amount'] ) && '' !== $coupon['amount'] ? sprintf( '<span class="font-num font-regular">%s</span>', esc_html( $coupon['amount'] ) ) : '',
				esc_url( $coupon['remove'] ),
				esc_attr( $coupon['code'] ),
				/* translators: %s: coupon code. */
				esc_attr( sprintf( $props['remove'], $coupon['code'] ) ),
				Icon::render( 'close', 'size-3.5' )
			);
		}
		$field  = sprintf(
			'<div class="flex gap-2"><label for="%1$s-code" class="sr-only">%2$s</label><input id="%1$s-code" type="text" name="coupon_code" class="h-11 min-w-0 flex-1 appearance-none rounded-md border border-border-strong bg-surface px-3.5 text-md text-fg outline-none placeholder:text-fg-3 hover:border-fg-3 focus:border-primary focus:shadow-[var(--um-focus)]" autocomplete="off" placeholder="%3$s" data-um-coupon-code>%4$s</div>',
			esc_attr( $id ),
			esc_html( $props['label'] ),
			esc_attr( $props['placeholder'] ?? $props['label'] ),
			Button::render(
				array(
					'children' => $props['submit'],
					'type'     => $inline ? 'button' : 'submit',
					'variant'  => 'neutral',
					'class'    => 'shrink-0',
					'attrs'    => array(
						'name'                => $inline ? null : 'apply_coupon',
						'value'               => $inline ? null : '1',
						'data-um-coupon-send' => true,
					),
				)
			)
		);
		$inside = sprintf(
			'%1$s%2$s<p class="m-0 text-sm empty:hidden data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text" role="status" aria-live="polite" data-um-coupon-status></p>%3$s',
			isset( $props['title'] ) && '' !== $props['title'] ? sprintf( '<h2 class="m-0 text-md font-bold">%s</h2>', esc_html( $props['title'] ) ) : '',
			$field,
			'' === $chips ? '' : sprintf( '<ul class="m-0 flex list-none flex-wrap gap-2 p-0">%s</ul>', $chips )
		);
		$attrs  = array(
			'id'                => $id,
			'class'             => Attributes::classes( 'um-scope um-coupon grid gap-3', $props['class'] ?? null ),
			'data-um-component' => 'coupon',
		);
		if ( $inline ) {
			return Html::trusted( sprintf( '<div%1$s data-inline>%2$s</div>', Attributes::render( $attrs ), $inside ) );
		}
		return Html::trusted(
			sprintf(
				'<form%1$s>%2$s%3$s</form>',
				Attributes::render(
					array_merge(
						$attrs,
						array(
							'method' => 'post',
							'action' => esc_url( $props['action'] ?? '' ),
						)
					)
				),
				$inside,
				$hidden
			)
		);
	}
}
