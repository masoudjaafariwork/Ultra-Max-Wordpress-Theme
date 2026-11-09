<?php
/**
 * EmptyState component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A centred message when there is nothing to show: the 404 page, an empty
 * search, the maintenance screen (CORE-09).
 *
 * @phpstan-type EmptyStateProps array{
 *     title: string,
 *     icon?: string,
 *     code?: string,
 *     text?: string,
 *     heading?: 'h1'|'h2',
 *     children?: Html|list<Html>,
 *     class?: string,
 * }
 */
final class EmptyState {

	/**
	 * Renders the message.
	 *
	 * @param array $props Props.
	 * @phpstan-param EmptyStateProps $props
	 */
	public static function render( array $props ): Html {
		$heading = Props::one_of( 'EmptyState', $props, 'heading', array( 'h2', 'h1' ) );
		$art     = isset( $props['code'] )
			? sprintf( '<p class="m-0 bg-(image:--um-deal-gradient) bg-clip-text text-[clamp(64px,14vw,120px)] font-heavy leading-none text-transparent" aria-hidden="true">%s</p>', esc_html( $props['code'] ) )
			: sprintf( '<span class="grid size-24 place-items-center rounded-pill bg-primary-soft text-primary">%s</span>', Icon::render( $props['icon'] ?? 'info', 'size-12' ) );
		$actions = Html::from( $props['children'] ?? null );
		$text    = isset( $props['text'] ) && '' !== $props['text'] ? sprintf( '<p class="m-0 max-w-xl text-md leading-body text-fg-2">%s</p>', esc_html( $props['text'] ) ) : '';

		return Html::trusted(
			sprintf(
				'<div class="%1$s">%2$s<%3$s class="m-0 text-2xl font-heavy leading-tight text-fg">%4$s</%3$s>%5$s%6$s</div>',
				esc_attr( Attributes::classes( 'mx-auto flex max-w-2xl flex-col items-center gap-4 py-12 text-center', $props['class'] ?? null ) ),
				$art,
				$heading,
				esc_html( $props['title'] ),
				$text,
				$actions->is_empty() ? '' : sprintf( '<div class="flex w-full flex-wrap justify-center gap-3">%s</div>', $actions )
			)
		);
	}
}
