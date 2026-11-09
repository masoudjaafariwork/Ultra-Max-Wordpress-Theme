<?php
/**
 * Media component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A featured image with rounded corners and an optional caption.
 *
 * `priority` marks the image at the top of the page: it loads eagerly with
 * high fetch priority (PERF-03). Other images load lazily.
 *
 * @phpstan-type MediaProps array{
 *     image_id: int,
 *     size?: string,
 *     alt?: string,
 *     caption?: string,
 *     priority?: bool,
 *     sizes?: string,
 *     class?: string,
 * }
 */
final class Media {

	/**
	 * Renders the image.
	 *
	 * @param array $props Props.
	 * @phpstan-param MediaProps $props
	 */
	public static function render( array $props ): Html {
		$priority = $props['priority'] ?? false;
		$alt      = get_post_meta( $props['image_id'], '_wp_attachment_image_alt', true );
		$image    = wp_get_attachment_image(
			$props['image_id'],
			$props['size'] ?? 'large',
			false,
			array(
				'class'         => 'block h-auto w-full',
				'alt'           => $props['alt'] ?? ( is_string( $alt ) ? $alt : '' ),
				'loading'       => $priority ? 'eager' : 'lazy',
				'fetchpriority' => $priority ? 'high' : 'auto',
				'decoding'      => 'async',
				'sizes'         => $props['sizes'] ?? '(max-width: 1336px) 100vw, 1336px',
			)
		);
		if ( '' === $image ) {
			return Html::none();
		}
		$caption = isset( $props['caption'] ) && '' !== $props['caption'] ? sprintf( '<figcaption class="mt-2 text-center text-sm text-fg-3">%s</figcaption>', esc_html( $props['caption'] ) ) : '';
		return Html::trusted(
			sprintf(
				'<figure class="%s"><div class="overflow-hidden rounded-lg bg-surface-3">%s</div>%s</figure>',
				esc_attr( Attributes::classes( 'm-0', $props['class'] ?? null ) ),
				$image,
				$caption
			)
		);
	}
}
