<?php
/**
 * SideCart component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * What the side cart drawer shows (CART-01): the lines in their compact
 * look, then at the bottom the amounts, the free shipping bar and the
 * buttons to the cart and the checkout.
 *
 * `shell` renders the empty holder the drawer carries in every page. Its
 * behaviour asks the store for the content when the drawer opens and after
 * every change of the cart, so cached pages never show an old cart.
 *
 * @phpstan-import-type CartSummaryRow from CartSummary
 * @phpstan-type SideCartProps array{
 *     lines: list<Html>,
 *     rows?: list<CartSummaryRow>,
 *     extras?: list<Html>,
 *     actions?: list<Html>,
 *     empty?: Html,
 *     nonce?: string,
 *     count?: int,
 * }
 * @phpstan-type SideCartShell array{endpoint: string, loading: string, error: string, open_on_add?: bool}
 */
final class SideCart {

	/**
	 * The holder inside the drawer.
	 *
	 * @param array $props Props.
	 * @phpstan-param SideCartShell $props
	 */
	public static function shell( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div class="um-scope um-side-cart flex min-h-full flex-col" data-um-component="side-cart" data-endpoint="%1$s" data-error="%2$s"%3$s aria-live="polite" aria-busy="true"><p class="m-0 grid flex-1 place-items-center py-10 text-sm text-fg-3" data-um-side-loading>%4$s</p></div>',
				esc_url( $props['endpoint'] ),
				esc_attr( $props['error'] ),
				( $props['open_on_add'] ?? true ) ? ' data-open-on-add' : '',
				esc_html( $props['loading'] )
			)
		);
	}

	/**
	 * The content.
	 *
	 * @param array $props Props.
	 * @phpstan-param SideCartProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['lines'] ) {
			return Html::trusted( sprintf( '<div class="grid flex-1 place-items-center py-6" data-um-cart-part="side" data-um-side-content data-count="0">%s</div>', $props['empty'] ?? '' ) );
		}
		$rows = '';
		foreach ( $props['rows'] ?? array() as $row ) {
			$save  = 'save' === ( $row['tone'] ?? '' );
			$rows .= sprintf(
				'<dt class="%1$s">%2$s</dt><dd class="m-0 text-end font-num %3$s">%4$s</dd>',
				$save ? 'font-bold text-primary' : 'text-fg-2',
				Html::from( $row['label'] ),
				$save ? 'font-bold text-primary' : 'font-bold',
				Html::from( $row['value'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<div class="flex min-h-full flex-col" data-um-cart-part="side" data-um-side-content data-count="%1$d" data-nonce="%2$s"><ul class="m-0 flex-1 list-none p-0">%3$s</ul><div class="sticky bottom-0 -mx-4 -mb-4 grid gap-3 border-t border-border bg-surface p-4">%4$s%5$s<div class="grid grid-cols-2 gap-2">%6$s</div></div></div>',
				$props['count'] ?? count( $props['lines'] ),
				esc_attr( $props['nonce'] ?? '' ),
				Html::from( $props['lines'] ),
				'' === $rows ? '' : sprintf( '<dl class="m-0 grid grid-cols-[1fr_auto] gap-x-2 gap-y-2 text-sm">%s</dl>', $rows ),
				Html::from( $props['extras'] ?? array() ),
				Html::from( $props['actions'] ?? array() )
			)
		);
	}
}
