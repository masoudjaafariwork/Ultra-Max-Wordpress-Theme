<?php
/**
 * Persian and Latin digits.
 *
 * @package UltraMax\Foundation
 */

declare(strict_types=1);

namespace UltraMax\Foundation;

/**
 * Converts digits between Latin (0-9), Persian (۰-۹) and Arabic (٠-٩).
 */
final class Digits {

	private const LATIN   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	private const PERSIAN = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	private const ARABIC  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );

	/**
	 * Replaces Latin and Arabic digits with Persian ones in plain text.
	 *
	 * @param string $text Plain text.
	 */
	public static function to_persian( string $text ): string {
		return str_replace( self::ARABIC, self::PERSIAN, str_replace( self::LATIN, self::PERSIAN, $text ) );
	}

	/**
	 * Replaces Persian and Arabic digits with Latin ones.
	 *
	 * @param string $text Text.
	 */
	public static function to_latin( string $text ): string {
		return str_replace( self::ARABIC, self::LATIN, str_replace( self::PERSIAN, self::LATIN, $text ) );
	}

	/**
	 * Replaces digits with Persian ones in the text of an HTML fragment only.
	 *
	 * Tags, attributes and character references such as `&#1578;` keep their
	 * Latin digits, so markup and entities never break.
	 *
	 * @param string $html HTML fragment.
	 */
	public static function to_persian_html( string $html ): string {
		$parts = preg_split( '/(<[^>]*>|&#?[a-zA-Z0-9]+;)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $parts ) {
			return $html;
		}
		foreach ( $parts as $index => $part ) {
			if ( '' !== $part && '<' !== $part[0] && '&' !== $part[0] ) {
				$parts[ $index ] = self::to_persian( $part );
			}
		}
		return implode( '', $parts );
	}
}
