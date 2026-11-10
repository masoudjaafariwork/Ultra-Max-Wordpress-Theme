<?php
/**
 * Deals component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The "amazing offers" band (HOME-03): a coloured band with a title, a
 * countdown to the end of the offers, an image and a "see all offers"
 * link, followed by a slider of deal cards. On phones the head becomes one
 * row above the slider.
 *
 * @phpstan-type DealsProps array{
 *     title: string,
 *     cards: list<Html>,
 *     countdown?: Html,
 *     ends_label?: string,
 *     image?: Html,
 *     more?: array{label: string, href: string},
 *     previous: string,
 *     next: string,
 *     slide_label: string,
 *     id?: string,
 *     class?: string,
 * }
 */
final class Deals {

	/**
	 * Renders the band.
	 *
	 * @param array $props Props.
	 * @phpstan-param DealsProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['cards'] ) {
			return Html::none();
		}
		$timer = isset( $props['countdown'] ) ? sprintf(
			'<div class="flex flex-col items-center gap-1 max-md:flex-row">%1$s%2$s</div>',
			isset( $props['ends_label'] ) ? sprintf( '<span class="text-xs max-md:hidden">%s</span>', esc_html( $props['ends_label'] ) ) : '',
			$props['countdown']
		) : '';
		$more  = isset( $props['more'] ) && '' !== $props['more']['href'] ? sprintf(
			'<a href="%1$s" class="inline-flex items-center gap-0.5 text-sm font-bold whitespace-nowrap text-white no-underline hover:text-accent-soft">%2$s%3$s</a>',
			esc_url( $props['more']['href'] ),
			esc_html( $props['more']['label'] ),
			Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' )
		) : '';
		$image = isset( $props['image'] ) && ! $props['image']->is_empty()
			? sprintf( '<span class="block w-27.5 -rotate-10 drop-shadow-[0_10px_16px_rgb(0_0_0/25%%)] max-md:hidden [&_img]:h-auto [&_img]:w-full">%s</span>', $props['image'] )
			: '';
		$cards = array();
		foreach ( $props['cards'] as $card ) {
			$cards[] = $card;
		}
		$slider = Carousel::render(
			array(
				'label'       => $props['title'],
				'slides'      => $cards,
				'previous'    => $props['previous'],
				'next'        => $props['next'],
				'slide_label' => $props['slide_label'],
				'slide_width' => 'card',
				'gap'         => 'xs',
				'arrows'      => 'outside',
				'class'       => 'min-w-0 flex-1 [&_[data-track]>*:first-child_article]:rounded-s-lg [&_[data-track]>*:last-child_article]:rounded-e-lg [&_article]:rounded-none',
			)
		);
		return Html::trusted(
			sprintf(
				'<section%1$s><div class="flex w-45 shrink-0 flex-col items-center justify-center gap-3 text-center text-white max-md:w-auto max-md:flex-row max-md:justify-between"><h2 class="m-0 flex flex-col items-center gap-1 text-[22px] leading-[1.35] font-heavy max-md:flex-row max-md:text-md">%2$s%3$s</h2>%4$s%5$s%6$s</div>%7$s</section>',
				Attributes::render(
					array(
						'id'         => $props['id'] ?? null,
						'class'      => Attributes::classes( 'um-deals flex scroll-mt-36 gap-3 rounded-xl bg-(image:--um-deal-gradient) p-4 max-md:flex-col max-md:px-3', $props['class'] ?? null ),
						'aria-label' => $props['title'],
					)
				),
				Icon::render( 'fire', 'size-8 text-accent-hover max-md:size-6' ),
				esc_html( $props['title'] ),
				$timer,
				$image,
				$more,
				$slider
			)
		);
	}
}
