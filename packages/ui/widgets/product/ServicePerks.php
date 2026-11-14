<?php
/**
 * ServicePerks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The store's promises under a product's buy box, such as express
 * delivery, cash on delivery and seven day returns.
 *
 * @phpstan-type ServicePerksProps array{
 *     label: string,
 *     items: list<array{icon: string, label: string}>,
 *     class?: string,
 * }
 */
final class ServicePerks {

	/**
	 * Renders the row, or nothing without items.
	 *
	 * @param array $props Props.
	 * @phpstan-param ServicePerksProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li class="flex items-center justify-center gap-2 text-xs text-fg-2 max-md:justify-start">%1$s<span>%2$s</span></li>',
				Icon::render( $item['icon'], 'size-6 text-primary' ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-service-perks m-0 grid list-none grid-cols-2 gap-x-2 gap-y-3 border-y border-border px-0 py-4 md:grid-cols-3 lg:grid-cols-5', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
