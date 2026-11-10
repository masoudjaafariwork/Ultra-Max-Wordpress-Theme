<?php
/**
 * TeamMember component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A person of the team: photo, name, role, a short bio and their social
 * links (HOME-12).
 *
 * @phpstan-type TeamMemberProps array{
 *     name: string,
 *     role?: string,
 *     bio?: string,
 *     photo?: Html,
 *     links?: list<array{label: string, href: string, icon: string}>,
 *     class?: string,
 * }
 */
final class TeamMember {

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param TeamMemberProps $props
	 */
	public static function render( array $props ): Html {
		$links = '';
		foreach ( $props['links'] ?? array() as $link ) {
			$links .= sprintf(
				'<a href="%1$s" class="grid size-9 place-items-center rounded-full bg-surface-3 text-fg-2 no-underline hover:bg-primary-soft hover:text-primary" aria-label="%2$s" rel="noopener" target="_blank">%3$s</a>',
				esc_url( $link['href'] ),
				esc_attr( $link['label'] ),
				Icon::render( $link['icon'], 'size-4.5' )
			);
		}
		$photo = isset( $props['photo'] ) && ! $props['photo']->is_empty()
			? sprintf( '<span class="block aspect-square overflow-hidden rounded-lg bg-surface-3 [&_img]:size-full [&_img]:object-cover">%s</span>', $props['photo'] )
			: '';
		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<div class="grid gap-0.5"><h3 class="m-0 text-md font-bold">%3$s</h3>%4$s</div>%5$s%6$s</div>',
				esc_attr( Attributes::classes( 'um-team-member flex flex-col gap-3 text-center', $props['class'] ?? null ) ),
				$photo,
				esc_html( $props['name'] ),
				isset( $props['role'] ) && '' !== $props['role'] ? sprintf( '<span class="text-sm text-primary">%s</span>', esc_html( $props['role'] ) ) : '',
				isset( $props['bio'] ) && '' !== $props['bio'] ? sprintf( '<p class="m-0 text-sm text-fg-2">%s</p>', esc_html( $props['bio'] ) ) : '',
				'' === $links ? '' : sprintf( '<div class="flex justify-center gap-2">%s</div>', $links )
			)
		);
	}
}
