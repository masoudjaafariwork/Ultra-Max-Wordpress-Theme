<?php
/**
 * Prose component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Typography for content written in the editor: headings, lists, quotes,
 * tables, code, images, captions, galleries, embeds and block alignments.
 * The looks live in prose.css because the markup comes from the editor.
 *
 * @phpstan-type ProseProps array{
 *     children: Html,
 *     class?: string,
 * }
 */
final class Prose {

	/**
	 * Renders the content wrapper.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProseProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div class="%s">%s</div>',
				esc_attr( Attributes::classes( 'um-prose', $props['class'] ?? null ) ),
				$props['children']
			)
		);
	}
}
