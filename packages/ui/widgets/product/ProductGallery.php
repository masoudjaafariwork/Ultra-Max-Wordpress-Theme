<?php
/**
 * ProductGallery component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The pictures and videos of a product page (PDP-01, PDP-02, PDP-03).
 *
 * The slides are one row that scrolls sideways with snap points, so on
 * phones the gallery swipes without JavaScript and every thumbnail is a
 * link to its slide. The behaviour adds hover zoom, a full screen viewer
 * with swipe, arrows and keys, and swaps in a variation's own pictures when
 * the shopper chooses one (the `um:variation` event).
 *
 * - `side`: thumbnails in a column beside the picture (desktop).
 * - `bottom`: thumbnails under the picture.
 * - `grid`: every picture in a two column grid on desktop.
 * - `slider`: one picture with arrows and dots, no thumbnails.
 *
 * `sets` are the galleries of variations, keyed by the `gallery` value of
 * the variation data that PurchaseForm sends.
 *
 * @phpstan-type GalleryItem array{
 *     type: 'image'|'video',
 *     image: Html,
 *     thumb: Html,
 *     label: string,
 *     full?: string,
 *     video?: string,
 *     provider?: 'youtube'|'aparat'|'file',
 * }
 * @phpstan-type ProductGalleryProps array{
 *     id: string,
 *     label: string,
 *     items: list<GalleryItem>,
 *     labels: array{zoom: string, open: string, close: string, previous: string, next: string, video: string, more: string, counter: string},
 *     product?: int,
 *     layout?: 'side'|'bottom'|'grid'|'slider',
 *     zoom?: bool,
 *     tools?: list<Html>,
 *     sets?: array<string, list<GalleryItem>>,
 *     class?: string,
 * }
 */
final class ProductGallery {

	public const LAYOUTS = array( 'side', 'bottom', 'grid', 'slider' );

	private const THUMBS = 5;

	/**
	 * Renders the gallery.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductGalleryProps $props
	 */
	public static function render( array $props ): Html {
		if ( array() === $props['items'] ) {
			return Html::none();
		}
		$id     = Props::id( 'ProductGallery', $props );
		$layout = Props::one_of( 'ProductGallery', $props, 'layout', self::LAYOUTS );
		$count  = count( $props['items'] );

		$thumbs = 'slider' === $layout || 'grid' === $layout || $count < 2 ? '' : sprintf(
			'<div class="%1$s" data-um-gallery-thumbs>%2$s</div>',
			'side' === $layout
				? 'flex flex-col gap-2 max-md:hidden'
				: 'mt-3 flex gap-2 max-md:hidden',
			self::thumbs( $id, $props['items'], $props['labels'] )
		);
		$frame  = 'side' === $layout && '' !== $thumbs ? 'grid items-start gap-3 md:grid-cols-[72px_minmax(0,1fr)]' : 'grid';
		$sets   = '';
		foreach ( $props['sets'] ?? array() as $key => $items ) {
			if ( array() === $items ) {
				continue;
			}
			$sets .= sprintf(
				'<template data-um-gallery-set="%1$s" data-part="track">%2$s</template><template data-um-gallery-set="%1$s" data-part="thumbs">%3$s</template>',
				esc_attr( (string) $key ),
				self::slides( $id, $items, $props['labels'], $layout ),
				self::thumbs( $id, $items, $props['labels'] )
			);
		}

		return Html::trusted(
			sprintf(
				'<div%1$s>%2$s<div class="%3$s">%4$s<div class="relative min-w-0">%5$s%6$s%7$s</div></div>%8$s%9$s</div>',
				Attributes::render(
					array(
						'id'                => $id,
						'class'             => Attributes::classes( 'um-scope um-gallery relative', $props['class'] ?? null ),
						'role'              => 'region',
						'aria-label'        => $props['label'],
						'data-um-component' => 'product-gallery',
						'data-layout'       => $layout,
						'data-zoom'         => ( $props['zoom'] ?? true ) ? 'true' : null,
						'data-product'      => isset( $props['product'] ) ? (string) $props['product'] : null,
						'data-counter'      => $props['labels']['counter'],
					)
				),
				self::tools( $props['tools'] ?? array() ),
				esc_attr( $frame ),
				'side' === $layout ? $thumbs : '',
				sprintf(
					'<div class="%1$s" data-um-gallery-track>%2$s</div>',
					esc_attr(
						Attributes::classes(
							'flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-md:-mx-(--um-gutter)',
							'grid' === $layout ? 'md:grid md:grid-cols-2 md:gap-3 md:overflow-visible' : 'md:rounded-lg'
						)
					),
					self::slides( $id, $props['items'], $props['labels'], $layout )
				),
				self::chrome( $props, $layout, $count ),
				'side' !== $layout ? $thumbs : '',
				$sets,
				self::lightbox( $id, $props['labels'] )
			)
		);
	}

