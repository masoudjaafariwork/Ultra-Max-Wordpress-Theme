<?php
/**
 * System status (ADM-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

/**
 * Checks what most often breaks a store: old PHP or WordPress, too little
 * memory or time, missing PHP extensions, plugins that overlap ours, and
 * WooCommerce templates copied into a theme and left outdated.
 *
 * Each check returns a status, a label, an explanation in plain words
 * and the measured value. The support report is plain text without
 * personal data.
 *
 * @phpstan-type Check array{id: string, status: 'good'|'warning'|'critical', label: string, description: string, value: string}
 */
final class SystemStatus {

	/**
	 * Plugins that do something we also do, by plugin file.
	 */
	private const OVERLAPS = array(
		'persian-woocommerce/woocommerce-persian.php' => 'jalali',
		'wp-parsidate/wp-parsidate.php'               => 'jalali',
		'digits/digit.php'                            => 'auth',
		'duplicate-post/duplicate-post.php'           => 'duplicate',
		'insert-headers-and-footers/ihaf.php'         => 'code',
		'wpcode-premium/wpcode.php'                   => 'code',
		'maintenance/maintenance.php'                 => 'maintenance',
		'coming-soon/coming-soon.php'                 => 'maintenance',
	);

	/**
	 * Every check.
	 *
	 * @return list<Check>
	 */
	public static function checks(): array {
		global $wp_version;
		$checks = array();

		$php      = PHP_VERSION;
		$checks[] = self::check(
			'php',
			version_compare( $php, '8.1', '<' ) ? 'critical' : ( version_compare( $php, '8.2', '<' ) ? 'warning' : 'good' ),
			__( 'PHP version', 'ultra-max-core' ),
			version_compare( $php, '8.2', '<' ) ? __( 'PHP 8.1 is the minimum. Ask your host for PHP 8.3 or later: it is faster and still receives security fixes.', 'ultra-max-core' ) : __( 'At least 8.1 is required.', 'ultra-max-core' ),
			$php
		);

		$wc       = defined( 'WC_VERSION' ) ? (string) constant( 'WC_VERSION' ) : '';
		$checks[] = self::check(
			'core',
			version_compare( (string) $wp_version, '6.9', '<' ) ? 'critical' : 'good',
			__( 'WordPress and WooCommerce', 'ultra-max-core' ),
			__( 'The last two major versions of WordPress and the last three of WooCommerce are supported.', 'ultra-max-core' ),
			'WP ' . $wp_version . ( '' !== $wc ? ' · WC ' . $wc : '' )
		);

		if ( '' !== $wc && class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$hpos     = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			$checks[] = self::check(
				'hpos',
				$hpos ? 'good' : 'warning',
				__( 'High-performance order storage (HPOS)', 'ultra-max-core' ),
				$hpos ? __( 'On, and every Ultra Max module supports it.', 'ultra-max-core' ) : __( 'Off. Turn it on in WooCommerce > Settings > Advanced > Features for faster orders.', 'ultra-max-core' ),
				$hpos ? 'HPOS' : __( 'Off', 'ultra-max-core' )
			);
		}

		$elementor = defined( 'ELEMENTOR_VERSION' ) ? (string) constant( 'ELEMENTOR_VERSION' ) : '';
		$checks[]  = self::check(
			'elementor',
			'' === $elementor ? 'warning' : 'good',
			__( 'Elementor', 'ultra-max-core' ),
			'' === $elementor ? __( 'Not active. The layout builder and demos need the free Elementor.', 'ultra-max-core' ) : __( 'The free version is enough. Elementor Pro also works.', 'ultra-max-core' ),
			'' === $elementor ? __( 'Not active', 'ultra-max-core' ) : 'Elementor ' . $elementor
		);

		$memory   = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
		$checks[] = self::check(
			'memory',
			$memory > 0 && $memory < 128 * MB_IN_BYTES ? 'critical' : ( $memory > 0 && $memory < 256 * MB_IN_BYTES ? 'warning' : 'good' ),
			__( 'PHP memory', 'ultra-max-core' ),
			__( 'At least 256 MB is recommended for WooCommerce and Elementor.', 'ultra-max-core' ),
			(string) ini_get( 'memory_limit' )
		);

		$time     = (int) ini_get( 'max_execution_time' );
		$checks[] = self::check(
			'time',
			$time > 0 && $time < 120 ? 'warning' : 'good',
			__( 'Maximum run time', 'ultra-max-core' ),
			__( 'At least 120 seconds is recommended for importing a demo. Ask your host to raise it.', 'ultra-max-core' ),
			0 === $time ? __( 'Unlimited', 'ultra-max-core' ) : $time . 's'
		);

		$upload   = wp_max_upload_size();
		$checks[] = self::check(
			'upload',
			$upload < 16 * MB_IN_BYTES ? 'warning' : 'good',
			__( 'Largest upload', 'ultra-max-core' ),
			__( 'At least 16 MB is recommended for images and demo files.', 'ultra-max-core' ),
			(string) size_format( $upload )
		);

		$missing  = array_values( array_filter( array( 'mbstring', 'intl', 'zip', 'curl' ), static fn ( string $ext ): bool => ! extension_loaded( $ext ) ) );
		$checks[] = self::check(
			'extensions',
			array() === $missing ? 'good' : 'warning',
			__( 'PHP extensions', 'ultra-max-core' ),
			array() === $missing ? __( 'mbstring, intl, zip and curl are present.', 'ultra-max-core' ) : __( 'Ask your host to enable the missing extensions; Persian text, packages and updates need them.', 'ultra-max-core' ),
			array() === $missing ? 'OK' : implode( ', ', $missing )
		);

		$checks[] = self::check(
			'https',
			is_ssl() || str_starts_with( home_url(), 'https://' ) ? 'good' : 'warning',
			__( 'Secure connection (HTTPS)', 'ultra-max-core' ),
			__( 'Payment gateways and browsers expect HTTPS.', 'ultra-max-core' ),
			str_starts_with( home_url(), 'https://' ) ? 'HTTPS' : 'HTTP'
		);

		$overlaps = self::overlaps();
		$checks[] = self::check(
			'overlaps',
			array() === $overlaps ? 'good' : 'warning',
			__( 'Overlapping plugins', 'ultra-max-core' ),
			array() === $overlaps ? __( 'No active plugin does the same job as an Ultra Max module.', 'ultra-max-core' ) : __( 'These plugins do a job Ultra Max also does. Use one of them for each job to avoid doubled dates, logins or code.', 'ultra-max-core' ),
			array() === $overlaps ? '0' : implode( ', ', $overlaps )
		);

		$outdated = self::outdated_templates();
		$checks[] = self::check(
			'templates',
			array() === $outdated ? 'good' : 'warning',
			__( 'Old WooCommerce templates in the theme', 'ultra-max-core' ),
			array() === $outdated ? __( 'No outdated template override was found.', 'ultra-max-core' ) : __( 'Update these copies in your child theme from the newer WooCommerce templates.', 'ultra-max-core' ) . ' ' . implode( ', ', $outdated ),
			(string) count( $outdated )
		);

		$debug    = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY && defined( 'WP_DEBUG' ) && WP_DEBUG && 'production' === wp_get_environment_type();
		$checks[] = self::check(
			'debug',
			$debug ? 'warning' : 'good',
			__( 'Error display', 'ultra-max-core' ),
			$debug ? __( 'PHP errors are shown to visitors. Turn WP_DEBUG_DISPLAY off on a live store.', 'ultra-max-core' ) : __( 'Errors are not shown to visitors.', 'ultra-max-core' ),
			$debug ? __( 'Shown', 'ultra-max-core' ) : __( 'Hidden', 'ultra-max-core' )
		);

		return $checks;
	}

