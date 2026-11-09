<?php
/**
 * Logo component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

use UltraMax\Foundation\Brand;

/**
 * The store logo: the uploaded image, or the product mark with the store name.
 *
 * @phpstan-type LogoProps array{
 *     name: string,
 *     href: string,
 *     image_id?: int,
 *     show_name?: bool,
 *     size?: 'md'|'lg',
 *     class?: string,
 * }
 */
final class Logo {

	/**
	 * Renders the logo.
	 *
	 * @param array $props Props.
	 * @phpstan-param LogoProps $props
	 */
	public static function render( array $props ): Html {
		$size  = Props::one_of( 'Logo', $props, 'size', array( 'md', 'lg' ) );
		$image = (int) ( $props['image_id'] ?? 0 );
		$art   = '';
		if ( $image > 0 ) {
			$art = wp_get_attachment_image(
				$image,
				'full',
				false,
				array(
					'class'         => 'lg' === $size ? 'block h-12 w-auto max-w-56' : 'block h-10 w-auto max-w-44',
					'alt'           => $props['name'],
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'decoding'      => 'async',
				)
			);
		}
		$show_name = $props['show_name'] ?? '' === $art;
		if ( '' === $art ) {
			$art = Brand::mark( 'lg' === $size ? 44 : 36 );
		}

		return Html::trusted(
			sprintf(
				'<a href="%1$s" rel="home" class="%2$s">%3$s%4$s</a>',
				esc_url( $props['href'] ),
				esc_attr( Attributes::classes( 'inline-flex shrink-0 items-center gap-2 text-fg no-underline hover:text-fg', $props['class'] ?? null ) ),
				$art,
				$show_name ? sprintf( '<span class="%s">%s</span>', 'lg' === $size ? 'text-3xl font-heavy leading-none' : 'text-2xl font-heavy leading-none', esc_html( $props['name'] ) ) : sprintf( '<span class="sr-only">%s</span>', esc_html( $props['name'] ) )
			)
		);
	}
}
