<?php
/**
 * Shared parts of the product page widgets (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Product\Product;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * The widgets of a single product layout. Each one prints one piece of the
 * product page through the page's renderer (Product\Page), so a page
 * designed with Elementor behaves exactly like the built-in one. They show
 * the product of the page, or a sample product while a layout is designed.
 */
abstract class PageWidget extends Widget {

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
		return array( 'BLD-04' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'product', 'reviews', 'product-card', 'carousel', 'countdown' );
	}

	/**
	 * Controls: one section of the widget's fields.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => str_replace( '-', '_', $this->id() ) . '_section',
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
				'label' => __( 'Shows the product of the page.', 'ultra-max-core' ),
			),
		);
	}

	/**
	 * Renders when the product page module is on and there is a product.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$product = Page::product();
		return Product::enabled() && null !== $product ? $this->piece( $product, $settings ) : Html::none();
	}

	/**
	 * The piece of the page.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	abstract protected function piece( \WC_Product $product, Values $settings ): Html;

	/**
	 * A switch field.
	 *
	 * @param string $id      Id.
	 * @param string $label   Label.
	 * @param bool   $initial Default.
	 * @return array<string, mixed>
	 */
	protected function toggle( string $id, string $label, bool $initial = true ): array {
		return array(
			'id'      => $id,
			'type'    => 'switch',
			'label'   => $label,
			'default' => $initial,
		);
	}

	/**
	 * A select field.
	 *
	 * @param string                $id      Id.
	 * @param string                $label   Label.
	 * @param array<string, string> $options Options.
	 * @param string                $initial Default.
	 * @return array<string, mixed>
	 */
	protected function choice( string $id, string $label, array $options, string $initial ): array {
		return array(
			'id'      => $id,
			'type'    => 'select',
			'label'   => $label,
			'options' => $options,
			'default' => $initial,
		);
	}
}
