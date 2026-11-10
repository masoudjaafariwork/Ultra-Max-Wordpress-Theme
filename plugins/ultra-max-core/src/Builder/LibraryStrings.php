<?php
/**
 * Translations of the text inside ready-made templates.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * Template files are JSON with English source text, like the rest of the
 * code. Every text they contain is listed here once, inside a gettext
 * call, so the translation tools find it and the Persian file translates
 * it. When a template is inserted, each known text in its settings is
 * replaced with its translation, and image addresses starting with
 * {library}/ point at the drawings shipped in assets/img/library.
 */
final class LibraryStrings {

	/**
	 * Source text to translated text.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		static $cache = array();
		$locale       = determine_locale();
		if ( ! isset( $cache[ $locale ] ) ) {
			$cache[ $locale ] = require __DIR__ . '/library-strings.php';
		}
		return $cache[ $locale ];
	}

	/**
	 * Translates every known text in a tree of elements.
	 *
	 * @param array<mixed> $tree Elements or settings.
	 * @return array<mixed>
	 */
	public static function translate( array $tree ): array {
		$strings = self::all();
		$images  = plugins_url( 'assets/img/library', ULTRA_MAX_CORE_FILE );
		array_walk_recursive(
			$tree,
			static function ( mixed &$value, mixed $key ) use ( $strings, $images ): void {
				if ( ! is_string( $value ) ) {
					return;
				}
				if ( 'url' === $key && str_starts_with( $value, '{library}/' ) ) {
					$value = $images . substr( $value, 9 );
				} elseif ( isset( $strings[ $value ] ) && ! in_array( $key, array( 'id', 'elType', 'widgetType', 'url' ), true ) ) {
					$value = $strings[ $value ];
				}
			}
		);
		return $tree;
	}
}
