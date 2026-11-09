<?php
/**
 * Dropdown component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A button that shows a list of links, such as the account menu.
 *
 * Uses the disclosure pattern (aria-expanded on the button), which suits
 * navigation links better than an application menu.
 *
 * @phpstan-type DropdownItem array{label: string|Html, href: string, current?: bool}
 * @phpstan-type DropdownProps array{
 *     id: string,
 *     label: string|Html,
 *     items: non-empty-list<DropdownItem>,
 *     variant?: 'neutral'|'primary'|'outline'|'soft'|'ghost',
 *     class?: string,
 * }
 */
final class Dropdown {

	/**
	 * Renders the dropdown.
	 *
	 * @param DropdownProps $props Props.
	 */
	public static function render( array $props ): Html {
		$id      = Props::id( 'Dropdown', $props );
		$variant = Props::one_of( 'Dropdown', $props, 'variant', array( 'neutral', 'primary', 'outline', 'soft', 'ghost' ) );
		$links   = '';

		foreach ( $props['items'] as $item ) {
			$current = (bool) ( $item['current'] ?? false );
			$links  .= sprintf(
				'<li><a%s>%s</a></li>',
				Attributes::render(
					array(
						'href'         => esc_url( $item['href'] ),
						'class'        => 'block px-4 py-2 text-fg no-underline hover:bg-surface-3 hover:text-fg focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40 aria-[current=page]:font-bold aria-[current=page]:text-primary',
						'aria-current' => $current ? 'page' : null,
					)
				),
				Html::from( $item['label'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<div id="%3$s-menu" class="z-(--um-z-header) hidden min-w-44 rounded-md border border-border bg-surface py-2 shadow-md"><ul class="m-0 list-none p-0">%4$s</ul></div></div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope relative inline-block', $props['class'] ?? null ),
						'data-um-component' => 'dropdown',
					)
				),
				Button::render(
					array(
						'id'       => $id . '-button',
						'variant'  => $variant,
						'attrs'    => array(
							'aria-expanded' => 'false',
							'aria-controls' => $id . '-menu',
						),
						'children' => array( Html::from( $props['label'] ), Icon::render( 'chevron-down', 'size-4' ) ),
					)
				),
				esc_attr( $id ),
				$links
			)
		);
	}
}