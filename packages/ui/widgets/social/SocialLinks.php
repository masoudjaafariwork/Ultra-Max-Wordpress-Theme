<?php
/**
 * SocialLinks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Round links to the store's pages on social networks and messengers
 * (HOME-12, HDR-11): Instagram, Telegram, WhatsApp, Eitaa, Bale, Aparat
 * and others, each named for screen readers.
 *
 * @phpstan-type SocialLinksProps array{label: string, items: list<array{label: string, href: string, icon: string}>, size?: 'sm'|'md', class?: string}
 */
final class SocialLinks {

	/**
	 * Renders the links.
	 *
	 * @param array $props Props.
	 * @phpstan-param SocialLinksProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$small = 'sm' === ( $props['size'] ?? 'md' );
		$items = '';
		foreach ( $props['items'] as $item ) {
			$items .= sprintf(
				'<li><a href="%1$s" class="%2$s" aria-label="%3$s" target="_blank" rel="noopener">%4$s</a></li>',
				esc_url( $item['href'] ),
				esc_attr( Attributes::classes( 'grid place-items-center rounded-md bg-surface-3 text-fg-2 no-underline hover:bg-primary-soft hover:text-primary focus-visible:outline-3 focus-visible:outline-primary/40', $small ? 'size-9' : 'size-11' ) ),
				esc_attr( $item['label'] ),
				Icon::render( $item['icon'], $small ? 'size-4.5' : 'size-5' )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-social m-0 flex list-none flex-wrap gap-2 p-0', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
