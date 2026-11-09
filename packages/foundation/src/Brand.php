<?php
/**
 * Product display name and logo, read from one place.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * The product's display name and logo mark.
 *
 * The internal code name `ultra-max` never changes. The name buyers see
 * and the logo come only from here, so the brand decision before phase 11
 * changes one file (roadmap section 12).
 */
final class Brand {

	/**
	 * Display name, translated.
	 */
	public static function name(): string {
		/**
		 * Filters the product display name.
		 *
		 * @param string $name Display name.
		 */
		$name = apply_filters( 'ultra_max_brand_name', __( 'Ultra Max', 'ultra-max-foundation' ) );
		return is_string( $name ) ? $name : 'Ultra Max';
	}

	/**
	 * Logo mark as inline SVG, decorative.
	 *
	 * @param int $size Width and height in pixels.
	 */
	public static function mark( int $size = 40 ): string {
		$id = 'um-mark-' . wp_unique_id();
		return sprintf(
			'<svg width="%1$d" height="%1$d" viewBox="0 0 40 40" aria-hidden="true" focusable="false"><defs><linearGradient id="%2$s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--um-brand-500, #6158e6)"/><stop offset="1" stop-color="var(--um-brand-800, #2e2783)"/></linearGradient></defs><rect width="40" height="40" rx="12" fill="url(#%2$s)"/><path d="M12 13v8a8 8 0 0 0 16 0v-8" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/><circle cx="28" cy="29" r="3.2" fill="var(--um-accent-500, #ff9a1f)"/></svg>',
			$size,
			esc_attr( $id )
		);
	}

	/**
	 * One-colour logo mark for the admin menu icon. The admin colours it.
	 */
	public static function menu_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path fill="black" d="M12 0h16a12 12 0 0 1 12 12v16a12 12 0 0 1-12 12H12A12 12 0 0 1 0 28V12A12 12 0 0 1 12 0zm-2 13v8a10 10 0 0 0 20 0v-8a2 2 0 0 0-4 0v8a6 6 0 0 1-12 0v-8a2 2 0 0 0-4 0z"/></svg>';
	}
}
