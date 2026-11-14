<?php
/**
 * StickyBuy component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The buy button that stays in reach while the shopper reads (PDP-20).
 *
 * - `bar`: fixed to the bottom of phones and tablets, above the bottom
 *   navigation, with the price and the add button.
 * - `card`: a small box beside the product sections on desktop, with the
 *   picture, the name, the chosen variation and the price.
 *
 * The button submits the product's purchase form through the `form`
 * attribute, so it works without JavaScript and adds exactly what the main
 * form would. The behaviour shows the bar only once the main buy box has
 * scrolled away.
 *
 * @phpstan-type StickyBuyProps array{
 *     form: string,
 *     product: int,
 *     price: Html,
 *     label: string,
 *     variant?: 'bar'|'card',
 *     title?: string,
 *     image?: Html,
 *     summary?: string,
 *     summary_empty?: string,
 *     disabled?: bool,
 *     watch?: string,
 *     class?: string,
 * }
 */
final class StickyBuy {

	/**
	 * Renders the bar or the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param StickyBuyProps $props
	 */
	public static function render( array $props ): Html {
		$form    = Props::id( 'StickyBuy', $props, 'form' );
		$variant = Props::one_of( 'StickyBuy', $props, 'variant', array( 'bar', 'card' ) );
		$button  = sprintf(
			'<button type="submit" form="%1$s" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-md border-0 bg-primary px-5 text-md font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40 disabled:cursor-not-allowed disabled:opacity-45 %2$s"%3$s>%4$s</button>',
			esc_attr( $form ),
			'bar' === $variant ? 'min-w-0 max-w-55 flex-1' : 'w-full',
			( $props['disabled'] ?? false ) ? ' disabled' : '',
			esc_html( $props['label'] )
		);
		if ( 'bar' === $variant ) {
			return Html::trusted(
				sprintf(
					'<div%1$s>%2$s<div class="min-w-0">%3$s</div></div>',
					Attributes::render(
						array(
							'class'             => Attributes::classes( 'um-scope um-sticky-buy fixed inset-x-0 bottom-0 z-(--um-z-sticky) flex items-center justify-between gap-3 border-t border-border bg-surface px-(--um-gutter) py-2.5 pb-[calc(10px+env(safe-area-inset-bottom))] shadow-[0_-6px_16px_rgb(21_22_28/6%)] transition-transform duration-200 data-away:translate-y-full lg:hidden', $props['class'] ?? null ),
							'data-um-component' => 'sticky-buy',
							'data-watch'        => $props['watch'] ?? null,
						)
					),
					$button,
					$props['price']
				)
			);
		}
		$summary = '';
		if ( isset( $props['summary'] ) || isset( $props['summary_empty'] ) ) {
			$summary = sprintf(
				'<p class="m-0 text-xs text-fg-3" data-um-summary-for="%1$d" data-empty="%2$s">%3$s</p>',
				$props['product'],
				esc_attr( $props['summary_empty'] ?? '' ),
				esc_html( '' !== ( $props['summary'] ?? '' ) ? ( $props['summary'] ?? '' ) : ( $props['summary_empty'] ?? '' ) )
			);
		}
		return Html::trusted(
			sprintf(
				'<aside class="%1$s"><div class="flex items-center gap-3">%2$s<p class="m-0 line-clamp-2 text-sm">%3$s</p></div>%4$s%5$s%6$s</aside>',
				esc_attr( Attributes::classes( 'um-scope um-mini-buy sticky top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+64px)] grid gap-3 rounded-lg border border-border bg-surface p-4', $props['class'] ?? null ) ),
				isset( $props['image'] ) ? sprintf( '<span class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-md bg-surface-2 [&_img]:size-full [&_img]:object-contain">%s</span>', $props['image'] ) : '',
				esc_html( $props['title'] ?? '' ),
				$summary,
				$props['price'],
				$button
			)
		);
	}
}
