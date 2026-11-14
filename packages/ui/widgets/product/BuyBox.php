<?php
/**
 * BuyBox component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The buy box of a product page (PDP-05): an offer strip with its
 * countdown, rows for the seller, warranty, delivery and stock, the price,
 * the purchase form, and anything else the store adds under it, such as
 * the free shipping bar.
 *
 * The stock row belongs to the product, so the purchase behaviour can
 * write a variation's stock into it.
 *
 * @phpstan-type BuyBoxRow array{icon: string, text: string|Html, title?: string, note?: string}
 * @phpstan-type BuyBoxProps array{
 *     price: Html,
 *     form: Html,
 *     rows?: list<BuyBoxRow>,
 *     stock?: array{product: int, text: string, tone: 'danger'|'success'|'muted'},
 *     deal?: array{label: string, countdown: Html},
 *     before?: list<Html>,
 *     after?: list<Html>,
 *     sticky?: bool,
 *     id?: string,
 *     class?: string,
 * }
 */
final class BuyBox {

	/**
	 * Renders the box.
	 *
	 * @param array $props Props.
	 * @phpstan-param BuyBoxProps $props
	 */
	public static function render( array $props ): Html {
		$rows = '';
		foreach ( $props['rows'] ?? array() as $row ) {
			$rows .= self::row( $row );
		}
		if ( isset( $props['stock'] ) ) {
			$rows .= sprintf(
				'<li class="flex gap-3 border-b border-border py-2.5 text-sm font-bold data-[tone=danger]:text-danger-text data-[tone=success]:text-success-text data-[tone=muted]:font-regular data-[tone=muted]:text-fg-2" data-um-stock-for="%1$d" data-tone="%2$s"%3$s>%4$s<span data-um-stock-text>%5$s</span></li>',
				$props['stock']['product'],
				esc_attr( $props['stock']['tone'] ),
				'' === $props['stock']['text'] ? ' hidden' : '',
				Icon::render( 'box', 'mt-0.5 size-5' ),
				esc_html( $props['stock']['text'] )
			);
		}
		$deal = isset( $props['deal'] ) ? sprintf(
			'<div class="-mx-4 -mt-4 flex items-center justify-between gap-2 rounded-t-lg bg-linear-to-l from-accent-strong to-accent px-4 py-2 text-sm font-heavy text-on-accent"><span class="inline-flex items-center gap-1">%1$s%2$s</span>%3$s</div>',
			Icon::render( 'fire', 'size-4' ),
			esc_html( $props['deal']['label'] ),
			$props['deal']['countdown']
		) : '';
		return Html::trusted(
			sprintf(
				'<aside%1$s>%2$s%3$s%4$s<div class="grid gap-3">%5$s%6$s</div>%7$s</aside>',
				Attributes::render(
					array(
						'id'    => $props['id'] ?? null,
						'class' => Attributes::classes(
							'um-scope um-buy-box flex flex-col gap-3 rounded-lg border border-border bg-surface p-4',
							( $props['sticky'] ?? true ) ? 'lg:sticky lg:top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+16px)]' : null,
							$props['class'] ?? null
						),
					)
				),
				$deal,
				Html::from( $props['before'] ?? array() ),
				'' === $rows ? '' : sprintf( '<ul class="m-0 list-none p-0">%s</ul>', $rows ),
				$props['price'],
				$props['form'],
				Html::from( $props['after'] ?? array() )
			)
		);
	}

	/**
	 * One row with an icon.
	 *
	 * @param array $row Row.
	 * @phpstan-param BuyBoxRow $row
	 */
	private static function row( array $row ): string {
		$title = isset( $row['title'] ) && '' !== $row['title'] ? sprintf( '<b class="font-bold">%s</b> ', esc_html( $row['title'] ) ) : '';
		$note  = isset( $row['note'] ) && '' !== $row['note'] ? sprintf( '<small class="block text-xs text-fg-3">%s</small>', esc_html( $row['note'] ) ) : '';
		return sprintf(
			'<li class="flex gap-3 border-b border-border py-2.5 text-sm">%1$s<span class="min-w-0">%2$s%3$s%4$s</span></li>',
			Icon::render( $row['icon'], 'mt-0.5 size-5 text-fg-3' ),
			$title,
			Html::from( $row['text'] ),
			$note
		);
	}
}
