<?php
/**
 * Image component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * A bare image from the media library, or from an address when a widget
 * was given a plain URL (for example a demo image). Lazy unless it is the
 * main image of the page (PERF-03).
 *
 * @phpstan-type ImageProps array{
 *     id?: int,
 *     url?: string,
 *     size?: string,
 *     alt?: string,
 *     priority?: bool,
 *     width?: int,
 *     height?: int,
 *     class?: string,
 * }
 */
final class Image {

	/**
	 * Renders the image.
	 *
	 * @param array $props Props.
	 * @phpstan-param ImageProps $props
	 */
	public static function render( array $props ): Html {
		$priority = $props['priority'] ?? false;
		$attrs    = array(
			'alt'      => $props['alt'] ?? '',
			'loading'  => $priority ? 'eager' : 'lazy',
			'decoding' => 'async',
		);
		if ( $priority ) {
			$attrs['fetchpriority'] = 'high';
		}
		if ( isset( $props['class'] ) ) {
			$attrs['class'] = $props['class'];
		}
		$id = $props['id'] ?? 0;
		if ( $id > 0 ) {
			$image = wp_get_attachment_image( $id, $props['size'] ?? 'medium', false, $attrs );
			if ( '' !== $image ) {
				return Html::trusted( $image );
			}
		}
		$url = $props['url'] ?? '';
		if ( '' === $url ) {
			return Html::none();
		}
		$attrs['src'] = $url;
		if ( isset( $props['width'], $props['height'] ) ) {
			$attrs['width']  = (string) $props['width'];
			$attrs['height'] = (string) $props['height'];
		}
		return Html::trusted( '<img' . Attributes::render( $attrs ) . '>' );
	}
}