	/**
	 * Builds one check.
	 *
	 * @param string                      $id          Id.
	 * @param 'good'|'warning'|'critical' $status      Status.
	 * @param string                      $label       Label.
	 * @param string                      $description Explanation.
	 * @param string                      $value       Measured value.
	 * @return Check
	 */
	private static function check( string $id, string $status, string $label, string $description, string $value ): array {
		return array(
			'id'          => $id,
			'status'      => $status,
			'label'       => $label,
			'description' => $description,
			'value'       => $value,
		);
	}

	/**
	 * Names of active plugins that overlap a module.
	 *
	 * @return list<string>
	 */
	public static function overlaps(): array {
		$active = get_option( 'active_plugins', array() );
		$names  = array();
		foreach ( is_array( $active ) ? $active : array() as $file ) {
			if ( is_string( $file ) && isset( self::OVERLAPS[ $file ] ) ) {
				$data    = get_file_data( WP_PLUGIN_DIR . '/' . $file, array( 'name' => 'Plugin Name' ) );
				$names[] = '' !== $data['name'] ? $data['name'] : dirname( $file );
			}
		}
		return $names;
	}

	/**
	 * WooCommerce templates overridden in the theme or child theme whose
	 * version is older than WooCommerce's own.
	 *
	 * @return list<string> Template paths.
	 */
	public static function outdated_templates(): array {
		if ( ! function_exists( 'WC' ) ) {
			return array();
		}
		$core_dir = WC()->plugin_path() . '/templates/';
		$outdated = array();
		foreach ( array_unique( array( get_stylesheet_directory(), get_template_directory() ) ) as $theme_dir ) {
			$dir = $theme_dir . '/woocommerce/';
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $files as $file ) {
				if ( ! $file instanceof \SplFileInfo || 'php' !== $file->getExtension() ) {
					continue;
				}
				$relative = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) ) ), '/' );
				$core     = $core_dir . $relative;
				if ( ! is_readable( $core ) ) {
					continue;
				}
				$theme_version = self::template_version( $file->getPathname() );
				$core_version  = self::template_version( $core );
				if ( '' !== $core_version && ( '' === $theme_version || version_compare( $theme_version, $core_version, '<' ) ) ) {
					$outdated[] = $relative;
				}
			}
		}
		return $outdated;
	}

	/**
	 * The `@version` of a WooCommerce template.
	 *
	 * @param string $file Template file.
	 */
	private static function template_version( string $file ): string {
		$data = get_file_data( $file, array( 'version' => '@version' ) );
		return trim( $data['version'] );
	}

	/**
	 * Counts per status.
	 *
	 * @param array $checks Checks.
	 * @phpstan-param list<Check> $checks
	 * @return array{good: int, warning: int, critical: int}
	 */
	public static function summary( array $checks ): array {
		$summary = array(
			'good'     => 0,
			'warning'  => 0,
			'critical' => 0,
		);
		foreach ( $checks as $check ) {
			++$summary[ $check['status'] ];
		}
		return $summary;
	}

	/**
	 * A plain-text report for support, without personal data.
	 *
	 * @param array $checks Checks.
	 * @phpstan-param list<Check> $checks
	 */
	public static function report( array $checks ): string {
		$lines = array( '### Ultra Max system status', 'Theme: ' . wp_get_theme()->get( 'Name' ) . ' ' . wp_get_theme()->get( 'Version' ) );
		foreach ( Products::all() as $product ) {
			$lines[] = $product['name'] . ': ' . ( $product['active'] ? $product['version'] : 'inactive' );
		}
		foreach ( $checks as $check ) {
			$lines[] = sprintf( '[%s] %s: %s', $check['status'], $check['label'], $check['value'] );
		}
		$lines[] = 'Locale: ' . get_locale() . ' · RTL: ' . ( is_rtl() ? 'yes' : 'no' );
		return implode( "\n", $lines );
	}
}
