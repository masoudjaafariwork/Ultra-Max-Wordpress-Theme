<?php
/**
 * The product page (PDP-01 to PDP-26, BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Builder\Builder;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Box;
use UltraMax\Core\Lib\UI\BuyBox;
use UltraMax\Core\Lib\UI\Countdown;
use UltraMax\Core\Lib\UI\Dialog;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\KeyFeatures;
use UltraMax\Core\Lib\UI\ProductGallery;
use UltraMax\Core\Lib\UI\ProductHeading;
use UltraMax\Core\Lib\UI\ProductPrice;
use UltraMax\Core\Lib\UI\ProductSections;
use UltraMax\Core\Lib\UI\PurchaseForm;
use UltraMax\Core\Lib\UI\RecentProducts;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Lib\UI\SeoText;
use UltraMax\Core\Lib\UI\ServicePerks;
use UltraMax\Core\Lib\UI\ShareLinks;
use UltraMax\Core\Lib\UI\SpecTable;
use UltraMax\Core\Lib\UI\StickyBuy;
use UltraMax\Core\Lib\UI\UI;
use UltraMax\Core\Lib\UI\VariationPicker;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\CurrentProduct;
use UltraMax\Core\Store\Format;

/**
 * One renderer for every piece of a product page. The default page, the
 * product layout widgets (BLD-04) and their shortcodes all call these
 * methods with the current product (CurrentProduct), so a page designed
 * with Elementor behaves exactly like the built-in one.
 *
 * The built-in arrangement follows design/pdp.html: gallery, info and buy
 * box on top; the store's promises; the sections with a small sticky buy
 * box beside them; similar, complementary and recently viewed products.
 */
final class Page {

	public const SHARE = 'um-share-dialog';

	/**
	 * The product of the page, or of the widget being drawn.
	 */
	public static function product(): ?\WC_Product {
		return CurrentProduct::get();
	}

	/**
	 * The whole page: a layout designed with Elementor (Pro first, then
	 * ours) or the built-in arrangement.
	 */
	public static function page(): Html {
		ob_start();
		$designed = function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( 'single' );
		$left     = array(
			'desktop' => ! $designed,
			'tablet'  => ! $designed,
			'mobile'  => ! $designed,
		);
		if ( ! $designed && Builder::enabled() ) {
			/** This filter is documented in themes/ultra-max/src/Template.php */
			$covered = apply_filters(
				'ultra_max_do_location',
				array(
					'desktop' => false,
					'tablet'  => false,
					'mobile'  => false,
				),
				Product::LOCATION
			);
			$covered = is_array( $covered ) ? $covered : array();
			foreach ( array_keys( $left ) as $device ) {
				$left[ $device ] = true !== ( $covered[ $device ] ?? false );
			}
		}
		$parts = array( Html::wp( (string) ob_get_clean() ) );
		if ( in_array( true, $left, true ) ) {
			$parts[] = self::arrangement( UI::visibility( $left ) );
		}
		return Html::trusted( sprintf( '<div class="um-product-page grid min-w-0 gap-6">%s</div>', Html::from( $parts ) ) );
	}

	/**
	 * The built-in arrangement of design/pdp.html.
	 *
	 * @param string $classes Visibility classes for the devices it covers.
	 */
	public static function arrangement( string $classes = '' ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$info  = array(
			self::heading(),
			self::picker(),
			self::key_features(),
			self::notice(),
		);
		$top   = sprintf(
			'<div class="grid items-start gap-6 md:grid-cols-2 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)_320px] max-md:gap-4">%1$s<div class="grid min-w-0 content-start gap-5">%2$s</div><div class="min-w-0 md:col-span-2 lg:col-span-1">%3$s</div></div>',
			self::gallery(),
			Html::from( $info ),
			self::buy_box()
		);
		$parts = array(
			Archive::breadcrumbs(),
			Html::trusted( $top ),
			self::perks(),
			self::extras( 'after_perks' ),
			self::sections( Store::bool( 'product_sticky' ) ? self::sticky( 'card' ) : Html::none() ),
			self::similar(),
			self::complementary(),
			self::recent(),
		);
		$after = array(
			Store::bool( 'product_sticky' ) ? self::sticky( 'bar' ) : Html::none(),
			self::share_dialog(),
			self::extras( 'dialogs' ),
		);
		return Html::trusted(
			sprintf(
				'<div class="%1$s" data-um-product="%2$d">%3$s</div>%4$s',
				esc_attr( trim( 'grid min-w-0 gap-6 max-md:gap-4 ' . $classes ) ),
				$product->get_id(),
				Html::from( $parts ),
				Html::from( $after )
			)
		);
	}

