<?php
/**
 * Translates our widget control schema into Elementor controls.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Elementor;

use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Widgets\Widget as Definition;

/**
 * Text, link and image controls accept Elementor's dynamic tags (BLD-10).
 * Posts and terms use our search control, which loads its choices from the
 * server only in the editor, so a page view never queries them.
 *
 * @phpstan-import-type Field from Definition
 */
final class Controls {

	/**
	 * Adds every section and control of a widget.
	 *
	 * @param \Elementor\Controls_Stack $stack      Widget.
	 * @param Definition                $definition Widget definition.
	 */
	public static function add( \Elementor\Controls_Stack $stack, Definition $definition ): void {
		foreach ( $definition->controls() as $section ) {
			$stack->start_controls_section(
				$section['id'],
				array(
					'label' => $section['label'],
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);
			foreach ( $section['fields'] as $field ) {
				if ( is_string( $field['id'] ?? null ) ) {
					$stack->add_control( $field['id'], self::control( $field ) );
				}
			}
			$stack->end_controls_section();
		}
	}

	/**
	 * One control.
	 *
	 * @param array $field Field.
	 * @phpstan-param Field|array<string, mixed> $field
	 * @return array<string, mixed>
	 */
	public static function control( array $field ): array {
		$type    = is_string( $field['type'] ?? null ) ? $field['type'] : 'text';
		$default = $field['default'] ?? null;
		$control = array( 'label' => $field['label'] ?? '' );
		if ( isset( $field['help'] ) ) {
			$control['description'] = $field['help'];
		}
		if ( isset( $field['condition'] ) && is_array( $field['condition'] ) ) {
			$control['condition'] = $field['condition'];
		}
		if ( isset( $field['placeholder'] ) ) {
			$control['placeholder'] = $field['placeholder'];
		}
		$dynamic = array( 'active' => true );
		switch ( $type ) {
			case 'heading':
				return array_merge(
					$control,
					array(
						'type'      => \Elementor\Controls_Manager::HEADING,
						'separator' => 'before',
					)
				);
			case 'textarea':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::TEXTAREA,
						'rows'    => $field['rows'] ?? 4,
						'default' => is_string( $default ) ? $default : '',
						'dynamic' => $dynamic,
					)
				);
			case 'wysiwyg':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::WYSIWYG,
						'default' => is_string( $default ) ? $default : '',
						'dynamic' => $dynamic,
					)
				);
			case 'code':
				return array_merge(
					$control,
					array(
						'type'     => \Elementor\Controls_Manager::CODE,
						'language' => 'html',
						'rows'     => $field['rows'] ?? 8,
						'default'  => is_string( $default ) ? $default : '',
					)
				);
			case 'url':
				return array_merge(
					$control,
					array(
						'type'        => \Elementor\Controls_Manager::URL,
						'default'     => array( 'url' => is_string( $default ) ? $default : '' ),
						'dynamic'     => $dynamic,
						'label_block' => true,
					)
				);
			case 'media':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::MEDIA,
						'default' => array(
							'id'  => 0,
							'url' => is_string( $default ) ? $default : '',
						),
						'dynamic' => $dynamic,
					)
				);
			case 'select':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::SELECT,
						'options' => $field['options'] ?? array(),
						'default' => is_scalar( $default ) ? (string) $default : (string) array_key_first( (array) ( $field['options'] ?? array() ) ),
					)
				);
			case 'icon':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::SELECT,
						'options' => self::icons(),
						'default' => is_string( $default ) ? $default : '',
					)
				);
			case 'switch':
				return array_merge(
					$control,
					array(
						'type'         => \Elementor\Controls_Manager::SWITCHER,
						'return_value' => 'yes',
						'default'      => true === $default ? 'yes' : '',
					)
				);
			case 'number':
				return array_merge(
					$control,
					array_filter(
						array(
							'type'    => \Elementor\Controls_Manager::NUMBER,
							'default' => is_numeric( $default ) ? $default : 0,
							'min'     => $field['min'] ?? null,
							'max'     => $field['max'] ?? null,
							'step'    => $field['step'] ?? null,
							'dynamic' => $dynamic,
						),
						static fn ( mixed $value ): bool => null !== $value
					)
				);
			case 'color':
				return array_merge(
					$control,
					array(
						'type'    => \Elementor\Controls_Manager::COLOR,
						'default' => is_string( $default ) ? $default : '',
					)
				);
			case 'date':
				return array_merge(
					$control,
					array(
						'type'           => \Elementor\Controls_Manager::DATE_TIME,
						'default'        => is_string( $default ) ? $default : '',
						'picker_options' => array( 'enableTime' => true ),
					)
				);
			case 'posts':
			case 'terms':
				return array_merge(
					$control,
					array(
						'type'        => SearchControl::TYPE,
						'source'      => 'posts' === $type ? ( is_string( $field['post_type'] ?? null ) ? $field['post_type'] : 'product' ) : 'tax:' . ( is_string( $field['taxonomy'] ?? null ) ? $field['taxonomy'] : 'product_cat' ),
						'multiple'    => $field['multiple'] ?? true,
						'label_block' => true,
						'default'     => array(),
					)
				);
			case 'repeater':
				$repeater = new \Elementor\Repeater();
				$fields   = is_array( $field['fields'] ?? null ) ? $field['fields'] : array();
				foreach ( $fields as $sub ) {
					if ( is_array( $sub ) && is_string( $sub['id'] ?? null ) ) {
						$repeater->add_control( $sub['id'], self::control( $sub ) );
					}
				}
				$title = is_string( $field['title_field'] ?? null ) ? $field['title_field'] : '';
				return array_merge(
					$control,
					array(
						'type'        => \Elementor\Controls_Manager::REPEATER,
						'fields'      => array_values( $repeater->get_controls() ),
						'default'     => is_array( $default ) ? $default : array(),
						'title_field' => '' === $title ? '' : '{{{ ' . $title . ' }}}',
					)
				);
			default:
				return array_merge(
					$control,
					array(
						'type'        => \Elementor\Controls_Manager::TEXT,
						'default'     => is_string( $default ) ? $default : '',
						'dynamic'     => $dynamic,
						'label_block' => true,
					)
				);
		}
	}

	/**
	 * Icon choices: our own icon set, so every icon matches the design.
	 *
	 * @return array<string, string>
	 */
	public static function icons(): array {
		$out = array( '' => __( 'None', 'ultra-max-core' ) );
		foreach ( Icon::names() as $name ) {
			$out[ $name ] = $name;
		}
		return $out;
	}
}
