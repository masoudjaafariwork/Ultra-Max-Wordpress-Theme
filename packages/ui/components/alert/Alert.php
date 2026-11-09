<?php
/**
 * Alert component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A message box with an icon and a tone. Colour is never the only signal:
 * every tone has its own icon.
 *
 * @phpstan-type AlertProps array{
 *     children: string|Html|array<int, string|Html>,
 *     tone?: 'info'|'success'|'warning'|'danger',
 *     title?: string,
 *     role?: 'status'|'alert'|'none',
 *     class?: string,
 * }
 */
final class Alert {

	public const TONES = array( 'info', 'success', 'warning', 'danger' );

	private const TONE = array(
		'info'    => array( 'info', 'border-info/25 bg-info-soft [&>svg]:text-info' ),
		'success' => array( 'check', 'border-success/25 bg-success-soft [&>svg]:text-success' ),
		'warning' => array( 'alert', 'border-warning/30 bg-warning-soft [&>svg]:text-warning' ),
		'danger'  => array( 'alert', 'border-danger/25 bg-danger-soft [&>svg]:text-danger' ),
	);

	/**
	 * Renders the alert.
	 *
	 * @param array $props Props.
	 * @phpstan-param AlertProps $props
	 */
	public static function render( array $props ): Html {
		$tone            = Props::one_of( 'Alert', $props, 'tone', self::TONES );
		[ $icon, $look ] = self::TONE[ $tone ];
		$role            = $props['role'] ?? 'none';
		$title           = isset( $props['title'] ) ? sprintf( '<strong class="block text-sm font-bold">%s</strong>', esc_html( $props['title'] ) ) : '';

		return Html::trusted(
			sprintf(
				'<div class="%1$s"%2$s>%3$s<div class="grid min-w-0 gap-1 text-sm leading-body text-fg">%4$s%5$s</div></div>',
				esc_attr( Attributes::classes( 'flex items-start gap-3 rounded-md border p-4', $look, $props['class'] ?? null ) ),
				'none' === $role ? '' : ' role="' . esc_attr( $role ) . '"',
				Icon::render( $icon, 'size-5 mt-0.5' ),
				$title,
				Html::from( $props['children'] )
			)
		);
	}
}
