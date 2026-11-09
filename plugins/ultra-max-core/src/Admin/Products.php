<?php
/**
 * The Ultra Max products on this site, their versions and changelog
 * (ADM-04, ADM-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

use UltraMax\Core\Lib\Foundation\Jalali;

/**
 * Lists the theme and plugins, finds available updates, and warns when an
 * update would leave them on different major versions, which are not
 * guaranteed to work together.
 *
 * @phpstan-type Product array{slug: string, name: string, type: 'theme'|'plugin', file: string, version: string, active: bool, installed: bool, update: string}
 */
final class Products {

	/**
	 * Plugin files of the companion plugins.
	 */
	public const PLUGINS = array(
		'ultra-max-core'    => 'ultra-max-core/ultra-max-core.php',
		'ultra-max-auth'    => 'ultra-max-auth/ultra-max-auth.php',
		'ultra-max-invoice' => 'ultra-max-invoice/ultra-max-invoice.php',
		'ultra-max-seo'     => 'ultra-max-seo/ultra-max-seo.php',
	);

	/**
	 * Registers the update warnings.
	 */
	public static function register(): void {
		foreach ( self::PLUGINS as $file ) {
			add_action( 'in_plugin_update_message-' . $file, array( self::class, 'update_message' ), 10, 2 );
		}
	}

	/**
	 * The theme and every companion plugin.
	 *
	 * @return list<Product>
	 */
	public static function all(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$theme   = wp_get_theme( 'ultra-max' );
		$updates = get_site_transient( 'update_themes' );
		$update  = is_object( $updates ) && isset( $updates->response['ultra-max']['new_version'] ) && is_string( $updates->response['ultra-max']['new_version'] ) ? $updates->response['ultra-max']['new_version'] : '';
		$list    = array(
			array(
				'slug'      => 'ultra-max',
				'name'      => $theme->exists() ? (string) $theme->get( 'Name' ) : 'Ultra Max',
				'type'      => 'theme',
				'file'      => 'ultra-max',
				'version'   => $theme->exists() ? (string) $theme->get( 'Version' ) : '',
				'active'    => 'ultra-max' === get_template(),
				'installed' => $theme->exists(),
				'update'    => $update,
			),
		);
		$plugins = get_plugins();
		$updates = get_site_transient( 'update_plugins' );
		foreach ( self::PLUGINS as $slug => $file ) {
			$data   = $plugins[ $file ] ?? null;
			$update = is_object( $updates ) && isset( $updates->response[ $file ] ) && is_object( $updates->response[ $file ] ) && isset( $updates->response[ $file ]->new_version ) ? (string) $updates->response[ $file ]->new_version : '';
			$list[] = array(
				'slug'      => $slug,
				'name'      => is_array( $data ) && isset( $data['Name'] ) ? (string) $data['Name'] : $slug,
				'type'      => 'plugin',
				'file'      => $file,
				'version'   => is_array( $data ) && isset( $data['Version'] ) ? (string) $data['Version'] : '',
				'active'    => is_plugin_active( $file ),
				'installed' => is_array( $data ),
				'update'    => $update,
			);
		}
		return $list;
	}

	/**
	 * The major version part ("1" of "1.4.2"; "0.2" for 0.x).
	 *
	 * @param string $version Version.
	 */
	public static function major( string $version ): string {
		$parts = explode( '.', $version );
		return '0' === $parts[0] ? '0.' . ( $parts[1] ?? '0' ) : $parts[0];
	}

	/**
	 * Products that would end up on a different major version than the
	 * others after the available updates (ADM-10).
	 *
	 * @param array $products Products.
	 * @phpstan-param list<Product> $products
	 * @return list<string> Names.
	 */
	public static function mismatched( array $products ): array {
		$majors = array();
		foreach ( $products as $product ) {
			if ( $product['installed'] && $product['active'] ) {
				$majors[ $product['name'] ] = self::major( '' !== $product['update'] ? $product['update'] : $product['version'] );
			}
		}
		$counts = array_count_values( $majors );
		arsort( $counts );
		$common = (string) array_key_first( $counts );
		return array_keys( array_filter( $majors, static fn ( string $major ): bool => $major !== $common ) );
	}

	/**
	 * Warns under a plugin's update row when the update changes its major
	 * version: every Ultra Max product must be updated together.
	 *
	 * @param mixed $plugin   Plugin data.
	 * @param mixed $response Update data.
	 */
	public static function update_message( mixed $plugin, mixed $response ): void {
		$new     = is_object( $response ) && isset( $response->new_version ) ? (string) $response->new_version : '';
		$current = is_array( $plugin ) && is_string( $plugin['Version'] ?? null ) ? $plugin['Version'] : '';
		if ( '' === $new || self::major( $new ) === self::major( $current ) ) {
			return;
		}
		printf(
			'<br><strong>%s</strong>',
			esc_html__( 'This is a major update. Take a backup, then update the Ultra Max theme and all Ultra Max plugins together so they keep working with each other.', 'ultra-max-core' )
		);
	}

	/**
	 * Changelog of the core plugin from its readme, newest first.
	 *
	 * Headings look like `= 0.2.0 - 2026-11-09 =`; items start with `*`.
	 *
	 * @param int $limit Number of versions.
	 * @return list<array{version: string, date: string, items: list<string>}>
	 */
	public static function changelog( int $limit = 4 ): array {
		$file = dirname( ULTRA_MAX_CORE_FILE ) . '/readme.txt';
		$text = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local readme.
		$text = (string) strstr( $text, '== Changelog ==' );
		preg_match_all( '/^= ([0-9][^ =]*)(?: - (\d{4})-(\d{2})-(\d{2}))? =\s*\n((?:\*.*\n?)*)/m', $text, $matches, PREG_SET_ORDER );
		$log = array();
		foreach ( array_slice( $matches, 0, $limit ) as $match ) {
			$date = '';
			if ( isset( $match[2] ) && '' !== $match[2] ) {
				$timestamp = ( new \DateTimeImmutable( sprintf( '%s-%s-%s 12:00:00', $match[2], $match[3], $match[4] ), wp_timezone() ) )->getTimestamp();
				$date      = Jalali::format( 'j F Y', $timestamp );
			}
			$items = array();
			$lines = preg_split( '/\n/', trim( $match[5] ) );
			foreach ( false === $lines ? array() : $lines as $line ) {
				$line = trim( ltrim( trim( $line ), '*' ) );
				if ( '' !== $line ) {
					$items[] = $line;
				}
			}
			$log[] = array(
				'version' => $match[1],
				'date'    => $date,
				'items'   => $items,
			);
		}
		return $log;
	}
}
