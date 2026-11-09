<?php
/**
 * Duplicate posts and pages (ADM-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core;

use UltraMax\Core\Lib\Settings\Modules;

/**
 * Adds a "Duplicate" link to post and page lists. The copy is a draft with
 * the same content, terms, featured image and custom fields, including
 * Elementor's layout. WooCommerce products keep WooCommerce's own
 * "Duplicate", so a product never shows two such links.
 */
final class Duplicate {

	public const ACTION = 'ultra_max_duplicate';

	/**
	 * Meta keys that belong to the original only.
	 */
	private const SKIP_META = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_dp_original', '_elementor_css' );

	/**
	 * Registers the hooks when the module is on.
	 */
	public static function register(): void {
		if ( ! Modules::enabled( 'duplicate' ) ) {
			return;
		}
		add_filter( 'post_row_actions', array( self::class, 'row_action' ), 10, 2 );
		add_filter( 'page_row_actions', array( self::class, 'row_action' ), 10, 2 );
		add_action( 'admin_action_' . self::ACTION, array( self::class, 'handle' ) );
	}

	/**
	 * Whether a post type can be duplicated here.
	 *
	 * @param string $type Post type.
	 */
	public static function supports( string $type ): bool {
		if ( 'product' === $type && class_exists( 'WC_Admin_Duplicate_Product' ) ) {
			return false;
		}
		$object = get_post_type_object( $type );
		return null !== $object && $object->show_ui && 'attachment' !== $type;
	}

	/**
	 * The duplicate link.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function url( \WP_Post $post ): string {
		return wp_nonce_url( admin_url( 'admin.php?action=' . self::ACTION . '&post=' . $post->ID ), self::ACTION . '_' . $post->ID );
	}

	/**
	 * Adds the link to a row of the list.
	 *
	 * @param mixed    $actions Row actions.
	 * @param \WP_Post $post    Post.
	 * @return mixed
	 */
	public static function row_action( mixed $actions, \WP_Post $post ): mixed {
		if ( ! is_array( $actions ) || ! self::supports( $post->post_type ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}
		$type = get_post_type_object( $post->post_type );
		if ( null === $type || ! current_user_can( $type->cap->create_posts ) ) {
			return $actions;
		}
		$actions['ultra_max_duplicate'] = sprintf(
			'<a href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( self::url( $post ) ),
			/* translators: %s: post title. */
			esc_attr( sprintf( __( 'Duplicate "%s"', 'ultra-max-core' ), get_the_title( $post ) ) ),
			esc_html__( 'Duplicate', 'ultra-max-core' )
		);
		return $actions;
	}

	/**
	 * Handles the link: checks the nonce and rights, copies, opens the copy.
	 */
	public static function handle(): void {
		$id = isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( self::ACTION . '_' . $id );
		$post = get_post( $id );
		if ( ! $post instanceof \WP_Post || ! self::supports( $post->post_type ) || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'You cannot duplicate this item.', 'ultra-max-core' ), '', array( 'response' => 403 ) );
		}
		$copy = self::copy( $post );
		if ( null === $copy ) {
			wp_die( esc_html__( 'The copy could not be created.', 'ultra-max-core' ) );
		}
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $copy ) );
		exit;
	}

	/**
	 * Copies a post as a draft.
	 *
	 * @param \WP_Post $post Original.
	 * @return int|null Id of the copy.
	 */
	public static function copy( \WP_Post $post ): ?int {
		$type = get_post_type_object( $post->post_type );
		if ( null === $type || ! current_user_can( $type->cap->create_posts ) ) {
			return null;
		}
		$copy = wp_insert_post(
			wp_slash(
				array(
					'post_type'      => $post->post_type,
					/* translators: %s: original title. */
					'post_title'     => sprintf( __( '%s (copy)', 'ultra-max-core' ), $post->post_title ),
					'post_content'   => $post->post_content,
					'post_excerpt'   => $post->post_excerpt,
					'post_status'    => 'draft',
					'post_author'    => get_current_user_id(),
					'post_parent'    => $post->post_parent,
					'menu_order'     => $post->menu_order,
					'comment_status' => $post->comment_status,
					'ping_status'    => $post->ping_status,
					'post_password'  => $post->post_password,
				)
			),
			true
		);
		if ( is_wp_error( $copy ) ) {
			return null;
		}

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_array( $terms ) && array() !== $terms ) {
				wp_set_object_terms( $copy, array_map( 'intval', $terms ), $taxonomy );
			}
		}

		$meta = get_post_meta( $post->ID );
		foreach ( is_array( $meta ) ? $meta : array() as $key => $values ) {
			if ( in_array( $key, self::SKIP_META, true ) || ! is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $copy, $key, wp_slash( is_string( $value ) ? maybe_unserialize( $value ) : $value ) );
			}
		}

		/**
		 * Fires after a post was duplicated.
		 *
		 * @param int      $copy Id of the copy.
		 * @param \WP_Post $post Original.
		 */
		do_action( 'ultra_max_duplicated', $copy, $post );
		return $copy;
	}
}
