<?php
/**
 * Dynamic tag: A date in the Jalali calendar (BLD-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor\Tags;

use UltraMax\Core\Elementor\DynamicTags;

/**
 * A date in the Jalali calendar. The value comes from DynamicTags::date().
 */
final class JalaliDate extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-jalali-date';
	}

	/**
	 * Tag title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Jalali date', 'ultra-max-core' );
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
			'which',
			array(
				'label'   => __( 'Date', 'ultra-max-core' ),
				'type'    => 'select',
				'default' => 'today',
				'options' => array(
					'today'     => __( 'Today', 'ultra-max-core' ),
					'published' => __( 'Publish date of the post', 'ultra-max-core' ),
					'modified'  => __( 'Last update of the post', 'ultra-max-core' ),
				),
			)
		);
		$this->add_control(
			'format',
			array(
				'label'       => __( 'Format', 'ultra-max-core' ),
				'type'        => 'text',
				'default'     => 'j F Y',
				'description' => __( 'For example j F Y shows 19 Aban 1405.', 'ultra-max-core' ),
			)
		);
	}

	/**
	 * Prints the value.
	 *
	 * @return void
	 */
	public function render() {
		echo esc_html( DynamicTags::date( $this->text( 'which' ), $this->text( 'format' ) ) );
	}
}
