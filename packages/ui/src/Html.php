<?php
/**
 * Safe HTML value returned by every component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Markup that is already escaped.
 *
 * Like JSX, a plain string passed as children is always escaped; only an
 * Html value produced by a component is inserted as markup. Templates print
 * a component with `->print()`.
 */
final class Html implements \Stringable {

	/**
	 * Creates the value.
	 *
	 * @param string $markup Escaped markup.
	 */
	private function __construct( private readonly string $markup ) {}

	/**
	 * Wraps markup that the caller has already escaped.
	 *
	 * Only component code inside packages/ui should call this.
	 *
	 * @param string $markup Escaped markup.
	 */
	public static function trusted( string $markup ): self {
		return new self( $markup );
	}

	/**
	 * Wraps markup that a WordPress function made and filtered itself, such
	 * as the output of `the_content`, `wp_get_attachment_image()`,
	 * `get_comment_text()` or a widget. Templates use this to pass such
	 * markup as `children`; never pass request data or settings through it.
	 *
	 * @param string $markup Markup made by WordPress.
	 */
	public static function wp( string $markup ): self {
		return new self( $markup );
	}

	/**
	 * Escapes plain text.
	 *
	 * @param string $text Plain text.
	 */
	public static function text( string $text ): self {
		return new self( esc_html( $text ) );
	}

	/**
	 * An empty value.
	 */
	public static function none(): self {
		return new self( '' );
	}

	/**
	 * Normalizes children: strings are escaped, Html is kept, lists are joined.
	 *
	 * @param string|int|float|Html|array<int, string|int|float|Html|null>|null $children Children.
	 */
	public static function from( string|int|float|Html|array|null $children ): self {
		if ( null === $children ) {
			return self::none();
		}
		if ( $children instanceof self ) {
			return $children;
		}
		if ( is_array( $children ) ) {
			$markup = '';
			foreach ( $children as $child ) {
				$markup .= self::from( $child )->markup;
			}
			return new self( $markup );
		}
		return self::text( (string) $children );
	}

	/**
	 * Whether the value holds no markup.
	 */
	public function is_empty(): bool {
		return '' === $this->markup;
	}

	/**
	 * Prints the markup.
	 */
	public function print(): void {
		echo $this->markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Html only holds escaped markup.
	}

	/**
	 * The escaped markup.
	 */
	public function __toString(): string {
		return $this->markup;
	}
}
