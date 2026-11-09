<?php
/**
 * Drawer component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A panel that slides in from a side edge, such as the mobile menu or the
 * mini cart. Same behaviour as Dialog, on the native `dialog` element.
 *
 * `side` is logical: "start" is the right edge in Persian and the left edge
 * in English.
 *
 * @phpstan-type DrawerProps array{
 *     id: string,
 *     title: string|Html,
 *     children: string|Html|array<int, string|Html>,
 *     footer?: string|Html|array<int, string|Html>,
 *     side?: 'start'|'end',
 *     class?: string,
 * }
 */
final class Drawer {

	public const SIDES = array( 'start', 'end' );

	/**
	 * Renders the drawer.
	 *
	 * @param array $props Props.
	 * @phpstan-param DrawerProps $props
	 */
	public static function render( array $props ): Html {
		$id   = Props::id( 'Drawer', $props );
		$side = Props::one_of( 'Drawer', $props, 'side', self::SIDES );

		return Html::trusted(
			sprintf(
				'<dialog%1$s>%2$s<div class="flex-1 overflow-y-auto p-4">%3$s</div>%4$s</dialog>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes(
							'um-scope um-drawer fixed inset-y-0 m-0 h-dvh max-h-none w-[min(400px,92vw)] max-w-none flex-col border-0 bg-surface p-0 text-fg shadow-lg open:flex',
							'start' === $side ? 'start-0 end-auto um-drawer-start' : 'end-0 start-auto um-drawer-end',
							$props['class'] ?? null
						),
						'aria-labelledby'   => $id . '-title',
						'data-um-component' => 'drawer',
					)
				),
				Overlay::header( $id, $props['title'] ),
				Html::from( $props['children'] ),
				Overlay::footer( $props['footer'] ?? null )
			)
		);
	}
}
