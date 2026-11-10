<?php
/**
 * Reads, writes and prints Elementor designs.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

/**
 * The only place outside the widget adapter that talks to Elementor's
 * classes. Every method checks that Elementor is loaded, so layouts keep
 * working (with block editor content) when Elementor is switched off.
 */
final class Data {

	/**
	 * Whether Elementor is loaded.
	 */
	public static function active(): bool {
		return did_action( 'elementor/loaded' ) > 0 && class_exists( \Elementor\Plugin::class );
	}

	/**
	 * Whether a post was designed with Elementor.
	 *
	 * @param int $id Post id.
	 */
	public static function built( int $id ): bool {
		return 'builder' === get_post_meta( $id, '_elementor_edit_mode', true );
	}

	/**
	 * The Elementor markup of a post, or null when it has none.
	 *
	 * @param int $id Post id.
	 */
	public static function render( int $id ): ?string {
		if ( ! self::active() || ! self::built( $id ) ) {
			return null;
		}
		return (string) \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id );
	}

	/**
	 * Queues the styles and scripts a post's design needs.
	 *
	 * Call it on `wp_enqueue_scripts`: then the styles print in the head.
	 *
	 * @param int $id Post id.
	 */
	public static function enqueue( int $id ): void {
		if ( ! self::active() || ! self::built( $id ) ) {
			return;
		}
		$elementor = \Elementor\Plugin::instance();
		$elementor->frontend->enqueue_styles();
		\Elementor\Core\Files\CSS\Post::create( $id )->enqueue();
		$assets = get_post_meta( $id, '_elementor_page_assets', true );
		$clean  = array();
		foreach ( is_array( $assets ) ? $assets : array() as $type => $handles ) {
			if ( is_string( $type ) && is_array( $handles ) ) {
				$clean[ $type ] = array_values( array_filter( $handles, 'is_string' ) );
			}
		}
		if ( array() !== $clean ) {
			$elementor->assets_loader->enable_assets( $clean );
		}
	}

	/**
	 * Stores a design in a post, with new element ids, as Elementor would.
	 *
	 * @param int          $id       Post id.
	 * @param array<mixed> $elements Elementor elements.
	 */
	public static function import( int $id, array $elements ): void {
		update_post_meta( $id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $id, '_elementor_template_type', 'wp-post' );
		update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.30.0' );
		update_post_meta( $id, '_elementor_data', wp_slash( (string) wp_json_encode( self::fresh_ids( $elements ) ) ) );
		delete_post_meta( $id, '_elementor_page_assets' );
		delete_post_meta( $id, '_elementor_css' );
	}

	/**
	 * Gives every element a new id, so two copies of a template never
	 * share one.
	 *
	 * @param array<mixed> $elements Elements.
	 * @return array<mixed>
	 */
	public static function fresh_ids( array $elements ): array {
		foreach ( $elements as $index => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$element['id'] = substr( md5( wp_generate_uuid4() ), 0, 7 );
			if ( is_array( $element['elements'] ?? null ) ) {
				$element['elements'] = self::fresh_ids( $element['elements'] );
			}
			$elements[ $index ] = $element;
		}
		return $elements;
	}

	/**
	 * The Elementor editing address of a post.
	 *
	 * @param int $id Post id.
	 */
	public static function edit_url( int $id ): string {
		if ( self::active() ) {
			$document = \Elementor\Plugin::instance()->documents->get( $id );
			if ( $document instanceof \Elementor\Core\Base\Document ) {
				return (string) $document->get_edit_url();
			}
		}
		return (string) get_edit_post_link( $id, 'raw' );
	}

	/**
	 * Widget types used in a post's design, for loading their assets early.
	 *
	 * @param int $id Post id.
	 * @return list<string>
	 */
	public static function widget_types( int $id ): array {
		$data = get_post_meta( $id, '_elementor_data', true );
		if ( ! is_string( $data ) || '' === $data ) {
			return array();
		}
		preg_match_all( '/"widgetType":"([a-z0-9_-]+)"/', $data, $matches );
		return array_values( array_unique( $matches[1] ) );
	}
}
