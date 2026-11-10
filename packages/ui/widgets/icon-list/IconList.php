<?php
/**
 * IconList component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A list where each line starts with an icon and may be a link (HOME-12):
 * features, contact details or footer links.
 *
 * @phpstan-type IconListItem array{text: string, icon?: string, href?: string}
 * @phpstan-type IconListProps array{items: list<IconListItem>, label?: string, layout?: 'column'|'row', class?: string}
 */
final class IconList {

	/**
	 * A small title above a list, as in footer columns.
	 *
	 * @param string $text Title.
	 */
	public static function title( string $text ): Html {
		return Html::trusted( sprintf( '<h3 class="m-0 mb-3 text-md font-bold">%s</h3>', esc_html( $text ) ) );
	}

	/**
	 * Renders the list.
	 *
	 * @param array $props Props.
	 * @phpstan-param IconListProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$items = '';
		foreach ( $props['items'] as $item ) {
			$icon   = isset( $item['icon'] ) && '' !== $item['icon'] ? Icon::render( $item['icon'], 'size-4.5 text-primary' ) : '';
			$text   = isset( $item['href'] ) && '' !== $item['href']
				? sprintf( '<a href="%1$s" class="text-fg-2 no-underline hover:text-primary">%2$s</a>', esc_url( $item['href'] ), esc_html( $item['text'] ) )
				: sprintf( '<span>%s</span>', esc_html( $item['text'] ) );
			$items .= sprintf( '<li class="flex items-center gap-2">%1$s%2$s</li>', $icon, $text );
		}
		return Html::trusted(
			sprintf(
				'<ul class="%1$s"%2$s>%3$s</ul>',
				esc_attr( Attributes::classes( 'um-icon-list m-0 list-none p-0 text-sm text-fg-2', 'row' === ( $props['layout'] ?? 'column' ) ? 'flex flex-wrap gap-x-5 gap-y-2' : 'grid gap-2', $props['class'] ?? null ) ),
				isset( $props['label'] ) ? sprintf( ' aria-label="%s"', esc_attr( $props['label'] ) ) : '',
				$items
			)
		);
	}
}
