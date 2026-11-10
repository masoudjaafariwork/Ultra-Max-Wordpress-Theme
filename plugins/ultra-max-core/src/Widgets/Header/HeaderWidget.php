<?php
/**
 * Shared parts of the header widgets (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Widgets\Widget;

/**
 * Header widgets live in their own panel group and share one small asset.
 */
abstract class HeaderWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'header';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-02' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'header' );
	}

	/**
	 * The "show as" control of header actions.
	 *
	 * @param string $initial Default look.
	 * @return array<string, mixed>
	 */
	protected function look_field( string $initial = 'icon' ): array {
		return array(
			'id'      => 'look',
			'type'    => 'select',
			'label'   => __( 'Show as', 'ultra-max-core' ),
			'default' => $initial,
			'options' => array(
				'icon'    => __( 'Icon', 'ultra-max-core' ),
				'button'  => __( 'Button with text', 'ultra-max-core' ),
				'stacked' => __( 'Icon above text', 'ultra-max-core' ),
			),
		);
	}

	/**
	 * The chosen look.
	 *
	 * @param string $look Setting.
	 * @return 'icon'|'button'|'stacked'
	 */
	protected static function look( string $look ): string {
		return match ( $look ) {
			'button'  => 'button',
			'stacked' => 'stacked',
			default   => 'icon',
		};
	}
}
