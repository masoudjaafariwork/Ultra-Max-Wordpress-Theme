<?php
/**
 * Plugin Name: Ultra Max Core
 * Description: Independent functionality for the Ultra Max WordPress monorepo.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Text Domain: ultra-max-core
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package UltraMaxCore
 */

namespace UltraMax\Core;

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		$directory = plugin_dir_path( __FILE__ );
		$url       = plugin_dir_url( __FILE__ );
		foreach ( array( 'style.css', 'index.js' ) as $asset ) {
			$file = $directory . 'build/' . $asset;
			if ( ! file_exists( $file ) ) {
				continue;
			}
			if ( 'style.css' === $asset ) {
				wp_register_style( 'ultra-max-core', $url . 'build/' . $asset, array(), (string) filemtime( $file ) );
			} else {
				wp_register_script( 'ultra-max-core', $url . 'build/' . $asset, array(), (string) filemtime( $file ), true );
			}
		}

		add_shortcode(
			'ultra_max_hello',
			static function ( $attributes ) {
				$attributes = shortcode_atts( array( 'name' => get_bloginfo( 'name' ) ), $attributes, 'ultra_max_hello' );
				// Footer scripts can be enqueued when the shortcode renders.
				wp_enqueue_script( 'ultra-max-core' );
				return sprintf(
					'<p class="ultra-max-hello" data-ultra-max-hello>%s</p>',
					esc_html( sprintf( /* translators: %s: visitor or site name. */ __( 'Hello, %s!', 'ultra-max-core' ), $attributes['name'] ) )
				);
			}
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'ultra-max-core' );
	}
);