	/**
	 * Pieces other parts of the plugin add to the arrangement, such as the
	 * "bought together" box or the size guide window.
	 *
	 * @param string $where after_perks or dialogs.
	 */
	public static function extras( string $where ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		/**
		 * Filters extra pieces of the built-in product page.
		 *
		 * @param array       $pieces  Pieces.
		 * @param string      $where   Place: after_perks or dialogs.
		 * @param \WC_Product $product Product.
		 * @phpstan-param list<Html> $pieces
		 */
		$pieces = apply_filters( 'ultra_max_product_page_extras', array(), $where, $product );
		return Html::from( is_array( $pieces ) ? array_values( array_filter( $pieces, static fn ( mixed $piece ): bool => $piece instanceof Html ) ) : array() );
	}

	/**
	 * Texts of the gallery.
	 *
	 * @return array{zoom: string, open: string, close: string, previous: string, next: string, video: string, more: string, counter: string}
	 */
	private static function gallery_labels(): array {
		return array(
			'zoom'     => __( 'Point to zoom, click to enlarge', 'ultra-max-core' ),
			'open'     => __( 'Show full screen', 'ultra-max-core' ),
			'close'    => __( 'Close', 'ultra-max-core' ),
			'previous' => __( 'Previous picture', 'ultra-max-core' ),
			'next'     => __( 'Next picture', 'ultra-max-core' ),
			'video'    => __( 'Play video', 'ultra-max-core' ),
			'more'     => __( 'See all pictures', 'ultra-max-core' ),
			/* translators: 1: picture number, 2: number of pictures. */
			'counter'  => __( '%1$s of %2$s', 'ultra-max-core' ),
		);
	}

	/**
	 * The round buttons over the picture: share, and those other modules
	 * add, such as wishlist and compare.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<Html>
	 */
	public static function tools( \WC_Product $product ): array {
		$tools = array();
		if ( Store::bool( 'product_share' ) ) {
			$tools[] = ProductGallery::tool( 'send', __( 'Share', 'ultra-max-core' ), array( 'data-um-open' => self::SHARE ) );
		}
		/**
		 * Filters the round buttons over a product's picture.
		 *
		 * @param array       $tools   Buttons.
		 * @param \WC_Product $product Product.
		 * @phpstan-param list<Html> $tools
		 */
		$tools = apply_filters( 'ultra_max_product_tools', $tools, $product );
		return is_array( $tools ) ? array_values( array_filter( $tools, static fn ( mixed $tool ): bool => $tool instanceof Html ) ) : array();
	}

	/**
	 * The gallery (PDP-01, PDP-02, PDP-03).
	 *
	 * @param string $layout Layout; the setting when empty.
	 * @param bool   $tools  Whether to show the round buttons.
	 */
	public static function gallery( string $layout = '', bool $tools = true ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$layout = '' === $layout ? Store::string( 'product_gallery' ) : $layout;
		$sets   = array();
		if ( $product instanceof \WC_Product_Variable ) {
			foreach ( Purchase::variations( $product ) as $variation ) {
				$ids = Gallery::variation_ids( $product, $variation );
				$key = Gallery::key( $ids );
				if ( '' !== $key && ! isset( $sets[ $key ] ) ) {
					$sets[ $key ] = Gallery::images( $product, $ids, false );
				}
			}
		}
		$props = array(
			'id'      => 'um-gallery-' . $product->get_id(),
			/* translators: %s: product name. */
			'label'   => sprintf( __( 'Pictures of %s', 'ultra-max-core' ), wp_strip_all_tags( $product->get_name() ) ),
			'items'   => Gallery::items( $product ),
			'labels'  => self::gallery_labels(),
			'product' => $product->get_id(),
			'layout'  => in_array( $layout, ProductGallery::LAYOUTS, true ) ? $layout : 'side',
			'zoom'    => Store::bool( 'product_zoom' ),
			'sets'    => $sets,
		);
		if ( $tools ) {
			$props['tools'] = self::tools( $product );
		}
		return ProductGallery::render( $props );
	}

