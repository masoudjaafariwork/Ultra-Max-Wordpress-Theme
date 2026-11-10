<?php
/**
 * Carousel component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A row of slides that scrolls sideways: product sliders, brand rows and
 * the hero slider all use it.
 *
 * Before its script runs the row scrolls natively with touch and snap
 * points, so the page draws complete and works without JavaScript. The
 * behaviour then adds arrows, dots, looping and autoplay through Embla
 * Carousel (MIT), the library chosen after Flowbite's carousel failed the
 * right-to-left test of phase 1. Only the behaviour file knows about it.
 *
 * `per_view` sets how many slides show at each width, for example
 * array( 'mobile' => 2, 'tablet' => 3, 'desktop' => 5 ); without it slides
 * keep their own width.
 *
 * @phpstan-type CarouselProps array{
 *     label: string,
 *     slides: list<Html>,
 *     previous: string,
 *     next: string,
 *     slide_label: string,
 *     id?: string,
 *     per_view?: array{mobile?: int, tablet?: int, desktop?: int},
 *     slide_width?: 'auto'|'card'|'card-sm'|'full',
 *     gap?: 'none'|'xs'|'sm'|'md',
 *     arrows?: 'outside'|'inside'|'none',
 *     dots?: bool,
 *     loop?: bool,
 *     autoplay?: int,
 *     class?: string,
 * }
 */
final class Carousel {

	public const WIDTHS = array( 'auto', 'card', 'card-sm', 'full' );
	public const GAPS   = array( 'sm', 'none', 'xs', 'md' );
	public const ARROWS = array( 'outside', 'inside', 'none' );

	/**
	 * Renders the carousel.
	 *
	 * @param array $props Props.
	 * @phpstan-param CarouselProps $props
	 */
	public static function render( array $props ): Html {
		$count = count( $props['slides'] );
		if ( 0 === $count ) {
			return Html::none();
		}
		$width  = Props::one_of( 'Carousel', $props, 'slide_width', self::WIDTHS );
		$gap    = Props::one_of( 'Carousel', $props, 'gap', self::GAPS );
		$arrows = Props::one_of( 'Carousel', $props, 'arrows', self::ARROWS );
		$per    = $props['per_view'] ?? array();
		$basis  = self::basis( $per, $width );
		$slides = '';
		foreach ( $props['slides'] as $index => $slide ) {
			$slides .= sprintf(
				'<div class="%1$s" role="group" aria-roledescription="slide" aria-label="%2$s">%3$s</div>',
				esc_attr( Attributes::classes( 'min-w-0 shrink-0 snap-start', $basis ) ),
				esc_attr( sprintf( $props['slide_label'], number_format_i18n( $index + 1 ), number_format_i18n( $count ) ) ),
				$slide
			);
		}
		$gaps = array(
			'none' => 'gap-0 [--um-gap:0px]',
			'xs'   => 'gap-0.5 [--um-gap:2px]',
			'sm'   => 'gap-3 [--um-gap:12px]',
			'md'   => 'gap-4 [--um-gap:16px]',
		);
		$nav  = '';
		if ( 'none' !== $arrows ) {
			$button = 'inside' === $arrows
				? 'grid size-10 place-items-center rounded-full bg-surface text-fg shadow-sm hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40 disabled:opacity-40'
				: 'absolute top-1/2 z-2 grid size-10 -translate-y-1/2 place-items-center rounded-full border border-border bg-surface text-fg shadow-md transition-opacity hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40 disabled:pointer-events-none disabled:opacity-0 max-lg:hidden';
			$nav    = sprintf(
				'%5$s<button type="button" class="%1$s" data-prev aria-label="%2$s" hidden>%6$s</button><button type="button" class="%7$s" data-next aria-label="%3$s" hidden>%8$s</button>%4$s',
				esc_attr( Attributes::classes( $button, 'outside' === $arrows ? '-start-3' : null ) ),
				esc_attr( $props['previous'] ),
				esc_attr( $props['next'] ),
				'inside' === $arrows ? '</div>' : '',
				'inside' === $arrows ? '<div class="absolute bottom-4 end-6 z-2 flex gap-2 max-lg:hidden">' : '',
				Icon::render( 'chevron-right', 'size-5 rtl:rotate-0 ltr:rotate-180' ),
				esc_attr( Attributes::classes( $button, 'outside' === $arrows ? '-end-3' : null ) ),
				Icon::render( 'chevron-left', 'size-5 rtl:rotate-0 ltr:rotate-180' )
			);
		}
		$dots = ( $props['dots'] ?? false ) ? '<div class="absolute start-6 bottom-4 z-2 flex gap-1.5 max-md:start-5 max-md:bottom-2.5" data-dots hidden></div>' : '';

		return Html::trusted(
			sprintf(
				'<div%1$s><div class="overflow-x-auto overscroll-x-contain scroll-smooth snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-viewport><div class="flex %2$s" data-track>%3$s</div></div>%4$s%5$s</div>',
				Attributes::render(
					array(
						'id'                   => $props['id'] ?? null,
						'class'                => Attributes::classes( 'um-carousel relative', $props['class'] ?? null ),
						'role'                 => 'region',
						'aria-roledescription' => 'carousel',
						'aria-label'           => $props['label'],
						'data-um-component'    => 'carousel',
						'data-loop'            => ( $props['loop'] ?? false ) ? 'true' : null,
						'data-autoplay'        => ( $props['autoplay'] ?? 0 ) > 0 ? (string) ( $props['autoplay'] ?? 0 ) : null,
						'data-dot-label'       => $props['slide_label'],
					)
				),
				$gaps[ $gap ],
				$slides,
				$nav,
				$dots
			)
		);
	}

