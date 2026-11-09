<?php
/**
 * PageHeader component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The title area of a page (CORE-08): breadcrumbs, title, optional
 * description and meta line.
 *
 * - `plain`: on the page background.
 * - `soft`: on a light band of the brand colour.
 * - `brand`: on the brand gradient, with white text.
 *
 * @phpstan-type PageHeaderProps array{
 *     title: string|Html,
 *     description?: string|Html,
 *     breadcrumbs?: Html,
 *     meta?: Html,
 *     variant?: 'plain'|'soft'|'brand',
 *     align?: 'start'|'center',
 *     show_title?: bool,
 *     class?: string,
 * }
 */
final class PageHeader {

	public const VARIANTS = array( 'plain', 'soft', 'brand' );

	/**
	 * Renders the header.
	 *
	 * @param array $props Props.
	 * @phpstan-param PageHeaderProps $props
	 */
	public static function render( array $props ): Html {
		$variant    = Props::one_of( 'PageHeader', $props, 'variant', self::VARIANTS );
		$align      = Props::one_of( 'PageHeader', $props, 'align', array( 'start', 'center' ) );
		$show_title = $props['show_title'] ?? true;
		$crumbs     = $props['breadcrumbs'] ?? Html::none();
		if ( ! $show_title && $crumbs->is_empty() ) {
			return Html::none();
		}

		$band = match ( $variant ) {
			'plain' => 'pt-6 pb-2',
			'soft'  => 'mb-6 bg-primary-soft py-8',
			'brand' => 'mb-6 bg-(image:--um-deal-gradient) py-10 text-white [&_a]:text-white/80 [&_a:hover]:text-white [&_[aria-current]]:text-white [&_svg]:text-white/70',
		};
		$title       = $show_title
			? sprintf(
				'<h1 class="%1$s">%2$s</h1>',
				'brand' === $variant ? 'm-0 text-3xl font-heavy leading-tight text-white max-md:text-2xl' : 'm-0 text-3xl font-heavy leading-tight text-fg max-md:text-2xl',
				Html::from( $props['title'] )
			)
			: '';
		$description = isset( $props['description'] ) ? Html::from( $props['description'] ) : Html::none();

		return Html::trusted(
			sprintf(
				'<header class="%1$s"><div class="%2$s">%3$s%4$s%5$s%6$s</div></header>',
				esc_attr( Attributes::classes( 'um-page-header', $band, $props['class'] ?? null ) ),
				esc_attr( Attributes::classes( 'mx-auto grid max-w-(--um-container) gap-3 px-(--um-gutter)', 'center' === $align ? 'justify-items-center text-center' : null ) ),
				$crumbs,
				$title,
				$description->is_empty() ? '' : sprintf( '<div class="%s">%s</div>', 'brand' === $variant ? 'max-w-3xl text-md text-white/85' : 'max-w-3xl text-md text-fg-2', $description ),
				Html::from( $props['meta'] ?? null )
			)
		);
	}
}
