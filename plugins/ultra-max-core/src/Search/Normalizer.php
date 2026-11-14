<?php
/**
 * Persian spellings of a search word (SRCH-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Search;

/**
 * The same Persian word is stored in several ways: with the Arabic ي and
 * ك or the Persian ی and ک, with Persian, Arabic or Latin digits, and with
 * a zero-width non-joiner or a space inside it. A shopper types one of
 * them. Instead of rewriting every product, search looks for each
 * spelling of each typed word.
 */
final class Normalizer {

	private const PERSIAN_DIGITS = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	private const ARABIC_DIGITS  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
	private const LATIN_DIGITS   = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	private const ZWNJ           = "\u{200C}";

	/**
	 * One canonical spelling: Persian letters, Latin digits, no
	 * zero-width characters, single spaces, lower case.
	 *
	 * @param string $text Text.
	 */
	public static function normalize( string $text ): string {
		$text = str_replace( array( 'ي', 'ى', 'ك', 'ۀ', 'ة' ), array( 'ی', 'ی', 'ک', 'ه', 'ه' ), $text );
		$text = str_replace( array_merge( self::PERSIAN_DIGITS, self::ARABIC_DIGITS ), array_merge( self::LATIN_DIGITS, self::LATIN_DIGITS ), $text );
		$text = str_replace( array( self::ZWNJ, "\u{200F}", "\u{200E}", 'ـ' ), array( ' ', '', '', '' ), $text );
		$text = (string) preg_replace( '/\s+/u', ' ', $text );
		return mb_strtolower( trim( $text ) );
	}

	/**
	 * Every spelling to look for, the typed one first.
	 *
	 * @param string $word A search word.
	 * @return list<string>
	 */
	public static function variants( string $word ): array {
		$word = trim( $word );
		if ( '' === $word ) {
			return array();
		}
		$base = self::normalize( $word );
		$out  = array( $word, $base );

		// Digits: Latin and Persian.
		$out[] = str_replace( self::LATIN_DIGITS, self::PERSIAN_DIGITS, $base );

		// Letters: the Arabic ي and ك that older keyboards and imported data use.
		$arabic = str_replace( array( 'ی', 'ک' ), array( 'ي', 'ك' ), $base );
		$out[]  = $arabic;
		$out[]  = str_replace( self::LATIN_DIGITS, self::PERSIAN_DIGITS, $arabic );

		// A word typed with a zero-width non-joiner may be stored with a space or joined.
		if ( str_contains( $word, self::ZWNJ ) ) {
			$out[] = str_replace( self::ZWNJ, '', $word );
			$out[] = str_replace( ' ', self::ZWNJ, $base );
		}

		$unique = array();
		foreach ( $out as $variant ) {
			$variant = trim( $variant );
			if ( '' !== $variant && ! in_array( $variant, $unique, true ) ) {
				$unique[] = $variant;
			}
		}
		return array_slice( $unique, 0, 8 );
	}
}
