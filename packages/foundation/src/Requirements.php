<?php
/**
 * Minimum PHP and WordPress version check shared by every product.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Checks the runtime against a product's minimum requirements.
 *
 * A product calls `met()` before it boots. When the check fails, the product
 * calls `notify()` and stops, so a too-old site shows a clear admin notice
 * instead of a fatal error.
 */
final class Requirements {

	/**
	 * Creates the check.
	 *
	 * @param string $product_name Translated display name of the product.
	 * @param string $min_php      Minimum PHP version, such as "8.1".
	 * @param string $min_wp       Minimum WordPress version, such as "6.9".
	 */
	public function __construct(
		private readonly string $product_name,
		private readonly string $min_php,
		private readonly string $min_wp,
	) {}

	/**
	 * Lists every unmet requirement as a translated sentence.
	 *
	 * @return list<string>
	 */
	public function errors(): array {
		$errors = array();

		if ( version_compare( PHP_VERSION, $this->min_php, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: product name, 2: required PHP version, 3: current PHP version. */
				__( '%1$s needs PHP %2$s or later. This site runs PHP %3$s.', 'ultra-max-foundation' ),
				$this->product_name,
				$this->min_php,
				PHP_VERSION
			);
		}

		$wp_version = (string) get_bloginfo( 'version' );
		if ( version_compare( $wp_version, $this->min_wp, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: product name, 2: required WordPress version, 3: current WordPress version. */
				__( '%1$s needs WordPress %2$s or later. This site runs WordPress %3$s.', 'ultra-max-foundation' ),
				$this->product_name,
				$this->min_wp,
				$wp_version
			);
		}

		return $errors;
	}

	/**
	 * Whether every requirement is met.
	 */
	public function met(): bool {
		return array() === $this->errors();
	}

	/**
	 * Shows the unmet requirements as an admin notice.
	 */
	public function notify(): void {
		$errors = $this->errors();
		add_action(
			'admin_notices',
			static function () use ( $errors ): void {
				foreach ( $errors as $error ) {
					printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
				}
			}
		);
	}
}
