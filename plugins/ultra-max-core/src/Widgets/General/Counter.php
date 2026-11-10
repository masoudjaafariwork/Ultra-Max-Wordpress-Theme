<?php
/**
 * Counter widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Counter as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * A large number that counts up when it comes into view, with a label.
 */
final class Counter extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'counter';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Counter', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-counter';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'counter' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Counter', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'value',
					'type'    => 'number',
					'label'   => __( 'Number', 'ultra-max-core' ),
					'default' => 12000,
					'min'     => 0,
				),
				array(
					'id'      => 'label',
					'type'    => 'text',
					'label'   => __( 'Label', 'ultra-max-core' ),
					'default' => __( 'Happy customers', 'ultra-max-core' ),
				),
				array(
					'id'    => 'prefix',
					'type'  => 'text',
					'label' => __( 'Before the number', 'ultra-max-core' ),
				),
				array(
					'id'      => 'suffix',
					'type'    => 'text',
					'label'   => __( 'After the number', 'ultra-max-core' ),
					'default' => '+',
				),
				array(
					'id'    => 'icon',
					'type'  => 'icon',
					'label' => __( 'Icon', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Component::render(
			array(
				'value'  => max( 0, $settings->int( 'value' ) ),
				'label'  => $settings->string( 'label' ),
				'prefix' => $settings->string( 'prefix' ),
				'suffix' => $settings->string( 'suffix' ),
				'icon'   => $settings->string( 'icon' ),
				'digits' => Format::persian() ? 'fa' : 'en',
			)
		);
	}
}
