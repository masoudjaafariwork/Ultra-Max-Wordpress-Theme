<?php
/**
 * Short description widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Prose;
use UltraMax\Core\Widgets\Values;

/**
 * The short description.
 */
final class ProductShortDescription extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-short-description';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Short description', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-description';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
		$text = (string) apply_filters( 'woocommerce_short_description', $product->get_short_description() );
		return '' === trim( $text ) ? Html::none() : Prose::render( array( 'children' => Html::wp( $text ) ) );
	}
}