	/**
	 * The round buttons over the picture, such as share and wishlist.
	 *
	 * @param array $tools Buttons.
	 * @phpstan-param list<Html> $tools
	 */
	private static function tools( array $tools ): string {
		if ( array() === $tools ) {
			return '';
		}
		return sprintf(
			'<div class="absolute end-0 top-0 z-3 flex flex-col gap-1 max-md:end-2 max-md:top-2 max-md:flex-row">%s</div>',
			implode( '', array_map( 'strval', $tools ) )
		);
	}

	/**
	 * A round tool button for the gallery's corner.
	 *
	 * @param string $icon  Icon name.
	 * @param string $label Accessible name.
	 * @param array  $attrs Extra attributes, such as data-um-open.
	 * @phpstan-param array<string, string|int|bool|null> $attrs
	 */
	public static function tool( string $icon, string $label, array $attrs = array() ): Html {
		return Html::trusted(
			sprintf(
				'<button%1$s>%2$s</button>',
				Attributes::render(
					array_merge(
						array(
							'type'       => 'button',
							'class'      => 'grid size-10 cursor-pointer place-items-center rounded-full border-0 bg-transparent text-fg-2 hover:bg-surface-3 hover:text-fg focus-visible:outline-3 focus-visible:outline-primary/40 aria-pressed:text-danger max-md:size-9 max-md:bg-surface max-md:shadow-sm',
							'aria-label' => $label,
							'title'      => $label,
						),
						$attrs
					)
				),
				Icon::render( $icon, 'size-5' )
			)
		);
	}

	/**
	 * The slides.
	 *
	 * @param string $id     Gallery id.
	 * @param array  $items  Items.
	 * @param array  $labels Texts.
	 * @param string $layout Layout.
	 * @phpstan-param list<GalleryItem> $items
	 * @phpstan-param array{zoom: string, open: string, close: string, previous: string, next: string, video: string, more: string, counter: string} $labels
	 */
	private static function slides( string $id, array $items, array $labels, string $layout ): string {
		$out = '';
		foreach ( $items as $index => $item ) {
			$video = 'video' === $item['type'];
			$out  .= sprintf(
				'<div%1$s><button%2$s>%3$s%4$s</button></div>',
				Attributes::render(
					array(
						'id'            => sprintf( '%s-slide-%d', $id, $index ),
						'class'         => Attributes::classes(
							'relative aspect-square w-full shrink-0 snap-center overflow-hidden bg-surface [&_img]:size-full [&_img]:object-contain',
							'grid' === $layout ? 'md:rounded-lg md:border md:border-border' : 'md:rounded-lg'
						),
						'data-um-slide' => (string) $index,
						'data-full'     => $item['full'] ?? null,
						'data-video'    => $video ? ( $item['video'] ?? '' ) : null,
						'data-provider' => $video ? ( $item['provider'] ?? 'file' ) : null,
						'data-label'    => $item['label'],
					)
				),
				Attributes::render(
					array(
						'type'                 => 'button',
						'class'                => Attributes::classes( 'block size-full border-0 bg-transparent p-0 focus-visible:outline-3 focus-visible:-outline-offset-3 focus-visible:outline-primary/40', $video ? 'cursor-pointer' : 'cursor-zoom-in' ),
						'aria-label'           => ( $video ? $labels['video'] : $labels['open'] ) . ': ' . $item['label'],
						'data-um-gallery-open' => (string) $index,
					)
				),
				$item['image'],
				$video ? sprintf( '<span class="pointer-events-none absolute inset-0 grid place-items-center bg-overlay text-white">%s</span>', Icon::render( 'play', 'size-12' ) ) : ''
			);
		}
		return $out;
	}

	/**
	 * The thumbnails: links to their slides, so they work without
	 * JavaScript; after five, "+N" opens the viewer.
	 *
	 * @param string $id     Gallery id.
	 * @param array  $items  Items.
	 * @param array  $labels Texts.
	 * @phpstan-param list<GalleryItem> $items
	 * @phpstan-param array{zoom: string, open: string, close: string, previous: string, next: string, video: string, more: string, counter: string} $labels
	 */
	private static function thumbs( string $id, array $items, array $labels ): string {
		$out   = '';
		$count = count( $items );
		$shown = $count > self::THUMBS ? self::THUMBS - 1 : $count;
		$base  = 'relative grid size-18 shrink-0 place-items-center overflow-hidden rounded-md border border-border bg-surface p-1.5 no-underline [&_img]:size-full [&_img]:object-contain focus-visible:outline-3 focus-visible:outline-primary/40 aria-current:border-primary aria-current:shadow-[0_0_0_1px_var(--um-primary)]';
		foreach ( array_slice( $items, 0, $shown ) as $index => $item ) {
			$out .= sprintf(
				'<a href="#%1$s-slide-%2$d" class="%3$s" data-um-gallery-thumb="%2$d" aria-label="%4$s"%5$s>%6$s%7$s</a>',
				esc_attr( $id ),
				$index,
				esc_attr( $base ),
				esc_attr( $item['label'] ),
				0 === $index ? ' aria-current="true"' : '',
				$item['thumb'],
				'video' === $item['type'] ? sprintf( '<span class="absolute inset-0 grid place-items-center bg-overlay text-white">%s</span>', Icon::render( 'play', 'size-6' ) ) : ''
			);
		}
		if ( $count > $shown ) {
			$out .= sprintf(
				'<button type="button" class="%1$s cursor-pointer font-num text-md font-bold text-fg-2" data-um-gallery-open="%2$d" aria-label="%3$s">%4$s</button>',
				esc_attr( $base ),
				$shown,
				esc_attr( $labels['more'] ),
				esc_html( '+' . number_format_i18n( $count - $shown ) )
			);
		}
		return $out;
	}

