<?php
/**
 * Divider widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Separator;
use UltraMax\Core\Widgets\Values;

/**
 * A thin upright line between header items.
 */
final class Divider extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'divider';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Header divider', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-divider';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array();
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Separator::render( array( 'kind' => 'line' ) );
	}
}
