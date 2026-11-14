<?php
/**
 * Layouts: the post type, their conditions and the index (BLD-01, BLD-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

use UltraMax\Core\Elementor\Data;

/**
 * A layout is a post of the `um_layout` type, designed with Elementor (or
 * the block editor when Elementor is not active). Its type and display
 * conditions live in post meta.
 *
 * The storefront never queries layouts: every published layout is copied
 * into one autoloaded option (the index) whenever a layout changes, so
 * choosing the header of a page costs no database query.
 *
 * @phpstan-import-type Rule from Rules
 * @phpstan-type Devices array{desktop: bool, tablet: bool, mobile: bool}
 * @phpstan-type Conditions array{include: list<Rule>, exclude: list<Rule>, roles: list<string>, devices: Devices, priority: int, sticky: 'none'|'sticky'|'smart'}
 * @phpstan-type Entry array{id: int, type: string, title: string, status: string, conditions: Conditions, modified: int}
 */
final class Layouts {

	public const POST_TYPE  = 'um_layout';
	public const TYPE_META  = '_um_layout_type';
	public const RULES_META = '_um_layout_conditions';
	public const INDEX      = 'ultra_max_layouts';
	public const STICKY     = array( 'none', 'sticky', 'smart' );

	/**
	 * Registers the post type and the hooks that keep the index fresh.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'post_type' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( self::class, 'rebuild' ) );
		add_action( 'deleted_post', array( self::class, 'deleted' ), 10, 2 );
	}

	/**
	 * Registers the post type. Only people who may change the design of the
	 * site can see or edit layouts.
	 */
	public static function post_type(): void {
		$cap = 'edit_theme_options';
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Layouts', 'ultra-max-core' ),
					'singular_name' => __( 'Layout', 'ultra-max-core' ),
					'add_new_item'  => __( 'Add a layout', 'ultra-max-core' ),
					'edit_item'     => __( 'Edit layout', 'ultra-max-core' ),
				),
				'public'              => false,
				// Elementor previews a layout at its own address while it is edited.
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'rewrite'             => false,
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'revisions', 'elementor' ),
				'map_meta_cap'        => true,
				'capabilities'        => array(
					'create_posts'           => $cap,
					'delete_posts'           => $cap,
					'delete_others_posts'    => $cap,
					'delete_private_posts'   => $cap,
					'delete_published_posts' => $cap,
					'edit_posts'             => $cap,
					'edit_others_posts'      => $cap,
					'edit_private_posts'     => $cap,
					'edit_published_posts'   => $cap,
					'publish_posts'          => $cap,
					'read'                   => $cap,
					'read_private_posts'     => $cap,
				),
			)
		);
	}

	/**
	 * Default conditions of a new layout: headers and footers show on the
	 * whole site, shop list layouts on every product list, product layouts on
	 * every product, blocks and cards only where they are placed.
	 *
	 * @param string $type Layout type.
	 * @return Conditions
	 */
	public static function defaults( string $type ): array {
		$include = match ( true ) {
			in_array( $type, array( 'header', 'footer', 'shop-archive' ), true ) => array( array( 'target' => 'all' ) ),
			'single-product' === $type => array( array( 'target' => 'products' ) ),
			default => array(),
		};
		return array(
			'include'  => $include,
			'exclude'  => array(),
			'roles'    => array(),
			'devices'  => array(
				'desktop' => true,
				'tablet'  => true,
				'mobile'  => true,
			),
			'priority' => 10,
			'sticky'   => 'header' === $type ? 'smart' : 'none',
		);
	}

	/**
	 * Cleans submitted conditions.
	 *
	 * @param mixed  $value Submitted conditions.
	 * @param string $type  Layout type, for the defaults.
	 * @return Conditions
	 */
	public static function sanitize( mixed $value, string $type ): array {
		$defaults = self::defaults( $type );
		if ( ! is_array( $value ) ) {
			return $defaults;
		}
		$roles = array();
		foreach ( is_array( $value['roles'] ?? null ) ? $value['roles'] : array() as $role ) {
			if ( is_string( $role ) && '' !== sanitize_key( $role ) ) {
				$roles[] = sanitize_key( $role );
			}
		}
		$devices = $defaults['devices'];
		if ( is_array( $value['devices'] ?? null ) ) {
			foreach ( array_keys( $devices ) as $device ) {
				$devices[ $device ] = (bool) ( $value['devices'][ $device ] ?? true );
			}
		}
		$priority = is_numeric( $value['priority'] ?? null ) ? (int) $value['priority'] : $defaults['priority'];
		$sticky   = $value['sticky'] ?? $defaults['sticky'];
		return array(
			'include'  => array_key_exists( 'include', $value ) ? Rules::sanitize( $value['include'] ) : $defaults['include'],
			'exclude'  => Rules::sanitize( $value['exclude'] ?? array() ),
			'roles'    => array_values( array_unique( $roles ) ),
			'devices'  => $devices,
			'priority' => max( 0, min( 100, $priority ) ),
			'sticky'   => in_array( $sticky, self::STICKY, true ) ? $sticky : 'none',
		);
	}

	/**
	 * The type of a layout, or an empty string.
	 *
	 * @param int $id Layout id.
	 */
	public static function type( int $id ): string {
		$type = get_post_meta( $id, self::TYPE_META, true );
		return is_string( $type ) ? $type : '';
	}

	/**
	 * The conditions of a layout.
	 *
	 * @param int $id Layout id.
	 * @return Conditions
	 */
	public static function conditions( int $id ): array {
		return self::sanitize( get_post_meta( $id, self::RULES_META, true ), self::type( $id ) );
	}

	/**
	 * A layout as the panel and the index see it.
	 *
	 * @param \WP_Post $post Layout.
	 * @return Entry
	 */
	public static function entry( \WP_Post $post ): array {
		return array(
			'id'         => $post->ID,
			'type'       => self::type( $post->ID ),
			'title'      => '' === $post->post_title ? __( '(no title)', 'ultra-max-core' ) : $post->post_title,
			'status'     => $post->post_status,
			'conditions' => self::conditions( $post->ID ),
			'modified'   => (int) get_post_modified_time( 'U', true, $post ),
		);
	}

	/**
	 * Every layout that is not in the trash, newest first.
	 *
	 * @return list<Entry>
	 */
	public static function all(): array {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);
		$out   = array();
		foreach ( $posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$out[] = self::entry( $post );
			}
		}
		return $out;
	}

	/**
	 * Creates a layout.
	 *
	 * @param string            $type     Layout type.
	 * @param string            $title    Name.
	 * @param array<mixed>|null $elements Elementor elements to start from, or null for an empty layout.
	 * @param Conditions|null   $conditions Conditions, or the type's defaults.
	 * @return int|\WP_Error New layout id.
	 */
	public static function create( string $type, string $title, ?array $elements = null, ?array $conditions = null ): int|\WP_Error {
		if ( ! Types::is_ready( $type ) ) {
			return new \WP_Error( 'ultra_max_layout_type', __( 'This kind of layout is not available yet.', 'ultra-max-core' ) );
		}
		$id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $title ),
				'meta_input'  => array(
					self::TYPE_META  => $type,
					self::RULES_META => $conditions ?? self::defaults( $type ),
				),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		if ( null !== $elements ) {
			Data::import( $id, $elements );
		}
		self::rebuild();
		return $id;
	}

	/**
	 * Keeps the index fresh after a layout is deleted.
	 *
	 * @param mixed $id   Post id.
	 * @param mixed $post Post.
	 */
	public static function deleted( mixed $id, mixed $post = null ): void {
		if ( $post instanceof \WP_Post && self::POST_TYPE === $post->post_type ) {
			self::rebuild();
		}
	}

	/**
	 * Copies every published layout into the index option.
	 */
	public static function rebuild(): void {
		$index = array();
		foreach ( self::all() as $entry ) {
			if ( 'publish' === $entry['status'] && Types::is_ready( $entry['type'] ) ) {
				$index[] = $entry;
			}
		}
		update_option( self::INDEX, $index, true );
	}

	/**
	 * Published layouts from the index.
	 *
	 * @return list<Entry>
	 */
	public static function index(): array {
		$index = get_option( self::INDEX, array() );
		$out   = array();
		foreach ( is_array( $index ) ? $index : array() as $entry ) {
			if ( is_array( $entry ) && is_int( $entry['id'] ?? null ) && is_string( $entry['type'] ?? null ) ) {
				$out[] = array(
					'id'         => $entry['id'],
					'type'       => $entry['type'],
					'title'      => is_string( $entry['title'] ?? null ) ? $entry['title'] : '',
					'status'     => 'publish',
					'conditions' => self::sanitize( $entry['conditions'] ?? null, $entry['type'] ),
					'modified'   => is_int( $entry['modified'] ?? null ) ? $entry['modified'] : 0,
				);
			}
		}
		return $out;
	}
}
