<?php
/**
 * BackToTop component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A round button that scrolls back to the top (CORE-12). It appears once
 * the visitor has scrolled down and moves focus to the top of the page.
 *
 * @phpstan-type BackToTopProps array{
 *     label: string,
 *     target?: string,
 *     side?: 'start'|'end',
 *     class?: string,
 * }
 */
final class BackToTop {

	/**
	 * Renders the button.
	 *
	 * @param array $props Props.
	 * @phpstan-param BackToTopProps $props
	 */
	public static function render( array $props ): Html {
		$side = Props::one_of( 'BackToTop', $props, 'side', array( 'start', 'end' ) );
		return Html::trusted(
			sprintf(
				'<a href="#%1$s" class="%2$s" aria-label="%3$s" data-um-component="back-to-top" data-visible="false">%4$s</a>',
				esc_attr( $props['target'] ?? 'top' ),
				esc_attr(
					Attributes::classes(
						'um-scope fixed bottom-6 z-(--um-z-sticky) grid size-11 place-items-center rounded-pill bg-surface text-fg shadow-md transition-opacity duration-150 ease-um hover:text-primary focus-visible:outline-3 focus-visible:outline-primary/40 data-[visible=false]:pointer-events-none data-[visible=false]:opacity-0',
						'start' === $side ? 'start-5' : 'end-5',
						$props['class'] ?? null
					)
				),
				esc_attr( $props['label'] ),
				Icon::render( 'arrow-up', 'size-5' )
			)
		);
	}
}
