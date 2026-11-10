<?php
/**
 * Base of every Ultra Max widget.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets;

use UltraMax\Core\Lib\UI\Html;

/**
 * One widget, defined once and shown three ways (roadmap, section 4,
 * "one renderer per feature"):
 *
 * - in Elementor, through the generic adapter in `Elementor\Widget`;
 * - as a shortcode, `[um_<id>]`, through `Shortcodes`;
 * - from PHP, by calling `render()` with settings.
 *
 * A widget declares its controls in our own small schema, which the
 * adapters translate, and renders by calling component APIs only. It never
 * touches Elementor, so it also works when Elementor is off.
 *
 * @phpstan-type Field array{
 *     id: string,
 *     type: 'text'|'textarea'|'wysiwyg'|'url'|'media'|'select'|'switch'|'number'|'color'|'icon'|'repeater'|'posts'|'terms'|'date'|'code'|'heading',
 *     label: string,
 *     default?: mixed,
 *     options?: array<string, string>,
 *     fields?: list<array<string, mixed>>,
 *     title_field?: string,
 *     post_type?: string,
 *     taxonomy?: string,
 *     multiple?: bool,
 *     min?: int|float,
 *     max?: int|float,
 *     step?: int|float,
 *     help?: string,
 *     placeholder?: string,
 *     condition?: array<string, mixed>,
 *     rows?: int,
 * }
 * @phpstan-type Section array{id: string, label: string, fields: list<array<string, mixed>>}
 *
 * Fields follow the Field shape; they are checked when read, so a widget may build them with helpers.
 */
abstract class Widget {

	/**
	 * Id, such as "hero-slider". Elementor shows it as "um-hero-slider";
	 * the shortcode is `[um_hero_slider]`.
	 */
	abstract public function id(): string;

	/**
	 * Name shown in the Elementor panel.
	 */
	abstract public function title(): string;

	/**
	 * Panel group: header, store, general or footer.
	 */
	abstract public function group(): string;

	/**
	 * Sections of controls.
	 *
	 * @return list<Section>
	 */
	abstract public function controls(): array;

	/**
	 * Renders the widget.
	 *
	 * @param Values $settings Settings, already normalized and typed.
	 */
	abstract public function render( Values $settings ): Html;

	/**
	 * Elementor icon class.
	 */
	public function icon(): string {
		return 'eicon-apps';
	}

	/**
	 * Feature ids this widget delivers.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array();
	}

	/**
	 * Search words for the Elementor panel.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array();
	}

	/**
	 * Widget assets of the component library this widget needs, loaded
	 * only on pages that show it (BLD-12).
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array();
	}

	/**
	 * Renders from raw settings, as Elementor or a shortcode stores them.
	 *
	 * @param array<string, mixed> $raw Raw settings.
	 */
	public function output( array $raw ): Html {
		return $this->render( new Values( Settings::normalize( $this->controls(), $raw ) ) );
	}

	/**
	 * Default value of every control.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return Settings::normalize( $this->controls(), array() );
	}
}
