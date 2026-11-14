<?php
/**
 * CartSummary component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The totals box beside the cart and the checkout (design/cart.html,
 * design/checkout.html): rows of amounts, the saving in the main colour
 * (CART-03), the amount to pay, anything the store adds such as the free
 * shipping bar (CART-04) or the order limit (CART-10), the buttons, and a
 * note in its own box under it.
 *
 * `actions` are rendered as given, so the checkout can put WooCommerce's
 * own "place order" button here.
 *
 * @phpstan-type CartSummaryRow array{label: string|Html, value: string|Html, tone?: 'save'|'total'|'strong', key?: string}
 * @phpstan-type CartSummaryProps array{
 *     rows: list<CartSummaryRow>,
 *     actions?: list<Html>,
 *     extras?: list<Html>,
 *     footnote?: string,
 *     note?: array{icon: string, text: string},
 *     sticky?: bool,
 *     label?: string,
 *     id?: string,
 *     class?: string,
 * }
 */
final class CartSummary {

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param CartSummaryProps $props
	 */
	public static function render( array $props ): Html {
		$rows = '';
		foreach ( $props['rows'] as $row ) {
			$tone  = $row['tone'] ?? '';
			$label = match ( $tone ) {
				'save'           => 'font-bold text-primary',
				'total'          => 'font-bold text-fg',
				default          => 'text-fg-2',
			};
			$value = match ( $tone ) {
				'save'           => 'font-num font-bold text-primary',
				'total'          => 'font-num text-lg font-heavy',
				'strong'         => 'font-num font-bold',
				default          => 'font-num',
			};
			$rows .= sprintf(
				'<dt class="%1$s"%3$s>%2$s</dt><dd class="m-0 text-end %4$s"%3$s>%5$s</dd>',
				esc_attr( $label ),
				Html::from( $row['label'] ),
				isset( $row['key'] ) ? sprintf( ' data-um-row="%s"', esc_attr( $row['key'] ) ) : '',
				esc_attr( $value ),
				Html::from( $row['value'] )
			);
		}
		$footnote = isset( $props['footnote'] ) && '' !== $props['footnote'] ? sprintf( '<p class="m-0 text-xs text-fg-3">%s</p>', esc_html( $props['footnote'] ) ) : '';
		$note     = isset( $props['note'] ) ? sprintf(
			'<div class="flex gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm text-fg-2">%1$s<span>%2$s</span></div>',
			Icon::render( $props['note']['icon'], 'mt-0.5 size-5 shrink-0 text-fg-3' ),
			esc_html( $props['note']['text'] )
		) : '';
		return Html::trusted(
			sprintf(
				'<aside%1$s><div class="flex flex-col gap-4 rounded-lg border border-border bg-surface p-5 max-md:p-4"><dl class="m-0 grid grid-cols-[1fr_auto] gap-x-2 gap-y-3 text-sm">%2$s</dl>%3$s%4$s%5$s</div>%6$s</aside>',
				Attributes::render(
					array(
						'id'         => $props['id'] ?? null,
						'class'      => Attributes::classes(
							'um-scope um-cart-summary flex min-w-0 flex-col gap-3',
							( $props['sticky'] ?? true ) ? 'lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+16px)]' : null,
							$props['class'] ?? null
						),
						'aria-label' => $props['label'] ?? null,
					)
				),
				$rows,
				Html::from( $props['extras'] ?? array() ),
				Html::from( $props['actions'] ?? array() ),
				$footnote,
				$note
			)
		);
	}
}
