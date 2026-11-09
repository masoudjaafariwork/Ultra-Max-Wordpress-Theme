<?php
/**
 * Device visibility classes (CORE-13).
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Turns "show on desktop, tablet, mobile" into classes.
 *
 * Breakpoints match the design: mobile below 768px, tablet from 768px to
 * 1023px, desktop from 1024px.
 */
final class Visibility {

	/**
	 * Classes that hide an element on the devices switched off.
	 *
	 * @param array{desktop?: bool, tablet?: bool, mobile?: bool} $devices Devices to show on.
	 */
	public static function classes( array $devices ): string {
		return Attributes::classes(
			( $devices['mobile'] ?? true ) ? null : 'max-md:hidden',
			( $devices['tablet'] ?? true ) ? null : 'md:max-lg:hidden',
			( $devices['desktop'] ?? true ) ? null : 'lg:hidden'
		);
	}

	/**
	 * Whether any device shows the element.
	 *
	 * @param array{desktop?: bool, tablet?: bool, mobile?: bool} $devices Devices to show on.
	 */
	public static function any( array $devices ): bool {
		return ( $devices['desktop'] ?? true ) || ( $devices['tablet'] ?? true ) || ( $devices['mobile'] ?? true );
	}
}
