<?php
/**
 * Custom CSS, JavaScript and head or footer code (ADM-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core;

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Prints the code from the settings on every page, and the code of the
 * current post or page from its own box in the editor.
 *
 * Saving JavaScript or HTML needs the `unfiltered_html` capability, the
 * same rule WordPress applies to its own Custom HTML block.
 */
final class CustomCode {

	public const META_CSS = '_ultra_max_css';
	public const META_JS  = '_ultra_max_js';

	/**
	 * Registers the hooks when the module is on.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( 'custom-code' ) ) {
			return;
		}
		add_action( 'wp_head', array( self::class, 'head' ), 100 );
		add_action( 'wp_body_open', array( self::class, 'body' ), 1 );
		add_action( 'wp_footer', array( self::class, 'footer' ), 100 );
		add_action( 'add_meta_boxes', array( self::class, 'meta_box' ) );
		add_action( 'save_post', array( self::class, 'save' ), 10, 2 );
	}

	/**
	 * CSS of the current post, if any.
	 */
	private static function post_css(): string {
		if ( ! is_singular() ) {
			return '';
		}
		$css = get_post_meta( (int) get_queried_object_id(), self::META_CSS, true );
		return is_string( $css ) ? $css : '';
	}

	/**
	 * JavaScript of the current post, if any.
	 */
	private static function post_js(): string {
		if ( ! is_singular() ) {
			return '';
		}
		$js = get_post_meta( (int) get_queried_object_id(), self::META_JS, true );
		return is_string( $js ) ? $js : '';
	}

	/**
	 * Prints CSS and head code.
	 */
	public static function head(): void {
		$css = trim( Store::string( 'code_css' ) . "\n" . self::post_css() );
		if ( '' !== $css ) {
			printf( "<style id=\"ultra-max-custom-css\">\n%s\n</style>\n", str_ireplace( '</style', '', wp_strip_all_tags( $css ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS, tags stripped.
		}
		echo Store::string( 'code_head' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Saved only by users with unfiltered_html.
	}

	/**
	 * Prints code after the opening body tag.
	 */
	public static function body(): void {
		echo Store::string( 'code_body' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Saved only by users with unfiltered_html.
	}

	/**
	 * Prints JavaScript and footer code.
	 */
	public static function footer(): void {
		$js = trim( Store::string( 'code_js' ) . "\n" . self::post_js() );
		if ( '' !== $js ) {
			wp_print_inline_script_tag( $js, array( 'id' => 'ultra-max-custom-js' ) );
		}
		echo Store::string( 'code_footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Saved only by users with unfiltered_html.
	}

	/**
	 * Adds the code box to the editor of public post types.
	 */
	public static function meta_box(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( 'attachment' !== $type ) {
				add_meta_box( 'ultra-max-code', __( 'Custom code for this page', 'ultra-max-core' ), array( self::class, 'render_box' ), $type, 'normal', 'low' );
			}
		}
	}

	/**
	 * The code box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_box( \WP_Post $post ): void {
		wp_nonce_field( 'ultra_max_code', 'ultra_max_code_nonce' );
		$css = get_post_meta( $post->ID, self::META_CSS, true );
		$js  = get_post_meta( $post->ID, self::META_JS, true );
		printf(
			'<p><label for="ultra-max-css"><strong>%1$s</strong></label></p><textarea id="ultra-max-css" name="ultra_max_css" rows="5" class="large-text code" dir="ltr">%2$s</textarea>',
			esc_html__( 'CSS', 'ultra-max-core' ),
			esc_textarea( is_string( $css ) ? $css : '' )
		);
		if ( current_user_can( 'unfiltered_html' ) ) {
			printf(
				'<p><label for="ultra-max-js"><strong>%1$s</strong></label></p><textarea id="ultra-max-js" name="ultra_max_js" rows="5" class="large-text code" dir="ltr">%2$s</textarea>',
				esc_html__( 'JavaScript', 'ultra-max-core' ),
				esc_textarea( is_string( $js ) ? $js : '' )
			);
		}
		printf( '<p class="description">%s</p>', esc_html__( 'Loaded only on this page, after the code for the whole site.', 'ultra-max-core' ) );
	}

	/**
	 * Saves the code box.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		$nonce = isset( $_POST['ultra_max_code_nonce'] ) && is_string( $_POST['ultra_max_code_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ultra_max_code_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'ultra_max_code' ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		if ( isset( $_POST['ultra_max_css'] ) && is_string( $_POST['ultra_max_css'] ) ) {
			$css = trim( str_ireplace( '</style', '', wp_strip_all_tags( wp_unslash( $_POST['ultra_max_css'] ) ) ) );
			'' === $css ? delete_post_meta( $post->ID, self::META_CSS ) : update_post_meta( $post->ID, self::META_CSS, wp_slash( $css ) );
		}
		if ( current_user_can( 'unfiltered_html' ) && isset( $_POST['ultra_max_js'] ) && is_string( $_POST['ultra_max_js'] ) ) {
			$js = trim( wp_unslash( $_POST['ultra_max_js'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Script from a user with unfiltered_html.
			'' === $js ? delete_post_meta( $post->ID, self::META_JS ) : update_post_meta( $post->ID, self::META_JS, wp_slash( $js ) );
		}
	}
}
