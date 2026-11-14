<?php
/**
 * AccountLayout component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The frame of the customer account (design/account.html): a side column
 * with the profile and the menu, and the page of the chosen menu item. On
 * phones the side column comes first and the menu scrolls sideways.
 *
 * @phpstan-type AccountLayoutProps array{
 *     side: Html,
 *     main: Html,
 *     class?: string,
 * }
 */
final class AccountLayout {

	/**
	 * Renders the frame.
	 *
	 * @param array $props Props.
	 * @phpstan-param AccountLayoutProps $props
	 */
	public static function render( array $props ): Html {
		return Html::trusted(
			sprintf(
				'<div class="%1$s"><aside class="um-account-side grid min-w-0 content-start gap-3 rounded-lg border border-border bg-surface p-3 lg:sticky lg:top-4">%2$s</aside><div class="um-account-main grid min-w-0 content-start gap-4">%3$s</div></div>',
				esc_attr( Attributes::classes( 'um-scope um-account grid items-start gap-5 lg:grid-cols-[280px_minmax(0,1fr)] max-lg:gap-3', $props['class'] ?? null ) ),
				$props['side'],
				$props['main']
			)
		);
	}
}
