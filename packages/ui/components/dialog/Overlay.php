<?php
/**
 * Pieces shared by Dialog and Drawer.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Header with title and close button, and the optional footer.
 *
 * @internal Used by Dialog and Drawer only.
 */
final class Overlay {

	/**
	 * Renders the header.
	 *
	 * @param string      $id    Id of the dialog.
	 * @param string|Html $title Title.
	 */
	public static function header( string $id, string|Html $title ): Html {
		return Html::trusted(
			sprintf(
				'<div class="flex min-h-15 items-center justify-between gap-3 border-b border-border px-5 py-3"><h2 id="%1$s-title" class="m-0 font-heading text-lg font-bold leading-tight">%2$s</h2>%3$s</div>',
				esc_attr( $id ),
				Html::from( $title ),
				self::close_button()
			)
		);
	}

	/**
	 * Renders the footer, or nothing.
	 *
	 * @param string|Html|array<int, string|Html>|null $footer Footer content.
	 */
	public static function footer( string|Html|array|null $footer ): Html {
		$content = Html::from( $footer );
		if ( $content->is_empty() ) {
			return Html::none();
		}
		return Html::trusted( sprintf( '<div class="flex flex-wrap items-center justify-end gap-2 border-t border-border p-4">%s</div>', $content ) );
	}

	/**
	 * Renders the close button.
	 */
	private static function close_button(): Html {
		return Button::render(
			array(
				'variant'  => 'ghost',
				'size'     => 'sm',
				'label'    => __( 'Close', 'ultra-max-ui' ),
				'class'    => 'px-2',
				'attrs'    => array( 'data-um-close' => true ),
				'children' => Icon::render( 'close' ),
			)
		);
	}
}