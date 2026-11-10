<?php
/**
 * Banners component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One, two or four promotional banners in a row (HOME-05). Each banner is a
 * link with either a photo (with its own phone version) or a two-colour
 * gradient, a small line, a bold line, an optional button and a product
 * image. Four banners become two per row on tablets and phones; two become
 * one per row on phones.
 *
 * @phpstan-type Banner array{
 *     href: string,
 *     title: string,
 *     kicker?: string,
 *     button?: string,
 *     from?: string,
 *     to?: string,
 *     photo?: array{url: string, mobile?: string},
 *     image?: Html,
 *     dark?: bool,
 * }
 * @phpstan-type BannersProps array{
 *     banners: list<Banner>,
 *     columns?: 1|2|3|4,
 *     size?: 'sm'|'lg',
 *     class?: string,
 * }
 */
final class Banners {

	/**
	 * Renders the banners.
	 *
	 * @param array $props Props.
	 * @phpstan-param BannersProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['banners'] ) {
			return Html::none();
		}
		$columns = $props['columns'] ?? count( $props['banners'] );
		$grid    = match ( $columns ) {
			1       => 'grid-cols-1',
			2       => 'grid-cols-1 md:grid-cols-2',
			3       => 'grid-cols-1 md:grid-cols-3',
			default => 'grid-cols-2 lg:grid-cols-4',
		};
		$large = 'lg' === ( $props['size'] ?? ( $columns <= 2 ? 'lg' : 'sm' ) );
		$items = '';
		foreach ( $props['banners'] as $index => $banner ) {
			$items .= self::banner( $banner, $large, $index );
		}
		return Html::trusted(
			sprintf(
				'<div class="%s">%s</div>',
				esc_attr( Attributes::classes( 'um-banners grid gap-4 max-md:gap-2', $grid, $props['class'] ?? null ) ),
				$items
			)
		);
	}

	/**
	 * One banner.
	 *
	 * @param array $banner Banner.
	 * @param bool  $large  Large size.
	 * @param int   $index  Position.
	 * @phpstan-param Banner $banner
	 */
	private static function banner( array $banner, bool $large, int $index ): string {
		$photo = '';
		if ( isset( $banner['photo'] ) && '' !== $banner['photo']['url'] ) {
			$photo = sprintf(
				'<picture class="absolute inset-0">%1$s<img src="%2$s" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="%3$s" decoding="async"></picture>',
				isset( $banner['photo']['mobile'] ) && '' !== $banner['photo']['mobile'] ? sprintf( '<source media="(max-width: 767px)" srcset="%s">', esc_url( $banner['photo']['mobile'] ) ) : '',
				esc_url( $banner['photo']['url'] ),
				$index < 2 ? 'eager' : 'lazy'
			);
		}
		$dark   = $banner['dark'] ?? ! $large;
		$style  = '' === $photo ? sprintf( ' style="%s"', esc_attr( sprintf( '--a:%1$s;--b:%2$s', self::color( $banner['from'] ?? '#e1e0fe' ), self::color( $banner['to'] ?? '#c4c1fb' ) ) ) ) : '';
		$kicker = isset( $banner['kicker'] ) && '' !== $banner['kicker'] ? sprintf( '<small class="text-xs opacity-80">%s</small>', esc_html( $banner['kicker'] ) ) : '';
		$button = isset( $banner['button'] ) && '' !== $banner['button']
			? sprintf( '<span class="mt-3 inline-flex h-9 w-fit items-center rounded-sm bg-accent px-3 text-sm font-bold text-on-accent">%s</span>', esc_html( $banner['button'] ) )
			: '';
		$image  = isset( $banner['image'] ) && ! $banner['image']->is_empty()
			? sprintf( '<span class="relative z-1 shrink-0 transition-transform duration-500 group-hover:scale-105 group-hover:-rotate-3 [&_img]:h-auto [&_img]:w-full %s">%s</span>', $large ? 'w-42 max-md:w-28' : 'w-30 max-md:w-16', $banner['image'] )
			: '';
		return sprintf(
			'<a href="%1$s" class="%2$s"%3$s>%4$s<span class="relative z-1 flex flex-col gap-1">%5$s<b class="%6$s">%7$s</b>%8$s</span>%9$s</a>',
			esc_url( $banner['href'] ),
			esc_attr(
				Attributes::classes(
					'group relative flex items-center justify-between gap-2 overflow-hidden rounded-lg p-5 no-underline max-md:p-3',
					$large ? 'min-h-50 max-md:min-h-37.5' : 'min-h-37.5 max-md:min-h-28',
					'' === $photo ? 'bg-[linear-gradient(135deg,var(--a),var(--b))]' : 'bg-surface-3',
					$dark ? 'text-fg' : 'text-white'
				)
			),
			$style,
			$photo,
			$kicker,
			esc_attr( $large ? 'text-3xl leading-[1.4] font-heavy max-md:text-xl' : 'text-xl leading-[1.4] font-heavy max-md:text-md' ),
			esc_html( $banner['title'] ),
			$button,
			$image
		);
	}

	/**
	 * A safe CSS colour.
	 *
	 * @param string $color Colour.
	 */
	private static function color( string $color ): string {
		return 1 === preg_match( '/^#[0-9a-fA-F]{3,8}$/', $color ) ? $color : '#e1e0fe';
	}
}
