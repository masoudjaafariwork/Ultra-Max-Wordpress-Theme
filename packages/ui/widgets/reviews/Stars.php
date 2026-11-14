<?php
/**
 * Stars component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Five stars: a read-only rating, or a rating input of five radio buttons
 * that works without JavaScript and with the keyboard (PDP-08).
 */
final class Stars {

	private const PATH = '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>';

	/**
	 * Read-only stars.
	 *
	 * @param float  $value Rating from 0 to 5.
	 * @param string $label Accessible text, such as "4.5 out of 5".
	 * @param string $size  Size classes.
	 */
	public static function show( float $value, string $label, string $size = 'size-4' ): Html {
		$out = '';
		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= sprintf(
				'<svg class="%1$s %2$s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">%3$s</svg>',
				esc_attr( $size ),
				$value >= $i - 0.25 ? 'text-rating' : 'text-border-strong',
				self::PATH
			);
		}
		return Html::trusted( sprintf( '<span class="inline-flex items-center gap-0.5" role="img" aria-label="%1$s">%2$s</span>', esc_attr( $label ), $out ) );
	}

	/**
	 * A rating input.
	 *
	 * @param string $name     Field name.
	 * @param string $legend   Question, such as "Your rating".
	 * @param array  $labels   Name of each star, from 1 to 5.
	 * @param bool   $required Whether a choice is required.
	 * @param string $size     Size classes of the stars.
	 * @phpstan-param list<string> $labels
	 */
	public static function input( string $name, string $legend, array $labels, bool $required = false, string $size = 'size-8' ): Html {
		$id    = 'um-stars-' . preg_replace( '/[^a-z0-9]+/i', '-', $name );
		$stars = '';
		// From five down, shown reversed: a star and the ones before it light up together.
		for ( $i = 5; $i >= 1; $i-- ) {
			$stars .= sprintf(
				'<label for="%1$s-%2$d" class="peer cursor-pointer text-border-strong has-checked:text-rating peer-has-checked:text-rating hover:text-rating peer-hover:text-rating has-focus-visible:outline-3 has-focus-visible:outline-primary/40" title="%3$s"><input id="%1$s-%2$d" type="radio" class="sr-only" name="%4$s" value="%2$d"%5$s><span class="sr-only">%3$s</span><svg class="%6$s" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">%7$s</svg></label>',
				esc_attr( $id ),
				$i,
				esc_attr( $labels[ $i - 1 ] ?? (string) $i ),
				esc_attr( $name ),
				$required && 5 === $i ? ' required' : '',
				esc_attr( $size ),
				self::PATH
			);
		}
		return Html::trusted(
			sprintf(
				'<fieldset class="m-0 grid gap-1 border-0 p-0"><legend class="mb-1 p-0 text-sm font-bold">%1$s</legend><div class="inline-flex flex-row-reverse justify-end gap-1">%2$s</div></fieldset>',
				esc_html( $legend ),
				$stars
			)
		);
	}
}
