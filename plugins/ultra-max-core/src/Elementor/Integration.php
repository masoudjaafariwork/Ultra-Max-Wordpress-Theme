<?php
/**
 * Connects Ultra Max to Elementor (BLD-01, BLD-10, BLD-11, HOME-13).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Builder\Library;
use UltraMax\Core\Builder\LibraryStrings;
use UltraMax\Core\Lib\Foundation\Assets as Registrar;
use UltraMax\Core\Lib\Foundation\Brand;
use UltraMax\Core\Widgets\Widgets;

/**
 * Registers our widget groups, widgets, search control, dynamic tags and
 * the editor scripts. Everything here works with Elementor Free alone;
 * nothing needs Elementor Pro.
 */
final class Integration {

	public const CATEGORY_PREFIX = 'ultra-max-';

	/**
	 * Hooks in once Elementor has loaded.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
		if ( did_action( 'elementor/loaded' ) ) {
			self::hooks();
		} else {
			add_action( 'elementor/loaded', array( self::class, 'hooks' ) );
		}
	}

	/**
	 * The Elementor hooks.
	 */
	public static function hooks(): void {
		if ( Widgets::enabled() ) {
			add_action( 'elementor/elements/categories_registered', array( self::class, 'categories' ) );
			add_action( 'elementor/widgets/register', array( self::class, 'widgets' ) );
			add_action( 'elementor/controls/register', array( self::class, 'controls' ) );
			add_action( 'elementor/editor/after_enqueue_scripts', array( self::class, 'editor' ) );
			add_action( 'elementor/preview/enqueue_scripts', array( self::class, 'preview' ) );
		}
		DynamicTags::register();
		Kit::register();
	}

