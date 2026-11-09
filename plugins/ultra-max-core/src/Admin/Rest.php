<?php
/**
 * REST routes of the settings panel.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

use UltraMax\Core\Lib\Settings\Modules;
use UltraMax\Core\Lib\Settings\Store;

/**
 * Routes under `ultra-max/v1`. Every route checks a capability; WordPress
 * checks the REST nonce the panel sends.
 */
final class Rest {

	public const NAMESPACE = 'ultra-max/v1';

	/**
	 * Registers the routes.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Permission callback: can change the theme options.
	 */
	public static function can_edit(): bool {
		return current_user_can( 'edit_theme_options' );
	}

	/**
	 * Permission callback: can manage the site.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Adds the routes.
	 */
	public static function routes(): void {
		$routes = array(
			'/settings'                 => array(
				array( 'GET', 'get_settings', 'can_edit' ),
				array( 'POST', 'save_settings', 'can_edit' ),
			),
			'/settings/reset'           => array( array( 'POST', 'reset_settings', 'can_edit' ) ),
			'/settings/export'          => array( array( 'GET', 'export_settings', 'can_edit' ) ),
			'/settings/import'          => array( array( 'POST', 'import_settings', 'can_edit' ) ),
			'/settings/backups'         => array(
				array( 'GET', 'backups', 'can_edit' ),
				array( 'POST', 'create_backup', 'can_edit' ),
			),
			'/settings/backups/restore' => array( array( 'POST', 'restore_backup', 'can_edit' ) ),
			'/modules'                  => array(
				array( 'GET', 'modules', 'can_manage' ),
				array( 'POST', 'switch_module', 'can_manage' ),
			),
			'/status'                   => array( array( 'GET', 'status', 'can_manage' ) ),
			'/wizard/plugin'            => array( array( 'POST', 'wizard_plugin', 'can_manage' ) ),
			'/wizard/brand'             => array( array( 'POST', 'wizard_brand', 'can_manage' ) ),
			'/wizard/finish'            => array( array( 'POST', 'wizard_finish', 'can_manage' ) ),
			'/tour'                     => array( array( 'POST', 'tour_seen', 'can_edit' ) ),
		);
		foreach ( $routes as $path => $endpoints ) {
			$args = array();
			foreach ( $endpoints as [ $method, $callback, $permission ] ) {
				$args[] = array(
					'methods'             => $method,
					'callback'            => array( self::class, $callback ),
					'permission_callback' => array( self::class, $permission ),
				);
			}
			register_rest_route( self::NAMESPACE, $path, $args );
		}
	}

	/**
	 * Current values, after a save or reset.
	 */
	private static function values(): \WP_REST_Response {
		return new \WP_REST_Response( array( 'values' => Store::all() ) );
	}

	/**
	 * GET /settings.
	 */
	public static function get_settings(): \WP_REST_Response {
		return self::values();
	}

	/**
	 * POST /settings: { values: { id: value } }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$values = $request->get_param( 'values' );
		$clean  = array();
		foreach ( is_array( $values ) ? $values : array() as $key => $value ) {
			if ( is_string( $key ) ) {
				$clean[ $key ] = $value;
			}
		}
		$result = Store::save( $clean );
		return new \WP_REST_Response(
			array(
				'values'   => Store::all(),
				'saved'    => $result['saved'],
				'rejected' => $result['rejected'],
			)
		);
	}

	/**
	 * POST /settings/reset: { section?: string }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function reset_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$section = $request->get_param( 'section' );
		Store::reset( is_string( $section ) && '' !== $section ? $section : null );
		return self::values();
	}

	/**
	 * GET /settings/export.
	 */
	public static function export_settings(): \WP_REST_Response {
		return new \WP_REST_Response( Store::export() );
	}