	/**
	 * Brand and category links of a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{label: string, href: string}>
	 */
	private static function links( \WC_Product $product ): array {
		$links = array();
		foreach ( array( 'product_brand', 'product_cat' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = get_the_terms( $product->get_id(), $taxonomy );
			if ( ! is_array( $terms ) || array() === $terms ) {
				continue;
			}
			// The deepest category says the most.
			usort( $terms, static fn ( \WP_Term $a, \WP_Term $b ): int => count( get_ancestors( $b->term_id, $taxonomy ) ) <=> count( get_ancestors( $a->term_id, $taxonomy ) ) );
			$link = get_term_link( $terms[0] );
			if ( is_string( $link ) ) {
				$links[] = array(
					'label' => $terms[0]->name,
					'href'  => $link,
				);
			}
		}
		return $links;
	}

	/**
	 * The name, links and rating line (PDP-07).
	 *
	 * @param 'h1'|'h2' $tag Heading level.
	 */
	public static function heading( string $tag = 'h1' ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$props  = array(
			'title' => wp_strip_all_tags( $product->get_name() ),
			'latin' => Meta::text( $product, Meta::LATIN ),
			'links' => self::links( $product ),
			'tag'   => $tag,
		);
		$rating = (float) $product->get_average_rating();
		$count  = $product->get_rating_count();
		if ( $rating > 0 && $count > 0 ) {
			$value           = Format::digits( number_format( $rating, 1, Format::persian() ? '٫' : '.', '' ) );
			$props['rating'] = array(
				'value' => $value,
				/* translators: 1: rating such as 4.5, 2: number of ratings. */
				'label' => sprintf( __( 'Rated %1$s out of 5 by %2$s shoppers', 'ultra-max-core' ), $value, Format::number( $count ) ),
				/* translators: %s: number of ratings. */
				'count' => sprintf( _n( '%s rating', '%s ratings', $count, 'ultra-max-core' ), Format::number( $count ) ),
			);
		}
		if ( comments_open( $product->get_id() ) ) {
			$reviews          = $product->get_review_count();
			$props['reviews'] = array(
				/* translators: %s: number of reviews. */
				'label' => $reviews > 0 ? sprintf( _n( '%s review', '%s reviews', $reviews, 'ultra-max-core' ), Format::number( $reviews ) ) : __( 'Write the first review', 'ultra-max-core' ),
				'href'  => '#reviews',
			);
		}
		/**
		 * Filters the props of a product's heading, such as the questions
		 * link, the share of buyers who recommend it and its badges.
		 *
		 * @param array                $props   ProductHeading props.
		 * @param \WC_Product          $product Product.
		 * @phpstan-param array<string, mixed> $props
		 */
		$filtered = apply_filters( 'ultra_max_product_heading', array(), $product );
		$filtered = is_array( $filtered ) ? $filtered : array();
		$link     = $filtered['questions'] ?? null;
		if ( is_array( $link ) && is_string( $link['label'] ?? null ) && is_string( $link['href'] ?? null ) ) {
			$props['questions'] = array(
				'label' => $link['label'],
				'href'  => $link['href'],
			);
		}
		if ( is_string( $filtered['recommend'] ?? null ) ) {
			$props['recommend'] = $filtered['recommend'];
		}
		$badges = array();
		foreach ( is_array( $filtered['badges'] ?? null ) ? $filtered['badges'] : self::badges( $product ) as $badge ) {
			if ( is_array( $badge ) && is_string( $badge['label'] ?? null ) && in_array( $badge['tone'] ?? '', array( 'hot', 'new', 'brand', 'neutral' ), true ) ) {
				$clean = array(
					'label' => $badge['label'],
					'tone'  => $badge['tone'],
				);
				if ( is_string( $badge['icon'] ?? null ) ) {
					$clean['icon'] = $badge['icon'];
				}
				$badges[] = $clean;
			}
		}
		if ( array() !== $badges ) {
			$props['badges'] = $badges;
		}
		return ProductHeading::render( $props );
	}

	/**
	 * The choices of a variable product (PDP-04).
	 */
	public static function picker(): Html {
		$product = self::product();
		if ( ! $product instanceof \WC_Product_Variable ) {
			return Html::none();
		}
		return VariationPicker::render(
			array(
				'form'       => Purchase::form_id( $product ),
				'attributes' => Purchase::attributes( $product ),
				'reset'      => __( 'Clear choices', 'ultra-max-core' ),
			)
		);
	}

	/**
	 * Key features (PDP-06).
	 *
	 * @param int $count Most to show; the setting when negative.
	 */
	public static function key_features( int $count = -1 ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$items = Specs::key( $product, $count < 0 ? Store::int( 'product_key_count' ) : $count );
		$props = array(
			'title' => __( 'Key features', 'ultra-max-core' ),
			'items' => $items,
		);
		if ( array() !== Specs::groups( $product ) ) {
			$props['more'] = array(
				'label' => __( 'See all specifications', 'ultra-max-core' ),
				'href'  => '#specs',
			);
		}
		return KeyFeatures::render( $props );
	}

	/**
	 * The store's note under the key features, such as its return rules.
	 */
	public static function notice(): Html {
		$text = trim( Store::string( 'product_notice' ) );
		if ( '' === $text ) {
			return Html::none();
		}
		return Alert::render(
			array(
				'children' => $text,
				'tone'     => 'info',
				'role'     => 'none',
			)
		);
	}

	/**
	 * The price (PDP-05).
	 *
	 * @param 'lg'|'md'|'sm' $size Size.
	 */
	public static function price( string $size = 'lg' ): Html {
		$product = self::product();
		return null === $product ? Html::none() : ProductPrice::render( Purchase::price( $product, $size ) );
	}

	/**
	 * The purchase form, or WooCommerce's own for grouped and external
	 * products (PDP-05, PDP-21, PDP-25).
	 *
	 * @param bool $quick Whether to offer "buy now".
	 */
	public static function form( bool $quick = true ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$props = Purchase::form( $product, $quick );
		if ( null === $props ) {
			ob_start();
			woocommerce_template_single_add_to_cart();
			return Html::wp( (string) ob_get_clean() );
		}
		return PurchaseForm::render( $props );
	}

	/**
	 * The rows of the buy box: seller, warranty, and those other parts add,
	 * such as the delivery estimate.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{icon: string, text: string|Html, title?: string, note?: string}>
	 */
	public static function rows( \WC_Product $product ): array {
		$rows = array();
		if ( Store::bool( 'product_seller' ) ) {
			$rows[] = array(
				'icon'  => 'store',
				'title' => get_bloginfo( 'name' ),
				'text'  => '',
				'note'  => Store::string( 'product_seller_note' ),
			);
		}
		$warranty = Meta::text( $product, Meta::WARRANTY );
		$warranty = '' === $warranty ? Store::string( 'product_warranty' ) : $warranty;
		if ( '' !== $warranty && '-' !== $warranty ) {
			$rows[] = array(
				'icon' => 'shield',
				'text' => $warranty,
			);
		}
		/**
		 * Filters the rows of a product's buy box.
		 *
		 * @param array                      $rows    Rows: icon, text, title, note.
		 * @param \WC_Product                $product Product.
		 * @phpstan-param list<array<string, mixed>> $rows
		 */
		$filtered = apply_filters( 'ultra_max_buy_box_rows', $rows, $product );
		$out      = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $row ) {
			if ( ! is_array( $row ) || ! is_string( $row['icon'] ?? null ) || ! ( is_string( $row['text'] ?? null ) || ( $row['text'] ?? null ) instanceof Html ) ) {
				continue;
			}
			$clean = array(
				'icon' => $row['icon'],
				'text' => $row['text'],
			);
			foreach ( array( 'title', 'note' ) as $key ) {
				if ( is_string( $row[ $key ] ?? null ) ) {
					$clean[ $key ] = $row[ $key ];
				}
			}
			$out[] = $clean;
		}
		return $out;
	}

