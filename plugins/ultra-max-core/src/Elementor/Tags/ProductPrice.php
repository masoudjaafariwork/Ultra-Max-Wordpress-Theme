<?php
/**
 * Dynamic tag: The price of a product, with the currency (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor\Tags;

use UltraMax\Core\Elementor\DynamicTags;

/**
 * The price of a product, with the currency. The value comes from DynamicTags::price().
 */
final class ProductPrice extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-product-price';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product price', 'ultra-max-core' );
	}

	/**
	 * Tag group.
	 *
	 * @return string
	 */
	public function get_group() {
		return DynamicTags::GROUP;
	}

	/**
	 * Controls that accept it.
	 *
	 * @return list<string>
	 */
	public function get_categories() {
		return array( 'text', 'number' );
	}

	/**
	 * A setting as text.
	 *
	 * @param string $key Setting.
	 */
	private function text( string $key ): string {
		$value = $this->get_settings( $key );
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Options of the tag.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'product',
			array(
				'label'       => __( 'Product', 'ultra-max-core' ),
				'type'        => 'um-search',
				'source'      => 'product',
				'multiple'    => false,
				'description' => __( 'Leave empty to use the product of the page.', 'ultra-max-core' ),
			)
		);      $this->add_control(
			'kind',
			array(
				'label'   => __( 'Price', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'current',
				'options' => array(
					'current' => __( 'Current price', 'ultra-max-core' ),
					'regular' => __( 'Regular price', 'ultra-max-core' ),
					'sale'    => __( 'Sale price', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * Prints the value.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( DynamicTags::price( (int) $this->text( 'product' ), $this->text( 'kind' ) ) );
	}
}
