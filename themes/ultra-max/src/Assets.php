<?php
/**
 * The theme's styles, scripts and loading performance (PERF-02 to PERF-05).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\Assets as UiAssets;

/**
 * Loads the component library, the switchable modules that are on, and
 * applies the loading settings:
 *
 * - Critical CSS (PERF-02): the storefront stylesheet is small, so it is
 *   printed into the page instead of a render-blocking file request.
 *   Module styles stay separate files and load only when a module is on.
 * - Lazy loading and the main image (PERF-03).
 * - Our storefront scripts never depend on jQuery (PERF-04); a unit test
 *   checks every built script's dependencies.
 */
final class Assets {

	/**
	 * Largest stylesheet, in bytes, that is printed inline.
	 */
	public const INLINE_LIMIT = 60000;

	/**
	 * Modules to load on this page.
	 *
	 * @var list<string>
	 */
	private static array $modules = array();

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 10 );
		add_filter( 'style_loader_tag', array( self::class, 'inline_critical' ), 10, 2 );
		add_filter( 'wp_lazy_loading_enabled', array( self::class, 'lazy' ), 10, 1 );
		add_action( 'wp_head', array( self::class, 'preload_image' ), 3 );
		add_filter( 'woocommerce_enqueue_styles', array( self::class, 'woocommerce_styles' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'woocommerce_scripts' ), 99 );
	}

	/**
	 * Asks for a module's assets on this page.
	 *
	 * @param string $module Module name.
	 */
	public static function module( string $module ): void {
		self::$modules[] = $module;
	}

	/**
	 * The theme's build folder.
	 */
	public static function build_dir(): string {
		return get_template_directory() . '/assets/build';
	}

	/**
	 * Enqueues the library and the modules that are on.
	 */
	public static function enqueue(): void {
		UiAssets::register( self::build_dir(), get_template_directory_uri() . '/assets/build' );
		// The theme alone decides whether the storefront stylesheet is printed inline (the "Inline critical CSS" setting), so WordPress's own size-based inlining leaves it alone.
		wp_style_add_data( UiAssets::STOREFRONT, 'path', false );
		UiAssets::enqueue();
		foreach ( array_unique( self::$modules ) as $module ) {
			UiAssets::enqueue_module( $module );
		}
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Prints the storefront stylesheet inline instead of linking it.
	 *
	 * The handle may have been registered by a plugin's copy of the library;
	 * the file is the same build, so the theme's copy is read.
	 *
	 * @param mixed $tag    Link tag.
	 * @param mixed $handle Style handle.
	 * @return mixed
	 */
	public static function inline_critical( mixed $tag, mixed $handle ): mixed {
		if ( UiAssets::STOREFRONT !== $handle || ! is_string( $tag ) || ! Store::bool( 'perf_critical_css' ) || is_customize_preview() ) {
			return $tag;
		}
		$file = UiAssets::css_file( self::build_dir(), 'ui/storefront' );
		$size = is_readable( $file ) ? filesize( $file ) : false;
		if ( false === $size || $size > self::INLINE_LIMIT ) {
			return $tag;
		}
		$css = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build file.
		// Relative font URLs point next to the build folder; make them absolute.
		$base = get_template_directory_uri() . '/assets/build/';
		$css  = str_replace( array( 'url(../fonts/', 'url("../fonts/' ), array( 'url(' . $base . 'fonts/', 'url("' . $base . 'fonts/' ), $css );
		return sprintf( "<style id=\"%s-css\">%s</style>\n", esc_attr( UiAssets::STOREFRONT ), wp_strip_all_tags( $css ) );
	}

	/**
	 * WooCommerce's own stylesheets only where WooCommerce shows its parts:
	 * shop pages, the cart, checkout, the account, and pages whose content
	 * holds a WooCommerce shortcode or block. Elsewhere, such as a home page
	 * made of our widgets, they would only delay the first paint (PERF-01).
	 *
	 * @param mixed $styles Styles by handle.
	 * @return mixed
	 */
	public static function woocommerce_styles( mixed $styles ): mixed {
		if ( ! is_array( $styles ) || is_admin() ) {
			return $styles;
		}
		return self::shop_page() ? $styles : array();
	}

	/**
	 * WooCommerce's general and add-to-cart scripts load in the head of
	 * every page. Pages without WooCommerce parts do not need them: our
	 * product cards add to the cart with their own small script.
	 */
	public static function woocommerce_scripts(): void {
		if ( ! self::shop_page() ) {
			wp_dequeue_script( 'wc-add-to-cart' );
			wp_dequeue_script( 'woocommerce' );
		}
	}

	/**
	 * Whether the page shows WooCommerce's own parts.
	 */
	public static function shop_page(): bool {
		if ( ! is_singular() ) {
			return true;
		}
		$shop = ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );
		if ( ! $shop ) {
			$post = get_post();
			$shop = $post instanceof \WP_Post && ( str_contains( $post->post_content, '[woocommerce_' ) || str_contains( $post->post_content, '[product' ) || str_contains( $post->post_content, '<!-- wp:woocommerce/' ) );
		}
		return $shop;
	}

	/**
	 * Turns WordPress's lazy loading off when the setting is off (PERF-03).
	 *
	 * @param mixed $enabled Whether lazy loading is on.
	 * @return mixed
	 */
	public static function lazy( mixed $enabled ): mixed {
		return Store::bool( 'perf_lazy' ) ? $enabled : false;
	}

	/**
	 * Preloads the image most likely to be the largest paint: the featured
	 * image of a post or page, or of the first post of a list (PERF-03).
	 */
	public static function preload_image(): void {
		if ( ! Store::bool( 'perf_preload_lcp' ) ) {
			return;
		}
		$image_id = 0;
		$size     = 'large';
		if ( is_singular() ) {
			$image_id = (int) get_post_thumbnail_id( (int) get_queried_object_id() );
		} elseif ( is_home() || is_archive() ) {
			global $wp_query;
			$first = $wp_query instanceof \WP_Query ? ( $wp_query->posts[0] ?? null ) : null;
			if ( $first instanceof \WP_Post ) {
				$image_id = (int) get_post_thumbnail_id( $first );
				$size     = 'medium_large';
			}
		}
		if ( $image_id <= 0 ) {
			return;
		}
		$src = wp_get_attachment_image_src( $image_id, $size );
		if ( false === $src ) {
			return;
		}
		$srcset = wp_get_attachment_image_srcset( $image_id, $size );
		printf(
			'<link rel="preload" as="image" href="%1$s"%2$s fetchpriority="high">' . "\n",
			esc_url( $src[0] ),
			is_string( $srcset ) ? sprintf( ' imagesrcset="%s" imagesizes="%s"', esc_attr( $srcset ), esc_attr( is_singular() ? '(max-width: 1336px) 100vw, 1336px' : '(max-width: 767px) 100vw, 420px' ) ) : ''
		);
	}
}
