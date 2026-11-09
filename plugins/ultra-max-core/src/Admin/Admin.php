<?php
/**
 * The Ultra Max admin menu and its pages (ADM-01 to ADM-07, ADM-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

use UltraMax\Core\Lib\Foundation\Assets as Registrar;
use UltraMax\Core\Lib\Foundation\Brand;
use UltraMax\Core\Lib\License\MarketLicense;
use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Schema;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Assets as UiAssets;

/**
 * Adds the menu and renders each page as a mount point for the React
 * panel, with everything the page needs in one inline data object, so a
 * page opens without extra requests.
 */
final class Admin {

	public const TOUR_META = 'ultra_max_tours_seen';

	/**
	 * Page slugs and their React page.
	 */
	public const PAGES = array(
		'ultra-max'          => 'dashboard',
		'ultra-max-settings' => 'settings',
		'ultra-max-modules'  => 'modules',
		'ultra-max-wizard'   => 'wizard',
		'ultra-max-help'     => 'help',
	);

	/**
	 * Hook suffixes of our pages.
	 *
	 * @var array<string, string>
	 */
	private static array $hooks = array();

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ULTRA_MAX_CORE_FILE ), array( self::class, 'action_links' ) );
	}

	/**
	 * Adds the top-level menu and its pages.
	 */
	public static function menu(): void {
		$name = Brand::name();
		$cap  = 'edit_theme_options';
		$page = array( self::class, 'render' );

		self::$hooks['ultra-max'] = (string) add_menu_page( $name, $name, $cap, 'ultra-max', $page, Brand::menu_icon(), 59 );
		$titles                   = array(
			'ultra-max'          => __( 'Dashboard', 'ultra-max-core' ),
			'ultra-max-settings' => __( 'Settings', 'ultra-max-core' ),
			'ultra-max-modules'  => __( 'Modules', 'ultra-max-core' ),
			'ultra-max-wizard'   => __( 'Setup wizard', 'ultra-max-core' ),
			'ultra-max-help'     => __( 'Help', 'ultra-max-core' ),
		);
		foreach ( $titles as $slug => $title ) {
			$capability           = in_array( $slug, array( 'ultra-max-modules', 'ultra-max-wizard' ), true ) ? 'manage_options' : $cap;
			self::$hooks[ $slug ] = (string) add_submenu_page( 'ultra-max', $title . ' ‹ ' . $name, $title, $capability, $slug, $page );
		}
	}

	/**
	 * Which of our pages is open, or null.
	 */
	public static function current(): ?string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen ) {
			return null;
		}
		$slug = array_search( $screen->id, self::$hooks, true );
		return is_string( $slug ) ? self::PAGES[ $slug ] : null;
	}

	/**
	 * Body class of our pages, for the wizard's full-width look.
	 *
	 * @param mixed $classes Classes.
	 * @return mixed
	 */
	public static function body_class( mixed $classes ): mixed {
		$page = self::current();
		return null !== $page && is_string( $classes ) ? $classes . ' um-admin-page um-admin-' . $page : $classes;
	}

	/**
	 * Settings link on the plugins screen.
	 *
	 * @param mixed $links Links.
	 * @return mixed
	 */
	public static function action_links( mixed $links ): mixed {
		if ( is_array( $links ) ) {
			array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=ultra-max-settings' ) ), esc_html__( 'Settings', 'ultra-max-core' ) ) );
		}
		return $links;
	}

	/**
	 * Loads the panel on our pages.
	 */
	public static function enqueue(): void {
		$page = self::current();
		if ( null === $page ) {
			return;
		}
		$dir       = plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build';
		$registrar = new Registrar( $dir, plugins_url( 'assets/build', ULTRA_MAX_CORE_FILE ) );
		UiAssets::enqueue_admin();
		if ( ! $registrar->register_script( 'ultra-max-admin', 'core/admin' ) ) {
			return;
		}
		if ( in_array( $page, array( 'settings', 'wizard' ), true ) ) {
			wp_enqueue_media();
		}
		wp_add_inline_script( 'ultra-max-admin', 'window.ultraMaxAdmin = ' . wp_json_encode( self::data( $page ) ) . ';', 'before' );
		wp_enqueue_script( 'ultra-max-admin' );
		self::help_tab( $page );
	}

	/**
	 * Mount point of the React page.
	 */
	public static function render(): void {
		$page = self::current() ?? 'dashboard';
		printf(
			'<div class="wrap um-admin-wrap"><h1 class="screen-reader-text">%1$s</h1><div id="um-admin-app" data-page="%2$s"><p>%3$s</p></div></div>',
			esc_html( get_admin_page_title() ),
			esc_attr( $page ),
			esc_html__( 'Loading…', 'ultra-max-core' )
		);
	}

	/**
	 * A contextual help tab on our pages that points to the help page (ADM-06).
	 *
	 * @param string $page Page.
	 */
	private static function help_tab( string $page ): void {
		$screen = get_current_screen();
		if ( null === $screen ) {
			return;
		}
		$screen->add_help_tab(
			array(
				'id'      => 'ultra-max-help',
				'title'   => Brand::name(),
				'content' => sprintf(
					'<p>%1$s</p><p><a href="%2$s">%3$s</a></p>',
					esc_html__( 'Every setting has a short explanation next to it. The help page explains each part of the store step by step, without leaving your dashboard.', 'ultra-max-core' ),
					esc_url( admin_url( 'admin.php?page=ultra-max-help' ) ),
					esc_html__( 'Open the help page', 'ultra-max-core' )
				),
			)
		);
		if ( 'help' !== $page ) {
			$screen->set_help_sidebar( '' );
		}
	}

	/**
	 * Every module with its state and asset size.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function modules(): array {
		$build = plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build/';
		$out   = array();
		foreach ( Modules::all() as $id => $module ) {
			$bytes = 0;
			foreach ( $module['assets'] ?? array() as $entry ) {
				foreach ( array( '.js', '.css' ) as $ext ) {
					$file   = $build . $entry . $ext;
					$bytes += is_readable( $file ) ? (int) filesize( $file ) : 0;
				}
			}
			$out[] = array_merge(
				$module,
				array(
					'on'    => Modules::enabled( $id, $module['default'] ),
					'bytes' => $bytes,
				)
			);
		}
		return $out;
	}

	/**
	 * Fonts the preview can draw: key to family and URL.
	 *
	 * @return array<string, array{family: string, url: string}>
	 */
	private static function preview_fonts(): array {
		$theme  = get_template_directory_uri();
		$fonts  = array(
			'vazirmatn' => array(
				'family' => '"Vazirmatn"',
				'url'    => '',
			),
			'estedad'   => array(
				'family' => '"Estedad"',
				'url'    => $theme . '/assets/fonts/estedad/Estedad-Variable.woff2',
			),
			'system'    => array(
				'family' => 'system-ui',
				'url'    => '',
			),
			'custom'    => array(
				'family' => '"UM Custom"',
				'url'    => '',
			),
		);
		$custom = Store::int( 'font_custom' );
		if ( $custom > 0 ) {
			$url                    = wp_get_attachment_url( $custom );
			$fonts['custom']['url'] = is_string( $url ) ? $url : '';
		}
		return $fonts;
	}

	/**
	 * Everything a page needs.
	 *
	 * @param string $page Page.
	 * @return array<string, mixed>
	 */
	public static function data( string $page ): array {
		$seen = get_user_meta( get_current_user_id(), self::TOUR_META, true );
		$data = array(
			'page'    => $page,
			'rest'    => array(
				'root'  => esc_url_raw( rest_url( Rest::NAMESPACE . '/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			),
			'brand'   => array(
				'name'    => Brand::name(),
				'version' => ULTRA_MAX_CORE_VERSION,
			),
			'locale'  => get_user_locale(),
			'strings' => Strings::all(),
			'urls'    => array(
				'dashboard' => admin_url( 'admin.php?page=ultra-max' ),
				'settings'  => admin_url( 'admin.php?page=ultra-max-settings' ),
				'modules'   => admin_url( 'admin.php?page=ultra-max-modules' ),
				'wizard'    => admin_url( 'admin.php?page=ultra-max-wizard' ),
				'help'      => admin_url( 'admin.php?page=ultra-max-help' ),
				'site'      => home_url( '/' ),
				'menus'     => admin_url( 'nav-menus.php' ),
				'widgets'   => admin_url( 'widgets.php' ),
				'plugins'   => admin_url( 'plugins.php' ),
				'demoImage' => plugins_url( 'assets/img/demos/', ULTRA_MAX_CORE_FILE ),
			),
			'tour'    => ! in_array( $page, is_array( $seen ) ? $seen : array(), true ),
		);

		if ( 'settings' === $page ) {
			$data['settings'] = array(
				'sections'      => Schema::sections(),
				'values'        => Store::all(),
				'defaults'      => Schema::defaults(),
				'backups'       => Rest::backup_list(),
				'fonts'         => self::preview_fonts(),
				'files'         => self::media_names(),
				'canScripts'    => current_user_can( 'unfiltered_html' ),
				'modules'       => array_map( static fn ( array $module ): bool => (bool) $module['on'], array_column( self::modules(), null, 'id' ) ),
				'canModules'    => current_user_can( 'manage_options' ),
				'dateFormatTip' => '1405/08/18',
			);
		}
		if ( 'modules' === $page ) {
			$data['modules'] = self::modules();
		}
		if ( 'dashboard' === $page ) {
			$checks            = SystemStatus::checks();
			$products          = Products::all();
			$license           = new MarketLicense();
			$data['dashboard'] = array(
				'products'   => $products,
				'mismatched' => Products::mismatched( $products ),
				'changelog'  => Products::changelog(),
				'checks'     => $checks,
				'summary'    => SystemStatus::summary( $checks ),
				'report'     => SystemStatus::report( $checks ),
				'license'    => array(
					'market' => $license->market(),
					'status' => $license->status()->value,
				),
				'store'      => Overview::store(),
			);
		}
		if ( 'wizard' === $page ) {
			$data['wizard'] = array(
				'plugins' => Wizard::plugins(),
				'demos'   => Wizard::demos(),
				'name'    => get_bloginfo( 'name' ),
				'logo'    => is_numeric( get_theme_mod( 'custom_logo', 0 ) ) ? (int) get_theme_mod( 'custom_logo', 0 ) : 0,
				'preset'  => Store::string( 'color_preset' ),
				'color'   => Store::string( 'color_primary' ),
				'presets' => self::presets(),
			);
		}
		if ( 'help' === $page ) {
			$data['help'] = Help::articles();
		}
		return $data;
	}

	/**
	 * File names of the media fields' current values.
	 *
	 * @return array<string, string>
	 */
	private static function media_names(): array {
		$names = array();
		foreach ( Schema::fields() as $id => $field ) {
			if ( 'media' === $field['type'] ) {
				$attachment = Store::int( $id );
				$file       = $attachment > 0 ? get_attached_file( $attachment ) : false;
				if ( is_string( $file ) ) {
					$names[ $id ] = wp_basename( $file );
				}
			}
		}
		return $names;
	}

	/**
	 * Colour presets from the theme's schema, for the wizard.
	 *
	 * @return list<mixed>
	 */
	private static function presets(): array {
		$fields = Schema::fields();
		$preset = $fields['color_preset'] ?? null;
		return null !== $preset && isset( $preset['options'] ) ? $preset['options'] : array();
	}
}
