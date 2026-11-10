<?php
/**
 * LanguageSwitcher component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The site's languages, from a translation plugin such as Polylang or
 * WPML: the current one as a button that opens the others, or all of them
 * in a row.
 *
 * @phpstan-type Language array{code: string, name: string, href: string, current: bool, flag?: string}
 * @phpstan-type LanguageSwitcherProps array{label: string, languages: list<Language>, layout?: 'dropdown'|'inline', id: string}
 */
final class LanguageSwitcher {

	/**
	 * Renders the switcher.
	 *
	 * @param array $props Props.
	 * @phpstan-param LanguageSwitcherProps $props
	 */
	public static function render( array $props ): Html {
		if ( count( $props['languages'] ) < 2 ) {
			return Html::none();
		}
		if ( 'inline' === ( $props['layout'] ?? 'dropdown' ) ) {
			$items = '';
			foreach ( $props['languages'] as $language ) {
				$items .= sprintf(
					'<li><a href="%1$s" lang="%2$s" hreflang="%2$s" class="%3$s"%4$s>%5$s</a></li>',
					esc_url( $language['href'] ),
					esc_attr( $language['code'] ),
					$language['current'] ? 'font-bold text-primary no-underline' : 'text-fg-2 no-underline hover:text-fg',
					$language['current'] ? ' aria-current="true"' : '',
					esc_html( $language['name'] )
				);
			}
			return Html::trusted( sprintf( '<nav aria-label="%1$s"><ul class="m-0 flex list-none gap-3 p-0 text-sm">%2$s</ul></nav>', esc_attr( $props['label'] ), $items ) );
		}
		$current = $props['languages'][0];
		$items   = array();
		foreach ( $props['languages'] as $language ) {
			if ( $language['current'] ) {
				$current = $language;
				continue;
			}
			$items[] = array(
				'label' => $language['name'],
				'href'  => $language['href'],
			);
		}
		if ( array() === $items ) {
			return Html::none();
		}
		return Dropdown::render(
			array(
				'id'      => $props['id'],
				'label'   => Html::from( array( Icon::render( 'globe', 'size-4.5' ), $current['name'] ) ),
				'items'   => $items,
				'variant' => 'ghost',
			)
		);
	}
}
