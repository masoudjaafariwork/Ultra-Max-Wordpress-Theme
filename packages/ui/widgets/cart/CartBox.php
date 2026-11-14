<?php
/**
 * CartBox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The white box of the cart lines (design/cart.html): a heading with the
 * number of items and actions beside it, messages, then the lines.
 *
 * The lines sit inside a form that posts to WooCommerce's cart handler
 * (`action`, `nonce`), so quantities update without JavaScript through the
 * "update" button, which only shows when scripts are off.
 *
 * @phpstan-type CartBoxProps array{
 *     title: string,
 *     lines: list<Html>,
 *     action: string,
 *     nonce: Html,
 *     update: string,
 *     count?: string,
 *     actions?: list<Html>,
 *     messages?: list<Html>,
 *     heading?: 'h1'|'h2',
 *     class?: string,
 * }
 */
final class CartBox {

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param CartBoxProps $props
	 */
	public static function render( array $props ): Html {
		$tag = 'h2' === ( $props['heading'] ?? 'h1' ) ? 'h2' : 'h1';
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><div class="flex flex-wrap items-center justify-between gap-3"><div><%2$s class="m-0 text-lg font-bold">%3$s</%2$s>%4$s</div>%5$s</div>%6$s<p class="m-0 text-sm font-bold empty:hidden data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text" role="status" aria-live="polite" data-um-cart-status></p><form method="post" action="%7$s" class="m-0"><ul class="m-0 list-none p-0">%8$s</ul>%9$s<noscript><p class="m-0 mt-3">%10$s</p></noscript></form></section>',
				esc_attr( Attributes::classes( 'um-scope um-cart-box flex min-w-0 flex-col gap-4 rounded-lg border border-border bg-surface p-5 max-md:-mx-(--um-gutter) max-md:rounded-none max-md:border-x-0 max-md:p-4', $props['class'] ?? null ) ),
				$tag,
				esc_html( $props['title'] ),
				isset( $props['count'] ) ? sprintf( '<span class="font-num text-sm text-fg-2">%s</span>', esc_html( $props['count'] ) ) : '',
				'' === (string) Html::from( $props['actions'] ?? array() ) ? '' : sprintf( '<div class="flex flex-wrap gap-2">%s</div>', Html::from( $props['actions'] ?? array() ) ),
				Html::from( $props['messages'] ?? array() ),
				esc_url( $props['action'] ),
				Html::from( $props['lines'] ),
				$props['nonce'],
				Button::render(
					array(
						'children' => $props['update'],
						'type'     => 'submit',
						'variant'  => 'outline',
						'size'     => 'sm',
						'attrs'    => array(
							'name'  => 'update_cart',
							'value' => '1',
						),
					)
				)
			)
		);
	}
}
