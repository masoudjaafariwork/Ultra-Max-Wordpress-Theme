<?php
/**
 * Every widget as a shortcode.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets;

/**
 * `[um_<id>]` with the widget's control ids as attributes, for example
 * `[um_products source="sale" count="8" layout="carousel"]`. Switches take
 * yes or no, lists of posts take comma separated ids, and repeaters take a
 * JSON list. The same renderer as the Elementor widget prints it.
 */
final class Shortcodes {

	/**
	 * Registers one shortcode per widget.
	 */
	public static function register(): void {
		foreach ( Widgets::all() as $widget ) {
			add_shortcode( self::tag( $widget ), array( self::class, 'render' ) );
		}
	}

	/**
	 * Shortcode tag of a widget.
	 *
	 * @param Widget $widget Widget.
	 * @return non-empty-string
	 */
	public static function tag( Widget $widget ): string {
		return 'um_' . str_replace( '-', '_', $widget->id() );
	}

	/**
	 * Renders a shortcode.
	 *
	 * @param mixed $atts    Attributes.
	 * @param mixed $content Enclosed content; unused.
	 * @param mixed $tag     Shortcode tag.
	 */
	public static function render( mixed $atts, mixed $content = '', mixed $tag = '' ): string {
		$id     = is_string( $tag ) ? str_replace( '_', '-', substr( $tag, 3 ) ) : '';
		$widget = Widgets::get( $id );
		if ( null === $widget ) {
			return '';
		}
		$raw = array();
		foreach ( is_array( $atts ) ? $atts : array() as $key => $value ) {
			if ( is_string( $key ) ) {
				$raw[ $key ] = $value;
			}
		}
		Widgets::enqueue( $widget );
		return (string) $widget->output( $raw );
	}
}
