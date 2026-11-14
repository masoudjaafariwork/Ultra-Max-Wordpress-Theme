<?php
/**
 * SaveButton component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Puts a product in the wishlist or the compare list, or opens its quick
 * view (SAVE-01, SAVE-02, SAVE-04).
 *
 * The page never knows whose it is, so it stays cacheable: every button
 * renders unpressed, and the saved script marks the products of the
 * shopper's lists as pressed (`aria-pressed`) once the page has loaded.
 *
 * - `card`: a small round button in a product card's corner.
 * - `tool`: a round button over the product page's gallery.
 * - `button`: an outlined button with its text, for widgets.
 *
 * `list` is `wishlist`, `compare` or `quick` (quick view, which is not a
 * toggle).
 *
 * @phpstan-type SaveButtonProps array{
 *     list: 'wishlist'|'compare'|'quick',
 *     product: int,
 *     label: string,
 *     text?: string,
 *     look?: 'card'|'tool'|'button',
 *     icon?: string,
 *     class?: string,
 * }
 */
final class SaveButton {

	public const LOOKS = array( 'card', 'tool', 'button' );

	private const ICONS = array(
		'wishlist' => 'heart',
		'compare'  => 'compare',
		'quick'    => 'eye',
	);

	private const LOOK_CLASSES = array(
		'card'   => 'grid size-8 place-items-center rounded-full border-0 bg-surface/90 p-0 text-fg-2 shadow-xs hover:text-fg',
		'tool'   => 'grid size-10 place-items-center rounded-full border-0 bg-transparent p-0 text-fg-2 hover:bg-surface-3 hover:text-fg max-md:size-9 max-md:bg-surface max-md:shadow-sm',
		'button' => 'inline-flex h-9 items-center gap-1.5 rounded-sm border border-border-strong bg-surface px-3 text-sm font-medium text-fg hover:bg-surface-3',
	);

	/**
	 * Renders the button.
	 *
	 * @param array $props Props.
	 * @phpstan-param SaveButtonProps $props
	 */
	public static function render( array $props ): Html {
		$look  = Props::one_of( 'SaveButton', $props, 'look', self::LOOKS );
		$list  = $props['list'];
		$state = 'quick' === $list ? array() : array(
			'data-um-save' => $list,
			'aria-pressed' => 'false',
		);
		$text  = 'button' === $look ? sprintf( '<span>%s</span>', esc_html( $props['text'] ?? $props['label'] ) ) : '';
		return Html::trusted(
			sprintf(
				'<button%1$s>%2$s%3$s</button>',
				Attributes::render(
					array_merge(
						array(
							'type'          => 'button',
							'class'         => Attributes::classes(
								'um-save-button cursor-pointer focus-visible:outline-3 focus-visible:outline-primary/40 aria-busy:opacity-60',
								self::LOOK_CLASSES[ $look ],
								'wishlist' === $list ? 'aria-pressed:text-danger aria-pressed:[&_svg]:fill-current' : 'aria-pressed:bg-primary-soft aria-pressed:text-primary',
								$props['class'] ?? null
							),
							'aria-label'    => '' === $text ? $props['label'] : null,
							'title'         => '' === $text ? $props['label'] : null,
							'data-id'       => (string) $props['product'],
							'data-um-quick' => 'quick' === $list ? (string) $props['product'] : null,
						),
						$state
					)
				),
				Icon::render( $props['icon'] ?? self::ICONS[ $list ], 'card' === $look ? 'size-4.5' : 'size-5' ),
				$text
			)
		);
	}
}
