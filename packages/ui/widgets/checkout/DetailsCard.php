<?php
/**
 * DetailsCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A titled card of label and value lines, such as the customer's details
 * on the thank you page (BLD-06): name, mobile, address, buyer details.
 * `children` adds anything else under the lines, such as a gateway's
 * payment instructions.
 *
 * @phpstan-type DetailLine array{label: string, value: string, ltr?: bool}
 * @phpstan-type DetailsCardProps array{
 *     title: string,
 *     lines?: list<DetailLine>,
 *     icon?: string,
 *     children?: Html,
 *     class?: string,
 * }
 */
final class DetailsCard {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param DetailsCardProps $props
	 */
	public static function render( array $props ): Html {
		$lines = '';
		foreach ( $props['lines'] ?? array() as $line ) {
			if ( '' === trim( $line['value'] ) ) {
				continue;
			}
			$lines .= sprintf(
				'<div class="grid gap-0.5"><dt class="text-xs text-fg-3">%1$s</dt><dd class="m-0 text-sm [&[dir=ltr]]:text-end"%2$s>%3$s</dd></div>',
				esc_html( $line['label'] ),
				( $line['ltr'] ?? false ) ? ' dir="ltr"' : '',
				esc_html( $line['value'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<section class="%1$s"><h2 class="m-0 flex items-center gap-2 text-md font-bold">%2$s%3$s</h2>%4$s%5$s</section>',
				esc_attr( Attributes::classes( 'um-scope um-details-card grid content-start gap-3 rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ) ),
				isset( $props['icon'] ) ? Icon::render( $props['icon'], 'size-5 text-fg-3' ) : '',
				esc_html( $props['title'] ),
				'' === $lines ? '' : sprintf( '<dl class="m-0 grid gap-3">%s</dl>', $lines ),
				$props['children'] ?? ''
			)
		);
	}
}