	/**
	 * Zoom hint, dots and counter over the picture.
	 *
	 * @param array  $props  Props.
	 * @param string $layout Layout.
	 * @param int    $count  Number of items.
	 * @phpstan-param ProductGalleryProps $props
	 */
	private static function chrome( array $props, string $layout, int $count ): string {
		$out = '';
		if ( ( $props['zoom'] ?? true ) && 'grid' !== $layout ) {
			$out .= sprintf(
				'<span class="pointer-events-none absolute bottom-3 start-3 z-2 inline-flex items-center gap-1 text-2xs text-fg-3 max-lg:hidden">%1$s%2$s</span>',
				Icon::render( 'search', 'size-3.5' ),
				esc_html( $props['labels']['zoom'] )
			);
		}
		if ( $count > 1 ) {
			$dots = '';
			for ( $i = 0; $i < $count; $i++ ) {
				$dots .= sprintf( '<i class="block h-1.5 w-1.5 rounded-pill bg-border-strong transition-[width] duration-200 data-current:w-4 data-current:bg-primary"%s></i>', 0 === $i ? ' data-current' : '' );
			}
			$out .= sprintf( '<div class="mt-2 flex justify-center gap-1 %1$s" aria-hidden="true" data-um-gallery-dots>%2$s</div>', 'slider' === $layout ? '' : 'md:hidden', $dots );
			if ( 'slider' === $layout ) {
				$button = 'absolute top-1/2 z-2 grid size-10 -translate-y-1/2 cursor-pointer place-items-center rounded-full border border-border bg-surface text-fg shadow-md hover:bg-surface-3 focus-visible:outline-3 focus-visible:outline-primary/40 max-md:hidden';
				$out   .= sprintf(
					'<button type="button" class="%1$s start-2" data-um-gallery-step="-1" aria-label="%2$s">%3$s</button><button type="button" class="%1$s end-2" data-um-gallery-step="1" aria-label="%4$s">%5$s</button>',
					esc_attr( $button ),
					esc_attr( $props['labels']['previous'] ),
					Icon::render( 'chevron-right', 'size-5 ltr:rotate-180' ),
					esc_attr( $props['labels']['next'] ),
					Icon::render( 'chevron-left', 'size-5 ltr:rotate-180' )
				);
			}
		}
		return $out;
	}

	/**
	 * The full screen viewer. Its pictures are added by the behaviour when
	 * it opens, so the page loads no large file before it is wanted.
	 *
	 * @param string $id     Gallery id.
	 * @param array  $labels Texts.
	 * @phpstan-param array{zoom: string, open: string, close: string, previous: string, next: string, video: string, more: string, counter: string} $labels
	 */
	private static function lightbox( string $id, array $labels ): string {
		$button = 'grid size-11 cursor-pointer place-items-center rounded-full border-0 bg-white/12 text-white hover:bg-white/24 focus-visible:outline-3 focus-visible:outline-white/60 disabled:opacity-30';
		return sprintf(
			'<dialog id="%1$s-viewer" class="um-scope um-dialog m-0 h-dvh max-h-none w-full max-w-none flex-col border-0 bg-[#111216] p-0 text-white open:flex" aria-label="%2$s" data-um-component="dialog" data-um-gallery-viewer><div class="flex items-center justify-between gap-3 p-3"><span class="font-num text-sm" data-um-viewer-count aria-live="polite"></span><button type="button" class="%3$s" data-um-close aria-label="%4$s">%5$s</button></div><div class="relative min-h-0 flex-1"><div class="flex h-full snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-um-viewer-track></div><button type="button" class="%3$s absolute start-3 top-1/2 -translate-y-1/2 max-md:hidden" data-um-viewer-step="-1" aria-label="%6$s">%7$s</button><button type="button" class="%3$s absolute end-3 top-1/2 -translate-y-1/2 max-md:hidden" data-um-viewer-step="1" aria-label="%8$s">%9$s</button></div><div class="flex justify-center gap-2 overflow-x-auto p-3 [scrollbar-width:none]" data-um-viewer-thumbs></div></dialog>',
			esc_attr( $id ),
			esc_attr( $labels['open'] ),
			esc_attr( $button ),
			esc_attr( $labels['close'] ),
			Icon::render( 'close', 'size-5' ),
			esc_attr( $labels['previous'] ),
			Icon::render( 'chevron-right', 'size-6 ltr:rotate-180' ),
			esc_attr( $labels['next'] ),
			Icon::render( 'chevron-left', 'size-6 ltr:rotate-180' )
		);
	}
}
