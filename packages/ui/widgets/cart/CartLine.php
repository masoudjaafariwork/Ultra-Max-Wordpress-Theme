<?php
/**
 * CartLine component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One product of the cart (design/cart.html): the picture with the
 * quantity stepper under it, the name, a few facts (colour, warranty,
 * seller, when it ships), the saving, the line total, and a button that
 * moves it to the "buy later" list (CART-02).
 *
 * The stepper is a real number field named like WooCommerce's cart form
 * (`cart[<key>][qty]`), so the list updates without JavaScript through the
 * form's "update" button. With JavaScript, the cart behaviour sends each
 * change at once. At the minimum the minus button becomes a bin that
 * removes the line.
 *
 * `compact` is the look of the side cart (CART-01): a small picture, the
 * stepper beside the total, and no facts.
 *
 * @phpstan-type CartLineFact array{text: string, icon?: string, color?: string, tone?: 'primary'}
 * @phpstan-type CartLineQuantity array{value: int, min: int, max: int, step: int}
 * @phpstan-type CartLineLabels array{quantity: string, increase: string, decrease: string, remove: string, later?: string}
 * @phpstan-type CartLineProps array{
 *     key: string,
 *     title: string,
 *     image: Html,
 *     total: string|Html,
 *     labels: CartLineLabels,
 *     remove: string,
 *     href?: string,
 *     facts?: list<CartLineFact>,
 *     saving?: string,
 *     quantity?: CartLineQuantity|null,
 *     later?: string,
 *     notice?: string,
 *     compact?: bool,
 *     class?: string,
 * }
 */
final class CartLine {

	private const STEP = 'grid w-9 cursor-pointer place-items-center border-0 bg-transparent text-primary hover:bg-primary-soft focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40 disabled:cursor-not-allowed disabled:text-fg-3 disabled:hover:bg-transparent data-[remove]:text-danger-text';

