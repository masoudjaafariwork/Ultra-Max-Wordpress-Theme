<?php
/**
 * Tabs component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Tabs with panels. Keyboard: arrow keys (mirrored in RTL), Home and End.
 *
 * @phpstan-type TabItem array{label: string|Html, content: string|Html|array<int, string|Html>}
 * @phpstan-type TabsProps array{
 *     id: string,
 *     label: string,
 *     items: non-empty-list<TabItem>,
 *     selected?: int,
 *     class?: string,
 * }
 */
final class Tabs {

	private const TAB = 'relative -mb-px inline-flex items-center gap-2 whitespace-nowrap border-0 border-b-3 border-solid border-transparent bg-transparent px-0.5 py-3 font-medium text-fg-2 cursor-pointer hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40 aria-selected:border-primary aria-selected:text-primary';

	/**
	 * Renders the tabs.
	 *
	 * @param TabsProps $props Props.
	 */
	public static function render( array $props ): Html {
		$id       = Props::id( 'Tabs', $props );
		$items    = $props['items'];
		$selected = $props['selected'] ?? 0;
		if ( ! isset( $items[ $selected ] ) ) {
			$selected = 0;
		}

		$tabs   = '';
		$panels = '';
		foreach ( $items as $index => $item ) {
			$is_selected = $index === $selected;
			$tab_id      = sprintf( '%s-tab-%d', $id, $index );
			$panel_id    = sprintf( '%s-panel-%d', $id, $index );

			$tabs .= sprintf(
				'<button%s>%s</button>',
				Attributes::render(
					array(
						'type'          => 'button',
						'role'          => 'tab',
						'id'            => $tab_id,
						'class'         => self::TAB,
						'aria-controls' => $panel_id,
						'aria-selected' => $is_selected ? 'true' : 'false',
						'tabindex'      => $is_selected ? '0' : '-1',
					)
				),
				Html::from( $item['label'] )
			);

			$panels .= sprintf(
				'<div%s>%s</div>',
				Attributes::render(
					array(
						'role'            => 'tabpanel',
						'id'              => $panel_id,
						'class'           => Attributes::classes( 'py-4 focus-visible:outline-3 focus-visible:outline-primary/40', $is_selected ? null : 'hidden' ),
						'aria-labelledby' => $tab_id,
						'tabindex'        => '0',
					)
				),
				Html::from( $item['content'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div%1$s><div role="tablist" aria-label="%2$s" class="flex gap-6 overflow-x-auto border-b border-border [scrollbar-width:none]">%3$s</div>%4$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope', $props['class'] ?? null ),
						'data-um-component' => 'tabs',
					)
				),
				esc_attr( $props['label'] ),
				$tabs,
				$panels
			)
		);
	}
}