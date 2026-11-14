<?php
/**
 * ProfileCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The shopper at the top of the account menu: a round initial, their name,
 * a line such as their mobile number, and a link to edit their details.
 *
 * @phpstan-type ProfileCardProps array{
 *     name: string,
 *     line?: string,
 *     line_dir?: 'ltr'|'rtl'|'auto',
 *     edit?: array{label: string, href: string},
 *     class?: string,
 * }
 */
final class ProfileCard {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProfileCardProps $props
	 */
	public static function render( array $props ): Html {
		$name    = trim( $props['name'] );
		$initial = '' === $name ? '?' : mb_substr( $name, 0, 1 );
		$edit    = isset( $props['edit'] )
			? sprintf(
				'<a href="%1$s" class="grid size-9 shrink-0 place-items-center rounded-full text-fg-2 no-underline hover:bg-surface-3 hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40" aria-label="%2$s" title="%2$s">%3$s</a>',
				esc_url( $props['edit']['href'] ),
				esc_attr( $props['edit']['label'] ),
				Icon::render( 'edit', 'size-5' )
			)
			: '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><span class="grid size-12 shrink-0 place-items-center rounded-full bg-primary text-lg font-bold text-on-primary" aria-hidden="true">%2$s</span><div class="grid min-w-0 flex-1 gap-0.5"><b class="truncate text-md font-bold">%3$s</b>%4$s</div>%5$s</div>',
				esc_attr( Attributes::classes( 'um-profile-card flex items-center gap-3 px-1 py-1', $props['class'] ?? null ) ),
				esc_html( $initial ),
				esc_html( $name ),
				isset( $props['line'] ) && '' !== $props['line'] ? sprintf( '<span class="truncate font-num text-xs text-fg-3" dir="%1$s">%2$s</span>', esc_attr( $props['line_dir'] ?? 'auto' ), esc_html( $props['line'] ) ) : '',
				$edit
			)
		);
	}
}
