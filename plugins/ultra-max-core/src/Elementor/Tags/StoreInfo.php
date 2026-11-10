<?php
/**
 * Dynamic tag: A detail of the store, such as its name or address (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor\Tags;

use UltraMax\Core\Elementor\DynamicTags;

/**
 * A detail of the store, such as its name or address. The value comes from DynamicTags::store().
 */
final class StoreInfo extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-store-info';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Store details', 'ultra-max-core' );
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
			'what',
			array(
				'label'   => __( 'Detail', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'name',
				'options' => array(
					'name'    => __( 'Store name', 'ultra-max-core' ),
					'tagline' => __( 'Tagline', 'ultra-max-core' ),
					'email'   => __( 'Email', 'ultra-max-core' ),
					'address' => __( 'Address', 'ultra-max-core' ),
					'city'    => __( 'City', 'ultra-max-core' ),
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
		echo esc_html( DynamicTags::store( $this->text( 'what' ) ) );
	}
}
