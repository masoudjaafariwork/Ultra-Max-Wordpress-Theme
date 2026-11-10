<?php
/**
 * Keeps Elementor's Global Kit in step with our settings (BLD-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

use UltraMax\Core\Lib\Settings\Schema;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Our settings panel is the source of truth for colours and fonts. Each
 * time they are saved, the active Elementor kit gets:
 *
 * - global colours: primary (brand), secondary and accent (accent colour),
 *   text;
 * - global fonts: the body and heading fonts;
 * - the content width, and the tablet breakpoint at 1023px so Elementor's
 *   "hide on tablet" matches the theme's own breakpoints.
 *
 * Our fonts are registered as local fonts, so Elementor never loads them
 * from Google (PERF-05), and Elementor's default colours and fonts are
 * turned off once so widgets follow the theme.
 */
final class Kit {

	public const SYNCED = 'ultra_max_kit_synced';

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_filter( 'elementor/fonts/groups', array( self::class, 'font_groups' ) );
		add_filter( 'elementor/fonts/additional_fonts', array( self::class, 'fonts' ) );
		add_action( 'ultra_max_settings_saved', array( self::class, 'sync' ) );
		add_action( 'admin_init', array( self::class, 'first_sync' ) );
	}

	/**
	 * Our font group in Elementor's font list.
	 *
	 * @param mixed $groups Groups.
	 * @return mixed
	 */
	public static function font_groups( mixed $groups ): mixed {
		if ( is_array( $groups ) ) {
			$groups = array( 'ultra-max' => __( 'Theme fonts', 'ultra-max-core' ) ) + $groups;
		}
		return $groups;
	}

	/**
	 * Our fonts, served from the site.
	 *
	 * @param mixed $fonts Fonts by family.
	 * @return mixed
	 */
	public static function fonts( mixed $fonts ): mixed {
		if ( is_array( $fonts ) ) {
			$fonts['Vazirmatn'] = 'ultra-max';
			$fonts['Estedad']   = 'ultra-max';
		}
		return $fonts;
	}

	/**
	 * Font family of a font setting.
	 *
	 * @param string $key Setting id.
	 */
	public static function font_family( string $key ): string {
		$value = Store::string( $key );
		if ( 'body' === $value ) {
			$value = Store::string( 'font_body' );
		}
		return match ( $value ) {
			'estedad' => 'Estedad',
			'system'  => 'system-ui',
			'custom'  => 'UM Custom',
			default   => 'Vazirmatn',
		};
	}

	/**
	 * The brand colour of the chosen preset, or the custom one.
	 */
	public static function brand(): string {
		$preset = Store::string( 'color_preset' );
		if ( 'custom' === $preset ) {
			return Store::string( 'color_primary' );
		}
		$field = Schema::fields()['color_preset'] ?? null;
		foreach ( is_array( $field ) && is_array( $field['options'] ?? null ) ? $field['options'] : array() as $option ) {
			if ( is_array( $option ) && isset( $option['value'] ) && $option['value'] === $preset && is_string( $option['color'] ?? null ) ) {
				return $option['color'];
			}
		}
		return '#4c42d3';
	}

	/**
	 * The kit settings our settings decide.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		$accent  = Store::string( 'color_accent' );
		$body    = self::font_family( 'font_body' );
		$heading = self::font_family( 'font_heading' );
		$font    = static fn ( string $id, string $title, string $family, int $weight ): array => array(
			'_id'                    => $id,
			'title'                  => $title,
			'typography_typography'  => 'custom',
			'typography_font_family' => $family,
			'typography_font_weight' => (string) $weight,
		);
		return array(
			'system_colors'     => array(
				array(
					'_id'   => 'primary',
					'title' => __( 'Brand', 'ultra-max-core' ),
					'color' => self::brand(),
				),
				array(
					'_id'   => 'secondary',
					'title' => __( 'Accent', 'ultra-max-core' ),
					'color' => '' === $accent ? '#ff9a1f' : $accent,
				),
				array(
					'_id'   => 'text',
					'title' => __( 'Text', 'ultra-max-core' ),
					'color' => '#15161c',
				),
				array(
					'_id'   => 'accent',
					'title' => __( 'Accent', 'ultra-max-core' ),
					'color' => '' === $accent ? '#ff9a1f' : $accent,
				),
			),
			'system_typography' => array(
				$font( 'primary', __( 'Headings', 'ultra-max-core' ), $heading, 800 ),
				$font( 'secondary', __( 'Subheadings', 'ultra-max-core' ), $heading, 700 ),
				$font( 'text', __( 'Text', 'ultra-max-core' ), $body, 400 ),
				$font( 'accent', __( 'Buttons', 'ultra-max-core' ), $body, 700 ),
			),
			'container_width'   => array(
				'unit' => 'px',
				'size' => max( 960, Store::int( 'container_width' ) ),
			),
			'viewport_tablet'   => 1023,
		);
	}

	/**
	 * Writes our settings into the active kit.
	 */
	public static function sync(): void {
		if ( ! Data::active() ) {
			return;
		}
		$kit = \Elementor\Plugin::instance()->kits_manager->get_active_kit();
		if ( ! $kit instanceof \Elementor\Core\Base\Document ) {
			return;
		}
		$current = $kit->get_settings();
		$kit->save( array( 'settings' => array_merge( is_array( $current ) ? $current : array(), self::settings() ) ) );
		update_option( self::SYNCED, 1, false );
	}

	/**
	 * The first sync, once, the first time an administrator opens the
	 * dashboard with Elementor active. Also turns off Elementor's default
	 * colours and fonts, so its widgets follow the theme.
	 */
	public static function first_sync(): void {
		if ( get_option( self::SYNCED ) || ! current_user_can( 'manage_options' ) || ! Data::active() ) {
			return;
		}
		update_option( 'elementor_disable_color_schemes', 'yes' );
		update_option( 'elementor_disable_typography_schemes', 'yes' );
		// Fonts come from the site itself (PERF-05), never from Google.
		update_option( 'elementor_google_font', '0' );
		// Our backgrounds are light gradients, not heavy photos; hiding them until they scroll in only makes them flash.
		update_option( 'elementor_lazy_load_background_images', '0' );
		// Small per-page CSS inside the page instead of one more file to wait for.
		update_option( 'elementor_css_print_method', 'internal' );
		self::sync();
	}
}
