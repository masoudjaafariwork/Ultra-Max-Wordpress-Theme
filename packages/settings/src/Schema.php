<?php
/**
 * Settings schema shared by every product.
 *
 * @package UltraMax\Settings
 */

declare(strict_types=1);

namespace UltraMax\Settings;

/**
 * The list of every setting, collected from all products.
 *
 * Each product adds its sections and groups through the
 * `ultra_max_settings_schema` filter, as plain arrays, so products of
 * different versions never share classes. The settings panel renders the
 * schema; the store uses it for defaults and sanitising.
 *
 * Field types:
 * - toggle: on or off.
 * - select, segmented, choice (cards), preset (colour chips): one of `options`.
 * - color: #rrggbb.
 * - range, number: a number between `min` and `max`.
 * - text, url, textarea: text.
 * - code: CSS, JS or HTML; JS and HTML need the `unfiltered_html` capability.
 * - devices: shown on desktop, tablet and mobile.
 * - jdate: a Jalali date such as 1405-08-18.
 * - media: an attachment id; `accept` limits the file type.
 * - repeater: a list of rows, each with the sub-`fields`.
 * - preview: no value; the panel shows a live preview.
 *
 * @phpstan-type Option array{value: string, label: string, color?: string, ramp?: array<int, string>}
 * @phpstan-type Field array{
 *     id: string,
 *     type: string,
 *     label: string,
 *     help?: string,
 *     default?: mixed,
 *     options?: list<Option>,
 *     min?: int|float,
 *     max?: int|float,
 *     step?: int|float,
 *     unit?: string,
 *     language?: string,
 *     accept?: string,
 *     placeholder?: string,
 *     keywords?: string,
 *     token?: string,
 *     show_if?: array{field: string, equals: mixed},
 *     fields?: list<array<string, mixed>>,
 *     max_rows?: int,
 * }
 * @phpstan-type Group array{id: string, title: string, icon?: string, features?: list<string>, module?: string, description?: string, fields: list<Field>}
 * @phpstan-type Section array{id: string, title: string, icon: string, order: int, groups: list<Group>}
 */
final class Schema {

	public const FILTER = 'ultra_max_settings_schema';

	/**
	 * Cached defaults, built once WordPress has loaded the theme.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $defaults = null;

	/**
	 * Every section, sorted, with groups merged from all products.
	 *
	 * @return list<Section>
	 */
	public static function sections(): array {
		/**
		 * Filters the settings schema. Add sections keyed by id; to add
		 * groups to an existing section, append to its `groups`.
		 *
		 * @param array<string, array<string, mixed>> $sections Sections keyed by id.
		 */
		$raw      = apply_filters( self::FILTER, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The constant is ultra_max_settings_schema.
		$sections = array();
		foreach ( is_array( $raw ) ? $raw : array() as $id => $section ) {
			if ( ! is_array( $section ) || ! is_string( $id ) ) {
				continue;
			}
			$groups = array();
			foreach ( is_array( $section['groups'] ?? null ) ? $section['groups'] : array() as $group ) {
				if ( is_array( $group ) && is_string( $group['id'] ?? null ) && is_array( $group['fields'] ?? null ) ) {
					// phpcs:ignore Generic.Commenting.DocComment.MissingShort -- Type note for PHPStan.
					/** @var Group $group */
					$groups[] = $group;
				}
			}
			$sections[] = array(
				'id'     => $id,
				'title'  => is_string( $section['title'] ?? null ) ? $section['title'] : $id,
				'icon'   => is_string( $section['icon'] ?? null ) ? $section['icon'] : 'sliders',
				'order'  => is_int( $section['order'] ?? null ) ? $section['order'] : 100,
				'groups' => $groups,
			);
		}
		usort( $sections, static fn ( array $a, array $b ): int => $a['order'] <=> $b['order'] );
		return $sections;
	}

	/**
	 * Every field keyed by id.
	 *
	 * @return array<string, Field>
	 */
	public static function fields(): array {
		$fields = array();
		foreach ( self::sections() as $section ) {
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['fields'] as $field ) {
					if ( 'preview' !== $field['type'] ) {
						$fields[ $field['id'] ] = $field;
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Default value of every field.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		if ( null !== self::$defaults ) {
			return self::$defaults;
		}
		$defaults = array();
		foreach ( self::fields() as $id => $field ) {
			$defaults[ $id ] = $field['default'] ?? Sanitizer::empty_value( $field );
		}
		// Cache only once every product has registered its schema.
		if ( did_action( 'after_setup_theme' ) ) {
			self::$defaults = $defaults;
		}
		return $defaults;
	}

	/**
	 * Clears the cached defaults, for tests and after the schema changes.
	 */
	public static function flush(): void {
		self::$defaults = null;
	}

	/**
	 * Ids of the fields in one section.
	 *
	 * @param string $section_id Section id.
	 * @return list<string>
	 */
	public static function section_fields( string $section_id ): array {
		$ids = array();
		foreach ( self::sections() as $section ) {
			if ( $section['id'] !== $section_id ) {
				continue;
			}
			foreach ( $section['groups'] as $group ) {
				foreach ( $group['fields'] as $field ) {
					if ( 'preview' !== $field['type'] ) {
						$ids[] = $field['id'];
					}
				}
			}
		}
		return $ids;
	}
}