	/**
	 * Renders the line.
	 *
	 * @param array $props Props.
	 * @phpstan-param CartLineProps $props
	 */
	public static function render( array $props ): Html {
		$compact = $props['compact'] ?? false;
		$key     = $props['key'];
		$title   = isset( $props['href'] ) && '' !== $props['href']
			? sprintf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $props['href'] ), $compact ? 'line-clamp-2 text-sm font-medium text-fg no-underline hover:text-primary' : 'line-clamp-2 text-md font-bold leading-[1.8] text-fg no-underline hover:text-primary max-md:text-sm', esc_html( $props['title'] ) )
			: sprintf( '<span class="%1$s">%2$s</span>', $compact ? 'line-clamp-2 text-sm font-medium' : 'line-clamp-2 text-md font-bold leading-[1.8] max-md:text-sm', esc_html( $props['title'] ) );
		$stepper = self::stepper( $key, $props['quantity'] ?? null, $props['labels'], $props['remove'] );
		$facts   = '';
		if ( ! $compact ) {
			foreach ( $props['facts'] ?? array() as $fact ) {
				$facts .= self::fact( $fact );
			}
			$facts = '' === $facts ? '' : sprintf( '<ul class="m-0 grid list-none gap-1 p-0 text-xs text-fg-2">%s</ul>', $facts );
		}
		$saving = isset( $props['saving'] ) && '' !== $props['saving'] ? sprintf( '<span class="font-num text-xs font-bold text-primary">%s</span>', esc_html( $props['saving'] ) ) : '';
		$notice = isset( $props['notice'] ) && '' !== $props['notice'] ? sprintf( '<p class="m-0 text-xs font-bold text-danger-text" role="status">%s</p>', esc_html( $props['notice'] ) ) : '';
		$later  = '';
		if ( isset( $props['later'], $props['labels']['later'] ) && '' !== $props['later'] ) {
			$later = sprintf(
				'<a href="%1$s" class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-sm font-medium text-fg-2 no-underline hover:bg-surface-3 hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40 max-md:px-1.5 max-md:text-xs" data-um-cart-action="later" data-key="%2$s" data-um-focus="later-%2$s">%3$s%4$s</a>',
				esc_url( $props['later'] ),
				esc_attr( $key ),
				Icon::render( 'history', 'size-4' ),
				esc_html( $props['labels']['later'] )
			);
		}
		if ( $compact ) {
			return Html::trusted(
				sprintf(
					'<li class="%1$s" data-um-cart-line="%2$s"><span class="grid size-18 shrink-0 place-items-center overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain">%3$s</span><div class="grid min-w-0 flex-1 content-start gap-2">%4$s%5$s<div class="flex items-center justify-between gap-2">%6$s<b class="font-num text-sm font-heavy">%7$s</b></div></div></li>',
					esc_attr( Attributes::classes( 'um-cart-line flex gap-3 border-t border-border py-3 first:border-t-0', $props['class'] ?? null ) ),
					esc_attr( $key ),
					$props['image'],
					$title,
					$notice,
					$stepper,
					Html::from( $props['total'] )
				)
			);
		}
		return Html::trusted(
			sprintf(
				'<li class="%1$s" data-um-cart-line="%2$s"><div class="flex w-32 shrink-0 flex-col items-center gap-3 max-md:w-24"><span class="grid size-30 place-items-center overflow-hidden [&_img]:size-full [&_img]:object-contain max-md:size-22">%3$s</span>%4$s</div><div class="flex min-w-0 flex-1 flex-col gap-2">%5$s%6$s%7$s%8$s<div class="mt-auto flex flex-wrap items-center gap-2"><b class="font-num text-xl font-heavy max-md:text-lg">%9$s</b><span class="grow"></span>%10$s</div></div></li>',
				esc_attr( Attributes::classes( 'um-cart-line flex gap-5 border-t border-border py-5 max-md:gap-3', $props['class'] ?? null ) ),
				esc_attr( $key ),
				$props['image'],
				$stepper,
				$title,
				$facts,
				$saving,
				$notice,
				Html::from( $props['total'] ),
				$later
			)
		);
	}

	/**
	 * One fact under the name.
	 *
	 * @param array $fact Fact.
	 * @phpstan-param CartLineFact $fact
	 */
	private static function fact( array $fact ): string {
		$mark = '';
		if ( isset( $fact['color'] ) && '' !== $fact['color'] ) {
			$mark = sprintf( '<span class="inline-block size-3.5 shrink-0 rounded-pill border border-border-strong" style="background:%s"></span>', esc_attr( $fact['color'] ) );
		} elseif ( isset( $fact['icon'] ) ) {
			$mark = Icon::render( $fact['icon'], 'size-4 shrink-0 ' . ( 'primary' === ( $fact['tone'] ?? '' ) ? 'text-primary' : 'text-fg-3' ) );
		}
		return sprintf( '<li class="flex items-center gap-1.5">%1$s<span class="min-w-0">%2$s</span></li>', $mark, esc_html( $fact['text'] ) );
	}

	/**
	 * The quantity stepper, or the fixed quantity of a product sold one at a time.
	 *
	 * @param string     $key      Cart line key.
	 * @param array|null $quantity Quantity and limits.
	 * @param array      $labels   Texts.
	 * @param string     $remove   Address that removes the line.
	 * @phpstan-param CartLineQuantity|null $quantity
	 * @phpstan-param CartLineLabels        $labels
	 */
	private static function stepper( string $key, ?array $quantity, array $labels, string $remove ): string {
		$bin = sprintf(
			'<a href="%1$s" class="%2$s" data-remove data-um-cart-action="remove" data-key="%3$s" aria-label="%4$s" data-um-focus="remove-%3$s">%5$s</a>',
			esc_url( $remove ),
			esc_attr( self::STEP . ' no-underline' ),
			esc_attr( $key ),
			esc_attr( $labels['remove'] ),
			Icon::render( 'trash', 'size-4' )
		);
		if ( null === $quantity ) {
			return sprintf( '<div class="inline-flex h-10 items-stretch overflow-hidden rounded-md border border-border-strong bg-surface"><span class="grid w-10 place-items-center font-num text-md font-bold">%1$s</span>%2$s</div>', esc_html( number_format_i18n( 1 ) ), $bin );
		}
		$min   = max( 1, $quantity['min'] );
		$value = max( $min, $quantity['value'] );
		$max   = $quantity['max'];
		$id    = 'um-qty-' . sanitize_html_class( $key );
		$down  = $value <= $min
			? $bin
			: sprintf(
				'<button type="button" class="%1$s" data-um-cart-step="-1" aria-label="%2$s" aria-controls="%3$s" data-um-focus="down-%4$s">%5$s</button>',
				esc_attr( self::STEP ),
				esc_attr( $labels['decrease'] ),
				esc_attr( $id ),
				esc_attr( $key ),
				Icon::render( 'minus', 'size-4' )
			);
		return sprintf(
			'<div class="inline-flex h-10 shrink-0 items-stretch overflow-hidden rounded-md border border-border-strong bg-surface" data-um-cart-qty="%1$s"><button type="button" class="%2$s" data-um-cart-step="1" aria-label="%3$s" aria-controls="%4$s" data-um-focus="up-%1$s"%5$s>%6$s</button><label for="%4$s" class="sr-only">%7$s</label><input id="%4$s" type="number" name="cart[%1$s][qty]" inputmode="numeric" class="w-10 appearance-none border-0 bg-transparent p-0 text-center font-num text-md font-bold [-moz-appearance:textfield] focus-visible:outline-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" min="%8$d"%9$s step="%10$d" value="%11$d" data-um-focus="qty-%1$s">%12$s</div>',
			esc_attr( $key ),
			esc_attr( self::STEP ),
			esc_attr( $labels['increase'] ),
			esc_attr( $id ),
			$max > 0 && $value >= $max ? ' disabled' : '',
			Icon::render( 'plus', 'size-4' ),
			esc_html( $labels['quantity'] ),
			$min,
			$max > 0 ? sprintf( ' max="%d"', $max ) : '',
			max( 1, $quantity['step'] ),
			$value,
			$down
		);
	}
}
