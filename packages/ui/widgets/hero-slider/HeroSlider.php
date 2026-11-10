<?php
/**
 * HeroSlider component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The big slider at the top of the home page (HOME-01): each slide is a
 * link with a background (two-colour gradient or a photo), a small label,
 * a title, a line of text, a button and a product image. A slide may have
 * its own photo for phones, swapped by the browser with `picture`, so a
 * phone never downloads the desktop photo.
 *
 * The first slide's image loads first (PERF-03); the rest load lazily.
 * Touch swiping, arrows, dots and autoplay come from Carousel.
 *
 * @phpstan-type HeroSlide array{
 *     href: string,
 *     title: string,
 *     kicker?: string,
 *     text?: string,
 *     button?: string,
 *     from?: string,
 *     to?: string,
 *     background?: array{url: string, mobile?: string, width?: int, height?: int},
 *     image?: Html,
 *     dark_text?: bool,
 * }
 * @phpstan-type HeroSliderProps array{
 *     label: string,
 *     slides: list<HeroSlide>,
 *     previous: string,
 *     next: string,
 *     slide_label: string,
 *     autoplay?: int,
 *     height?: 'sm'|'md'|'lg',
 *     class?: string,
 * }
 */
final class HeroSlider {

	public const HEIGHTS = array( 'md', 'sm', 'lg' );

	/**
	 * Renders the slider.
	 *
	 * @param array $props Props.
	 * @phpstan-param HeroSliderProps $props
	 */
	public static function render( array $props ): Html {
		$height = Props::one_of( 'HeroSlider', $props, 'height', self::HEIGHTS );
		$box    = match ( $height ) {
			'sm'    => 'h-50 md:h-60 lg:h-70',
			'lg'    => 'h-55 md:h-80 lg:h-105',
			default => 'h-50 md:h-70 lg:h-85',
		};
		$slides = array();
		foreach ( $props['slides'] as $index => $slide ) {
			$slides[] = self::slide( $slide, $box, 0 === $index );
		}
		return Carousel::render(
			array(
				'label'       => $props['label'],
				'slides'      => $slides,
				'previous'    => $props['previous'],
				'next'        => $props['next'],
				'slide_label' => $props['slide_label'],
				'slide_width' => 'full',
				'gap'         => 'none',
				'arrows'      => 'inside',
				'dots'        => count( $slides ) > 1,
				'loop'        => count( $slides ) > 1,
				'autoplay'    => $props['autoplay'] ?? 5000,
				'class'       => Attributes::classes( 'um-hero overflow-hidden rounded-xl bg-brand-strong max-md:rounded-none', $props['class'] ?? null ),
			)
		);
	}

	/**
	 * One slide.
	 *
	 * @param array  $slide Slide.
	 * @param string $box   Height classes.
	 * @param bool   $first Whether it shows first.
	 * @phpstan-param HeroSlide $slide
	 */
	private static function slide( array $slide, string $box, bool $first ): Html {
		$style = '';
		if ( ! isset( $slide['background'] ) ) {
			$style = sprintf( '--a:%1$s;--b:%2$s', self::color( $slide['from'] ?? '#3d34ad' ), self::color( $slide['to'] ?? '#7f78f0' ) );
		}
		$background = '';
		if ( isset( $slide['background'] ) && '' !== $slide['background']['url'] ) {
			$bg         = $slide['background'];
			$background = sprintf(
				'<picture class="absolute inset-0">%1$s<img src="%2$s" alt="" class="size-full object-cover"%3$s%4$s decoding="async"></picture>',
				isset( $bg['mobile'] ) && '' !== $bg['mobile'] ? sprintf( '<source media="(max-width: 767px)" srcset="%s">', esc_url( $bg['mobile'] ) ) : '',
				esc_url( $bg['url'] ),
				isset( $bg['width'], $bg['height'] ) ? sprintf( ' width="%d" height="%d"', $bg['width'], $bg['height'] ) : '',
				$first ? ' fetchpriority="high"' : ' loading="lazy"'
			);
		}
		$dark   = $slide['dark_text'] ?? false;
		$kicker = isset( $slide['kicker'] ) && '' !== $slide['kicker']
			? sprintf( '<span class="mb-3 inline-block rounded-pill px-3 py-0.5 text-sm font-bold max-md:mb-1.5 max-md:text-2xs %1$s">%2$s</span>', $dark ? 'bg-white/60' : 'bg-white/20', esc_html( $slide['kicker'] ) )
			: '';
		$text   = isset( $slide['text'] ) && '' !== $slide['text'] ? sprintf( '<p class="m-0 mb-5 opacity-85 max-md:hidden">%s</p>', esc_html( $slide['text'] ) ) : '';
		$button = isset( $slide['button'] ) && '' !== $slide['button']
			? sprintf( '<span class="inline-flex h-11 items-center rounded-md bg-accent px-5 text-sm font-bold text-on-accent max-md:h-8.5 max-md:px-3 max-md:text-xs">%s</span>', esc_html( $slide['button'] ) )
			: '';
		$image  = isset( $slide['image'] ) && ! $slide['image']->is_empty()
			? sprintf( '<span class="relative z-1 h-[88%%] shrink-0 -rotate-6 drop-shadow-[0_24px_30px_rgb(0_0_0/25%%)] [&_img]:h-full [&_img]:w-auto [&_img]:max-w-none">%s</span>', $slide['image'] )
			: '';
		return Html::trusted(
			sprintf(
				'<a href="%1$s" class="%2$s"%3$s>%4$s<span class="absolute start-[6%%] top-1/2 size-95 -translate-y-1/2 rounded-full bg-white/10 max-md:size-55" aria-hidden="true"></span><span class="relative z-1 grid max-w-115 content-center">%5$s<span class="mb-2 block text-4xl leading-[1.4] font-heavy max-lg:text-3xl max-md:mb-1 max-md:text-lg">%6$s</span>%7$s<span>%8$s</span></span>%9$s</a>',
				esc_url( $slide['href'] ),
				esc_attr(
					Attributes::classes(
						'relative flex items-center justify-between overflow-hidden px-16 no-underline max-lg:px-8 max-md:px-5',
						$box,
						isset( $slide['background'] ) ? 'bg-brand-strong' : 'bg-[linear-gradient(115deg,var(--a),var(--b))]',
						$dark ? 'text-fg' : 'text-white'
					)
				),
				'' === $style ? '' : sprintf( ' style="%s"', esc_attr( $style ) ),
				$background,
				$kicker,
				esc_html( $slide['title'] ),
				$text,
				$button,
				$image
			)
		);
	}

	/**
	 * A safe CSS colour.
	 *
	 * @param string $color Colour from settings.
	 */
	private static function color( string $color ): string {
		return 1 === preg_match( '/^#[0-9a-fA-F]{3,8}$|^rgba?\([\d\s.,%\/]+\)$/', $color ) ? $color : '#4c42d3';
	}
}
