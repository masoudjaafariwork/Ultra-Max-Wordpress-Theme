<?php
/**
 * ProductCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One product in a list or slider, in the six designs of
 * design/design-system.html (PLP-07). It is composed of smaller parts:
 * image, title, rating, price and an add button; every number arrives
 * already formatted.
 *
 * - `classic`: lined grids, the default of product lists, with the
 *   colours a product comes in and a stock warning.
 * - `hover`: a framed card whose second image shows on hover (HOME-06).
 * - `deal`: with a countdown and a "sold" bar, for amazing offers (HOME-03).
 * - `swatch`: colour buttons that change the picture, sizes and a full
 *   width add button, for fashion stores.
 * - `list`: a row with the picture, key features, the price and the add
 *   button; product lists switch any card to this look (PLP-05).
 * - `mini`: small supermarket card with a round add button.
 *
 * The add button is a plain link to WooCommerce's add-to-cart address, so
 * it works without JavaScript; the behaviour adds the product in place
 * and updates the cart counters on the page.
 *
 * `actions` are small round buttons in the top corner, such as wishlist,
 * compare and quick view; the modules that own them pass them in.
 *
 * @phpstan-type ProductBadge array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}
 * @phpstan-type ProductCardProps array{
 *     title: string,
 *     href: string,
 *     image: Html,
 *     image_alt?: Html,
 *     price: string,
 *     regular?: string,
 *     currency?: string,
 *     discount?: string,
 *     call?: string,
 *     rating?: string,
 *     rating_label?: string,
 *     brand?: string,
 *     badges?: list<ProductBadge>,
 *     note?: string,
 *     note_tone?: 'danger'|'muted',
 *     unit?: string,
 *     add?: array{href: string, label: string, id: int, ajax: bool},
 *     deal?: array{countdown: Html, sold: int, sold_label: string, left_label: string},
 *     colors?: list<array{color: string, label: string}>,
 *     colors_label?: string,
 *     swatches?: list<array{color: string, label: string, src?: string, srcset?: string}>,
 *     swatches_label?: string,
 *     sizes?: list<array{label: string, out?: bool}>,
 *     details?: array{line?: string, features?: list<string>},
 *     actions?: list<Html>,
 *     variant?: 'classic'|'hover'|'deal'|'swatch'|'list'|'mini',
 *     class?: string,
 * }
 */
final class ProductCard {

	public const VARIANTS = array( 'classic', 'hover', 'deal', 'swatch', 'list', 'mini' );

	private const BADGE = array(
		'hot'     => 'bg-accent text-on-accent',
		'new'     => 'bg-success-text text-white',
		'brand'   => 'bg-primary-soft text-primary',
		'neutral' => 'bg-surface-3 text-fg-2',
	);

