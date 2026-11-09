<?php
/**
 * Badge component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A short label, such as a category, "Sticky" or a status.
 *
 * @phpstan-type BadgeProps array{
 *     children: string|Html,
 *     tone?: 'neutral'|'brand'|'accent'|'success'|'warning'|'danger'|'info',
 *     class?: string,
 * }
 */
final class Badge {

	public const TONES = array( 'neutral', 'brand', 'accent', 'success', 'warning', 'danger', 'info' );

	private const TONE_CLASSES = array(
		'neutral' => 'bg-surface-3 text-fg-2',
		'brand'   => 'bg-primary-soft text-primary',
		'accent'  => 'bg-accent-soft text-on-accent',
		'success' => 'bg-success-soft text-success',
		'warning' => 'bg-warning-soft text-[color-mix(in_srgb,var(--um-warning)_70%,#000)]',
		'danger'  => 'bg-danger-soft text-danger',
		'info'    => 'bg-info-soft text-info',
	);

	/**
	 * Renders the badge.
	 *
	 * @param array $props Props.
	 * @phpstan-param BadgeProps $props
	 */
	public static function render( array $props ): Html {
		$tone = Props::one_of( 'Badge', $props, 'tone', self::TONES );
		return Html::trusted(
			sprintf(
				'<span class="%s">%s</span>',
				esc_attr( Attributes::classes( 'inline-flex h-6 w-fit items-center gap-1 rounded-pill px-2.5 text-xs font-bold whitespace-nowrap', self::TONE_CLASSES[ $tone ], $props['class'] ?? null ) ),
				Html::from( $props['children'] )
			)
		);
	}
}
