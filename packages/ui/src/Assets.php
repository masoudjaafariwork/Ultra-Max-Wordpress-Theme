<?php
/**
 * Shared storefront and admin assets of the component library.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

use UltraMax\Foundation\Assets as Registrar;

/**
 * Registers the library's CSS and JS under shared handles.
 *
 * Every product ships the same build. The first product to register wins,
 * so a page that uses the theme and three plugins loads the library once.
 */
final class Assets {

	public const STOREFRONT = 'ultra-max-ui';
	public const ADMIN      = 'ultra-max-ui-admin';

	/**
	 * Registers the handles from one product's build folder.
	 *
	 * @param string $build_dir Absolute path of the product's assets/build folder.
	 * @param string $build_url URL of the same folder.
	 */
	public static function register( string $build_dir, string $build_url ): void {
		$registrar = new Registrar( $build_dir, $build_url );
		if ( ! wp_style_is( self::STOREFRONT, 'registered' ) ) {
			$registrar->register_style( self::STOREFRONT, 'ui/storefront' );
			$registrar->register_script( self::STOREFRONT, 'ui/storefront' );
		}
		if ( ! wp_style_is( self::ADMIN, 'registered' ) ) {
			$registrar->register_style( self::ADMIN, 'ui/admin' );
		}
	}

	/**
	 * Enqueues the storefront library.
	 */
	public static function enqueue(): void {
		wp_enqueue_style( self::STOREFRONT );
		wp_enqueue_script( self::STOREFRONT );
	}

	/**
	 * Enqueues the admin stylesheet.
	 */
	public static function enqueue_admin(): void {
		wp_enqueue_style( self::ADMIN );
	}
}