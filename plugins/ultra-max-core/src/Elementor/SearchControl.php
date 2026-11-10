<?php
/**
 * An Elementor control that finds posts or terms by typing.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

/**
 * A multiple choice list whose options come from the server as the editor
 * types, so a store with thousands of products never loads them all. The
 * editor script (assets/src/elementor/editor.ts) gives it its behaviour.
 * Saved value: a list of ids as strings.
 */
final class SearchControl extends \Elementor\Base_Data_Control {

	public const TYPE = 'um-search';

	/**
	 * Control type.
	 *
	 * @return string
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_default_settings() {
		return array(
			'source'      => 'product',
			'multiple'    => true,
			'label_block' => true,
		);
	}

	/**
	 * Markup template of the control.
	 *
	 * @return void
	 */
	public function content_template() {
		?>
		<div class="elementor-control-field">
			<# if ( data.label ) { #>
			<label for="<?php $this->print_control_uid(); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<div class="elementor-control-input-wrapper elementor-control-unit-5">
				<# var multiple = data.multiple ? 'multiple' : ''; #>
				<select id="<?php $this->print_control_uid(); ?>" class="elementor-select2" type="select2" {{ multiple }} data-setting="{{ data.name }}" data-source="{{ data.source }}"></select>
			</div>
		</div>
		<# if ( data.description ) { #>
		<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
