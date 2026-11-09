<?php
/**
 * On and off switches of every module (ADM-07).
 *
 * @package UltraMax\Settings
 */

declare(strict_types=1);

namespace UltraMax\Settings;

/**
 * Every product declares its modules through the `ultra_max_modules`
 * filter. A disabled module must load no CSS or JS and run no queries:
 * each module checks `Modules::enabled()` before it hooks anything.
 *
 * @phpstan-type Module array{
 *     id: string,
 *     title: string,
 *     description: string,
 *     icon: string,
 *     features: list<string>,
 *     default: bool,
 *     section?: string,
 *     assets?: list<string>,
 *     product: string,
 * }
 */
final class Modules {

	public const OPTION = 'ultra_max_modules';
	public const FILTER = 'ultra_max_modules';

	/**
	 * Every declared module, keyed by id.
	 *
	 * @return array<string, Module>
	 */
	public static function all(): array {
		/**
		 * Filters the declared modules.
		 *
		 * @param array<string, array<string, mixed>> $modules Modules keyed by id.
		 */
		$raw     = apply_filters( self::FILTER, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The constant is ultra_max_modules.
		$modules = array();
		foreach ( is_array( $raw ) ? $raw : array() as $id => $module ) {
			if ( ! is_string( $id ) || ! is_array( $module ) ) {
				continue;
			}
			$features = array();
			foreach ( is_array( $module['features'] ?? null ) ? $module['features'] : array() as $feature ) {
				if ( is_string( $feature ) ) {
					$features[] = $feature;
				}
			}
			$assets = array();
			foreach ( is_array( $module['assets'] ?? null ) ? $module['assets'] : array() as $asset ) {
				if ( is_string( $asset ) ) {
					$assets[] = $asset;
				}
			}
			$entry = array(
				'id'          => $id,
				'title'       => is_string( $module['title'] ?? null ) ? $module['title'] : $id,
				'description' => is_string( $module['description'] ?? null ) ? $module['description'] : '',
				'icon'        => is_string( $module['icon'] ?? null ) ? $module['icon'] : 'puzzle',
				'features'    => $features,
				'default'     => (bool) ( $module['default'] ?? true ),
				'assets'      => $assets,
				'product'     => is_string( $module['product'] ?? null ) ? $module['product'] : '',
			);
			if ( is_string( $module['section'] ?? null ) ) {
				$entry['section'] = $module['section'];
			}
			$modules[ $id ] = $entry;
		}
		return $modules;
	}

	/**
	 * Stored switches.
	 *
	 * @return array<string, bool>
	 */
	public static function states(): array {
		$stored = get_option( self::OPTION, array() );
		$states = array();
		foreach ( is_array( $stored ) ? $stored : array() as $id => $on ) {
			if ( is_string( $id ) ) {
				$states[ $id ] = (bool) $on;
			}
		}
		return $states;
	}

	/**
	 * Whether a module is on.
	 *
	 * @param string $id       Module id.
	 * @param bool   $fallback Value when the module was never switched.
	 */
	public static function enabled( string $id, bool $fallback = true ): bool {
		$states = self::states();
		return $states[ $id ] ?? $fallback;
	}

	/**
	 * Switches a module on or off.
	 *
	 * @param string $id Module id.
	 * @param bool   $on New state.
	 */
	public static function set( string $id, bool $on ): void {
		$states        = self::states();
		$states[ $id ] = $on;
		update_option( self::OPTION, $states, true );
		/**
		 * Fires after a module was switched.
		 *
		 * @param string $id Module id.
		 * @param bool   $on New state.
		 */
		do_action( 'ultra_max_module_switched', $id, $on );
	}
}
