<?php
/**
 * LayoutRegion component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The frame around a layout made with the layout builder (BLD-01): a
 * `header`, `footer` or `div` landmark that can stay at the top while the
 * page scrolls.
 *
 * - `none`: scrolls away with the page.
 * - `sticky`: always stays at the top.
 * - `smart`: hides while scrolling down and comes back on the first scroll
 *   up (HDR-05). Its behaviour loads only when a page uses it.
 *
 * @phpstan-type LayoutRegionProps array{
 *     location: string,
 *     children: Html,
 *     id?: int,
 *     sticky?: 'none'|'sticky'|'smart',
 *     class?: string,
 * }
 */
final class LayoutRegion {

	public const STICKY = array( 'none', 'sticky', 'smart' );

	/**
	 * Renders the region.
	 *
	 * @param array $props Props.
	 * @phpstan-param LayoutRegionProps $props
	 */
	public static function render( array $props ): Html {
		$sticky = Props::one_of( 'LayoutRegion', $props, 'sticky', self::STICKY );
		$tag    = match ( $props['location'] ) {
			'header' => 'header',
			'footer' => 'footer',
			default  => 'div',
		};
		return Html::trusted(
			sprintf(
				'<%1$s%2$s>%3$s</%1$s>',
				$tag,
				Attributes::render(
					array(
						'class'             => Attributes::classes(
							'um-scope um-layout um-layout-' . sanitize_html_class( $props['location'] ),
							'none' === $sticky ? 'relative' : 'sticky top-[var(--wp-admin--admin-bar--height,0px)] z-(--um-z-header)',
							'smart' === $sticky ? 'transition-transform duration-300 ease-um data-hidden:-translate-y-full motion-reduce:transition-none' : null,
							$props['class'] ?? null
						),
						'data-layout'       => isset( $props['id'] ) ? (string) $props['id'] : null,
						'data-um-component' => 'smart' === $sticky ? 'smart-header' : null,
					)
				),
				$props['children']
			)
		);
	}
}