	/**
	 * Adds our groups to the widget panel.
	 *
	 * @param mixed $manager Elements manager.
	 */
	public static function categories( mixed $manager ): void {
		if ( ! is_object( $manager ) || ! method_exists( $manager, 'add_category' ) ) {
			return;
		}
		$name   = Brand::name();
		$groups = array(
			/* translators: %s: product name. */
			'header'  => sprintf( __( '%s: header and footer', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'store'   => sprintf( __( '%s: store', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'shop'    => sprintf( __( '%s: product lists', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'product' => sprintf( __( '%s: product', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'account' => sprintf( __( '%s: customer account', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'general' => sprintf( __( '%s: general', 'ultra-max-core' ), $name ),
			/* translators: %s: product name. */
			'footer'  => sprintf( __( '%s: footer', 'ultra-max-core' ), $name ),
		);
		foreach ( $groups as $id => $title ) {
			$manager->add_category(
				self::CATEGORY_PREFIX . $id,
				array(
					'title' => $title,
					'icon'  => 'eicon-apps',
				)
			);
		}
	}

	/**
	 * Registers every widget through the one adapter class.
	 *
	 * @param mixed $manager Widgets manager.
	 */
	public static function widgets( mixed $manager ): void {
		if ( ! $manager instanceof \Elementor\Widgets_Manager ) {
			return;
		}
		foreach ( Widgets::all() as $id => $widget ) {
			$manager->register( new Widget( array(), array( 'um_widget' => $id ) ) );
		}
	}

	/**
	 * Registers the search control.
	 *
	 * @param mixed $manager Controls manager.
	 */
	public static function controls( mixed $manager ): void {
		if ( $manager instanceof \Elementor\Controls_Manager ) {
			$manager->register( new SearchControl() );
		}
	}

	/**
	 * A registrar for this plugin's build folder.
	 */
	private static function registrar(): Registrar {
		return new Registrar( plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build', plugins_url( 'assets/build', ULTRA_MAX_CORE_FILE ) );
	}

	/**
	 * The editor panel script: the search control and the template
	 * library button (HOME-13).
	 */
	public static function editor(): void {
		$registrar = self::registrar();
		if ( ! $registrar->register_script( 'ultra-max-elementor-editor', 'core/elementor-editor' ) ) {
			return;
		}
		$registrar->register_style( 'ultra-max-elementor-editor', 'core/elementor-editor' );
		wp_add_inline_script(
			'ultra-max-elementor-editor',
			'window.ultraMaxElementor = ' . wp_json_encode(
				array(
					'rest'    => array(
						'root'  => esc_url_raw( rest_url( AdminRest::NAMESPACE . '/' ) ),
						'nonce' => wp_create_nonce( 'wp_rest' ),
					),
					'brand'   => Brand::name(),
					'strings' => array(
						'library'  => __( 'Ready-made designs', 'ultra-max-core' ),
						'sections' => __( 'Sections', 'ultra-max-core' ),
						'pages'    => __( 'Pages', 'ultra-max-core' ),
						'insert'   => __( 'Insert', 'ultra-max-core' ),
						'close'    => __( 'Close', 'ultra-max-core' ),
						'loading'  => __( 'Loading…', 'ultra-max-core' ),
						'empty'    => __( 'Nothing here yet.', 'ultra-max-core' ),
						'search'   => __( 'Type to search', 'ultra-max-core' ),
						'inserted' => __( 'The design was added to the page.', 'ultra-max-core' ),
						'error'    => __( 'Something went wrong. Please try again.', 'ultra-max-core' ),
					),
				)
			) . ';',
			'before'
		);
		wp_enqueue_script( 'ultra-max-elementor-editor' );
		wp_enqueue_style( 'ultra-max-elementor-editor' );
	}

	/**
	 * The preview script: mounts our component behaviours on markup that
	 * the editor draws again after every change.
	 */
	public static function preview(): void {
		$registrar = self::registrar();
		if ( $registrar->register_script( 'ultra-max-elementor-preview', 'core/elementor-preview', array( 'ultra-max-ui' ) ) ) {
			wp_enqueue_script( 'ultra-max-elementor-preview' );
		}
	}

	/**
	 * REST routes the editor uses.
	 */
	public static function routes(): void {
		register_rest_route(
			AdminRest::NAMESPACE,
			'/elementor/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'search' ),
				'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
		register_rest_route(
			AdminRest::NAMESPACE,
			'/elementor/library',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'library' ),
				'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
		register_rest_route(
			AdminRest::NAMESPACE,
			'/elementor/library/(?P<kind>[a-z-]+)/(?P<name>[a-z0-9-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'template' ),
				'permission_callback' => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * GET /elementor/search?source=&q=&include=: posts or terms by name.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function search( \WP_REST_Request $request ): \WP_REST_Response {
		$source  = $request->get_param( 'source' );
		$query   = $request->get_param( 'q' );
		$include = $request->get_param( 'include' );
		$source  = is_string( $source ) ? $source : 'product';
		$query   = is_string( $query ) ? sanitize_text_field( $query ) : '';
		$ids     = is_string( $include ) && '' !== $include ? array_values( array_filter( array_map( 'intval', explode( ',', $include ) ) ) ) : array();
		$out     = array();
		if ( str_starts_with( $source, 'tax:' ) ) {
			$taxonomy = substr( $source, 4 );
			$terms    = taxonomy_exists( $taxonomy ) ? get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'search'     => $query,
					'include'    => $ids,
					'number'     => 40,
				)
			) : array();
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				if ( $term instanceof \WP_Term ) {
					$out[] = array(
						'id'   => (string) $term->term_id,
						'text' => $term->name,
					);
				}
			}
		} elseif ( post_type_exists( $source ) ) {
			$posts = get_posts(
				array(
					'post_type'      => $source,
					'post_status'    => 'publish',
					's'              => $query,
					'include'        => $ids,
					'posts_per_page' => 40,
				)
			);
			foreach ( $posts as $post ) {
				if ( $post instanceof \WP_Post ) {
					$out[] = array(
						'id'   => (string) $post->ID,
						'text' => '' === $post->post_title ? '#' . $post->ID : $post->post_title,
					);
				}
			}
		}
		return new \WP_REST_Response( array( 'results' => $out ) );
	}

	/**
	 * GET /elementor/library: sections and pages for the editor (HOME-13).
	 */
	public static function library(): \WP_REST_Response {
		return new \WP_REST_Response(
			array(
				'sections' => Library::all( 'section' ),
				'pages'    => Library::all( 'page' ),
			)
		);
	}

	/**
	 * GET /elementor/library/{kind}/{name}: one template's elements, translated.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function template( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$kind     = $request->get_param( 'kind' );
		$name     = $request->get_param( 'name' );
		$template = is_string( $kind ) && is_string( $name ) ? Library::get( $kind . '/' . $name ) : null;
		if ( null === $template ) {
			return new \WP_Error( 'ultra_max_template_missing', __( 'This template could not be found.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		return new \WP_REST_Response( array( 'content' => Data::fresh_ids( LibraryStrings::translate( $template['content'] ) ) ) );
	}
}
