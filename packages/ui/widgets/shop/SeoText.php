<?php
/**
 * SeoText component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A long text under a product list, such as the category's buying guide
 * (PLP-10). The whole text is in the page for search engines; with
 * JavaScript it is folded to a few lines with a "show more" button.
 *
 * @phpstan-type SeoTextProps array{
 *     children: Html,
 *     more: string,
 *     less: string,
 *     title?: string,
 *     id: string,
 *     variant?: 'box'|'plain',
 *     class?: string,
 * }
 *
 * `plain` drops the frame, for a text inside another box such as a
 * product's introduction.
 */
final class SeoText {

	/**
	 * Renders the text, or nothing when it is empty.
	 *
	 * @param array $props Props.
	 * @phpstan-param SeoTextProps $props
	 */
	public static function render( array $props ): Html {
		if ( $props['children']->is_empty() ) {
			return Html::none();
		}
		$id = Props::id( 'SeoText', $props );
		return Html::trusted(
			sprintf(
				'<section%1$s>%2$s<div id="%3$s-text" class="relative text-sm leading-body text-fg-2 data-folded:max-h-24 data-folded:overflow-hidden data-folded:after:absolute data-folded:after:inset-x-0 data-folded:after:bottom-0 data-folded:after:h-10 data-folded:after:bg-linear-to-b data-folded:after:from-transparent data-folded:after:to-surface [&_h2]:mt-4 [&_h2]:mb-2 [&_h2]:text-lg [&_h2]:text-fg [&_h3]:mt-3 [&_h3]:mb-2 [&_h3]:text-md [&_h3]:text-fg [&_p]:my-2" data-um-fold>%4$s</div><button type="button" class="mt-2 hidden cursor-pointer border-0 bg-transparent p-0 text-sm font-bold text-link hover:underline data-ready:inline-block" aria-controls="%3$s-text" aria-expanded="false" data-more="%5$s" data-less="%6$s">%5$s</button></section>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-seo-text', 'plain' === Props::one_of( 'SeoText', $props, 'variant', array( 'box', 'plain' ) ) ? null : 'rounded-lg border border-border bg-surface p-5 max-md:p-4', $props['class'] ?? null ),
						'data-um-component' => 'seo-text',
					)
				),
				isset( $props['title'] ) && '' !== $props['title'] ? sprintf( '<h2 class="m-0 mb-3 text-lg font-bold">%s</h2>', esc_html( $props['title'] ) ) : '',
				esc_attr( $id ),
				$props['children'],
				esc_attr( $props['more'] ),
				esc_attr( $props['less'] )
			)
		);
	}
}
