<?php
/**
 * Ultra Max theme setup.
 *
 * @package UltraMax
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'wp-block-styles' );
		add_editor_style( 'build/style.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$file = get_theme_file_path( 'build/style.css' );
		if ( file_exists( $file ) ) {
			wp_enqueue_style( 'ultra-max', get_theme_file_uri( 'build/style.css' ), array(), (string) filemtime( $file ) );
		}
	}
);
