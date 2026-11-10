<?php
/**
 * Countdown widget (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Countdown as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * Time left until a moment, such as the end of a sale, with a title. Only
 * the time left is shown, never the date itself.
 */
final class Countdown extends GeneralWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'countdown';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Countdown', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-countdown';
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'countdown' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->one(
			__( 'Countdown', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'title',
					'type'    => 'text',
					'label'   => __( 'Title', 'ultra-max-core' ),
					'default' => __( 'The sale ends in', 'ultra-max-core' ),
				),
				array(
					'id'    => 'end',
					'type'  => 'date',
					'label' => __( 'Ends', 'ultra-max-core' ),
					'help'  => __( 'Leave empty to count to midnight.', 'ultra-max-core' ),
				),
				array(
					'id'      => 'size',
					'type'    => 'select',
					'label'   => __( 'Size', 'ultra-max-core' ),
					'default' => 'lg',
					'options' => array(
						'sm' => __( 'Small', 'ultra-max-core' ),
						'md' => __( 'Medium', 'ultra-max-core' ),
						'lg' => __( 'Large', 'ultra-max-core' ),
					),
				),
				array(
					'id'      => 'ended',
					'type'    => 'text',
					'label'   => __( 'Text when time is up', 'ultra-max-core' ),
					'default' => __( 'This offer has ended.', 'ultra-max-core' ),
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
		$end = ( new \DateTimeImmutable( 'now', wp_timezone() ) )->setTime( 23, 59, 59 )->getTimestamp();
		if ( '' !== $settings->string( 'end' ) ) {
			try {
				$end = ( new \DateTimeImmutable( $settings->string( 'end' ), wp_timezone() ) )->getTimestamp();
			} catch ( \Exception ) {
				$end = time();
			}
		}
		$size = $settings->string( 'size' );
		return Html::from(
			array(
				SectionHead::render(
					array(
						'title' => $settings->string( 'title' ),
						'align' => 'center',
						'level' => 3,
						'class' => 'mb-3',
					)
				),
				Component::render(
					array(
						'end'    => $end,
						'label'  => $settings->string( 'title' ),
						'size'   => in_array( $size, array( 'sm', 'md', 'lg' ), true ) ? $size : 'lg',
						'digits' => Format::persian() ? 'fa' : 'en',
						'ended'  => $settings->string( 'ended' ),
						'class'  => 'mx-auto flex w-fit',
					)
				),
			)
		);
	}
}
