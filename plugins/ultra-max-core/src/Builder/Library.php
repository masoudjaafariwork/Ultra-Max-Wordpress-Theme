<?php
/**
 * Ready-made layouts and sections (BLD-03, HDR-03, HOME-13).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

/**
 * Each template is a JSON file in `templates/library/<kind>/`, written for
 * this product: a title, the layout type or section kind it is for, the
 * devices it suits and the Elementor elements. Preview images live next
 * to it with the same name. The layouts panel offers them as starting
 * points and the Elementor editor inserts them as sections or pages.
 *
 * @phpstan-type Template array{id: string, kind: string, title: string, description: string, devices: array{desktop: bool, tablet: bool, mobile: bool}, preview: string, tags: list<string>}
 */
final class Library {

	/**
	 * Folder of the templates.
	 */
	public static function dir(): string {
		return plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'templates/library';
	}

	/**
	 * Every template, without its elements.
	 *
	 * @param string|null $kind Only this kind (a layout type, "section" or "page").
	 * @return list<Template>
	 */
	public static function all( ?string $kind = null ): array {
		$out   = array();
		$files = glob( self::dir() . '/*/*.json' );
		foreach ( is_array( $files ) ? $files : array() as $file ) {
			$template = self::read( $file );
			if ( null !== $template && ( null === $kind || $kind === $template['kind'] ) ) {
				unset( $template['content'] );
				$out[] = $template;
			}
		}
		usort( $out, static fn ( array $a, array $b ): int => strnatcmp( $a['id'], $b['id'] ) );
		return $out;
	}

	/**
	 * One template with its elements.
	 *
	 * @param string $id Template id, such as "header/desktop-1".
	 * @return array{id: string, kind: string, title: string, description: string, devices: array{desktop: bool, tablet: bool, mobile: bool}, preview: string, tags: list<string>, content: array<mixed>}|null
	 */
	public static function get( string $id ): ?array {
		if ( 1 !== preg_match( '#^[a-z0-9-]+/[a-z0-9-]+$#', $id ) ) {
			return null;
		}
		return self::read( self::dir() . '/' . $id . '.json' );
	}

	/**
	 * Reads one template file.
	 *
	 * @param string $file Absolute path.
	 * @return array{id: string, kind: string, title: string, description: string, devices: array{desktop: bool, tablet: bool, mobile: bool}, preview: string, tags: list<string>, content: array<mixed>}|null
	 */
	private static function read( string $file ): ?array {
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file shipped with the plugin.
		if ( ! is_array( $data ) || ! is_array( $data['content'] ?? null ) ) {
			return null;
		}
		$kind    = basename( dirname( $file ) );
		$name    = basename( $file, '.json' );
		$devices = is_array( $data['devices'] ?? null ) ? $data['devices'] : array();
		$preview = $kind . '/' . $name . '.webp';
		$tags    = array();
		foreach ( is_array( $data['tags'] ?? null ) ? $data['tags'] : array() as $tag ) {
			if ( is_string( $tag ) ) {
				$tags[] = $tag;
			}
		}
		return array(
			'id'          => $kind . '/' . $name,
			'kind'        => $kind,
			'title'       => self::text( $data['title'] ?? '' ),
			'description' => self::text( $data['description'] ?? '' ),
			'devices'     => array(
				'desktop' => (bool) ( $devices['desktop'] ?? true ),
				'tablet'  => (bool) ( $devices['tablet'] ?? true ),
				'mobile'  => (bool) ( $devices['mobile'] ?? true ),
			),
			'preview'     => is_readable( self::dir() . '/' . $preview ) ? plugins_url( 'templates/library/' . $preview, ULTRA_MAX_CORE_FILE ) : '',
			'tags'        => $tags,
			'content'     => $data['content'],
		);
	}

	/**
	 * A template text. Template files hold English source text; the
	 * translations of these strings live in Strings::library().
	 *
	 * @param mixed $value Text from the file.
	 */
	private static function text( mixed $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$strings = LibraryStrings::all();
		return $strings[ $value ] ?? $value;
	}
}
