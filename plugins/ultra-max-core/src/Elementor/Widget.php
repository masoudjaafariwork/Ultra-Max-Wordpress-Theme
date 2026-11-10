<?php
/**
 * The one Elementor widget class behind every Ultra Max widget.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

use UltraMax\Core\Widgets\Widget as Definition;
use UltraMax\Core\Widgets\Widgets;

/**
 * Elementor creates widgets from a registered instance and passes that
 * instance's arguments to every copy it makes, the same way it shows
 * WordPress widgets. So one class serves all our widgets: the argument
 * `um_widget` names the definition, and every method asks it.
 */
final class Widget extends \Elementor\Widget_Base {

	/**
	 * The widget definition.
	 *
	 * @var Definition|null
	 */
	private ?Definition $definition = null;

	/**
	 * Creates the widget.
	 *
	 * @param array<mixed>      $data Element data.
	 * @param array<mixed>|null $args Arguments; `um_widget` is the definition id.
	 */
	public function __construct( $data = array(), $args = null ) {
		$id               = is_array( $args ) && is_string( $args['um_widget'] ?? null ) ? $args['um_widget'] : '';
		$this->definition = Widgets::get( $id );
		parent::__construct( $data, $args );
	}

	/**
	 * The definition.
	 *
	 * @throws \LogicException When the widget was created without one.
	 */
	private function definition(): Definition {
		if ( null === $this->definition ) {
			throw new \LogicException( 'Ultra Max widget without a definition.' );
		}
		return $this->definition;
	}

	/**
	 * Elementor name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'um-' . $this->definition()->id();
	}

	/**
	 * Panel title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->definition()->title();
	}

	/**
	 * Panel icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return $this->definition()->icon();
	}

	/**
	 * Panel group.
	 *
	 * @return list<string>
	 */
	public function get_categories() {
		return array( Integration::CATEGORY_PREFIX . $this->definition()->group() );
	}

	/**
	 * Search words.
	 *
	 * @return list<string>
	 */
	public function get_keywords() {
		return array_merge( array( 'ultra', 'um' ), $this->definition()->keywords() );
	}

	/**
	 * Styles, loaded only where the widget is used (BLD-12).
	 *
	 * @return list<string>
	 */
	public function get_style_depends(): array {
		return Widgets::handles( $this->definition() );
	}

	/**
	 * Scripts, loaded only where the widget is used (BLD-12).
	 *
	 * @return list<string>
	 */
	public function get_script_depends(): array {
		return Widgets::handles( $this->definition() );
	}

	/**
	 * Our markup needs no extra wrapper.
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Store data such as prices and stock changes between visits, so
	 * Elementor's element cache must not keep it.
	 */
	protected function is_dynamic_content(): bool {
		return 'store' === $this->definition()->group() || 'header' === $this->definition()->group();
	}

	/**
	 * Adds the controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		Controls::add( $this, $this->definition() );
	}

	/**
	 * Prints the widget.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$raw      = array();
		foreach ( is_array( $settings ) ? $settings : array() as $key => $value ) {
			if ( is_string( $key ) ) {
				$raw[ $key ] = $value;
			}
		}
		$this->definition()->output( $raw )->print();
	}
}
