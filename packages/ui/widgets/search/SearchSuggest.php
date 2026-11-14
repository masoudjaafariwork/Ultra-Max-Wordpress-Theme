<?php
/**
 * SearchSuggest component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The suggestions of the live search (SRCH-01): matching categories and
 * brands as chips, products with picture and price, posts, and a link to
 * every result. Each link is an option of the search field's list box, so
 * arrow keys move through them.
 *
 * The server renders it and the live search behaviour puts it under the
 * field.
 *
 * @phpstan-type SuggestLink array{label: string, href: string, hint?: string}
 * @phpstan-type SuggestProduct array{title: string, href: string, image: Html, price?: string, regular?: string}
 * @phpstan-type SearchSuggestProps array{
 *     id: string,
 *     products: list<SuggestProduct>,
 *     categories?: list<SuggestLink>,
 *     brands?: list<SuggestLink>,
 *     posts?: list<SuggestLink>,
 *     labels: array{products: string, categories: string, brands: string, posts: string},
 *     all?: array{label: string, href: string},
 *     empty: string,
 * }
 */
final class SearchSuggest {

	/**
	 * Renders the suggestions.
	 *
	 * @param array $props Props.
	 * @phpstan-param SearchSuggestProps $props
	 */
	public static function render( array $props ): Html {
		$id    = Props::id( 'SearchSuggest', $props );
		$index = 0;
		$next  = static function () use ( $id, &$index ): string {
			$current = $index;
			++$index;
			return $id . '-o' . $current;
		};
		$out   = '';

		foreach ( array( 'categories', 'brands' ) as $kind ) {
			$links = $props[ $kind ] ?? array();
			if ( array() === $links ) {
				continue;
			}
			$chips = '';
			foreach ( $links as $link ) {
				$chips .= sprintf(
					'<a id="%1$s" role="option" aria-selected="false" href="%2$s" class="inline-flex h-8 items-center gap-1.5 rounded-pill border border-border px-3 text-xs font-medium text-fg no-underline hover:border-primary aria-selected:border-primary aria-selected:bg-primary-soft">%3$s%4$s%5$s</a>',
					esc_attr( $next() ),
					esc_url( $link['href'] ),
					Icon::render( 'categories' === $kind ? 'folder' : 'tag', 'size-3.5 text-fg-3' ),
					esc_html( $link['label'] ),
					isset( $link['hint'] ) && '' !== $link['hint'] ? sprintf( '<span class="text-fg-3" dir="ltr">%s</span>', esc_html( $link['hint'] ) ) : ''
				);
			}
			$out .= self::group( $props['labels'][ $kind ], sprintf( '<div class="flex flex-wrap gap-1.5 px-2">%s</div>', $chips ) );
		}

		if ( array() !== $props['products'] ) {
			$rows = '';
			foreach ( $props['products'] as $product ) {
				$price = isset( $product['price'] ) && '' !== $product['price']
					? sprintf(
						'<span class="flex flex-wrap items-baseline gap-x-2"><span class="font-num text-sm font-bold text-fg">%1$s</span>%2$s</span>',
						esc_html( $product['price'] ),
						isset( $product['regular'] ) && '' !== $product['regular'] ? sprintf( '<del class="font-num text-xs text-fg-3">%s</del>', esc_html( $product['regular'] ) ) : ''
					)
					: '';
				$rows .= sprintf(
					'<a id="%1$s" role="option" aria-selected="false" href="%2$s" class="flex items-center gap-3 rounded-md p-2 text-fg no-underline hover:bg-surface-2 aria-selected:bg-primary-soft"><span class="size-12 shrink-0 overflow-hidden rounded-sm bg-surface-2 [&_img]:size-full [&_img]:object-contain">%3$s</span><span class="grid min-w-0 gap-0.5"><span class="line-clamp-2 text-sm">%4$s</span>%5$s</span></a>',
					esc_attr( $next() ),
					esc_url( $product['href'] ),
					$product['image'],
					esc_html( $product['title'] ),
					$price
				);
			}
			$out .= self::group( $props['labels']['products'], $rows );
		}

		$posts = $props['posts'] ?? array();
		if ( array() !== $posts ) {
			$rows = '';
			foreach ( $posts as $post ) {
				$rows .= sprintf(
					'<a id="%1$s" role="option" aria-selected="false" href="%2$s" class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-fg no-underline hover:bg-surface-2 aria-selected:bg-primary-soft">%3$s<span class="line-clamp-1">%4$s</span></a>',
					esc_attr( $next() ),
					esc_url( $post['href'] ),
					Icon::render( 'file', 'size-4 shrink-0 text-fg-3' ),
					esc_html( $post['label'] )
				);
			}
			$out .= self::group( $props['labels']['posts'], $rows );
		}

		if ( '' === $out ) {
			return Html::trusted( sprintf( '<p class="m-0 p-3 text-sm text-fg-3" role="status">%s</p>', esc_html( $props['empty'] ) ) );
		}
		if ( isset( $props['all'] ) ) {
			$out .= sprintf(
				'<a id="%1$s" role="option" aria-selected="false" href="%2$s" class="mt-1 flex items-center justify-center gap-1 rounded-md border-t border-border p-2.5 text-sm font-bold text-link no-underline hover:bg-surface-2 aria-selected:bg-primary-soft">%3$s%4$s</a>',
				esc_attr( $next() ),
				esc_url( $props['all']['href'] ),
				esc_html( $props['all']['label'] ),
				Icon::render( 'chevron-left', 'size-4 ltr:rotate-180' )
			);
		}
		return Html::trusted( $out );
	}

	/**
	 * A titled group of options.
	 *
	 * @param string $label Group title.
	 * @param string $body  Options.
	 */
	private static function group( string $label, string $body ): string {
		$id = 'um-sg-' . substr( md5( $label ), 0, 6 );
		return sprintf(
			'<div role="group" aria-labelledby="%1$s" class="grid gap-1 py-1.5"><p id="%1$s" class="m-0 px-2 text-xs font-bold text-fg-3">%2$s</p>%3$s</div>',
			esc_attr( $id ),
			esc_html( $label ),
			$body
		);
	}
}
