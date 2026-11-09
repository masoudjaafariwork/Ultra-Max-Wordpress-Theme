<?php
/**
 * Entry point of Ultra Max SEO.
 *
 * @package UltraMax\Seo
 */

declare(strict_types=1);

namespace UltraMax\Seo;

use UltraMax\Seo\Lib\Foundation\Requirements;
use UltraMax\Seo\Lib\Foundation\WooCompat;
use UltraMax\Seo\Lib\UI\Assets;

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
		WooCompat::declare( ULTRA_MAX_SEO_FILE );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Runs after every plugin has loaded.
	 */
	public function init(): void {
		$requirements = new Requirements( 'Ultra Max SEO', '8.1', '6.9' );
		if ( ! $requirements->met() ) {
			$requirements->notify();
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		/**
		 * Fires when Ultra Max SEO has booted. Modules hook in here.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'ultra_max_seo_loaded', $this );
	}

	/**
	 * Registers the shared component library from this plugin's build.
	 */
	public function register_assets(): void {
		Assets::register( plugin_dir_path( ULTRA_MAX_SEO_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_SEO_FILE ) );
	}

	/**
	 * Version of the plugin.
	 */
	public function version(): string {
		return ULTRA_MAX_SEO_VERSION;
	}
}