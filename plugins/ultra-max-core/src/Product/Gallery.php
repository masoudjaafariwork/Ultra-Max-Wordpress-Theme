<?php
/**
 * The pictures and videos of a product (PDP-01, PDP-02, PDP-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductGallery;

/**
 * Turns a product's image, gallery and video addresses into gallery items,
 * and a variation's own image and gallery into a set the gallery swaps in.
 *
 * Videos are addresses in the product's video field, one per line: a
 * YouTube or Aparat page, or a video file. They play from the provider only
 * when the shopper starts them; YouTube uses its privacy-enhanced address.
 *
 * @phpstan-import-type GalleryItem from ProductGallery
 */
final class Gallery {

	/**
	 * Gallery items of a product.
	 *
	 * @param \WC_Product $product Product.
	 * @return list<GalleryItem>
	 */
	public static function items( \WC_Product $product ): array {
		$ids   = array_values( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) );
		$items = self::images( $product, $ids );
		foreach ( self::videos( Meta::text( $product, Meta::VIDEOS ) ) as $video ) {
			$items[] = array(
				'type'     => 'video',
				'image'    => $items[0]['image'] ?? Html::none(),
				'thumb'    => $items[0]['thumb'] ?? Html::none(),
				/* translators: %s: product name. */
				'label'    => sprintf( __( 'Video of %s', 'ultra-max-core' ), wp_strip_all_tags( $product->get_name() ) ),
				'video'    => $video['src'],
				'provider' => $video['provider'],
			);
		}
		return $items;
	}

	/**
	 * Items of a list of attachments; the product placeholder when none.
	 *
	 * @param \WC_Product $product Product.
	 * @param array       $ids     Attachment ids, the first loads first.
	 * @param bool        $first   Whether these are the first pictures of the page.
	 * @phpstan-param list<int> $ids
	 * @return list<GalleryItem>
	 */
	public static function images( \WC_Product $product, array $ids, bool $first = true ): array {
		$ids   = array_values( array_unique( array_filter( $ids ) ) );
		$name  = wp_strip_all_tags( $product->get_name() );
		$items = array();
		if ( array() !== $ids ) {
			_prime_post_caches( $ids );
		}
		foreach ( $ids as $index => $id ) {
			$alt   = get_post_meta( $id, '_wp_attachment_image_alt', true );
			$alt   = is_string( $alt ) && '' !== trim( $alt ) ? trim( $alt ) : $name;
			$eager = $first && 0 === $index;
			$main  = wp_get_attachment_image(
				$id,
				'woocommerce_single',
				false,
				array(
					'alt'           => $alt,
					'loading'       => $eager ? 'eager' : 'lazy',
					'fetchpriority' => $eager ? 'high' : 'auto',
					'decoding'      => 'async',
					'sizes'         => '(max-width: 767px) 100vw, (max-width: 1023px) 50vw, 520px',
				)
			);
			if ( '' === $main ) {
				continue;
			}
			$full    = wp_get_attachment_image_url( $id, 'full' );
			$items[] = array(
				'type'  => 'image',
				'image' => Html::wp( $main ),
				'thumb' => Html::wp(
					wp_get_attachment_image(
						$id,
						'woocommerce_gallery_thumbnail',
						false,
						array(
							'alt'     => '',
							'loading' => 'lazy',
						)
					)
				),
				/* translators: 1: picture number, 2: product name. */
				'label' => 0 === $index ? $alt : sprintf( __( 'Picture %1$s of %2$s', 'ultra-max-core' ), number_format_i18n( $index + 1 ), $name ),
				'full'  => is_string( $full ) ? $full : '',
			);
		}
		if ( array() === $items ) {
			$placeholder = wc_placeholder_img( 'woocommerce_single', array( 'alt' => $name ) );
			$items[]     = array(
				'type'  => 'image',
				'image' => Html::wp( $placeholder ),
				'thumb' => Html::wp( $placeholder ),
				'label' => $name,
			);
		}
		return $items;
	}

	/**
	 * Parses video addresses: YouTube, Aparat or a video file.
	 *
	 * @param string $text One address per line.
	 * @return list<array{provider: 'youtube'|'aparat'|'file', src: string}>
	 */
	public static function videos( string $text ): array {
		$out   = array();
		$lines = preg_split( '/\R/', $text );
		foreach ( false === $lines ? array() : $lines as $line ) {
			$url = esc_url_raw( trim( $line ) );
			if ( '' === $url ) {
				continue;
			}
			if ( 1 === preg_match( '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,15})~', $url, $match ) ) {
				$out[] = array(
					'provider' => 'youtube',
					'src'      => 'https://www.youtube-nocookie.com/embed/' . $match[1] . '?rel=0',
				);
			} elseif ( 1 === preg_match( '~aparat\.com/(?:v/|video/video/embed/videohash/)([A-Za-z0-9]{3,20})~', $url, $match ) ) {
				$out[] = array(
					'provider' => 'aparat',
					'src'      => 'https://www.aparat.com/video/video/embed/videohash/' . $match[1] . '/vt/frame',
				);
			} elseif ( 1 === preg_match( '~\.(mp4|webm|ogv)(\?.*)?$~i', $url ) ) {
				$out[] = array(
					'provider' => 'file',
					'src'      => $url,
				);
			}
		}
		return $out;
	}

	/**
	 * Attachment ids of a variation's own gallery: its image, then its extra
	 * pictures, then the product's gallery when it has no extra pictures.
	 * Empty when the variation shows the product's pictures.
	 *
	 * @param \WC_Product           $product   Variable product.
	 * @param \WC_Product_Variation $variation Variation.
	 * @return list<int>
	 */
	public static function variation_ids( \WC_Product $product, \WC_Product_Variation $variation ): array {
		$image = (int) $variation->get_image_id( 'edit' );
		$extra = Meta::ids( $variation, Meta::GALLERY );
		if ( 0 === $image && array() === $extra ) {
			return array();
		}
		$own = array_merge( array( 0 === $image ? (int) $product->get_image_id() : $image ), array() === $extra ? array_map( 'intval', $product->get_gallery_image_ids() ) : $extra );
		$own = array_values( array_unique( array_filter( $own ) ) );
		$all = array_values( array_unique( array_filter( array_merge( array( (int) $product->get_image_id() ), array_map( 'intval', $product->get_gallery_image_ids() ) ) ) ) );
		return $own === $all ? array() : $own;
	}

	/**
	 * The key of a set of pictures.
	 *
	 * @param array $ids Attachment ids.
	 * @phpstan-param list<int> $ids
	 */
	public static function key( array $ids ): string {
		return array() === $ids ? '' : 'g' . substr( md5( implode( ',', $ids ) ), 0, 10 );
	}
}
