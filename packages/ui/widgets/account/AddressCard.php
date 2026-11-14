<?php
/**
 * AddressCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One saved address (ACC-06): its kind, the lines of the address, and a
 * button to change it; `empty` shows instead when none is saved yet.
 *
 * @phpstan-type AddressCardProps array{
 *     title: string,
 *     lines: list<string>,
 *     edit: array{label: string, href: string},
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
		return Html::trusted(
			sprintf(
				'<article class="%1$s"><div class="flex items-center gap-2">%2$s<h3 class="m-0 flex-1 text-md font-bold">%3$s</h3></div><address class="grid gap-1 text-sm leading-body text-fg-2 not-italic">%4$s</address><a href="%5$s" class="inline-flex h-9 items-center justify-center gap-1.5 self-start rounded-sm border border-border-strong bg-surface px-3 text-sm font-medium text-fg no-underline hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40">%6$s%7$s</a></article>',
				esc_attr( Attributes::classes( 'um-address-card grid content-start gap-3 rounded-lg border border-border bg-surface p-4', $props['class'] ?? null ) ),
				Icon::render( 'pin', 'size-5 text-primary' ),
				esc_html( $props['title'] ),
				'' === $lines ? sprintf( '<span class="text-fg-3">%s</span>', esc_html( $props['empty'] ) ) : $lines,
				esc_url( $props['edit']['href'] ),
				Icon::render( 'edit', 'size-4' ),
				esc_html( $props['edit']['label'] )
			)
		);
	}
}
