<?php
/**
 * Counter component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A large number with a label, such as "12,000 happy customers" (HOME-12).
 * The final number is in the page; when the counter scrolls into view it
 * counts up to it, unless the visitor prefers reduced motion.
 *
 * @phpstan-type CounterProps array{
 *     value: int,
 *     label: string,
 *     prefix?: string,
 *     suffix?: string,
 *     icon?: string,
 *     digits?: 'fa'|'en',
 *     duration?: int,
 *     class?: string,
 * }
 */
final class Counter {

	/**
	 * Renders the counter.
	 *
	 * @param array $props Props.
	 * @phpstan-param CounterProps $props
	 */
	public static function render( array $props ): Html {
		$digits = ( $props['digits'] ?? 'fa' ) === 'en' ? 'en' : 'fa';
		$number = number_format( $props['value'] );
		if ( 'fa' === $digits ) {
			$number = strtr( $number, array_combine( array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ',' ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٬' ) ) );
		}
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<b class="font-num text-4xl leading-tight font-heavy text-primary">%3$s<span data-value>%4$s</span>%5$s</b><span class="text-sm text-fg-2">%6$s</span></div>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-counter flex flex-col items-center gap-1 text-center', $props['class'] ?? null ),
						'data-um-component' => 'counter',
						'data-target'       => (string) $props['value'],
						'data-digits'       => $digits,
						'data-duration'     => (string) ( $props['duration'] ?? 1600 ),
					)
				),
				isset( $props['icon'] ) && '' !== $props['icon'] ? Icon::render( $props['icon'], 'size-8 text-accent' ) : '',
				esc_html( $props['prefix'] ?? '' ),
				esc_html( $number ),
				esc_html( $props['suffix'] ?? '' ),
				esc_html( $props['label'] )
			)
		);
	}
}
