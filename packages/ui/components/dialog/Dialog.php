<?php
/**
 * Dialog component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A modal window on the browser's native `dialog` element.
 *
 * Open it with a Button whose `opens` prop is the dialog id, or from code
 * with `UltraMaxUI.get( element ).open()`.
 *
 * `full` fills the screen on phones and is a wide window elsewhere, as the
 * phone search does (SRCH-04).
 *
 * @phpstan-type DialogProps array{
 *     id: string,
 *     title: string|Html,
 *     children: string|Html|array<int, string|Html>,
 *     footer?: string|Html|array<int, string|Html>,
 *     size?: 'md'|'sm'|'lg'|'full',
 *     class?: string,
 * }
 */
final class Dialog {

	public const SIZES = array( 'md', 'sm', 'lg', 'full' );

	private const SIZE_CLASSES = array(
		'sm'   => 'w-[min(400px,calc(100vw-32px))]',
		'md'   => 'w-[min(560px,calc(100vw-32px))]',
		'lg'   => 'w-[min(800px,calc(100vw-32px))]',
		'full' => 'w-[min(680px,calc(100vw-32px))] max-md:m-0 max-md:h-dvh max-md:max-h-none max-md:w-full max-md:max-w-none max-md:rounded-none',
	);

	/**
	 * Renders the dialog.
	 *
	 * @param array $props Props.
	 * @phpstan-param DialogProps $props
	 */
	public static function render( array $props ): Html {
		$id   = Props::id( 'Dialog', $props );
		$size = Props::one_of( 'Dialog', $props, 'size', self::SIZES );

		return Html::trusted(
			sprintf(
				'<dialog%1$s>%2$s<div class="flex-1 overflow-y-auto p-5">%3$s</div>%4$s</dialog>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes(
							'um-scope um-dialog m-auto max-h-[calc(100dvh-48px)] flex-col rounded-xl border-0 bg-surface p-0 text-fg shadow-lg open:flex',
							self::SIZE_CLASSES[ $size ],
							$props['class'] ?? null
						),
						'aria-labelledby'   => $id . '-title',
						'data-um-component' => 'dialog',
					)
				),
				Overlay::header( $id, $props['title'] ),
				Html::from( $props['children'] ),
				Overlay::footer( $props['footer'] ?? null )
			)
		);
	}
}
