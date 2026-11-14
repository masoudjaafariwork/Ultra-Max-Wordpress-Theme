<?php
/**
 * View switch widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * Shows the products as a grid or as rows (PLP-05). Works with the
 * "Archive products" widget of the same layout.
 */
final class ViewToggle extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'view-toggle';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'View switch', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-gallery-grid';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-05' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'View switch', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'note',
					'type'  => 'heading',
					'label' => __( 'The shopper\'s choice is remembered in their browser.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * The switch.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::view_toggle();
	}
}
