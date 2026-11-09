<?php
/**
 * Entry point of the Ultra Max theme.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

/**
 * Registers theme supports, menus, widget areas and every theme part.
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
		Options::register();
		Dates::register();
		Tokens::register();
		Assets::register();

		add_action( 'after_setup_theme', array( $this, 'setup' ) );
		add_action( 'widgets_init', array( $this, 'widgets' ) );
		add_action( 'wp', array( $this, 'front' ) );
		add_filter( 'body_class', array( Template::class, 'body_class' ) );
		add_filter( 'get_search_form', array( $this, 'search_form' ), 10, 0 );
		add_filter( 'excerpt_length', static fn (): int => 28 );
		add_filter( 'excerpt_more', static fn (): string => '…' );

		// WooCommerce pages use our frame and container (CORE-01: no template overrides).
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		add_action( 'woocommerce_before_main_content', array( $this, 'woocommerce_open' ), 10 );
		add_action( 'woocommerce_after_main_content', array( $this, 'woocommerce_close' ), 10 );
	}

	/**
	 * Declares theme supports and menu locations.
	 */
	public function setup(): void {
		load_theme_textdomain( 'ultra-max', get_template_directory() . '/languages' );

		$GLOBALS['content_width'] = 860;

		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 96,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary' => __( 'Primary menu', 'ultra-max' ),
				'footer'  => __( 'Footer menu', 'ultra-max' ),
			)
		);
	}

	/**
	 * Registers the sidebar and four footer columns.
	 */
	public function widgets(): void {
		register_sidebar(
			array_merge(
				array(
					'id'          => 'sidebar',
					'name'        => __( 'Sidebar', 'ultra-max' ),
					'description' => __( 'Shown next to posts, pages and archives when the layout settings allow a sidebar.', 'ultra-max' ),
				),
				Template::widget_args()
			)
		);
		for ( $i = 1; $i <= 4; $i++ ) {
			register_sidebar(
				array_merge(
					array(
						'id'   => 'footer-' . $i,
						/* translators: %d: footer column number. */
						'name' => sprintf( __( 'Footer column %d', 'ultra-max' ), $i ),
					),
					Template::footer_widget_args()
				)
			);
		}
	}

	/**
	 * Hooks the storefront parts once the request is known.
	 */
	public function front(): void {
		if ( is_admin() ) {
			return;
		}
		Maintenance::register();
		Extras::register();
	}

	/**
	 * Uses our search form wherever WordPress asks for one.
	 */
	public function search_form(): string {
		return (string) Template::search_form( 'um-search-' . wp_unique_id(), 'outline' );
	}

	/**
	 * Opens WooCommerce content inside our container.
	 */
	public function woocommerce_open(): void {
		Template::main_open();
		echo '<div class="mx-auto w-full max-w-(--um-container) px-(--um-gutter) py-6">';
	}

	/**
	 * Closes WooCommerce content.
	 */
	public function woocommerce_close(): void {
		echo '</div>';
		Template::main_close();
	}
}
