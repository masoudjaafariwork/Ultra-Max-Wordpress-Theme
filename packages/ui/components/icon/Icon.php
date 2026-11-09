<?php
/**
 * Icon component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Inline SVG icon drawn for this project. Decorative: hidden from screen
 * readers, so the surrounding control must carry the accessible name.
 */
final class Icon {

	/**
	 * Path data of every icon, on a 24 by 24 grid, stroked.
	 */
	private const PATHS = array(
		'close'        => 'M6 6l12 12M18 6L6 18',
		'chevron-down' => 'M6 9l6 6 6-6',
		'menu'         => 'M4 7h16M4 12h16M4 17h16',
	);

	/**
	 * Renders an icon.
	 *
	 * @param 'close'|'chevron-down'|'menu' $name    Icon name.
	 * @param string                        $classes Extra classes for size.
	 */
	public static function render( string $name, string $classes = 'size-5' ): Html {
		$path = self::PATHS[ $name ] ?? self::PATHS['close'];
		return Html::trusted(
			sprintf(
				'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="%s"/></svg>',
				esc_attr( Attributes::classes( 'shrink-0', $classes ) ),
				esc_attr( $path )
			)
		);
	}
}