	/**
	 * The offer strip of the buy box: a countdown to the end of the sale
	 * (PDP-16), when the sale has an end date.
	 *
	 * @param \WC_Product $product Product.
	 * @return array{label: string, countdown: Html}|null
	 */
	public static function deal( \WC_Product $product ): ?array {
		if ( ! $product->is_on_sale() ) {
			return null;
		}
		$end = $product->get_date_on_sale_to();
		if ( ! $end instanceof \WC_DateTime || $end->getTimestamp() <= time() ) {
			return null;
		}
		return array(
			'label'     => __( 'Special offer', 'ultra-max-core' ),
			'countdown' => Countdown::render(
				array(
					'end'    => $end->getTimestamp(),
					'label'  => __( 'Time left for this offer', 'ultra-max-core' ),
					'tone'   => 'light',
					'size'   => 'sm',
					'digits' => Format::persian() ? 'fa' : 'en',
				)
			),
		);
	}

	/**
	 * The buy box (PDP-05).
	 *
	 * @param bool $sticky Whether it stays in view on desktop.
	 */
	public static function buy_box( bool $sticky = true ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$stock = Purchase::stock( $product );
		$props = array(
			'id'     => 'um-buy-box-' . $product->get_id(),
			'price'  => self::price(),
			'form'   => self::form(),
			'rows'   => self::rows( $product ),
			'stock'  => array(
				'product' => $product->get_id(),
				'text'    => $stock['text'],
				'tone'    => $stock['tone'],
			),
			'sticky' => $sticky,
		);
		$deal  = self::deal( $product );
		if ( null !== $deal ) {
			$props['deal'] = $deal;
		}
		/**
		 * Filters pieces under the buy box's form, such as the free shipping bar.
		 *
		 * @param array       $pieces  Pieces.
		 * @param \WC_Product $product Product.
		 * @phpstan-param list<Html> $pieces
		 */
		$after = apply_filters( 'ultra_max_buy_box_after', array(), $product );
		if ( is_array( $after ) ) {
			$props['after'] = array_values( array_filter( $after, static fn ( mixed $piece ): bool => $piece instanceof Html ) );
		}
		return BuyBox::render( $props );
	}

