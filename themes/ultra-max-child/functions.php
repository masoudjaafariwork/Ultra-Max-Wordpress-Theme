<?php
/**
 * Ultra Max child theme. Add your customizations below.
 *
 * @package UltraMax\Child
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		load_child_theme_textdomain( 'ultra-max-child', get_stylesheet_directory() . '/languages' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'ultra-max-child',
			get_stylesheet_uri(),
			array(),
			(string) wp_get_theme()->get( 'Version' )
		);
	},
	20
);
