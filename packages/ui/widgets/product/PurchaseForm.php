<?php
/**
 * PurchaseForm component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The add to cart form of a product page (PDP-05, PDP-21, PDP-25): the
 * quantity stepper, "add to cart" and "buy now".
 *
 * It is WooCommerce's own form, posted to the product address, so it works
 * without JavaScript and every plugin that adds fields through WooCommerce's
 * hooks keeps working (`extra`). With JavaScript the purchase behaviour adds
 * the product in place and updates the cart counters; "buy now" goes on to
 * the checkout.
 *
 * For a variable product, `variations` lists every variation the shopper
 * can buy; the VariationPicker's choices belong to this form through their
 * `form` attribute. When a choice matches a variation, the behaviour fills
 * `variation_id`, updates prices, stock and limits, and fires
 * `um:variation` on the document for the gallery and other parts.
 *
 * @phpstan-type PurchaseVariation array{
 *     id: int,
 *     attributes: array<string, string>,
 *     price: string,
 *     regular: string,
 *     discount: string,
 *     in_stock: bool,
 *     stock: string,
 *     stock_tone: 'danger'|'success'|'muted',
 *     min: int,
 *     max: int,
 *     step: int,
 *     gallery: string,
 *     sku: string,
 *     summary: string,
 * }
 * @phpstan-type PurchaseFormProps array{
 *     id: string,
 *     product: int,
 *     action: string,
 *     add: array{label: string, disabled?: bool},
 *     messages: array{choose: string, unavailable: string, out: string, added: string, cart: string, cart_url: string, error: string},
 *     quantity?: array{min: int, max: int, step: int, value: int, label: string, increase: string, decrease: string},
 *     quick?: string,
 *     variations?: list<PurchaseVariation>,
 *     hidden?: array<string, string>,
 *     extra?: Html,
 *     after?: Html,
 *     endpoint?: string,
 *     class?: string,
 * }
 */
final class PurchaseForm {

	/**
	 * Renders the form.
	 *
	 * @param array $props Props.
	 * @phpstan-param PurchaseFormProps $props
	 */
	public static function render( array $props ): Html {
		$id       = Props::id( 'PurchaseForm', $props );
		$variable = isset( $props['variations'] );
		$hidden   = array(
			'add-to-cart' => (string) $props['product'],
			'product_id'  => (string) $props['product'],
		);
		if ( $variable ) {
			$hidden['variation_id'] = '0';
		}
		$fields = '';
		foreach ( array_merge( $hidden, $props['hidden'] ?? array() ) as $name => $value ) {
			$fields .= sprintf( '<input type="hidden" name="%1$s" value="%2$s"%3$s>', esc_attr( $name ), esc_attr( $value ), 'variation_id' === $name ? ' data-um-variation-id' : '' );
		}
		$disabled = ( $props['add']['disabled'] ?? false ) || $variable;
		$add      = sprintf(
			'<button type="submit" class="inline-flex min-h-12 min-w-0 flex-1 cursor-pointer items-center justify-center gap-2 rounded-md border-0 bg-primary px-5 text-md font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40 disabled:cursor-not-allowed disabled:opacity-45 aria-busy:opacity-60" data-um-add-button%1$s>%2$s</button>',
			$disabled && ! $variable ? ' disabled' : '',
			esc_html( $props['add']['label'] )
		);
		$quick    = isset( $props['quick'] ) && '' !== $props['quick'] ? sprintf(
			'<button type="submit" name="um-buy-now" value="1" class="inline-flex min-h-9 w-full cursor-pointer items-center justify-center gap-2 rounded-sm border-0 bg-primary-soft px-3 text-sm font-bold text-primary hover:bg-primary-soft-2 focus-visible:outline-3 focus-visible:outline-primary/40 disabled:cursor-not-allowed disabled:opacity-45" data-um-quick-button%1$s>%2$s%3$s</button>',
			$disabled && ! $variable ? ' disabled' : '',
			Icon::render( 'zap', 'size-4' ),
			esc_html( $props['quick'] )
		) : '';
		$data     = $variable ? sprintf(
			'<script type="application/json" data-um-variations>%s</script>',
			wp_json_encode( $props['variations'] ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE )
		) : '';

		return Html::trusted(
			sprintf(
				'<form%1$s>%2$s%3$s<div class="flex items-stretch gap-2">%4$s%5$s</div>%6$s<p class="m-0 hidden text-sm data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text data-shown:block" role="status" data-um-purchase-status></p>%7$s%8$s</form>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-purchase m-0 grid gap-3', $props['class'] ?? null ),
						'method'            => 'post',
						'action'            => esc_url( $props['action'] ),
						'enctype'           => 'multipart/form-data',
						'data-um-component' => 'purchase',
						'data-product'      => (string) $props['product'],
						'data-endpoint'     => isset( $props['endpoint'] ) ? esc_url_raw( $props['endpoint'] ) : null,
						'data-messages'     => (string) wp_json_encode( $props['messages'] ),
					)
				),
				$fields,
				isset( $props['extra'] ) ? $props['extra'] : '',
				isset( $props['quantity'] ) ? self::quantity( $id, $props['quantity'] ) : '',
				$add,
				$quick,
				isset( $props['after'] ) ? $props['after'] : '',
				$data
			)
		);
	}

	/**
	 * The quantity stepper: a number field between minus and plus buttons.
	 *
	 * @param string $id       Form id.
	 * @param array  $quantity Limits and texts.
	 * @phpstan-param array{min: int, max: int, step: int, value: int, label: string, increase: string, decrease: string} $quantity
	 */
	private static function quantity( string $id, array $quantity ): string {
		$button = 'grid w-10 cursor-pointer place-items-center border-0 bg-transparent text-primary hover:bg-primary-soft focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40 disabled:cursor-not-allowed disabled:text-fg-3 disabled:hover:bg-transparent';
		return sprintf(
			'<div class="inline-flex shrink-0 items-stretch overflow-hidden rounded-md border border-border-strong bg-surface" data-um-quantity><button type="button" class="%1$s" data-um-step="1" aria-label="%2$s" aria-controls="%3$s-qty">%4$s</button><label for="%3$s-qty" class="sr-only">%5$s</label><input id="%3$s-qty" type="number" name="quantity" inputmode="numeric" class="w-12 appearance-none border-0 bg-transparent p-0 text-center font-num text-md font-bold [-moz-appearance:textfield] focus-visible:outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" min="%6$d"%7$s step="%8$d" value="%9$d"><button type="button" class="%1$s" data-um-step="-1" aria-label="%10$s" aria-controls="%3$s-qty">%11$s</button></div>',
			esc_attr( $button ),
			esc_attr( $quantity['increase'] ),
			esc_attr( $id ),
			Icon::render( 'plus', 'size-4' ),
			esc_html( $quantity['label'] ),
			max( 1, $quantity['min'] ),
			$quantity['max'] > 0 ? sprintf( ' max="%d"', $quantity['max'] ) : '',
			max( 1, $quantity['step'] ),
			max( 1, $quantity['value'] ),
			esc_attr( $quantity['decrease'] ),
			Icon::render( 'minus', 'size-4' )
		);
	}
}
