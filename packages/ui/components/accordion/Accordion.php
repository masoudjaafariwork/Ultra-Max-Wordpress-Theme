<?php
/**
 * Accordion component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Questions and answers that open one at a time, or several when
 * `multiple` is true.
 *
 * @phpstan-type AccordionItem array{title: string|Html, content: string|Html|array<int, string|Html>, open?: bool}
 * @phpstan-type AccordionProps array{
 *     id: string,
 *     items: non-empty-list<AccordionItem>,
 *     multiple?: bool,
 *     heading?: 'h3'|'h2'|'h4',
 *     class?: string,
 * }
 */
final class Accordion {

	/**
	 * Renders the accordion.
	 *
	 * @param array $props Props.
	 * @phpstan-param AccordionProps $props
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'Accordion', $props );
		$heading = Props::one_of( 'Accordion', $props, 'heading', array( 'h3', 'h2', 'h4' ) );
		$markup  = '';

		foreach ( $props['items'] as $index => $item ) {
			$open      = (bool) ( $item['open'] ?? false );
			$button_id = sprintf( '%s-button-%d', $id, $index );
			$panel_id  = sprintf( '%s-panel-%d', $id, $index );

			$markup .= sprintf(
				'<div class="border-b border-border last:border-b-0"><%1$s class="m-0 text-md font-bold"><button%2$s><span>%3$s</span>%4$s</button></%1$s><div%5$s>%6$s</div></div>',
				$heading,
				Attributes::render(
					array(
						'type'          => 'button',
						'id'            => $button_id,
						'class'         => 'flex w-full items-center justify-between gap-3 border-0 bg-transparent px-4 py-3 text-start font-bold text-fg cursor-pointer hover:bg-surface-2 focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40 aria-expanded:text-primary',
						'aria-expanded' => $open ? 'true' : 'false',
						'aria-controls' => $panel_id,
					)
				),
				Html::from( $item['title'] ),
				Icon::render( 'chevron-down', 'size-5 transition-transform duration-200 in-aria-expanded:rotate-180' ),
				Attributes::render(
					array(
						'id'              => $panel_id,
						'role'            => 'region',
						'class'           => Attributes::classes( 'px-4 pb-4 text-fg-2', $open ? null : 'hidden' ),
						'aria-labelledby' => $button_id,
					)
				),
				Html::from( $item['content'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div%s>%s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope rounded-lg border border-border bg-surface', $props['class'] ?? null ),
						'data-um-component' => 'accordion',
						'data-um-multiple'  => ( $props['multiple'] ?? false ) ? 'true' : null,
					)
				),
				$markup
			)
		);
	}
}
