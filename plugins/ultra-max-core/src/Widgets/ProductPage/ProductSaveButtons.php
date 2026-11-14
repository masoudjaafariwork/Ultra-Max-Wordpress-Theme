<?php
/**
 * Wishlist and compare buttons widget (BLD-04, SAVE-01, SAVE-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Saved\Buttons;
use UltraMax\Core\Saved\Lists;
use UltraMax\Core\Saved\Saved;
use UltraMax\Core\Widgets\Values;

/**
 * The product's wishlist and compare buttons, for a product page designed
 * with Elementor. Shows only the buttons whose modules are on.
 */
final class ProductSaveButtons extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-save-buttons';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Wishlist and compare buttons', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-heart-o';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'SAVE-01', 'SAVE-02' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'saved' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			$this->toggle( 'wishlist', __( 'Wishlist', 'ultra-max-core' ) ),
			$this->toggle( 'compare', __( 'Compare', 'ultra-max-core' ) ),
			$this->choice(
				'look',
				__( 'Look', 'ultra-max-core' ),
				array(
					'button' => __( 'Buttons with text', 'ultra-max-core' ),
					'tool'   => __( 'Round icons', 'ultra-max-core' ),
				),
				'button'
			),
		);
	}

	/**
	 * Renders when the product page module or a list module is on.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$product = \UltraMax\Core\Product\Page::product();
		return null !== $product ? $this->piece( $product, $settings ) : Html::none();
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$look    = 'tool' === $settings->string( 'look' ) ? 'tool' : 'button';
		$buttons = array();
		if ( $settings->bool( 'wishlist' ) && Saved::enabled( Saved::WISHLIST ) ) {
			$buttons[] = Buttons::button( Lists::WISHLIST, $product->get_id(), $look );
		}
		if ( $settings->bool( 'compare' ) && Saved::enabled( Saved::COMPARE ) ) {
			$buttons[] = Buttons::button( Lists::COMPARE, $product->get_id(), $look );
		}
		return array() === $buttons ? Html::none() : Html::trusted( sprintf( '<div class="flex flex-wrap items-center gap-2">%s</div>', Html::from( $buttons ) ) );
	}
}
