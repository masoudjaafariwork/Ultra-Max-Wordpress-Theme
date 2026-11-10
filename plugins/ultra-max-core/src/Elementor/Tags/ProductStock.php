<?php
/**
 * Dynamic tag: The stock of a product (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor\Tags;

use UltraMax\Core\Elementor\DynamicTags;

/**
 * The stock of a product. The value comes from DynamicTags::stock().
 */
final class ProductStock extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-product-stock';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product stock', 'ultra-max-core' );
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
			'format',
			array(
				'label'   => __( 'Show', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'label',
				'options' => array(
					'label'    => __( 'In stock or out of stock', 'ultra-max-core' ),
					'quantity' => __( 'Quantity left', 'ultra-max-core' ),
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
		echo esc_html( DynamicTags::stock( (int) $this->text( 'product' ), $this->text( 'format' ) ) );
	}
}
