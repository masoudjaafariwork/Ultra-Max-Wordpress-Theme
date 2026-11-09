<?php
/**
 * Reads and writes setting values, with backups.
 *
 * @package UltraMax\Settings
 */

declare(strict_types=1);

namespace UltraMax\Settings;

/**
 * All settings of every product live in one autoloaded option, so the
 * front end reads them with no extra query. Values that equal the default
 * are not stored, which keeps the option small and lets a new default
 * reach every site that never changed that setting.
 *
 * Backups (ADM-02) live in a separate option that is not autoloaded.
 */
final class Store {

	public const OPTION      = 'ultra_max_settings';
	public const BACKUPS     = 'ultra_max_settings_backups';
	public const MAX_BACKUPS = 10;
	public const FORMAT      = 'ultra-max-settings';

	/**
	 * The stored values that differ from the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function stored(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}
		$clean = array();
		foreach ( $stored as $key => $value ) {
			if ( is_string( $key ) ) {
				$clean[ $key ] = $value;
			}
		}
		return $clean;
	}

	/**
	 * Every value, stored or default.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		return array_merge( Schema::defaults(), array_intersect_key( self::stored(), Schema::defaults() ) );
	}

	/**
	 * One value.
	 *
	 * @param string $key      Field id.
	 * @param mixed  $fallback Value when the field is unknown.
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$stored = self::stored();
		if ( array_key_exists( $key, $stored ) ) {
			return $stored[ $key ];
		}
		$defaults = Schema::defaults();
		return array_key_exists( $key, $defaults ) ? $defaults[ $key ] : $fallback;
	}

	/**
	 * A string value.
	 *
	 * @param string $key Field id.
	 */
	public static function string( string $key ): string {
		$value = self::get( $key, '' );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * A boolean value.
	 *
	 * @param string $key Field id.
	 */
	public static function bool( string $key ): bool {
		return true === self::get( $key, false );
	}

	/**
	 * A whole-number value.
	 *
	 * @param string $key Field id.
	 */
	public static function int( string $key ): int {
		$value = self::get( $key, 0 );
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Device visibility of a `devices` field.
	 *
	 * @param string $key Field id.
	 * @return array{desktop: bool, tablet: bool, mobile: bool}
	 */
	public static function devices( string $key ): array {
		$value = self::get( $key, array() );
		$value = is_array( $value ) ? $value : array();
		return array(
			'desktop' => (bool) ( $value['desktop'] ?? true ),
			'tablet'  => (bool) ( $value['tablet'] ?? true ),
			'mobile'  => (bool) ( $value['mobile'] ?? true ),
		);
	}

	/**
	 * Rows of a `repeater` field.
	 *
	 * @param string $key Field id.
	 * @return list<array<string, mixed>>
	 */
	public static function rows( string $key ): array {
		$value = self::get( $key, array() );
		$rows  = array();
		foreach ( is_array( $value ) ? $value : array() as $row ) {
			if ( is_array( $row ) ) {
				$rows[] = array_filter( $row, 'is_string', ARRAY_FILTER_USE_KEY );
			}
		}
		return $rows;
	}

	/**
	 * Saves submitted values. Unknown keys are ignored and invalid values
	 * keep their previous value.
	 *
	 * @param array $values Submitted values keyed by field id.
	 * @phpstan-param array<string, mixed> $values
	 * @return array{saved: list<string>, rejected: list<string>} Field ids.
	 */
	public static function save( array $values ): array {
		$fields   = Schema::fields();
		$defaults = Schema::defaults();
		$stored   = self::stored();
		$saved    = array();
		$rejected = array();
		foreach ( $values as $key => $value ) {
			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}
			$clean = Sanitizer::sanitize( $fields[ $key ], $value );
			if ( null === $clean ) {
				$rejected[] = $key;
				continue;
			}
			if ( array_key_exists( $key, $defaults ) && $clean === $defaults[ $key ] ) {
				unset( $stored[ $key ] );
			} else {
				$stored[ $key ] = $clean;
			}
			$saved[] = $key;
		}
		self::write( $stored );
		return array(
			'saved'    => $saved,
			'rejected' => $rejected,
		);
	}

	/**
	 * Restores defaults, for one section or for everything. Takes a backup first.
	 *
	 * @param string|null $section_id Section id, or null for all settings.
	 */
	public static function reset( ?string $section_id = null ): void {
		self::backup( null === $section_id ? __( 'Before resetting all settings', 'ultra-max-settings' ) : __( 'Before resetting a section', 'ultra-max-settings' ) );
		if ( null === $section_id ) {
			self::write( array() );
			return;
		}
		$stored = self::stored();
		foreach ( Schema::section_fields( $section_id ) as $key ) {
			unset( $stored[ $key ] );
		}
		self::write( $stored );
	}

	/**
	 * Every value as an export document.
	 *
	 * @return array{format: string, version: int, site: string, created: int, settings: array<string, mixed>}
	 */
	public static function export(): array {
		return array(
			'format'   => self::FORMAT,
			'version'  => 1,
			'site'     => home_url( '/' ),
			'created'  => time(),
			'settings' => self::stored(),
		);
	}

	/**
	 * Imports an export document. Takes a backup first.
	 *
	 * @param mixed $document Decoded export document.
	 * @return array{saved: list<string>, rejected: list<string>}|null Null when the document is not an export.
	 */
	public static function import( mixed $document ): ?array {
		if ( ! is_array( $document ) || self::FORMAT !== ( $document['format'] ?? null ) || ! is_array( $document['settings'] ?? null ) ) {
			return null;
		}
		self::backup( __( 'Before importing settings', 'ultra-max-settings' ) );
		self::write( array() );
		$values = array();
		foreach ( $document['settings'] as $key => $value ) {
			if ( is_string( $key ) ) {
				$values[ $key ] = $value;
			}
		}
		return self::save( $values );
	}

	/**
	 * Every backup, newest first.
	 *
	 * @return list<array{id: string, label: string, created: int, settings: array<string, mixed>}>
	 */
	public static function backups(): array {
		$backups = get_option( self::BACKUPS, array() );
		$out     = array();
		foreach ( is_array( $backups ) ? $backups : array() as $backup ) {
			if ( is_array( $backup ) && is_string( $backup['id'] ?? null ) && is_array( $backup['settings'] ?? null ) ) {
				$settings = array();
				foreach ( $backup['settings'] as $key => $value ) {
					if ( is_string( $key ) ) {
						$settings[ $key ] = $value;
					}
				}
				$out[] = array(
					'id'       => $backup['id'],
					'label'    => is_string( $backup['label'] ?? null ) ? $backup['label'] : '',
					'created'  => is_int( $backup['created'] ?? null ) ? $backup['created'] : 0,
					'settings' => $settings,
				);
			}
		}
		return $out;
	}

	/**
	 * Takes a backup of the current values. Keeps the newest ten.
	 *
	 * @param string $label Why the backup was taken.
	 * @return string Backup id.
	 */
	public static function backup( string $label ): string {
		$id      = wp_generate_uuid4();
		$backups = self::backups();
		array_unshift(
			$backups,
			array(
				'id'       => $id,
				'label'    => $label,
				'created'  => time(),
				'settings' => self::stored(),
			)
		);
		update_option( self::BACKUPS, array_slice( $backups, 0, self::MAX_BACKUPS ), false );
		return $id;
	}

	/**
	 * Restores a backup. Takes a backup of the current values first.
	 *
	 * @param string $id Backup id.
	 * @return bool Whether the backup existed.
	 */
	public static function restore( string $id ): bool {
		foreach ( self::backups() as $backup ) {
			if ( $backup['id'] === $id ) {
				self::backup( __( 'Before restoring a backup', 'ultra-max-settings' ) );
				self::write( $backup['settings'] );
				return true;
			}
		}
		return false;
	}

	/**
	 * Writes the stored values.
	 *
	 * @param array<string, mixed> $stored Values that differ from the defaults.
	 */
	private static function write( array $stored ): void {
		update_option( self::OPTION, $stored, true );
		/**
		 * Fires after the settings were written.
		 *
		 * @param array<string, mixed> $stored Values that differ from the defaults.
		 */
		do_action( 'ultra_max_settings_saved', $stored );
	}
}
