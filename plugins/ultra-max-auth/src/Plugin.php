<?php
/**
 * Entry point of Ultra Max Auth.
 *
 * @package UltraMax\Auth
 */

declare(strict_types=1);

namespace UltraMax\Auth;

use UltraMax\Auth\Lib\Foundation\Requirements;
use UltraMax\Auth\Lib\Foundation\WooCompat;
use UltraMax\Auth\Lib\UI\Assets;

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
		WooCompat::declare( ULTRA_MAX_AUTH_FILE );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Runs after every plugin has loaded.
	 */
	public function init(): void {
		$requirements = new Requirements( 'Ultra Max Auth', '8.1', '6.9' );
		if ( ! $requirements->met() ) {
			$requirements->notify();
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );

		/**
		 * Fires when Ultra Max Auth has booted. Modules hook in here.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'ultra_max_auth_loaded', $this );
	}

	/**
	 * Loads the translations shipped in the plugin's languages folder.
	 *
	 * Plugin Check discourages this for WordPress.org plugins, which get
	 * language packs. Marketplace plugins ship their own translations, and
	 * WordPress only finds them in the plugin folder when told where.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'ultra-max-auth', false, dirname( plugin_basename( ULTRA_MAX_AUTH_FILE ) ) . '/languages' );
	}

	/**
	 * Registers the shared component library from this plugin's build.
	 */
	public function register_assets(): void {
		Assets::register( plugin_dir_path( ULTRA_MAX_AUTH_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_AUTH_FILE ) );
	}

	/**
	 * Version of the plugin.
	 */
	public function version(): string {
		return ULTRA_MAX_AUTH_VERSION;
	}
}