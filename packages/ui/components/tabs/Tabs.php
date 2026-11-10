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
 *     variant?: 'line'|'pill',
 *     start?: Html,
 *     class?: string,
 * }
 *
 * pill is the compact look of home page sections; start puts content,
 * such as a section title, on the same row before the tabs.
 */
final class Tabs {

	private const PILL = 'inline-flex items-center whitespace-nowrap rounded-sm border-0 bg-transparent px-3.5 py-1 text-sm font-medium text-fg-2 cursor-pointer hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40 aria-selected:bg-surface aria-selected:text-fg aria-selected:shadow-xs';

	private const TAB = 'relative -mb-px inline-flex items-center gap-2 whitespace-nowrap border-0 border-b-3 border-solid border-transparent bg-transparent px-0.5 py-3 font-medium text-fg-2 cursor-pointer hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40 aria-selected:border-primary aria-selected:text-primary';

	/**
	 * Renders the tabs.
	 *
	 * @param array $props Props.
	 * @phpstan-param TabsProps $props
	 */
	public static function render( array $props ): Html {
		$id       = Props::id( 'Tabs', $props );
		$items    = $props['items'];
		$pill     = 'pill' === ( $props['variant'] ?? 'line' );
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
						'class'         => $pill ? self::PILL : self::TAB,
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
						'class'           => Attributes::classes( $pill ? 'focus-visible:outline-3 focus-visible:outline-primary/40' : 'py-4 focus-visible:outline-3 focus-visible:outline-primary/40', $is_selected ? null : 'hidden' ),
						'aria-labelledby' => $tab_id,
						'tabindex'        => '0',
					)
				),
				Html::from( $item['content'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div%1$s>%5$s<div role="tablist" aria-label="%2$s" class="%6$s">%3$s</div>%7$s%4$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope', $props['class'] ?? null ),
						'data-um-component' => 'tabs',
					)
				),
				esc_attr( $props['label'] ),
				$tabs,
				$panels,
				isset( $props['start'] ) ? '<div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">' . $props['start'] : '',
				$pill ? 'inline-flex max-w-full gap-1 overflow-x-auto rounded-md bg-surface-3 p-0.75 [scrollbar-width:none] max-md:w-full' : 'flex gap-6 overflow-x-auto border-b border-border [scrollbar-width:none]',
				isset( $props['start'] ) ? '</div>' : ''
			)
		);
	}
}
