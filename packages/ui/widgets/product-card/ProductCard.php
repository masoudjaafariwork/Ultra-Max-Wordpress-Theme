<?php
/**
 * ProductCard component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One product in a list or slider, in the designs of design/design-system.html.
 * It is composed of smaller parts: image, title, rating, price and an add
 * button; every number arrives already formatted.
 *
 * - `classic`: lined grids, as in "discount picks" (HOME-09).
 * - `hover`: a framed card whose second image shows on hover (HOME-06).
 * - `deal`: with a countdown and a "sold" bar, for amazing offers (HOME-03).
 * - `mini`: small supermarket card with a round add button.
 *
 * The add button is a plain link to WooCommerce's add-to-cart address, so
 * it works without JavaScript; the behaviour adds the product in place
 * and updates the cart counters on the page.
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
 *     variant?: 'classic'|'hover'|'deal'|'mini',
 *     class?: string,
 * }
 */
final class ProductCard {

	public const VARIANTS = array( 'classic', 'hover', 'deal', 'mini' );

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
			'<h3 class="m-0 line-clamp-2 %1$s"><a href="%2$s" class="text-fg no-underline hover:text-primary">%3$s</a></h3>',
			'mini' === $variant ? 'min-h-[3.4em] text-xs leading-body font-medium' : 'min-h-[3.5em] text-sm leading-[1.75] font-medium',
			esc_url( $props['href'] ),
			esc_html( $props['title'] )
		);
		$media   = self::media( $props, $variant );
		$price   = self::price( $props, 'mini' === $variant );
		$rating  = self::rating( $props );

		$body = match ( $variant ) {
			'hover' => $title . sprintf( '<div class="flex min-h-5 items-center justify-between gap-1.5"><span class="text-xs text-fg-3">%1$s</span>%2$s</div>', esc_html( $props['brand'] ?? '' ), $rating ) . '<div class="mt-auto">' . $price . '</div>',
			'deal'  => $title . '<div class="mt-auto">' . $price . '</div>' . self::sold( $props ),
			'mini'  => $title . ( isset( $props['unit'] ) && '' !== $props['unit'] ? sprintf( '<span class="text-2xs text-fg-3">%s</span>', esc_html( $props['unit'] ) ) : '' ) . sprintf( '<div class="mt-auto flex items-center justify-between gap-1.5">%1$s%2$s</div>', $price, self::add_round( $props ) ),
			default => $title . sprintf( '<div class="flex min-h-5 items-center justify-between gap-1.5">%1$s%2$s</div>', self::note( $props ), $rating ) . '<div class="mt-auto">' . $price . '</div>',
		};

		$frame = match ( $variant ) {
			'hover' => 'group/card rounded-lg border border-border transition-[box-shadow,border-color] duration-200 hover:border-transparent hover:shadow-md',
			'deal'  => 'rounded-lg p-2',
			'mini'  => 'rounded-lg border border-border',
			default => 'p-2 transition-shadow duration-200 hover:z-2 hover:shadow-md',
		};
		$top = '';
		if ( 'deal' === $variant && isset( $props['deal'] ) ) {
			$top = sprintf( '<div class="flex items-center justify-between gap-1.5 px-1 pt-1 text-xs font-bold text-accent-strong">%1$s%2$s</div>', Icon::render( 'clock', 'size-4' ), $props['deal']['countdown'] );
		}
		$labels = self::badges( $props );
		$quick  = 'hover' === $variant && isset( $props['add'] ) ? sprintf(
			'<a href="%1$s" class="absolute inset-x-2 bottom-2 z-2 inline-flex h-9 items-center justify-center gap-1.5 rounded-sm bg-primary text-sm font-bold text-on-primary no-underline opacity-0 transition duration-200 group-hover/card:translate-y-0 group-hover/card:opacity-100 max-lg:hidden translate-y-2 focus-visible:opacity-100 focus-visible:translate-y-0"%2$s rel="nofollow">%3$s%4$s</a>',
			esc_url( $props['add']['href'] ),
			$props['add']['ajax'] ? sprintf( ' data-um-add="%d"', $props['add']['id'] ) : '',
			Icon::render( 'cart', 'size-4' ),
			esc_html( $props['add']['label'] )
		) : '';

		return Html::trusted(
			sprintf(
				'<article%1$s>%2$s%3$s<div class="relative">%4$s%5$s</div><div class="flex flex-1 flex-col %6$s">%7$s</div></article>',
				Attributes::render(
					array(
						'class'             => Attributes::classes( 'um-product-card relative flex h-full min-w-0 flex-col overflow-hidden bg-surface text-fg', $frame, $props['class'] ?? null ),
						'data-um-component' => isset( $props['add'] ) && $props['add']['ajax'] ? 'product-card' : null,
					)
				),
				$top,
				$labels,
				$media,
				$quick,
				match ( $variant ) {
					'mini' => 'gap-1 px-3 pb-3',
					default => 'gap-1.5 p-3',
				},
				$body
			)
		);
	}

	/**
	 * The image link.
	 *
	 * @param array  $props   Props.
	 * @param string $variant Variant.
	 * @phpstan-param ProductCardProps $props
	 */
	private static function media( array $props, string $variant ): string {
		$frame = match ( $variant ) {
			'hover' => 'm-1.5 rounded-md bg-surface-2',
			'mini'  => 'm-2 rounded-md bg-surface-2',
			default => '',
		};
		$alt = '';
		if ( 'hover' === $variant && isset( $props['image_alt'] ) && ! $props['image_alt']->is_empty() ) {
			$alt = sprintf( '<span class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover/card:opacity-100 [&_img]:size-full [&_img]:object-contain">%s</span>', $props['image_alt'] );
		}
		return sprintf(
			'<a href="%1$s" class="%2$s" tabindex="-1" aria-hidden="true">%3$s%4$s</a>',
			esc_url( $props['href'] ),
			esc_attr( Attributes::classes( 'relative block aspect-square overflow-hidden [&_img]:size-full [&_img]:object-contain', $frame ) ),
			$props['image'],
			$alt
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