	/**
	 * Renders the card.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	public static function render( array $props ): Html {
		$variant = Props::one_of( 'ProductCard', $props, 'variant', self::VARIANTS );
		$title   = sprintf(
			'<h3 class="um-pc-title m-0 line-clamp-2 %1$s"><a href="%2$s" class="text-fg no-underline hover:text-primary">%3$s</a></h3>',
			'mini' === $variant ? 'min-h-[3.4em] text-xs leading-body font-medium' : 'min-h-[3.5em] text-sm leading-[1.75] font-medium',
			esc_url( $props['href'] ),
			esc_html( $props['title'] )
		);
		$media   = self::media( $props, $variant );
		$price   = '<div class="um-pc-price mt-auto">' . self::price( $props, 'mini' === $variant ) . '</div>';
		$rating  = self::rating( $props );
		$details = 'mini' === $variant || 'deal' === $variant ? '' : self::details( $props );

		$body = match ( $variant ) {
			'hover'  => $title . sprintf( '<div class="um-pc-meta flex min-h-5 items-center justify-between gap-1.5"><span class="text-xs text-fg-3">%1$s</span>%2$s</div>', esc_html( $props['brand'] ?? '' ), $rating ) . $details . $price,
			'deal'   => $title . $price . self::sold( $props ),
			'swatch' => self::swatches( $props ) . $title . self::sizes( $props ) . $price . self::add_block( $props, true ),
			'mini'   => $title . ( isset( $props['unit'] ) && '' !== $props['unit'] ? sprintf( '<span class="text-2xs text-fg-3">%s</span>', esc_html( $props['unit'] ) ) : '' ) . sprintf( '<div class="mt-auto flex items-center justify-between gap-1.5">%1$s%2$s</div>', self::price( $props, true ), self::add_round( $props ) ),
			default  => $title . sprintf( '<div class="um-pc-meta flex min-h-5 items-center justify-between gap-1.5">%1$s%2$s</div>', self::note( $props ), $rating ) . $details . $price,
		};

		$frame = match ( $variant ) {
			'hover'  => 'group/card rounded-lg border border-border transition-[box-shadow,border-color] duration-200 hover:border-transparent hover:shadow-md',
			'deal'   => 'rounded-lg p-2',
			'mini'   => 'rounded-lg border border-border',
			'swatch' => 'rounded-lg border border-border',
			default  => 'p-2 transition-shadow duration-200 hover:z-2 hover:shadow-md',
		};
		$top = '';
		if ( 'deal' === $variant && isset( $props['deal'] ) ) {
			$top = sprintf( '<div class="flex items-center justify-between gap-1.5 px-1 pt-1 text-xs font-bold text-accent-text">%1$s%2$s</div>', Icon::render( 'clock', 'size-4' ), $props['deal']['countdown'] );
		}
		$quick  = 'hover' === $variant && isset( $props['add'] ) ? sprintf(
			'<a href="%1$s" class="absolute inset-x-2 bottom-2 z-2 inline-flex h-9 items-center justify-center gap-1.5 rounded-sm bg-primary text-sm font-bold text-on-primary no-underline opacity-0 transition duration-200 group-hover/card:translate-y-0 group-hover/card:opacity-100 max-lg:hidden translate-y-2 focus-visible:opacity-100 focus-visible:translate-y-0"%2$s rel="nofollow">%3$s%4$s</a>',
			esc_url( $props['add']['href'] ),
			$props['add']['ajax'] ? sprintf( ' data-um-add="%d"', $props['add']['id'] ) : '',
			Icon::render( 'cart', 'size-4' ),
			esc_html( $props['add']['label'] )
		) : '';
		$active = ( isset( $props['add'] ) && $props['add']['ajax'] ) || array() !== ( $props['swatches'] ?? array() );

		return Html::trusted(
			sprintf(
				'<article%1$s>%2$s%3$s%4$s<div class="um-pc-media relative">%5$s%6$s</div><div class="um-pc-body flex min-w-0 flex-1 flex-col %7$s">%8$s</div></article>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-product-card relative flex h-full min-w-0 flex-col overflow-hidden bg-surface text-fg', $frame, $props['class'] ?? null ),
						'data-layout'       => 'list' === $variant ? 'list' : null,
						'data-um-component' => $active ? 'product-card' : null,
					)
				),
				$top,
				self::badges( $props ),
				self::actions( $props, $variant ),
				$media,
				$quick,
				'mini' === $variant ? 'gap-1 px-3 pb-3' : 'gap-1.5 p-3',
				$body
			)
		);
	}

	/**
	 * The frame of a card whose inside was designed with Elementor (PLP-08),
	 * so it sits in grids and sliders like the built-in designs.
	 *
	 * @param Html $children The designed card.
	 */
	public static function shell( Html $children ): Html {
		return Html::trusted(
			sprintf(
				'<article class="um-product-card um-product-card-designed relative flex h-full min-w-0 flex-col overflow-hidden bg-surface text-fg" data-um-component="product-card">%s</article>',
				$children
			)
		);
	}

	public const PARTS = array( 'title', 'image', 'price', 'rating', 'badges', 'colors', 'brand', 'stock', 'add' );

	/**
	 * One piece of a card on its own, for cards designed with Elementor
	 * (PLP-08): the same markup as inside the built-in designs.
	 *
	 * @param string $part  One of PARTS.
	 * @param array  $props Card props.
	 * @param array  $options Options: the heading tag of the title, a hover image.
	 * @phpstan-param ProductCardProps $props
	 * @phpstan-param array{tag?: 'h2'|'h3'|'h4'|'p', hover?: bool} $options
	 */
	public static function part( string $part, array $props, array $options = array() ): Html {
		$out = match ( $part ) {
			'title'  => sprintf(
				'<%1$s class="um-pc-title m-0 line-clamp-2 text-sm leading-[1.75] font-medium"><a href="%2$s" class="text-fg no-underline hover:text-primary">%3$s</a></%1$s>',
				in_array( $options['tag'] ?? 'h3', array( 'h2', 'h3', 'h4', 'p' ), true ) ? ( $options['tag'] ?? 'h3' ) : 'h3',
				esc_url( $props['href'] ),
				esc_html( $props['title'] )
			),
			'image'  => sprintf( '<div class="group/card um-pc-media relative">%s</div>', self::media( $props, ( $options['hover'] ?? false ) ? 'hover' : 'classic' ) ),
			'price'  => self::price( $props ),
			'rating' => self::rating( $props ),
			'badges' => '' === self::badges( $props ) ? '' : '<div class="relative min-h-6">' . self::badges( $props ) . '</div>',
			'colors' => self::color_dots( $props ),
			'brand'  => isset( $props['brand'] ) && '' !== $props['brand'] ? sprintf( '<span class="text-xs text-fg-3">%s</span>', esc_html( $props['brand'] ) ) : '',
			'stock'  => isset( $props['note'] ) && '' !== $props['note'] ? self::note( $props ) : '',
			'add'    => self::add_block( $props, true ),
			default  => '',
		};
		return Html::trusted( $out );
	}

