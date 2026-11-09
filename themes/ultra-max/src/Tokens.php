<?php
/**
 * Design tokens from the settings (CORE-05, CORE-06, PERF-05).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Foundation\Color;
use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\Assets as UiAssets;

/**
 * Turns colour, font, corner, shadow and width settings into CSS custom
 * properties. Every component reads these variables, so one inline rule
 * restyles the whole store with no extra file.
 */
final class Tokens {

	/**
	 * Default values that tokens.css already holds; settings equal to these print nothing.
	 */
	private const DEFAULTS = array(
		'accent'    => '#ff9a1f',
		'radius'    => 14,
		'container' => 1336,
		'gutter'    => 16,
	);

	/**
	 * Colour presets with their full ramp, the same as tokens.css.
	 *
	 * @return array<string, array{label: string, ramp: array<int, string>}>
	 */
	public static function presets(): array {
		return array(
			'indigo'   => array(
				'label' => __( 'Indigo', 'ultra-max' ),
				'ramp'  => array(
					50  => '#efefff',
					100 => '#e1e0fe',
					200 => '#c4c1fb',
					400 => '#7f78f0',
					500 => '#6158e6',
					600 => '#4c42d3',
					700 => '#3d34ad',
					800 => '#2e2783',
				),
			),
			'ocean'    => array(
				'label' => __( 'Ocean', 'ultra-max' ),
				'ramp'  => array(
					50  => '#eaf3ff',
					100 => '#d3e6ff',
					200 => '#a8ccff',
					400 => '#4e93f2',
					500 => '#2c7be5',
					600 => '#1a64cc',
					700 => '#154fa3',
					800 => '#103c7a',
				),
			),
			'teal'     => array(
				'label' => __( 'Teal', 'ultra-max' ),
				'ramp'  => array(
					50  => '#e6f6f6',
					100 => '#c9ecec',
					200 => '#96d9d9',
					400 => '#35aeb0',
					500 => '#149a9c',
					600 => '#0e7f82',
					700 => '#0c6668',
					800 => '#0a4f51',
				),
			),
			'emerald'  => array(
				'label' => __( 'Emerald', 'ultra-max' ),
				'ramp'  => array(
					50  => '#e8f7ef',
					100 => '#cbeedb',
					200 => '#9bddb9',
					400 => '#3db77a',
					500 => '#1ea263',
					600 => '#168550',
					700 => '#126a41',
					800 => '#0e5233',
				),
			),
			'graphite' => array(
				'label' => __( 'Graphite', 'ultra-max' ),
				'ramp'  => array(
					50  => '#f0f1f4',
					100 => '#e1e3e9',
					200 => '#c3c7d2',
					400 => '#6b7286',
					500 => '#4b5266',
					600 => '#353b4d',
					700 => '#272c3b',
					800 => '#1b1f2a',
				),
			),
		);
	}

