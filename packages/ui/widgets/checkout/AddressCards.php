<?php
/**
 * AddressCards component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The shopper's saved addresses (design/checkout.html, CHK-02): one card
 * per address with the recipient, phone and postal code. Pressing a card
 * fills the checkout's address with it; `new` is the button that empties
 * the address fields for a new one.
 *
 * Each card carries its address as JSON in `data-um-address`, keyed like
 * WooCommerce's fields (first_name, state, city, address_1, postcode…).
 *
 * @phpstan-type SavedAddress array{id: string, title: string, text: string, fields: array<string, string>, selected?: bool}
 * @phpstan-type AddressCardsProps array{
 *     addresses: list<SavedAddress>,
 *     label: string,
 *     new?: string,
 *     selected_label?: string,
 *     class?: string,
 * }
 */
final class AddressCards {

	/**
	 * Renders the cards.
	 *
	 * @param array $props Props.
	 * @phpstan-param AddressCardsProps $props
	 */
	public static function render( array $props ): Html {
		$cards = '';
		foreach ( $props['addresses'] as $address ) {
			$selected = $address['selected'] ?? false;
			$cards   .= sprintf(
				'<li><button type="button" class="group flex w-full cursor-pointer items-start gap-3 rounded-md border border-border bg-surface p-4 text-start text-fg hover:border-fg-3 focus-visible:outline-3 focus-visible:outline-primary/40 aria-pressed:border-primary aria-pressed:bg-primary-soft" data-um-address="%1$s" data-id="%2$s" aria-pressed="%3$s">%4$s<span class="grid min-w-0 flex-1 gap-1"><b class="text-sm leading-[1.8]">%5$s</b><span class="text-sm text-fg-2">%6$s</span></span>%7$s</button></li>',
				esc_attr( (string) wp_json_encode( $address['fields'] ) ),
				esc_attr( $address['id'] ),
				$selected ? 'true' : 'false',
				Icon::render( 'pin', 'mt-0.5 size-6 shrink-0 text-fg-3 group-aria-pressed:text-primary' ),
				esc_html( $address['title'] ),
				esc_html( $address['text'] ),
				isset( $props['selected_label'] ) ? sprintf( '<span class="hidden shrink-0 rounded-pill bg-primary px-2 py-0.5 text-2xs font-bold text-on-primary group-aria-pressed:inline">%s</span>', esc_html( $props['selected_label'] ) ) : ''
			);
		}
		$new = isset( $props['new'] ) ? sprintf(
			'<li><button type="button" class="flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed border-border-strong bg-transparent px-4 text-sm font-bold text-primary hover:bg-primary-soft focus-visible:outline-3 focus-visible:outline-primary/40" data-um-address-new>%1$s%2$s</button></li>',
			Icon::render( 'plus', 'size-5' ),
			esc_html( $props['new'] )
		) : '';
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s" data-um-address-cards>%3$s%4$s</ul>',
				esc_attr( Attributes::classes( 'um-scope um-address-cards m-0 grid list-none gap-2 p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$cards,
				$new
			)
		);
	}
}