	/**
	 * POST /settings/import: { document: object }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function import_settings( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$result = Store::import( $request->get_param( 'document' ) );
		if ( null === $result ) {
			return new \WP_Error( 'invalid_file', __( 'This file is not an Ultra Max settings export.', 'ultra-max-core' ), array( 'status' => 400 ) );
		}
		return new \WP_REST_Response(
			array(
				'values'   => Store::all(),
				'rejected' => $result['rejected'],
			)
		);
	}

	/**
	 * Backups without their values, for the list.
	 *
	 * @return list<array{id: string, label: string, created: int, count: int}>
	 */
	public static function backup_list(): array {
		return array_map(
			static fn ( array $backup ): array => array(
				'id'      => $backup['id'],
				'label'   => $backup['label'],
				'created' => $backup['created'],
				'count'   => count( $backup['settings'] ),
			),
			Store::backups()
		);
	}

	/**
	 * GET /settings/backups.
	 */
	public static function backups(): \WP_REST_Response {
		return new \WP_REST_Response( array( 'backups' => self::backup_list() ) );
	}

	/**
	 * POST /settings/backups: takes a backup now.
	 */
	public static function create_backup(): \WP_REST_Response {
		Store::backup( __( 'Taken by hand', 'ultra-max-core' ) );
		return self::backups();
	}

	/**
	 * POST /settings/backups/restore: { id: string }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function restore_backup( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = $request->get_param( 'id' );
		if ( ! is_string( $id ) || ! Store::restore( $id ) ) {
			return new \WP_Error( 'not_found', __( 'That backup no longer exists.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		return new \WP_REST_Response(
			array(
				'values'  => Store::all(),
				'backups' => self::backup_list(),
			)
		);
	}

	/**
	 * GET /modules.
	 */
	public static function modules(): \WP_REST_Response {
		return new \WP_REST_Response( array( 'modules' => Admin::modules() ) );
	}

	/**
	 * POST /modules: { id: string, on: bool }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function switch_module( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id = $request->get_param( 'id' );
		if ( ! is_string( $id ) || ! array_key_exists( $id, Modules::all() ) ) {
			return new \WP_Error( 'unknown_module', __( 'Unknown module.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		Modules::set( $id, (bool) $request->get_param( 'on' ) );
		return self::modules();
	}

	/**
	 * GET /status.
	 */
	public static function status(): \WP_REST_Response {
		$checks = SystemStatus::checks();
		return new \WP_REST_Response(
			array(
				'checks' => $checks,
				'report' => SystemStatus::report( $checks ),
			)
		);
	}

	/**
	 * POST /wizard/plugin: { slug: string }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function wizard_plugin( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$slug   = $request->get_param( 'slug' );
		$result = Wizard::install( is_string( $slug ) ? $slug : '' );
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}
		return new \WP_REST_Response( array( 'plugins' => Wizard::plugins() ) );
	}

	/**
	 * POST /wizard/brand: { name, logo, preset, color }.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function wizard_brand( \WP_REST_Request $request ): \WP_REST_Response {
		$name   = $request->get_param( 'name' );
		$logo   = $request->get_param( 'logo' );
		$preset = $request->get_param( 'preset' );
		$color  = $request->get_param( 'color' );
		Wizard::brand(
			is_string( $name ) ? $name : '',
			is_numeric( $logo ) ? (int) $logo : 0,
			is_string( $preset ) ? $preset : 'indigo',
			is_string( $color ) ? $color : ''
		);
		return self::values();
	}

	/**
	 * POST /wizard/finish.
	 */
	public static function wizard_finish(): \WP_REST_Response {
		Wizard::finish();
		return new \WP_REST_Response( array( 'done' => true ) );
	}

	/**
	 * POST /tour: { page: string } marks a page's tour as seen.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function tour_seen( \WP_REST_Request $request ): \WP_REST_Response {
		$page = $request->get_param( 'page' );
		$seen = get_user_meta( get_current_user_id(), Admin::TOUR_META, true );
		$seen = is_array( $seen ) ? array_filter( $seen, 'is_string' ) : array();
		if ( is_string( $page ) && '' !== $page ) {
			$seen[] = sanitize_key( $page );
		}
		update_user_meta( get_current_user_id(), Admin::TOUR_META, array_values( array_unique( $seen ) ) );
		return new \WP_REST_Response( array( 'seen' => $seen ) );
	}
}