	/**
	 * Colour dots in a row, for a designed card.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function color_dots( array $props ): string {
		$items = '';
		foreach ( array_slice( $props['colors'] ?? array(), 0, 6 ) as $color ) {
			$items .= sprintf( '<span class="size-3 rounded-full border border-border-strong" style="background:%1$s" title="%2$s"></span>', esc_attr( $color['color'] ), esc_attr( $color['label'] ) );
		}
		return '' === $items ? '' : sprintf( '<span class="flex flex-wrap gap-1.5" role="img" aria-label="%1$s">%2$s</span>', esc_attr( $props['colors_label'] ?? '' ), $items );
	}

	/**
	 * The image link, with colour dots over it.
	 *
	 * @param array  $props   Props.
	 * @param string $variant Variant.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function media( array $props, string $variant ): string {
		$frame = match ( $variant ) {
			'hover', 'swatch' => 'm-1.5 rounded-md bg-surface-2',
			'mini'  => 'm-2 rounded-md bg-surface-2',
			default => '',
		};
		$alt = '';
		if ( 'hover' === $variant && isset( $props['image_alt'] ) && ! $props['image_alt']->is_empty() ) {
			$alt = sprintf( '<span class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover/card:opacity-100 [&_img]:size-full [&_img]:object-contain">%s</span>', $props['image_alt'] );
		}
		$dots = '';
		if ( in_array( $variant, array( 'classic', 'hover', 'list' ), true ) && array() !== ( $props['colors'] ?? array() ) ) {
			$items = '';
			foreach ( array_slice( $props['colors'] ?? array(), 0, 5 ) as $color ) {
				$items .= sprintf( '<span class="size-2.5 rounded-full border border-border-strong" style="background:%1$s" title="%2$s"></span>', esc_attr( $color['color'] ), esc_attr( $color['label'] ) );
			}
			$dots = sprintf( '<span class="absolute bottom-1 start-1 z-1 flex flex-col gap-1" role="img" aria-label="%1$s">%2$s</span>', esc_attr( $props['colors_label'] ?? '' ), $items );
		}
		return sprintf(
			'<a href="%1$s" class="%2$s" tabindex="-1" aria-hidden="true" data-um-card-image>%3$s%4$s</a>%5$s',
			esc_url( $props['href'] ),
			esc_attr( Attributes::classes( 'relative block aspect-square overflow-hidden [&_img]:size-full [&_img]:object-contain', $frame ) ),
			$props['image'],
			$alt,
			$dots
		);
	}

	/**
	 * The badges in the top corner.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function badges( array $props ): string {
		$out = '';
		foreach ( $props['badges'] ?? array() as $badge ) {
			$out .= sprintf(
				'<span class="inline-flex h-5.5 items-center gap-1 rounded-pill px-2 text-xs font-bold whitespace-nowrap %1$s">%2$s%3$s</span>',
				esc_attr( self::BADGE[ $badge['tone'] ] ),
				isset( $badge['icon'] ) ? Icon::render( $badge['icon'], 'size-3.5' ) : '',
				esc_html( $badge['label'] )
			);
		}
		return '' === $out ? '' : sprintf( '<div class="absolute start-2 top-2 z-2 flex flex-col items-start gap-1">%s</div>', $out );
	}

	/**
	 * Round action buttons in the top end corner. On the hover card they
	 * show with the pointer, and always on touch screens.
	 *
	 * @param array  $props   Props.
	 * @param string $variant Variant.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function actions( array $props, string $variant ): string {
		$actions = $props['actions'] ?? array();
		if ( array() === $actions ) {
			return '';
		}
		return sprintf(
			'<div class="%1$s">%2$s</div>',
			'hover' === $variant
				? 'absolute end-2 top-2 z-3 flex flex-col gap-1 transition-opacity duration-200 lg:opacity-0 lg:group-hover/card:opacity-100 lg:focus-within:opacity-100'
				: 'absolute end-2 top-2 z-3 flex flex-col gap-1',
			implode( '', array_map( 'strval', $actions ) )
		);
	}

	/**
	 * The stock or shipping note.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function note( array $props ): string {
		if ( ! isset( $props['note'] ) || '' === $props['note'] ) {
			return '<span></span>';
		}
		return sprintf(
			'<span class="inline-flex items-center gap-1 text-xs %1$s">%2$s%3$s</span>',
			'danger' === ( $props['note_tone'] ?? 'muted' ) ? 'font-medium text-danger-text' : 'text-fg-3',
			'danger' === ( $props['note_tone'] ?? 'muted' ) ? '' : Icon::render( 'truck', 'size-3.5 text-primary' ),
			esc_html( $props['note'] )
		);
	}

	/**
	 * The star rating.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function rating( array $props ): string {
		if ( ! isset( $props['rating'] ) || '' === $props['rating'] ) {
			return '';
		}
		return sprintf(
			'<span class="inline-flex items-center gap-0.5 text-xs font-medium text-fg-2"%1$s>%2$s<span class="font-num">%3$s</span></span>',
			isset( $props['rating_label'] ) ? sprintf( ' aria-label="%s"', esc_attr( $props['rating_label'] ) ) : '',
			'<svg class="size-3.5 shrink-0 text-rating" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>',
			esc_html( $props['rating'] )
		);
	}

	/**
	 * The parts a row shows (PLP-05): a line such as the brand and the
	 * number of reviews, key features and the add button. Hidden in the
	 * card look; the row look shows them.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function details( array $props ): string {
		$details  = $props['details'] ?? array();
		$line     = isset( $details['line'] ) && '' !== $details['line'] ? sprintf( '<p class="m-0 text-xs text-fg-3">%s</p>', esc_html( $details['line'] ) ) : '';
		$features = '';
		foreach ( array_slice( $details['features'] ?? array(), 0, 3 ) as $feature ) {
			$features .= sprintf( '<li class="relative ps-3 before:absolute before:start-0 before:top-2 before:size-1 before:rounded-full before:bg-fg-3">%s</li>', esc_html( $feature ) );
		}
		$features = '' === $features ? '' : sprintf( '<ul class="m-0 mt-1 grid list-none gap-1 p-0 text-xs text-fg-2 max-md:hidden">%s</ul>', $features );
		$out      = '';
		if ( '' !== $line || '' !== $features ) {
			$out .= sprintf( '<div class="um-pc-details">%1$s%2$s</div>', $line, $features );
		}
		return $out . self::add_block( $props, false );
	}

	/**
	 * A full width add button: always on the swatch card, in the row look
	 * of the others.
	 *
	 * @param array $props  Props.
	 * @param bool  $always Shown in every look.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function add_block( array $props, bool $always ): string {
		if ( ! isset( $props['add'] ) ) {
			return '';
		}
		return sprintf(
			'<div class="%1$s"><a href="%2$s" class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-sm border text-sm font-bold no-underline focus-visible:outline-3 focus-visible:outline-primary/40 aria-busy:opacity-60 %3$s" rel="nofollow"%4$s>%5$s%6$s</a></div>',
			$always ? 'mt-1' : 'um-pc-buy mt-1',
			esc_url( $props['add']['href'] ),
			$always ? 'border-primary bg-surface text-primary hover:bg-primary-soft' : 'border-primary bg-primary text-on-primary hover:bg-primary-hover',
			$props['add']['ajax'] ? sprintf( ' data-um-add="%d"', $props['add']['id'] ) : '',
			Icon::render( 'cart', 'size-4' ),
			esc_html( $props['add']['label'] )
		);
	}

	/**
	 * Colour buttons of the swatch card; each shows its colour's picture.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function swatches( array $props ): string {
		$swatches = $props['swatches'] ?? array();
		if ( array() === $swatches ) {
			return '';
		}
		$buttons = '';
		foreach ( array_slice( $swatches, 0, 6 ) as $index => $swatch ) {
			$buttons .= sprintf(
				'<button type="button" class="size-5.5 cursor-pointer rounded-full border border-border-strong p-0 aria-pressed:outline-2 aria-pressed:outline-offset-2 aria-pressed:outline-primary focus-visible:outline-3 focus-visible:outline-primary/40" style="background:%1$s" aria-pressed="%2$s" aria-label="%3$s" data-um-swatch%4$s%5$s></button>',
				esc_attr( $swatch['color'] ),
				0 === $index ? 'true' : 'false',
				esc_attr( $swatch['label'] ),
				isset( $swatch['src'] ) ? sprintf( ' data-src="%s"', esc_url( $swatch['src'] ) ) : '',
				isset( $swatch['srcset'] ) ? sprintf( ' data-srcset="%s"', esc_attr( $swatch['srcset'] ) ) : ''
			);
		}
		return sprintf(
			'<div class="flex flex-wrap items-center gap-2" role="group" aria-label="%1$s">%2$s<small class="font-num text-2xs text-fg-3">%1$s</small></div>',
			esc_attr( $props['swatches_label'] ?? '' ),
			$buttons
		);
	}

	/**
	 * Size chips of the swatch card; sold-out sizes are struck through.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function sizes( array $props ): string {
		$sizes = $props['sizes'] ?? array();
		if ( array() === $sizes ) {
			return '';
		}
		$out = '';
		foreach ( array_slice( $sizes, 0, 8 ) as $size ) {
			$out .= sprintf(
				'<li class="inline-grid h-6 min-w-6 place-items-center rounded-xs border px-1.5 text-2xs font-medium %1$s">%2$s</li>',
				( $size['out'] ?? false ) ? 'border-border bg-surface-3 text-fg-3 line-through' : 'border-border-strong text-fg-2',
				esc_html( $size['label'] )
			);
		}
		return sprintf( '<ul class="m-0 flex list-none flex-wrap gap-1 p-0">%s</ul>', $out );
	}

	/**
	 * The price block: discount, current price, currency and old price.
	 *
	 * @param array $props Props.
	 * @param bool  $small Smaller, start aligned (mini card).
	 * @phpstan-param ProductCardProps $props
	 */
	public static function price( array $props, bool $small = false ): string {
		if ( isset( $props['call'] ) && '' !== $props['call'] ) {
			return sprintf( '<p class="m-0 text-sm font-bold text-primary">%s</p>', esc_html( $props['call'] ) );
		}
		$discount = isset( $props['discount'] ) && '' !== $props['discount']
			? sprintf( '<span class="inline-flex h-5.5 items-center rounded-pill bg-primary px-2 font-num text-xs font-bold text-on-primary">%s</span>', esc_html( $props['discount'] ) )
			: '';
		$now      = sprintf(
			'<span class="%1$s">%2$s<span class="ms-0.5 text-xs font-regular text-fg-2">%3$s</span></span>',
			$small ? 'font-num text-md font-heavy whitespace-nowrap' : 'ms-auto font-num text-lg font-heavy whitespace-nowrap',
			esc_html( $props['price'] ),
			esc_html( $props['currency'] ?? '' )
		);
		$old      = isset( $props['regular'] ) && '' !== $props['regular']
			? sprintf( '<del class="font-num text-xs leading-tight text-fg-3 %1$s">%2$s</del>', $small ? '' : 'pe-8.5', esc_html( $props['regular'] ) )
			: '';
		if ( $small ) {
			return sprintf( '<div class="flex flex-col items-start">%1$s%2$s</div>', $now, $old );
		}
		return sprintf( '<div class="flex flex-col items-end"><div class="flex w-full items-center justify-between gap-2">%1$s%2$s</div>%3$s</div>', $discount, $now, $old );
	}

