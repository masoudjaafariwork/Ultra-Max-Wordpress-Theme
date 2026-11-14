<?php
/**
 * AddressCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One saved address (ACC-06): its kind or name, the lines of the address,
 * and buttons such as "Edit" and "Remove"; `empty` shows instead of the
 * lines when none is saved yet.
 *
 * @phpstan-type AddressCardProps array{
 *     title: string,
 *     lines: list<string>,
 *     actions?: list<Html>,
 *     empty: string,
 *     class?: string,
 * }
 */
final class AddressCard {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param AddressCardProps $props
	 */
	public static function render( array $props ): Html {
		$lines = '';
		foreach ( $props['lines'] as $index => $line ) {
			$lines .= sprintf( '<span class="%1$s">%2$s</span>', 0 === $index ? 'font-bold text-fg' : '', esc_html( $line ) );
		}
		$actions = implode( '', array_map( 'strval', $props['actions'] ?? array() ) );
		return Html::trusted(
			sprintf(
				'<article class="%1$s"><div class="flex items-center gap-2">%2$s<h3 class="m-0 flex-1 text-md font-bold">%3$s</h3></div><address class="grid gap-1 text-sm leading-body text-fg-2 not-italic">%4$s</address>%5$s</article>',
				esc_attr( Attributes::classes( 'um-address-card grid content-start gap-3 rounded-lg border border-border bg-surface p-4', $props['class'] ?? null ) ),
				Icon::render( 'pin', 'size-5 text-primary' ),
				esc_html( $props['title'] ),
				'' === $lines ? sprintf( '<span class="text-fg-3">%s</span>', esc_html( $props['empty'] ) ) : $lines,
				'' === $actions ? '' : sprintf( '<div class="flex flex-wrap items-center gap-2">%s</div>', $actions )
			)
		);
	}
}
