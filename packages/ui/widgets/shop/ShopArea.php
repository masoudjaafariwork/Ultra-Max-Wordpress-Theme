<?php
/**
 * ShopArea component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The frame of a product list page that the shop behaviour drives
 * (PLP-01, PLP-04). Inside it, every piece that changes with the filters
 * is a part (`part()`): when a filter, a sort choice or a page link is
 * used, the behaviour loads the new address and swaps each part for the
 * part of the same name, without reloading the page.
 *
 * Parts work in any arrangement, so a list designed with Elementor
 * updates in place too.
 *
 * @phpstan-type ShopAreaProps array{
 *     id: string,
 *     children: Html|array<int, Html>,
 *     status?: string,
 *     class?: string,
 * }
 */
final class ShopArea {

	/**
	 * Renders the frame.
	 *
	 * @param array $props Props.
	 * @phpstan-param ShopAreaProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<p class="sr-only" role="status" aria-live="polite" data-um-shop-status>%3$s</p></div>',
				Attributes::render(
					array(
						'id'                => Props::id( 'ShopArea', $props ),
						'class'             => Attributes::classes( 'um-shop-area um-scope min-w-0', $props['class'] ?? null ),
						'data-um-component' => 'shop',
					)
				),
				Html::from( $props['children'] ),
				esc_html( $props['status'] ?? '' )
			)
		);
	}

	/**
	 * Wraps a piece that changes with the filters.
	 *
	 * @param string $name    Part name, the same on every page.
	 * @param Html   $content Content.
	 * @param string $classes Layout classes.
	 */
	public static function part( string $name, Html $content, string $classes = '' ): Html {
		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s</div>',
				Attributes::render(
					array(
						'class'             => '' === $classes ? null : $classes,
						'data-um-shop-part' => $name,
					)
				),
				$content
			)
		);
	}
}