	/**
	 * Fonts the buyer can choose. `file` is relative to the theme folder;
	 * Vazirmatn comes with the component library.
	 *
	 * @return array<string, array{label: string, family: string, file?: string}>
	 */
	public static function fonts(): array {
		return array(
			'vazirmatn' => array(
				'label'  => __( 'Vazirmatn', 'ultra-max' ),
				'family' => '"Vazirmatn"',
			),
			'estedad'   => array(
				'label'  => __( 'Estedad', 'ultra-max' ),
				'family' => '"Estedad"',
				'file'   => 'assets/fonts/estedad/Estedad-Variable.woff2',
			),
			'custom'    => array(
				'label'  => __( 'Uploaded font', 'ultra-max' ),
				'family' => '"UM Custom"',
			),
			'system'    => array(
				'label'  => __( 'Device font (loads nothing)', 'ultra-max' ),
				'family' => 'system-ui',
			),
		);
	}

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
		add_action( 'wp_head', array( self::class, 'preload' ), 2 );
	}

	/**
	 * The brand ramp from the settings, or null for the default indigo.
	 *
	 * @return array<int, string>|null
	 */
	public static function brand_ramp(): ?array {
		$preset = Store::string( 'color_preset' );
		if ( 'custom' === $preset ) {
			return Color::ramp( Store::string( 'color_primary' ) );
		}
		if ( 'indigo' === $preset ) {
			return null;
		}
		return self::presets()[ $preset ]['ramp'] ?? null;
	}

	/**
	 * The font key in use for a role, resolving "same as body text".
	 *
	 * @param string $role "body", "heading" or "numbers".
	 */
	public static function font_key( string $role ): string {
		$fonts = self::fonts();
		$key   = Store::string( 'font_' . $role );
		if ( 'body' === $key || ! isset( $fonts[ $key ] ) ) {
			$key = 'body' === $role ? 'vazirmatn' : self::font_key( 'body' );
		}
		if ( 'custom' === $key && null === self::custom_font_url() ) {
			return 'vazirmatn';
		}
		return $key;
	}

	/**
	 * URL of the uploaded font, when it is a WOFF2 file.
	 */
	public static function custom_font_url(): ?string {
		$id = Store::int( 'font_custom' );
		if ( $id <= 0 ) {
			return null;
		}
		$url = wp_get_attachment_url( $id );
		return is_string( $url ) && str_ends_with( strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) ), '.woff2' ) ? $url : null;
	}

	/**
	 * URL of a font file, or null when the font loads nothing.
	 *
	 * @param string $key Font key.
	 */
	public static function font_url( string $key ): ?string {
		if ( 'custom' === $key ) {
			return self::custom_font_url();
		}
		if ( 'vazirmatn' === $key ) {
			$style = wp_styles()->registered[ UiAssets::STOREFRONT ] ?? null;
			$src   = $style instanceof \_WP_Dependency && is_string( $style->src ) ? $style->src : '';
			// The library's CSS is <build>/ui/storefront.css and its font sits in <build>/fonts.
			return '' === $src ? null : dirname( $src, 2 ) . '/fonts/vazirmatn/Vazirmatn-Variable.woff2';
		}
		$file = self::fonts()[ $key ]['file'] ?? null;
		return null === $file ? null : get_template_directory_uri() . '/' . $file;
	}

	/**
	 * The CSS that applies the settings.
	 */
	public static function css(): string {
		$vars = array();

		$ramp = self::brand_ramp();
		if ( null !== $ramp ) {
			foreach ( $ramp as $step => $color ) {
				$vars[ '--um-brand-' . $step ] = $color;
			}
			$vars['--um-on-primary'] = Color::on( $ramp[600] ?? '#4c42d3' );
		}

		$accent = Color::normalize( Store::string( 'color_accent' ) ) ?? self::DEFAULTS['accent'];
		if ( self::DEFAULTS['accent'] !== $accent ) {
			$vars['--um-accent-50']  = Color::mix( $accent, 8, '#ffffff' );
			$vars['--um-accent-100'] = Color::mix( $accent, 24, '#ffffff' );
			$vars['--um-accent-400'] = Color::mix( $accent, 75, '#ffffff' );
			$vars['--um-accent-500'] = $accent;
			$vars['--um-accent-600'] = Color::mix( $accent, 88, '#000000' );
			$vars['--um-accent-700'] = Color::mix( $accent, 70, '#000000' );
		}

		$radius = Store::int( 'radius' );
		if ( self::DEFAULTS['radius'] !== $radius ) {
			$vars['--um-radius-xs'] = round( $radius * 0.29 ) . 'px';
			$vars['--um-radius-sm'] = round( $radius * 0.43 ) . 'px';
			$vars['--um-radius-md'] = round( $radius * 0.7 ) . 'px';
			$vars['--um-radius-lg'] = $radius . 'px';
			$vars['--um-radius-xl'] = round( $radius * 1.43 ) . 'px';
		}

		$shadow = Store::string( 'shadow' );
		if ( 'none' === $shadow ) {
			foreach ( array( 'xs', 'sm', 'md', 'lg' ) as $size ) {
				$vars[ '--um-shadow-' . $size ] = 'none';
			}
		} elseif ( 'strong' === $shadow ) {
			$vars['--um-shadow-xs'] = '0 1px 3px rgb(21 22 28 / 10%)';
			$vars['--um-shadow-sm'] = '0 2px 6px rgb(21 22 28 / 12%), 0 1px 2px rgb(21 22 28 / 6%)';
			$vars['--um-shadow-md'] = '0 10px 28px rgb(21 22 28 / 14%)';
			$vars['--um-shadow-lg'] = '0 20px 56px rgb(21 22 28 / 22%)';
		}

		$container = Store::int( 'container_width' );
		if ( self::DEFAULTS['container'] !== $container && $container > 0 ) {
			$vars['--um-container'] = $container . 'px';
		}
		$gutter = Store::int( 'gutter' );
		if ( self::DEFAULTS['gutter'] !== $gutter && $gutter > 0 ) {
			$vars['--um-gutter'] = $gutter . 'px';
		}

		$fonts = self::fonts();
		$stack = ', Tahoma, "Segoe UI", sans-serif';
		$body  = self::font_key( 'body' );
		if ( 'vazirmatn' !== $body ) {
			$vars['--um-font-body'] = $fonts[ $body ]['family'] . $stack;
		}
		foreach ( array(
			'heading' => '--um-font-heading',
			'numbers' => '--um-font-num',
		) as $role => $token ) {
			$key = self::font_key( $role );
			if ( $key !== $body ) {
				$vars[ $token ] = $fonts[ $key ]['family'] . $stack;
			}
		}

		$css = '';
		foreach ( self::font_faces() as $family => $url ) {
			$css .= sprintf( '@font-face{font-family:"%s";src:url("%s") format("woff2");font-weight:100 900;font-style:normal;font-display:swap}', $family, esc_url_raw( $url ) );
		}
		if ( array() !== $vars ) {
			$css .= ':root{';
			foreach ( $vars as $name => $value ) {
				$css .= $name . ':' . $value . ';';
			}
			$css .= '}';
		}
		return $css;
	}

	/**
	 * Fonts in use that tokens.css does not declare: family to URL.
	 *
	 * @return array<string, string>
	 */
	private static function font_faces(): array {
		$faces = array();
		foreach ( array( 'body', 'heading', 'numbers' ) as $role ) {
			$key = self::font_key( $role );
			$url = self::font_url( $key );
			if ( 'estedad' === $key && null !== $url ) {
				$faces['Estedad'] = $url;
			} elseif ( 'custom' === $key && null !== $url ) {
				$faces['UM Custom'] = $url;
			}
		}
		return $faces;
	}

	/**
	 * Adds the token rule after the component library's stylesheet.
	 */
	public static function enqueue(): void {
		$css = self::css();
		if ( '' !== $css ) {
			wp_add_inline_style( UiAssets::STOREFRONT, $css );
		}
	}

	/**
	 * Preloads the body font so text draws in it from the first paint (PERF-05).
	 */
	public static function preload(): void {
		if ( ! Store::bool( 'perf_preload_font' ) ) {
			return;
		}
		$url = self::font_url( self::font_key( 'body' ) );
		if ( null !== $url ) {
			printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $url ) );
		}
	}
}
