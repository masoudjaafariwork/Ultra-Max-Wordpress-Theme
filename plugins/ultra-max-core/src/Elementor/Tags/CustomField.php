<?php
/**
 * Dynamic tag: A custom field of the current post (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor\Tags;

use UltraMax\Core\Elementor\DynamicTags;

/**
 * A custom field of the current post. The value comes from DynamicTags::field().
 */
final class CustomField extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-custom-field';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Custom field', 'ultra-max-core' );
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
			'key',
			array(
				'label' => __( 'Field name', 'ultra-max-core' ),
				'type'  => 'text',
			)
		);
	}

	/**
	 * Prints the value.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( DynamicTags::field( $this->text( 'key' ) ) );
	}
}
