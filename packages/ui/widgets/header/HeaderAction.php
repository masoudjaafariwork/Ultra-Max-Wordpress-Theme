<?php
/**
 * HeaderAction component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A link or button in the header: cart, account, wishlist, compare, search
 * or menu. Shown as a round icon with an optional number badge, or as an
 * outlined button with text, like "Log in | Sign up".
 *
 * `count_key` names the counter, so pages can update every badge of that
 * kind at once (for example the cart after adding a product): the badge
 * element carries `data-um-count="<key>"`.
 *
 * @phpstan-type HeaderActionProps array{
 *     label: string,
 *     icon: string,
 *     href?: string,
 *     open?: string,
 *     text?: string,
 *     count?: int|null,
 *     count_text?: string,
 *     count_key?: string,
 *     variant?: 'icon'|'button'|'stacked',
 *     class?: string,
 * }
 */
final class HeaderAction {

	public const VARIANTS = array( 'icon', 'button', 'stacked' );

	/**
	 * The number badge alone, for example to send updated counts after a
	 * product was added to the cart.
	 *
	 * @param string      $key   Counter name.
	 * @param int         $count Number; 0 hides the badge.
	 * @param string|null $text  The number as shown, such as Persian digits.
	 */
	public static function badge( string $key, int $count, ?string $text = null ): Html {
		return Html::trusted(
			sprintf(
				'<span class="absolute -top-1 -start-1 grid h-4.5 min-w-4.5 place-items-center rounded-pill border-2 border-surface bg-primary px-1 font-num text-2xs font-bold text-on-primary empty:hidden" data-um-count="%1$s">%2$s</span>',
				esc_attr( $key ),
				$count > 0 ? esc_html( $text ?? (string) $count ) : ''
			)
		);
	}

	/**
	 * Renders the action.
	 *
	 * @param array $props Props.
	 * @phpstan-param HeaderActionProps $props
	 */
	public static function render( array $props ): Html {
		$variant = Props::one_of( 'HeaderAction', $props, 'variant', self::VARIANTS );
		$badge   = isset( $props['count_key'] ) ? (string) self::badge( $props['count_key'], $props['count'] ?? 0, $props['count_text'] ?? null ) : '';
		$classes = match ( $variant ) {
			'button'  => 'relative inline-flex h-9 items-center gap-1.5 rounded-sm border border-border-strong bg-surface px-3 text-sm font-bold text-fg no-underline hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40',
			'stacked' => 'relative inline-flex flex-col items-center gap-0.5 px-1 text-2xs text-fg-2 no-underline hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40',
			default   => 'relative inline-flex size-11 items-center justify-center rounded-md text-fg no-underline hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40',
		};
		$icon  = sprintf( '<span class="relative inline-flex">%1$s%2$s</span>', Icon::render( $props['icon'], 'button' === $variant ? 'size-4.5' : 'size-6' ), 'button' === $variant ? '' : $badge );
		$text  = isset( $props['text'] ) && '' !== $props['text'] && 'icon' !== $variant ? sprintf( '<span>%s</span>', esc_html( $props['text'] ) ) : '';
		$attrs = array(
			'class'      => Attributes::classes( 'um-header-action', $classes, $props['class'] ?? null ),
			'aria-label' => '' === $text ? $props['label'] : null,
		);
		if ( isset( $props['open'], $props['href'] ) ) {
			// A link that opens a drawer, and still goes to its page without JavaScript.
			$attrs = array_merge(
				array(
					'href'          => esc_url( $props['href'] ),
					'data-um-open'  => $props['open'],
					'aria-haspopup' => 'dialog',
				),
				$attrs
			);
			return Html::trusted( sprintf( '<a%1$s>%2$s%3$s%4$s</a>', Attributes::render( $attrs ), $icon, $text, 'button' === $variant ? $badge : '' ) );
		}
		if ( isset( $props['open'] ) ) {
			$attrs = array_merge(
				array(
					'type'          => 'button',
					'data-um-open'  => $props['open'],
					'aria-haspopup' => 'dialog',
				),
				$attrs
			);
			return Html::trusted( sprintf( '<button%1$s>%2$s%3$s%4$s</button>', Attributes::render( $attrs ), $icon, $text, 'button' === $variant ? $badge : '' ) );
		}
		$attrs = array_merge( array( 'href' => esc_url( $props['href'] ?? '#' ) ), $attrs );
		return Html::trusted( sprintf( '<a%1$s>%2$s%3$s%4$s</a>', Attributes::render( $attrs ), $icon, $text, 'button' === $variant ? $badge : '' ) );
	}
}
