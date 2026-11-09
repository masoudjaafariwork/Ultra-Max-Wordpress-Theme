<?php
/**
 * Registers the scripts and styles produced by `npm run build`.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Registers built assets of one product.
 *
 * The build writes, for every entry, `<entry>.js`, `<entry>.css` (right to
 * left, as authored), `<entry>-ltr.css` (generated) and `<entry>.asset.php`,
 * which returns the script dependencies and a content hash used as version.
 */
final class Assets {

	/**
	 * Creates the registrar.
	 *
	 * @param string $build_dir Absolute path of the product's build folder, with no trailing slash.
	 * @param string $build_url URL of the same folder, with no trailing slash.
	 */
	public function __construct(
		private readonly string $build_dir,
		private readonly string $build_url,
	) {}

	/**
	 * Reads the manifest of one entry.
	 *
	 * @param string $entry Entry path inside the build folder, such as "ui/storefront".
	 * @return array{dependencies: list<string>, version: string}|null Null when the entry was not built.
	 */
	public function manifest( string $entry ): ?array {
		$file = $this->build_dir . '/' . $entry . '.asset.php';
		if ( ! is_readable( $file ) ) {
			return null;
		}
		/** @var array{dependencies?: list<string>, version?: string} $data */
		$data = require $file;
		return array(
			'dependencies' => $data['dependencies'] ?? array(),
			'version'      => $data['version'] ?? '0',
		);
	}

	/**
	 * Registers the script of an entry.
	 *
	 * @param string       $handle Script handle.
	 * @param string       $entry  Entry path inside the build folder.
	 * @param list<string> $deps   Extra dependencies.
	 * @return bool Whether the entry exists.
	 */
	public function register_script( string $handle, string $entry, array $deps = array() ): bool {
		$manifest = $this->manifest( $entry );
		if ( null === $manifest || ! is_readable( $this->build_dir . '/' . $entry . '.js' ) ) {
			return false;
		}
		return wp_register_script(
			$handle,
			$this->build_url . '/' . $entry . '.js',
			array_values( array_unique( array_merge( $manifest['dependencies'], $deps ) ) ),
			$manifest['version'],
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Registers the stylesheet of an entry in the direction of the site.
	 *
	 * @param string       $handle Style handle.
	 * @param string       $entry  Entry path inside the build folder.
	 * @param list<string> $deps   Style dependencies.
	 * @return bool Whether the entry exists.
	 */
	public function register_style( string $handle, string $entry, array $deps = array() ): bool {
		$manifest = $this->manifest( $entry );
		$file     = $entry . ( is_rtl() ? '' : '-ltr' ) . '.css';
		if ( null === $manifest || ! is_readable( $this->build_dir . '/' . $file ) ) {
			return false;
		}
		return wp_register_style( $handle, $this->build_url . '/' . $file, $deps, $manifest['version'] );
	}
}
