<?php
/**
 * Separator component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A thin line between header items, or empty space that pushes the next
 * items to the far end of the row (HDR-02 divider and spacer).
 *
 * @phpstan-type SeparatorProps array{kind?: 'line'|'grow'|'space', size?: int, class?: string}
 */
final class Separator {

	public const KINDS = array( 'line', 'grow', 'space' );

	/**
	 * Renders the separator.
	 *
	 * @param array $props Props.
	 * @phpstan-param SeparatorProps $props
	 */
	public static function render( array $props ): Html {
		$kind = Props::one_of( 'Separator', $props, 'kind', self::KINDS );
		if ( 'grow' === $kind ) {
			return Html::trusted( sprintf( '<span class="%s" aria-hidden="true"></span>', esc_attr( Attributes::classes( 'block min-w-0 flex-1', $props['class'] ?? null ) ) ) );
		}
		if ( 'space' === $kind ) {
			return Html::trusted( sprintf( '<span class="%1$s" style="width:%2$dpx" aria-hidden="true"></span>', esc_attr( Attributes::classes( 'block shrink-0', $props['class'] ?? null ) ), max( 0, min( 400, $props['size'] ?? 24 ) ) ) );
		}
		return Html::trusted( sprintf( '<span class="%s" aria-hidden="true"></span>', esc_attr( Attributes::classes( 'mx-2 block h-6 w-px shrink-0 bg-border', $props['class'] ?? null ) ) ) );
	}
}
