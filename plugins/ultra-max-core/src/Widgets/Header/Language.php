<?php
/**
 * Language switcher widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\LanguageSwitcher;
use UltraMax\Core\Widgets\Values;

/**
 * The site's languages when a translation plugin (Polylang or WPML) is
 * active; nothing otherwise.
 */
final class Language extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'language';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Language switcher', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-globe';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'language_section',
				'label'  => __( 'Languages', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'layout',
						'type'    => 'select',
						'label'   => __( 'Show as', 'ultra-max-core' ),
						'help'    => __( 'Needs a translation plugin such as Polylang or WPML.', 'ultra-max-core' ),
						'default' => 'dropdown',
						'options' => array(
							'dropdown' => __( 'Drop-down list', 'ultra-max-core' ),
							'inline'   => __( 'All in a row', 'ultra-max-core' ),
						),
					),
				),
			),
		);
	}

	/**
	 * The languages from Polylang or WPML.
	 *
	 * @return list<array{code: string, name: string, href: string, current: bool}>
	 */
	public static function languages(): array {
		$out = array();
		if ( function_exists( 'pll_the_languages' ) ) {
			$raw = pll_the_languages( array( 'raw' => 1 ) );
			foreach ( is_array( $raw ) ? $raw : array() as $language ) {
				if ( is_array( $language ) ) {
					$out[] = array(
						'code'    => is_string( $language['slug'] ?? null ) ? $language['slug'] : '',
						'name'    => is_string( $language['name'] ?? null ) ? $language['name'] : '',
						'href'    => is_string( $language['url'] ?? null ) ? $language['url'] : '',
						'current' => ! empty( $language['current_lang'] ),
					);
				}
			}
			return $out;
		}
		$raw = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own filter.
		foreach ( is_array( $raw ) ? $raw : array() as $language ) {
			if ( is_array( $language ) ) {
				$out[] = array(
					'code'    => is_string( $language['language_code'] ?? null ) ? $language['language_code'] : '',
					'name'    => is_string( $language['native_name'] ?? null ) ? $language['native_name'] : '',
					'href'    => is_string( $language['url'] ?? null ) ? $language['url'] : '',
					'current' => ! empty( $language['active'] ),
				);
			}
		}
		return $out;
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return LanguageSwitcher::render(
			array(
				'id'        => 'um-languages',
				'label'     => __( 'Language', 'ultra-max-core' ),
				'languages' => self::languages(),
				'layout'    => 'inline' === $settings->string( 'layout' ) ? 'inline' : 'dropdown',
			)
		);
	}
}
