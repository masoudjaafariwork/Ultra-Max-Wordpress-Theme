<?php
/**
 * Sales counter widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Product\Sales;
use UltraMax\Core\Widgets\Values;

/**
 * How many were sold in the last days, from real orders.
 */
final class ProductSalesCount extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-sales-count';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Sales counter', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-counter';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-17' );
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$rows = Sales::row( array(), $product );
		$row  = is_array( $rows ) && isset( $rows[0] ) && is_array( $rows[0] ) && is_string( $rows[0]['text'] ?? null ) ? $rows[0]['text'] : '';
		return '' === $row ? Html::none() : Html::from( array( Icon::render( 'fire', 'size-5' ), Html::text( ' ' . $row ) ) );
	}
}
