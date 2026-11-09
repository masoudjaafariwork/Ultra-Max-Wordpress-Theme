<?php
/**
 * Entry point of the Ultra Max theme.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\UI\Assets as UiAssets;

/**
 * Registers theme supports, menus and assets.
 */
final class Theme {

	/**
	 * The single instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns the single instance.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Registers the theme's hooks.
	 */
	public function boot(): void {
		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Declares theme supports and menu locations.
	 */
	public function setup(): void {
		load_theme_textdomain( 'ultra-max', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'woocommerce' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary menu', 'ultra-max' ),
				'footer'  => __( 'Footer menu', 'ultra-max' ),
			)
		);
	}

	/**
	 * Enqueues the component library and the theme stylesheet.
	 */
	public function enqueue(): void {
		$build = get_template_directory() . '/assets/build';
		$url   = get_template_directory_uri() . '/assets/build';

		UiAssets::register( $build, $url );
		UiAssets::enqueue();

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
