<?php
/**
 * Prints layouts in their page locations (BLD-01, BLD-09, BLD-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

use UltraMax\Core\Elementor\Data;
use UltraMax\Core\Lib\UI\Assets;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Shop\Scope;
use UltraMax\Core\Widgets\Widgets;

/**
 * The theme asks for each location through the `ultra_max_do_location`
 * filter and prints its own default for the devices nobody covered.
 * Elementor Pro's Theme Builder is asked first by the theme, so a Pro
 * header wins over ours (BLD-09).
 *
 * The styles of every layout on the page are queued in the head, before
 * the page draws, so a header never flashes unstyled (BLD-12).
 *
 * @phpstan-import-type Devices from Layouts
 */
final class Renderer {

	/**
	 * Layouts being rendered, to stop a block that includes itself.
	 *
	 * @var list<int>
	 */
	private static array $stack = array();

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		add_filter( 'ultra_max_do_location', array( self::class, 'do_location' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
		add_shortcode( 'ultra_max_layout', array( self::class, 'shortcode' ) );
		add_filter( 'template_include', array( self::class, 'canvas' ), 99 );
		add_action( 'template_redirect', array( self::class, 'protect' ) );
		add_filter( 'wp_robots', array( self::class, 'robots' ) );
	}

	/**
	 * Prints the layouts of a location and reports the devices they cover.
	 *
	 * @param mixed $covered  Devices already covered.
	 * @param mixed $location Location.
	 * @return mixed
	 */
	public static function do_location( mixed $covered, mixed $location ): mixed {
		if ( ! is_string( $location ) || is_singular( Layouts::POST_TYPE ) ) {
			return $covered;
		}
		$covered = self::devices( $covered );
		foreach ( Resolver::location( $location ) as $pick ) {
			$devices = array();
			foreach ( $pick['devices'] as $device => $on ) {
				$devices[ $device ] = $on && ! $covered[ $device ];
			}
			if ( ! in_array( true, $devices, true ) ) {
				continue;
			}
			$content = self::content( $pick['id'] );
			if ( $content->is_empty() ) {
				continue;
			}
			UI::layout_region(
				array(
					'location' => $location,
					'id'       => $pick['id'],
					'sticky'   => 'header' === $location && in_array( $pick['sticky'], array( 'sticky', 'smart' ), true ) ? $pick['sticky'] : 'none',
					'class'    => UI::visibility( $devices ),
					'children' => $content,
				)
			)->print();
			foreach ( $devices as $device => $on ) {
				$covered[ $device ] = $covered[ $device ] || $on;
			}
		}
		return $covered;
	}

	/**
	 * Normalizes a devices value.
	 *
	 * @param mixed $value Value.
	 * @return Devices
	 */
	private static function devices( mixed $value ): array {
		$value = is_array( $value ) ? $value : array();
		return array(
			'desktop' => true === ( $value['desktop'] ?? false ),
			'tablet'  => true === ( $value['tablet'] ?? false ),
			'mobile'  => true === ( $value['mobile'] ?? false ),
		);
	}

	/**
	 * The markup of a layout: its Elementor design, or its editor content
	 * when it was not built with Elementor.
	 *
	 * @param int $id Layout id.
	 */
	public static function content( int $id ): Html {
		if ( in_array( $id, self::$stack, true ) || 'publish' !== get_post_status( $id ) ) {
			return Html::none();
		}
		self::$stack[] = $id;
		$markup        = Data::render( $id );
		if ( null === $markup ) {
			$post = get_post( $id );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own content filter.
			$markup = $post instanceof \WP_Post ? (string) apply_filters( 'the_content', $post->post_content ) : '';
		}
		array_pop( self::$stack );
		return Html::wp( $markup );
	}

	/**
	 * Queues the styles of the layouts this page shows, in the head.
	 */
	public static function enqueue(): void {
		if ( is_singular( Layouts::POST_TYPE ) ) {
			return;
		}
		$locations = array( 'header', 'footer' );
		if ( Scope::is_list() ) {
			$locations[] = 'shop-archive';
		}
		foreach ( $locations as $location ) {
			foreach ( Resolver::location( $location ) as $pick ) {
				Data::enqueue( $pick['id'] );
				Widgets::enqueue_for( $pick['id'] );
				if ( 'header' === $location && 'smart' === $pick['sticky'] ) {
					Assets::enqueue_module( 'smart-header' );
				}
			}
		}
	}

	/**
	 * `[ultra_max_layout id="12"]` prints a reusable block anywhere.
	 *
	 * @param mixed $atts Attributes.
	 */
	public static function shortcode( mixed $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), is_array( $atts ) ? $atts : array() );
		$id   = is_numeric( $atts['id'] ) ? (int) $atts['id'] : 0;
		if ( $id <= 0 || Layouts::POST_TYPE !== get_post_type( $id ) || 'block' !== Layouts::type( $id ) ) {
			return '';
		}
		Data::enqueue( $id );
		Widgets::enqueue_for( $id );
		return (string) self::content( $id );
	}

	/**
	 * Shows a layout on its own, without the site's header and footer, while
	 * it is edited or previewed.
	 *
	 * @param mixed $template Template file.
	 * @return mixed
	 */
	public static function canvas( mixed $template ): mixed {
		if ( is_singular( Layouts::POST_TYPE ) ) {
			return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/layout-canvas.php';
		}
		return $template;
	}

	/**
	 * Visitors who cannot edit layouts never see one at its own address.
	 */
	public static function protect(): void {
		if ( is_singular( Layouts::POST_TYPE ) && ! current_user_can( 'edit_post', (int) get_queried_object_id() ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Keeps layout previews out of search engines.
	 *
	 * @param mixed $robots Robots directives.
	 * @return mixed
	 */
	public static function robots( mixed $robots ): mixed {
		if ( is_array( $robots ) && is_singular( Layouts::POST_TYPE ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}
}