	/**
	 * Width classes of a slide.
	 *
	 * @param array{mobile?: int, tablet?: int, desktop?: int} $per   Slides per view.
	 * @param string                                           $width Fixed width when no per-view is given.
	 */
	private static function basis( array $per, string $width ): string {
		if ( array() === $per ) {
			return match ( $width ) {
				'card'    => 'w-55 max-md:w-42',
				'card-sm' => 'w-45 max-md:w-37',
				'full'    => 'w-full',
				default   => '',
			};
		}
		$mobile_map  = array(
			1 => 'basis-full',
			2 => 'basis-[calc((100%-var(--um-gap))/2)]',
			3 => 'basis-[calc((100%-2*var(--um-gap))/3)]',
			4 => 'basis-[calc((100%-3*var(--um-gap))/4)]',
		);
		$tablet_map  = array(
			1 => 'md:basis-full',
			2 => 'md:basis-[calc((100%-var(--um-gap))/2)]',
			3 => 'md:basis-[calc((100%-2*var(--um-gap))/3)]',
			4 => 'md:basis-[calc((100%-3*var(--um-gap))/4)]',
			5 => 'md:basis-[calc((100%-4*var(--um-gap))/5)]',
		);
		$desktop_map = array(
			1 => 'lg:basis-full',
			2 => 'lg:basis-[calc((100%-var(--um-gap))/2)]',
			3 => 'lg:basis-[calc((100%-2*var(--um-gap))/3)]',
			4 => 'lg:basis-[calc((100%-3*var(--um-gap))/4)]',
			5 => 'lg:basis-[calc((100%-4*var(--um-gap))/5)]',
			6 => 'lg:basis-[calc((100%-5*var(--um-gap))/6)]',
		);
		return Attributes::classes(
			$mobile_map[ max( 1, min( 4, $per['mobile'] ?? 2 ) ) ],
			$tablet_map[ max( 1, min( 5, $per['tablet'] ?? 3 ) ) ],
			$desktop_map[ max( 1, min( 6, $per['desktop'] ?? 5 ) ) ]
		);
	}
}
