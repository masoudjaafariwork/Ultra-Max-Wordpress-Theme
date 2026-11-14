<?php
/**
 * Shared parts of the product widgets (PLP-08).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Product;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ProductCard;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\CurrentProduct;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * Widgets that show one piece of the current product: the product of the
 * card a card layout draws (PLP-08), or of a single product page. While a
 * layout is designed they show a sample product.
 *
 * Each piece is the same markup as inside the built-in card designs.
 */
abstract class ProductWidget extends Widget {

	/**
	 * The card piece this widget shows, one of ProductCard::PARTS.
	 */
	abstract protected function part(): string;

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'product';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'PLP-08' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'product-card' );
	}

	/**
	 * Controls: none beyond a note, unless a widget adds its own.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'product_section',
				'label'  => $this->title(),
				'fields' => $this->fields(),
			),
		);
	}

	/**
	 * Fields of the widget.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'    => 'note',
				'type'  => 'heading',
				'label' => __( 'Shows the product of the card or page.', 'ultra-max-core' ),
			),
		);
	}

	/**
	 * Options of the piece from the settings.
	 *
	 * @param Values $settings Settings.
	 * @return array{tag?: 'h2'|'h3'|'h4'|'p', hover?: bool}
	 */
	protected function options( Values $settings ): array {
		return array();
	}

	/**
	 * Renders the piece of the current product.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$product = CurrentProduct::get();
		if ( null === $product ) {
			return Html::none();
		}
		$options = $this->options( $settings );
		$variant = ( $options['hover'] ?? false ) ? 'hover' : 'classic';
		$props   = Cards::props( $product, $variant );
		if ( 'brand' === $this->part() ) {
			$props['brand'] = Cards::brand( $product );
		}
		return ProductCard::part( $this->part(), $props, $options );
	}
}
