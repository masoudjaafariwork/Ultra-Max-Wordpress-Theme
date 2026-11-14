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
 *
 * Switchable modules (PERF-02) have their own small stylesheet and script,
 * registered as `ultra-max-ui-<module>` and enqueued only when the module
 * is on.
 */
final class Assets {

	public const STOREFRONT = 'ultra-max-ui';
	public const ADMIN      = 'ultra-max-ui-admin';
	public const MODULES    = array( 'notice-bar', 'contact-buttons', 'back-to-top', 'smart-header', 'bottom-nav' );

	/**
	 * Widget assets (BLD-12): one stylesheet and script per folder of
	 * packages/ui/widgets, loaded only by pages that show the widget.
	 */
	public const WIDGETS = array(
		'header',
		'category-menu',
		'carousel',
		'hero-slider',
		'stories',
		'product-card',
		'countdown',
		'deals',
		'banners',
		'category-circles',
		'quick-links',
		'ranked-list',
		'brand-tiles',
		'post-tiles',
		'trust-marks',
		'discount-picks',
		'icon-box',
		'counter',
		'pricing-table',
		'testimonials',
		'team',
		'timeline',
		'gallery',
		'video',
		'hotspots',
		'image-compare',
		'animated-text',
		'data-table',
		'icon-list',
		'social',
		'forms',
		'footer',
		'shop',
		'search',
		'product',
		'reviews',
	);

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
			foreach ( self::MODULES as $module ) {
				$registrar->register_style( self::STOREFRONT . '-' . $module, 'ui/modules/' . $module, array( self::STOREFRONT ) );
				$registrar->register_script( self::STOREFRONT . '-' . $module, 'ui/modules/' . $module, array( self::STOREFRONT ) );
			}
			foreach ( self::WIDGETS as $widget ) {
				$registrar->register_style( self::widget_handle( $widget ), 'ui/widgets/' . $widget, array( self::STOREFRONT ) );
				$registrar->register_script( self::widget_handle( $widget ), 'ui/widgets/' . $widget, array( self::STOREFRONT ) );
			}
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
	 * Enqueues one switchable module's stylesheet and script.
	 *
	 * @param string $module Module name, one of MODULES.
	 */
	public static function enqueue_module( string $module ): void {
		if ( in_array( $module, self::MODULES, true ) ) {
			wp_enqueue_style( self::STOREFRONT . '-' . $module );
			wp_enqueue_script( self::STOREFRONT . '-' . $module );
		}
	}

	/**
	 * Handle of a widget asset.
	 *
	 * @param string $widget Widget asset name, one of WIDGETS.
	 * @return non-empty-string
	 */
	public static function widget_handle( string $widget ): string {
		return self::STOREFRONT . '-w-' . $widget;
	}

	/**
	 * Enqueues one widget asset's stylesheet and script.
	 *
	 * @param string $widget Widget asset name, one of WIDGETS.
	 */
	public static function enqueue_widget( string $widget ): void {
		if ( in_array( $widget, self::WIDGETS, true ) ) {
			wp_enqueue_style( self::widget_handle( $widget ) );
			wp_enqueue_script( self::widget_handle( $widget ) );
		}
	}

	/**
	 * File path of a registered stylesheet, for inlining it.
	 *
	 * @param string $build_dir Absolute path of a product's assets/build folder.
	 * @param string $entry     Entry, such as "ui/storefront".
	 */
	public static function css_file( string $build_dir, string $entry ): string {
		return $build_dir . '/' . $entry . ( is_rtl() ? '' : '-ltr' ) . '.css';
	}

	/**
	 * Enqueues the admin stylesheet.
	 */
	public static function enqueue_admin(): void {
		wp_enqueue_style( self::ADMIN );
	}
}
