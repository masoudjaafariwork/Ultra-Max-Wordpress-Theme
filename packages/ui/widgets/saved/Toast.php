<?php
/**
 * Toast component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A short message at the bottom of the screen after an action, such as
 * "Added to your wishlist" with a link to the list. Printed once, empty
 * and hidden; scripts fill it with `showToast()` from saved.behavior.
 * Screen readers hear it through its live region. `lists` names the saved
 * lists that are on (`wishlist,compare`), for the saved script.
 *
 * @phpstan-type ToastProps array{
 *     close: string,
 *     lists?: string,
 *     class?: string,
 * }
 */
final class Toast {

	public const ID = 'um-toast';

	/**
	 * Classes of a link or button inside the toast, for scripts that add one.
	 */
	public const ACTION = 'inline-flex h-8 items-center rounded-sm border-0 bg-white/15 px-3 text-sm font-bold whitespace-nowrap text-white no-underline cursor-pointer hover:bg-white/25';

	/**
	 * Renders the empty toast.
	 *
	 * @param array $props Props.
	 * @phpstan-param ToastProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div%1$s><div class="flex items-center gap-3 rounded-md bg-tooltip px-4 py-3 text-sm text-white shadow-lg"><p class="m-0 flex-1" data-um-toast-text></p><span class="flex items-center gap-2" data-um-toast-actions></span><button type="button" class="grid size-7 shrink-0 cursor-pointer place-items-center rounded-full border-0 bg-transparent p-0 text-white/80 hover:text-white focus-visible:outline-2 focus-visible:outline-white" data-um-toast-close aria-label="%2$s">%3$s</button></div></div>',
				Attributes::render(
					array(
						'id'                => self::ID,
						'class'             => Attributes::classes( 'um-scope um-toast fixed inset-x-4 bottom-4 z-(--um-z-toast) mx-auto max-w-120 [&[hidden]]:hidden', $props['class'] ?? null ),
						'role'              => 'status',
						'aria-live'         => 'polite',
						'hidden'            => true,
						'data-action-class' => self::ACTION,
						'data-lists'        => $props['lists'] ?? '',
					)
				),
				esc_attr( $props['close'] ),
				Icon::render( 'close', 'size-4' )
			)
		);
	}
}
