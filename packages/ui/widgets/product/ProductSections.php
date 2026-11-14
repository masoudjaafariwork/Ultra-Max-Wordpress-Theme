<?php
/**
 * ProductSections component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * The lower part of a product page: introduction, specifications, the
 * store's own tabs, reviews and questions (PDP-06 to PDP-10, PDP-14).
 *
 * - `sections`: one long page under a sticky row of links, as on large
 *   Iranian stores; the link of the section in view is marked. Everything
 *   is in the page, so search engines read it all.
 * - `tabs`: classic tabs.
 * - `accordion`: folding panels, good for short pages.
 *
 * `aside` sits beside the sections on desktop, such as the small sticky buy
 * box (PDP-20).
 *
 * @phpstan-type ProductSection array{id: string, title: string, content: Html, count?: string}
 * @phpstan-type ProductSectionsProps array{
 *     id: string,
 *     label: string,
 *     items: list<ProductSection>,
 *     variant?: 'sections'|'tabs'|'accordion',
 *     aside?: Html,
 *     class?: string,
 * }
 */
final class ProductSections {

	public const VARIANTS = array( 'sections', 'tabs', 'accordion' );

	/**
	 * Renders the sections, or nothing without items.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProductSectionsProps $props
	 */
	public static function render( array $props ): Html {
		$items = array_values( array_filter( $props['items'], static fn ( array $item ): bool => ! $item['content']->is_empty() ) );
		if ( array() === $items ) {
			return Html::none();
		}
		$id      = Props::id( 'ProductSections', $props );
		$variant = Props::one_of( 'ProductSections', $props, 'variant', self::VARIANTS );
		$body    = match ( $variant ) {
			'tabs'      => Tabs::render(
				array(
					'id'    => $id . '-tabs',
					'label' => $props['label'],
					'items' => array_map(
						static fn ( array $item ): array => array(
							'label'   => self::label( $item ),
							'content' => Html::trusted( sprintf( '<div id="%1$s">%2$s</div>', esc_attr( $item['id'] ), $item['content'] ) ),
						),
						$items
					),
					'class' => 'rounded-lg border border-border bg-surface px-5 max-md:px-4',
				)
			),
			'accordion' => Accordion::render(
				array(
					'id'       => $id . '-accordion',
					'multiple' => true,
					'heading'  => 'h2',
					'items'    => array_map(
						static fn ( array $item, int $index ): array => array(
							'title'   => self::label( $item ),
							'content' => Html::trusted( sprintf( '<div id="%1$s">%2$s</div>', esc_attr( $item['id'] ), $item['content'] ) ),
							'open'    => 0 === $index,
						),
						$items,
						array_keys( $items )
					),
				)
			),
			default     => self::sections( $id, $props['label'], $items ),
		};
		$aside = isset( $props['aside'] ) && ! $props['aside']->is_empty()
			? sprintf( '<div class="max-lg:hidden">%s</div>', $props['aside'] )
			: '';
		return Html::trusted(
			sprintf(
				'<div%1$s><div class="min-w-0">%2$s</div>%3$s</div>',
				Attributes::render(
					array(
						'id'    => $id,
						'class' => Attributes::classes(
							'um-scope um-product-sections grid items-start gap-6',
							'' === $aside ? null : 'lg:grid-cols-[minmax(0,1fr)_300px]',
							$props['class'] ?? null
						),
					)
				),
				$body,
				$aside
			)
		);
	}

	/**
	 * A section title with its count.
	 *
	 * @param array $item Section.
	 * @phpstan-param ProductSection $item
	 */
	private static function label( array $item ): Html {
		return Html::trusted(
			esc_html( $item['title'] ) . ( isset( $item['count'] ) && '' !== $item['count']
				? sprintf( ' <span class="inline-grid h-5 min-w-5 place-items-center rounded-pill bg-surface-3 px-1.5 font-num text-2xs font-bold text-fg-2">%s</span>', esc_html( $item['count'] ) )
				: '' )
		);
	}

	/**
	 * The long page with its sticky links.
	 *
	 * @param string $id    Id.
	 * @param string $label Name of the links.
	 * @param array  $items Sections.
	 * @phpstan-param list<ProductSection> $items
	 */
	private static function sections( string $id, string $label, array $items ): string {
		$links    = '';
		$sections = '';
		foreach ( $items as $index => $item ) {
			$links    .= sprintf(
				'<a href="#%1$s" class="relative -mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-3 border-transparent py-3 text-sm font-medium text-fg-2 no-underline hover:text-fg aria-current:border-primary aria-current:text-primary focus-visible:outline-3 focus-visible:outline-primary/40"%2$s data-um-section-link>%3$s</a>',
				esc_attr( $item['id'] ),
				0 === $index ? ' aria-current="true"' : '',
				self::label( $item )
			);
			$sections .= sprintf(
				'<section id="%1$s" class="scroll-mt-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px)+64px)] border-b border-border py-6 last:border-b-0" aria-labelledby="%1$s-title" data-um-section><h2 id="%1$s-title" class="m-0 mb-4 inline-block border-b-3 border-primary pb-1.5 text-lg font-bold">%2$s</h2>%3$s</section>',
				esc_attr( $item['id'] ),
				esc_html( $item['title'] ),
				$item['content']
			);
		}
		return sprintf(
			'<div data-um-component="product-sections"><nav class="sticky top-[calc(var(--wp-admin--admin-bar--height,0px)+var(--um-header-offset,0px))] z-3 bg-page transition-[top] duration-300" aria-label="%1$s"><div class="flex gap-6 overflow-x-auto rounded-t-lg border border-border bg-surface px-5 [scrollbar-width:none] max-md:gap-4 max-md:px-4">%2$s</div></nav><div class="rounded-b-lg border border-t-0 border-border bg-surface px-5 max-md:px-4">%3$s</div></div>',
			esc_attr( $label ),
			$links,
			$sections
		);
	}
}