	/**
	 * The store's promises (design/pdp.html).
	 */
	public static function perks(): Html {
		$items = array();
		foreach ( Store::rows( 'product_perks' ) as $row ) {
			$label = is_string( $row['label'] ?? null ) ? trim( $row['label'] ) : '';
			$icon  = is_string( $row['icon'] ?? null ) && '' !== $row['icon'] ? $row['icon'] : 'check';
			if ( '' !== $label ) {
				$items[] = array(
					'icon'  => $icon,
					'label' => $label,
				);
			}
		}
		return ServicePerks::render(
			array(
				'label' => __( 'Our promises', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}

	/**
	 * The introduction: the product's description, folded to a few lines.
	 */
	public static function description(): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$post = get_post( $product->get_id() );
		if ( ! $post instanceof \WP_Post || '' === trim( $post->post_content ) ) {
			return Html::none();
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own content filter.
		$content = (string) apply_filters( 'the_content', $post->post_content );
		return SeoText::render(
			array(
				'id'       => 'um-product-intro-' . $product->get_id(),
				'children' => Html::wp( $content ),
				'more'     => __( 'Read more', 'ultra-max-core' ),
				'less'     => __( 'Show less', 'ultra-max-core' ),
				'variant'  => 'plain',
			)
		);
	}

	/**
	 * The specification table (PDP-06).
	 */
	public static function specs(): Html {
		$product = self::product();
		return null === $product ? Html::none() : SpecTable::render( array( 'groups' => Specs::groups( $product ) ) );
	}

	/**
	 * WooCommerce's product tabs that other plugins and the store add, as
	 * sections (PDP-14). WooCommerce's own description, attributes and
	 * reviews tabs are drawn by us.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{id: string, title: string, content: Html}>
	 */
	public static function tabs( \WC_Product $product ): array {
		if ( ! is_singular( 'product' ) || get_queried_object_id() !== $product->get_id() ) {
			// Tab callbacks print the product of the main query.
			return array();
		}
		$tabs = apply_filters( 'woocommerce_product_tabs', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		$out  = array();
		foreach ( is_array( $tabs ) ? $tabs : array() as $key => $tab ) {
			if ( ! is_string( $key ) || in_array( $key, array( 'description', 'additional_information', 'reviews' ), true ) || ! is_array( $tab ) || ! is_callable( $tab['callback'] ?? null ) ) {
				continue;
			}
			ob_start();
			call_user_func( $tab['callback'], $key, $tab );
			$content = trim( (string) ob_get_clean() );
			if ( '' === $content ) {
				continue;
			}
			$title = is_string( $tab['title'] ?? null ) ? wp_strip_all_tags( $tab['title'] ) : $key;
			$out[] = array(
				'id'      => 'tab-' . sanitize_html_class( $key ),
				'title'   => $title,
				'content' => Html::wp( $content ),
			);
		}
		return $out;
	}

	/**
	 * The sections with their sticky links (PDP-06 to PDP-10, PDP-14).
	 *
	 * @param Html   $aside   Beside the sections on desktop.
	 * @param string $variant sections, tabs or accordion; the setting when empty.
	 */
	public static function sections( ?Html $aside = null, string $variant = '' ): Html {
		$product = self::product();
		if ( null === $product ) {
			return Html::none();
		}
		$items = array(
			array(
				'id'      => 'intro',
				'title'   => __( 'Introduction', 'ultra-max-core' ),
				'content' => self::description(),
			),
			array(
				'id'      => 'specs',
				'title'   => __( 'Specifications', 'ultra-max-core' ),
				'content' => self::specs(),
			),
		);
		foreach ( self::tabs( $product ) as $tab ) {
			$items[] = $tab;
		}
		/**
		 * Filters the sections of a product page, such as reviews and questions.
		 *
		 * @param array                                                                 $items   Sections.
		 * @param \WC_Product                                                           $product Product.
		 * @phpstan-param list<array{id: string, title: string, content: Html, count?: string}> $items
		 */
		$filtered = apply_filters( 'ultra_max_product_sections', $items, $product );
		$clean    = array();
		foreach ( is_array( $filtered ) ? $filtered : array() as $item ) {
			if ( is_array( $item ) && is_string( $item['id'] ?? null ) && is_string( $item['title'] ?? null ) && ( $item['content'] ?? null ) instanceof Html ) {
				$section = array(
					'id'      => $item['id'],
					'title'   => $item['title'],
					'content' => $item['content'],
				);
				if ( is_string( $item['count'] ?? null ) ) {
					$section['count'] = $item['count'];
				}
				$clean[] = $section;
			}
		}
		$variant = '' === $variant ? Store::string( 'product_sections' ) : $variant;
		$props   = array(
			'id'      => 'um-product-sections-' . $product->get_id(),
			'label'   => __( 'Parts of this page', 'ultra-max-core' ),
			'items'   => $clean,
			'variant' => in_array( $variant, ProductSections::VARIANTS, true ) ? $variant : 'sections',
		);
		if ( null !== $aside ) {
			$props['aside'] = $aside;
		}
		return ProductSections::render( $props );
	}

	/**
	 * The buy button that stays in reach (PDP-20).
	 *
	 * @param 'bar'|'card' $variant Phone bar or desktop card.
	 */
	public static function sticky( string $variant = 'bar' ): Html {
		$product = self::product();
		if ( null === $product || null === Purchase::form( $product, false ) ) {
			return Html::none();
		}
		$open  = $product->is_purchasable() && $product->is_in_stock();
		$props = array(
			'form'     => Purchase::form_id( $product ),
			'product'  => $product->get_id(),
			'price'    => ProductPrice::render( Purchase::price( $product, 'card' === $variant ? 'md' : 'sm' ) ),
			'label'    => $open ? __( 'Add to cart', 'ultra-max-core' ) : __( 'Out of stock', 'ultra-max-core' ),
			'variant'  => $variant,
			'disabled' => ! $open,
			'watch'    => 'um-buy-box-' . $product->get_id(),
		);
		if ( 'card' === $variant ) {
			$props['title'] = wp_strip_all_tags( $product->get_name() );
			$props['image'] = Image::render(
				array(
					'id'   => (int) $product->get_image_id(),
					'size' => 'woocommerce_gallery_thumbnail',
					'alt'  => '',
				)
			);
			if ( $product instanceof \WC_Product_Variable ) {
				$default                = Purchase::default_variation( $product );
				$props['summary_empty'] = __( 'Choose the options first', 'ultra-max-core' );
				$props['summary']       = '';
				if ( null !== $default ) {
					foreach ( Purchase::variation_data( $product ) as $data ) {
						if ( $data['id'] === $default->get_id() ) {
							$props['summary'] = $data['summary'];
						}
					}
				}
			}
		}
		return StickyBuy::render( $props );
	}

	/**
	 * A slider of products under a section title.
	 *
	 * @param string $title    Title.
	 * @param array  $products Products.
	 * @param string $design   Card design.
	 * @phpstan-param list<\WC_Product> $products
	 */
	private static function slider_section( string $title, array $products, string $design = 'hover' ): Html {
		$slider = Related::slider( $products, $title, $design );
		if ( $slider->is_empty() ) {
			return Html::none();
		}
		return Box::render(
			array(
				'tag'      => 'section',
				'padding'  => 'md',
				'children' => array( SectionHead::render( array( 'title' => $title ) ), $slider ),
			)
		);
	}

	/**
	 * Similar products (PDP-23).
	 *
	 * @param int    $count  Most to show; the setting when 0.
	 * @param string $design Card design.
	 */
	public static function similar( int $count = 0, string $design = 'hover' ): Html {
		$product = self::product();
		if ( null === $product || ( 0 === $count && ! Store::bool( 'product_similar' ) ) ) {
			return Html::none();
		}
		return self::slider_section( __( 'Similar products', 'ultra-max-core' ), Related::similar( $product, $count > 0 ? $count : max( 4, Store::int( 'product_related_count' ) ) ), $design );
	}

	/**
	 * Complementary products (PDP-23).
	 *
	 * @param int    $count  Most to show; the setting when 0.
	 * @param string $design Card design.
	 */
	public static function complementary( int $count = 0, string $design = 'hover' ): Html {
		$product = self::product();
		if ( null === $product || ( 0 === $count && ! Store::bool( 'product_similar' ) ) ) {
			return Html::none();
		}
		return self::slider_section( __( 'Goes well with', 'ultra-max-core' ), Related::complementary( $product, $count > 0 ? $count : max( 4, Store::int( 'product_related_count' ) ) ), $design );
	}

	/**
	 * Recently viewed products, filled in the browser (PDP-23). With the
	 * setting off, the page still remembers the product for other pages.
	 *
	 * @param bool $show Whether to show the list.
	 */
	public static function recent( bool $show = true ): Html {
		$product = self::product();
		$props   = array(
			'title'    => __( 'Your recent views', 'ultra-max-core' ),
			'endpoint' => $show && Store::bool( 'product_recent' ) ? Related::endpoint() : '',
		);
		if ( null !== $product && is_singular( 'product' ) ) {
			$props['record'] = $product->get_id();
		}
		return RecentProducts::render( $props );
	}

	/**
	 * The share window (PDP-24).
	 */
	public static function share_dialog(): Html {
		$product = self::product();
		if ( null === $product || ! Store::bool( 'product_share' ) ) {
			return Html::none();
		}
		return Dialog::render(
			array(
				'id'       => self::SHARE,
				'title'    => __( 'Share', 'ultra-max-core' ),
				'size'     => 'sm',
				'children' => ShareLinks::render(
					array(
						'url'    => (string) $product->get_permalink(),
						'title'  => wp_strip_all_tags( $product->get_name() ),
						'intro'  => __( 'Send this product to a friend.', 'ultra-max-core' ),
						'labels' => array(
							'copy'     => __( 'Copy link', 'ultra-max-core' ),
							'copied'   => __( 'Link copied', 'ultra-max-core' ),
							'whatsapp' => __( 'WhatsApp', 'ultra-max-core' ),
							'telegram' => __( 'Telegram', 'ultra-max-core' ),
							'native'   => __( 'More ways to share', 'ultra-max-core' ),
						),
					)
				),
			)
		);
	}

	/**
	 * Card props badges reused for the heading, so cards and the page agree.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<array{label: string, tone: 'hot'|'new'|'brand'|'neutral', icon?: string}>
	 */
	public static function badges( \WC_Product $product ): array {
		return Cards::badges( $product );
	}
}