	/**
	 * The "sold" bar of a deal card.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function sold( array $props ): string {
		if ( ! isset( $props['deal'] ) ) {
			return '';
		}
		$sold = max( 0, min( 100, $props['deal']['sold'] ) );
		return sprintf(
			'<div><div class="h-1.5 overflow-hidden rounded-pill bg-accent-soft" role="progressbar" aria-valuenow="%1$d" aria-valuemin="0" aria-valuemax="100" aria-label="%2$s"><i class="block h-full rounded-pill bg-accent" style="width:%1$d%%"></i></div><div class="mt-1 flex justify-between text-2xs text-fg-3"><span>%2$s</span><span>%3$s</span></div></div>',
			$sold,
			esc_html( $props['deal']['sold_label'] ),
			esc_html( $props['deal']['left_label'] )
		);
	}

	/**
	 * The round add button of the mini card.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function add_round( array $props ): string {
		if ( ! isset( $props['add'] ) ) {
			return '';
		}
		return sprintf(
			'<a href="%1$s" class="grid size-9 shrink-0 place-items-center rounded-full bg-primary text-on-primary no-underline hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-primary/40" aria-label="%2$s" rel="nofollow"%3$s>%4$s</a>',
			esc_url( $props['add']['href'] ),
			esc_attr( $props['add']['label'] . ': ' . $props['title'] ),
			$props['add']['ajax'] ? sprintf( ' data-um-add="%d"', $props['add']['id'] ) : '',
			Icon::render( 'plus', 'size-5' )
		);
	}
}
