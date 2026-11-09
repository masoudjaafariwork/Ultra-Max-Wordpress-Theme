<?php
/**
 * Maintenance and coming-soon mode without a plugin (CORE-09).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Foundation\Jalali;
use UltraMax\Theme\Lib\Settings\Store;

/**
 * Shows visitors a notice page while the store is closed. Anyone who can
 * edit posts still sees the site, with a reminder in the admin bar.
 *
 * Maintenance answers 503 with Retry-After, so search engines keep the
 * pages; coming soon answers 200 and asks not to be indexed.
 */
final class Maintenance {

	/**
	 * Registers the hooks.
	 */
	public static function register(): void {
		if ( 'off' === self::mode() ) {
			return;
		}
		add_action( 'template_redirect', array( self::class, 'maybe_block' ), 1 );
		add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 100 );
	}

	/**
	 * The mode: "off", "maintenance" or "coming_soon".
	 */
	public static function mode(): string {
		$mode = Store::string( 'maintenance_mode' );
		return in_array( $mode, array( 'maintenance', 'coming_soon' ), true ) ? $mode : 'off';
	}

	/**
	 * Whether the current visitor bypasses the notice page.
	 */
	public static function bypass(): bool {
		/**
		 * Filters whether the current visitor sees the site during maintenance.
		 *
		 * @param bool $bypass Whether the visitor bypasses the notice page.
		 */
		return (bool) apply_filters( 'ultra_max_maintenance_bypass', current_user_can( 'edit_posts' ) );
	}

	/**
	 * Shows the notice page to visitors.
	 */
	public static function maybe_block(): void {
		if ( self::bypass() || is_robots() || is_feed() || is_trackback() ) {
			return;
		}
		$mode = self::mode();
		if ( 'maintenance' === $mode ) {
			status_header( 503 );
			header( 'Retry-After: 3600' );
		} else {
			status_header( 200 );
		}
		nocache_headers();
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter(
			'pre_get_document_title',
			static fn (): string => self::title() . ' – ' . get_bloginfo( 'name' )
		);
		get_template_part( 'template-parts/maintenance' );
		exit;
	}

	/**
	 * Title of the notice page.
	 */
	public static function title(): string {
		$title = Store::string( 'maintenance_title' );
		if ( '' !== $title ) {
			return $title;
		}
		return 'coming_soon' === self::mode() ? __( 'Opening soon', 'ultra-max' ) : __( 'We will be back soon', 'ultra-max' );
	}

	/**
	 * Message of the notice page.
	 */
	public static function text(): string {
		$text = Store::string( 'maintenance_text' );
		if ( '' !== $text ) {
			return $text;
		}
		return 'coming_soon' === self::mode()
			? __( 'Our store is getting ready. Please visit us again soon.', 'ultra-max' )
			: __( 'We are improving the store and will be back shortly. Thank you for your patience.', 'ultra-max' );
	}

	/**
	 * "Back on" line with the Jalali date, or an empty string.
	 */
	public static function until(): string {
		$timestamp = Dates::setting_timestamp( 'maintenance_until' );
		if ( null === $timestamp ) {
			return '';
		}
		/* translators: %s: Jalali date. */
		return sprintf( __( 'Back on %s', 'ultra-max' ), Dates::digits( Jalali::format( 'j F Y', $timestamp ) ) );
	}

	/**
	 * Reminds editors that visitors see the notice page.
	 *
	 * @param \WP_Admin_Bar $bar Admin bar.
	 */
	public static function admin_bar( \WP_Admin_Bar $bar ): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'ultra-max-maintenance',
				'title' => 'coming_soon' === self::mode() ? __( 'Coming soon mode is on', 'ultra-max' ) : __( 'Maintenance mode is on', 'ultra-max' ),
				'href'  => admin_url( 'admin.php?page=ultra-max-settings#general' ),
			)
		);
	}
}
