<?php
/**
 * Frame component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The page frame around header, content and footer (CORE-07): full width,
 * or a boxed page on the background colour. The header template opens it
 * and the footer template closes it.
 *
 * @phpstan-type FrameProps array{boxed?: bool}
 */
final class Frame {

	/**
	 * Classes for the `body` element.
	 *
	 * @param bool $boxed Whether the page is boxed.
	 * @return list<string>
	 */
	public static function body_classes( bool $boxed ): array {
		return array( 'um-body', 'bg-page', 'text-fg', $boxed ? 'um-boxed' : 'um-full' );
	}

	/**
	 * Opens the frame.
	 *
	 * @param array $props Props.
	 * @phpstan-param FrameProps $props
	 */
	public static function open( array $props = array() ): Html {
		$boxed = $props['boxed'] ?? false;
		return Html::trusted(
			sprintf(
				'<div id="page" class="%s">',
				esc_attr( $boxed ? 'um-scope mx-auto flex min-h-dvh max-w-[calc(var(--um-container)+2*var(--um-gutter)+32px)] flex-col bg-page shadow-md lg:my-6 lg:min-h-[calc(100dvh-48px)] lg:overflow-clip lg:rounded-xl' : 'um-scope flex min-h-dvh flex-col' )
			)
		);
	}

	/**
	 * Closes the frame.
	 */
	public static function close(): Html {
		return Html::trusted( '</div>' );
	}

	/**
	 * The skip link, first in the page, visible on keyboard focus.
	 *
	 * @param string $target Id of the main content.
	 * @param string $label  Link text.
	 */
	public static function skip_link( string $target, string $label ): Html {
		return Html::trusted(
			sprintf(
				'<a class="um-scope sr-only z-(--um-z-toast) rounded-md bg-primary px-4 py-2 font-bold text-on-primary focus:not-sr-only focus:fixed focus:start-4 focus:top-4" href="#%s">%s</a>',
				esc_attr( $target ),
				esc_html( $label )
			)
		);
	}

	/**
	 * Opens the main content area.
	 *
	 * @param string $id Id the skip link points to.
	 */
	public static function main_open( string $id = 'um-main' ): Html {
		return Html::trusted( sprintf( '<main id="%s" class="flex-1 outline-none" tabindex="-1">', esc_attr( $id ) ) );
	}

	/**
	 * Closes the main content area.
	 */
	public static function main_close(): Html {
		return Html::trusted( '</main>' );
	}
}
