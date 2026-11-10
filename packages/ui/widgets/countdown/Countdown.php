<?php
/**
 * Countdown component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Hours, minutes and seconds left until a moment (HOME-03, HOME-12). The
 * server prints the time left, so the page draws complete and nothing
 * moves; the behaviour then ticks every second and fires `um:ended` once.
 *
 * Over a day, days show first. Digits follow the `digits` prop.
 *
 * @phpstan-type CountdownProps array{
 *     end: int,
 *     label: string,
 *     tone?: 'plain'|'deal'|'light',
 *     size?: 'sm'|'md'|'lg',
 *     digits?: 'fa'|'en',
 *     ended?: string,
 *     class?: string,
 * }
 */
final class Countdown {

	public const TONES = array( 'plain', 'deal', 'light' );
	public const SIZES = array( 'md', 'sm', 'lg' );

	/**
	 * Renders the countdown.
	 *
	 * @param array $props Props.
	 * @phpstan-param CountdownProps $props
	 */
	public static function render( array $props ): Html {
		$tone   = Props::one_of( 'Countdown', $props, 'tone', self::TONES );
		$size   = Props::one_of( 'Countdown', $props, 'size', self::SIZES );
		$digits = ( $props['digits'] ?? 'fa' ) === 'en' ? 'en' : 'fa';
		$left   = max( 0, $props['end'] - time() );
		$parts  = array(
			intdiv( $left, 86400 ),
			intdiv( $left % 86400, 3600 ),
			intdiv( $left % 3600, 60 ),
			$left % 60,
		);
		if ( 0 === $parts[0] ) {
			array_shift( $parts );
		}
		$box = Attributes::classes(
			'grid place-items-center rounded-sm px-1 font-num font-bold tabular-nums',
			match ( $size ) {
				'sm' => 'h-6 min-w-6 text-xs',
				'lg' => 'h-11 min-w-11 text-xl',
				default => 'h-7.5 min-w-7.5 text-md',
			},
			match ( $tone ) {
				'deal'  => 'bg-accent-soft text-accent-text',
				'light' => 'bg-white/20 text-white',
				default => 'bg-surface text-fg shadow-xs',
			}
		);
		$cells = array();
		foreach ( $parts as $part ) {
			$cells[] = sprintf( '<span class="%s" data-part>%s</span>', esc_attr( $box ), esc_html( self::digits( str_pad( (string) $part, 2, '0', STR_PAD_LEFT ), $digits ) ) );
		}
		return Html::trusted(
			sprintf(
				'<span%1$s>%2$s</span>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-countdown inline-flex items-center gap-1 font-bold', $props['class'] ?? null ),
						'dir'               => 'ltr',
						'role'              => 'timer',
						'aria-label'        => $props['label'],
						'data-um-component' => 'countdown',
						'data-end'          => (string) $props['end'],
						'data-digits'       => $digits,
						'data-ended'        => $props['ended'] ?? null,
					)
				),
				implode( '<b class="opacity-80" aria-hidden="true">:</b>', $cells )
			)
		);
	}

	/**
	 * Writes digits in Persian when asked.
	 *
	 * @param string $text   Latin digits.
	 * @param string $digits fa or en.
	 */
	private static function digits( string $text, string $digits ): string {
		return 'fa' === $digits ? strtr( $text, array_combine( range( '0', '9' ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ) ) ) : $text;
	}
}
