<?php
/**
 * Entry point of Ultra Max Core.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core;

use UltraMax\Core\Lib\Foundation\Requirements;
use UltraMax\Core\Lib\Foundation\WooCompat;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Admin\Admin;
use UltraMax\Core\Admin\Products;
use UltraMax\Core\Admin\Rest;
use UltraMax\Core\Admin\Wizard;
use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Elementor\Integration;
use UltraMax\Core\Product\Product;
use UltraMax\Core\Saved\Saved;
use UltraMax\Core\Search\Search;
use UltraMax\Core\Store\Pages;
use UltraMax\Core\Shop\Shop;
use UltraMax\Core\Widgets\Widgets;

/**
 * Boots the plugin once all plugins are loaded.
 */
final class Plugin {

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
	 * Registers the plugin's hooks.
	 */
	public function boot(): void {
		WooCompat::declare( ULTRA_MAX_CORE_FILE );
		Options::register();
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Runs after every plugin has loaded.
	 */
	public function init(): void {
		$requirements = new Requirements( 'Ultra Max Core', '8.1', '6.9' );
		if ( ! $requirements->met() ) {
			$requirements->notify();
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'drop_off_assets' ), 6 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_filter( 'upload_mimes', array( $this, 'font_mimes' ) );

		Rest::register();
		Products::register();
		CustomCode::register();
		Shop::register();
		Product::register();
		Pages::register();
		Saved::register();
		Search::register();
		Builder::register();
		Widgets::register();
		Integration::register();
		if ( is_admin() ) {
			Admin::register();
			Wizard::register();
			Duplicate::register();
		}

		/**
		 * Fires when Ultra Max Core has booted. Modules hook in here.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'ultra_max_core_loaded', $this );
	}

	/**
	 * Loads the translations shipped in the plugin's languages folder.
	 *
	 * Plugin Check discourages this for WordPress.org plugins, which get
	 * language packs. Marketplace plugins ship their own translations, and
	 * WordPress only finds them in the plugin folder when told where.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'ultra-max-core', false, dirname( plugin_basename( ULTRA_MAX_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * Registers the shared component library from this plugin's build.
	 */
	public function register_assets(): void {
		Assets::register( plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_CORE_FILE ) );
	}

	/**
	 * A module that is off loads no file (ADM-07), even when a saved Elementor
	 * design still lists one of its files from the time it was on.
	 */
	public function drop_off_assets(): void {
		$off = array(
			'shop'    => ! Shop::enabled(),
			'search'  => ! Search::enabled(),
			// The quick view window shows the product page's pieces (SAVE-04).
			'product' => ! Product::enabled() && ! Saved::enabled( Saved::QUICK ),
			'reviews' => ! Product::enabled(),
			'saved'   => ! Saved::lists() && ! Saved::enabled( Saved::QUICK ),
		);
		foreach ( $off as $asset => $drop ) {
			if ( $drop ) {
				wp_deregister_script( Assets::widget_handle( $asset ) );
				wp_deregister_style( Assets::widget_handle( $asset ) );
			}
		}
	}

	/**
	 * Lets administrators upload WOFF2 fonts for the custom font setting (CORE-05).
	 *
	 * @param mixed $mimes Allowed types.
	 * @return mixed
	 */
	public function font_mimes( mixed $mimes ): mixed {
		if ( is_array( $mimes ) && current_user_can( 'edit_theme_options' ) ) {
			$mimes['woff2'] = 'font/woff2';
		}
		return $mimes;
	}

	/**
	 * Version of the plugin.
	 */
	public function version(): string {
		return ULTRA_MAX_CORE_VERSION;
	}
}
