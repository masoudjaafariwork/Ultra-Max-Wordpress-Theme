<?php
/**
 * REST routes of the layouts panel.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Elementor\Data;

/**
 * Routes under `ultra-max/v1/layouts`, for people who may change the
 * design of the site.
 *
 * @phpstan-import-type Entry from Layouts
 */
final class Rest {

	/**
	 * Registers the routes.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Permission callback.
	 */
	public static function can_edit(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	/**
	 * Adds the routes.
	 */
	public static function routes(): void {
		$routes = array(
			'/layouts'                           => array(
				array( 'GET', 'list' ),
				array( 'POST', 'create' ),
			),
			'/layouts/values'                    => array( array( 'GET', 'values' ) ),
			'/layouts/(?P<id>\d+)'               => array(
				array( 'POST', 'update' ),
				array( 'DELETE', 'delete' ),
			),
			'/layouts/(?P<id>\d+)/duplicate'     => array( array( 'POST', 'duplicate' ) ),
			'/layouts/library/(?P<kind>[a-z-]+)' => array( array( 'GET', 'library' ) ),
		);
		foreach ( $routes as $path => $endpoints ) {
			$args = array();
			foreach ( $endpoints as [ $method, $callback ] ) {
				$args[] = array(
					'methods'             => $method,
					'callback'            => array( self::class, $callback ),
					'permission_callback' => array( self::class, 'can_edit' ),
				);
			}
			register_rest_route( AdminRest::NAMESPACE, $path, $args );
		}
	}

	/**
	 * A layout with its editing address, for the panel.
	 *
	 * @param array $entry Layout.
	 * @phpstan-param Entry $entry
	 * @return array<string, mixed>
	 */
	public static function row( array $entry ): array {
		return array_merge(
			$entry,
			array(
				'editUrl'            => Data::edit_url( $entry['id'] ),
				'builtWithElementor' => Data::built( $entry['id'] ),
			)
		);
	}

	/**
	 * Every layout, for the panel.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function rows(): array {
		return array_map( array( self::class, 'row' ), Layouts::all() );
	}

	/**
	 * GET /layouts.
	 */
	public static function list(): \WP_REST_Response {
		return new \WP_REST_Response( array( 'layouts' => self::rows() ) );
	}

	/**
	 * The id route parameter.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private static function id( \WP_REST_Request $request ): int {
		$id = $request->get_param( 'id' );
		return is_numeric( $id ) && Layouts::POST_TYPE === get_post_type( (int) $id ) ? (int) $id : 0;
	}

	/**
	 * A "not found" answer.
	 */
	private static function missing(): \WP_Error {
		return new \WP_Error( 'ultra_max_layout_missing', __( 'This layout no longer exists.', 'ultra-max-core' ), array( 'status' => 404 ) );
	}

	/**
	 * POST /layouts: { type, title, template? }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function create( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$type     = $request->get_param( 'type' );
		$title    = $request->get_param( 'title' );
		$template = $request->get_param( 'template' );
		if ( ! is_string( $type ) || ! Types::is_ready( $type ) ) {
			return new \WP_Error( 'ultra_max_layout_type', __( 'Choose what kind of layout to make.', 'ultra-max-core' ), array( 'status' => 400 ) );
		}
		$elements   = null;
		$conditions = null;
		if ( is_string( $template ) && '' !== $template ) {
			$found = Library::get( $template );
			if ( null === $found || $found['kind'] !== $type ) {
				return new \WP_Error( 'ultra_max_layout_template', __( 'This template could not be found.', 'ultra-max-core' ), array( 'status' => 400 ) );
			}
			$elements              = LibraryStrings::translate( $found['content'] );
			$conditions            = Layouts::defaults( $type );
			$conditions['devices'] = $found['devices'];
			if ( ! is_string( $title ) || '' === trim( $title ) ) {
				$title = $found['title'];
			}
		}
		// A blank layout opens straight in Elementor; without Elementor, in the block editor.
		if ( null === $elements && Data::active() ) {
			$elements = array();
		}
		$id = Layouts::create( $type, is_string( $title ) && '' !== trim( $title ) ? $title : Types::all()[ $type ]['label'], $elements, $conditions );
		if ( is_wp_error( $id ) ) {
			$id->add_data( array( 'status' => 400 ) );
			return $id;
		}
		$post = get_post( $id );
		return new \WP_REST_Response(
			array(
				'layout'  => $post instanceof \WP_Post ? self::row( Layouts::entry( $post ) ) : null,
				'layouts' => self::rows(),
			)
		);
	}

	/**
	 * POST /layouts/{id}: { title?, active?, conditions? }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function update( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = self::id( $request );
		if ( 0 === $id ) {
			return self::missing();
		}
		$changes = array( 'ID' => $id );
		$title   = $request->get_param( 'title' );
		if ( is_string( $title ) && '' !== trim( $title ) ) {
			$changes['post_title'] = sanitize_text_field( $title );
		}
		$active = $request->get_param( 'active' );
		if ( is_bool( $active ) ) {
			$changes['post_status'] = $active ? 'publish' : 'draft';
		}
		$conditions = $request->get_param( 'conditions' );
		if ( null !== $conditions ) {
			update_post_meta( $id, Layouts::RULES_META, Layouts::sanitize( $conditions, Layouts::type( $id ) ) );
		}
		$result = wp_update_post( $changes, true );
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}
		Layouts::rebuild();
		return self::list();
	}

	/**
	 * DELETE /layouts/{id}: moves a layout to the trash.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function delete( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = self::id( $request );
		if ( 0 === $id ) {
			return self::missing();
		}
		wp_trash_post( $id );
		Layouts::rebuild();
		return self::list();
	}

	/**
	 * POST /layouts/{id}/duplicate: a draft copy with the same design.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function duplicate( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id   = self::id( $request );
		$post = get_post( $id );
		if ( 0 === $id || ! $post instanceof \WP_Post ) {
			return self::missing();
		}
		$copy = wp_insert_post(
			array(
				'post_type'    => Layouts::POST_TYPE,
				'post_status'  => 'draft',
				/* translators: %s: layout name. */
				'post_title'   => sprintf( __( '%s (copy)', 'ultra-max-core' ), $post->post_title ),
				'post_content' => $post->post_content,
				'meta_input'   => array(
					Layouts::TYPE_META  => Layouts::type( $id ),
					Layouts::RULES_META => Layouts::conditions( $id ),
				),
			),
			true
		);
		if ( is_wp_error( $copy ) ) {
			return $copy;
		}
		$data = get_post_meta( $id, '_elementor_data', true );
		if ( is_string( $data ) && '' !== $data ) {
			$elements = json_decode( $data, true );
			Data::import( $copy, is_array( $elements ) ? $elements : array() );
		}
		Layouts::rebuild();
		return self::list();
	}

	/**
	 * GET /layouts/values?target=&search=&include=: choices for a rule.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function values( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$target  = $request->get_param( 'target' );
		$search  = $request->get_param( 'search' );
		$include = $request->get_param( 'include' );
		$targets = Rules::targets();
		if ( ! is_string( $target ) || ! isset( $targets[ $target ] ) ) {
			return new \WP_Error( 'ultra_max_layout_target', __( 'Unknown condition.', 'ultra-max-core' ), array( 'status' => 400 ) );
		}
		$search = is_string( $search ) ? sanitize_text_field( $search ) : '';
		$ids    = is_string( $include ) && '' !== $include ? array_filter( array_map( 'intval', explode( ',', $include ) ) ) : array();
		return new \WP_REST_Response( array( 'options' => self::choices( $targets[ $target ], $search, array_values( $ids ) ) ) );
	}

	/**
	 * Choices of one target.
	 *
	 * @param array{values: string, taxonomy?: string} $target Target.
	 * @param string                                   $search Search words.
	 * @param array                                    $ids    Only these ids, to name saved values.
	 * @phpstan-param list<int> $ids
	 * @return list<array{value: string, label: string}>
	 */
	public static function choices( array $target, string $search, array $ids ): array {
		$out = array();
		if ( 'product_type' === $target['values'] ) {
			foreach ( Rules::product_types() as $value => $label ) {
				if ( '' === $search || false !== mb_stripos( $label, $search ) ) {
					$out[] = array(
						'value' => (string) $value,
						'label' => $label,
					);
				}
			}
			return $out;
		}
		if ( 'term' === $target['values'] && isset( $target['taxonomy'] ) ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $target['taxonomy'],
					'hide_empty' => false,
					'number'     => 50,
					'search'     => $search,
					'include'    => $ids,
				)
			);
			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				if ( $term instanceof \WP_Term ) {
					$out[] = array(
						'value' => (string) $term->term_id,
						'label' => $term->name,
					);
				}
			}
			return $out;
		}
		if ( in_array( $target['values'], array( 'post', 'page', 'product' ), true ) ) {
			$posts = get_posts(
				array(
					'post_type'      => $target['values'],
					'post_status'    => array( 'publish', 'private', 'draft' ),
					'posts_per_page' => 30,
					's'              => $search,
					'include'        => $ids,
					'orderby'        => '' === $search ? 'date' : 'relevance',
				)
			);
			foreach ( $posts as $post ) {
				if ( $post instanceof \WP_Post ) {
					$out[] = array(
						'value' => (string) $post->ID,
						'label' => '' === $post->post_title ? '#' . $post->ID : $post->post_title,
					);
				}
			}
		}
		return $out;
	}

	/**
	 * GET /layouts/library/{kind}: templates of one kind.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function library( \WP_REST_Request $request ): \WP_REST_Response {
		$kind = $request->get_param( 'kind' );
		return new \WP_REST_Response( array( 'templates' => Library::all( is_string( $kind ) ? $kind : null ) ) );
	}
}
