<?php
/**
 * QuickLinks component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Rounded icon tiles under the hero, such as "amazing offers" or "track my
 * order": eight in a row on desktop, four on phones. Each tile has its own
 * colour, softened behind the icon.
 *
 * @phpstan-type QuickLink array{label: string, href: string, icon: string, color?: string}
 * @phpstan-type QuickLinksProps array{items: list<QuickLink>, label: string, class?: string}
 */
final class QuickLinks {

	/**
	 * Renders the tiles.
	 *
	 * @param array $props Props.
	 * @phpstan-param QuickLinksProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$color  = $item['color'] ?? '';
			$color  = 1 === preg_match( '/^#[0-9a-fA-F]{3,8}$/', $color ) ? $color : 'var(--um-primary)';
			$items .= sprintf(
				'<li><a href="%1$s" class="group flex flex-col items-center gap-2 text-center text-xs font-medium text-fg-2 no-underline hover:text-fg" style="--c:%2$s"><span class="grid size-14 place-items-center rounded-[18px] bg-[color-mix(in_srgb,var(--c)_12%%,white)] text-(--c) transition-transform duration-200 group-hover:-translate-y-0.5 max-md:size-12.5 max-md:rounded-2xl">%3$s</span>%4$s</a></li>',
				esc_url( $item['href'] ),
				esc_attr( $color ),
				Icon::render( $item['icon'], 'size-7' ),
				esc_html( $item['label'] )
			);
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s" aria-label="%2$s">%3$s</ul>',
				esc_attr( Attributes::classes( 'um-quick-links m-0 grid list-none grid-cols-4 gap-x-2 gap-y-4 p-0 lg:grid-cols-8', $props['class'] ?? null ) ),
				esc_attr( $props['label'] ),
				$items
			)
		);
	}
}
