<?php
/**
 * Active filters widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The chosen filters as chips that remove them (PLP-12).
 */
final class ActiveFilters extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'active-filters';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Active filters', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-tags';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-12' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Active filters', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'note',
					'type'  => 'heading',
					'label' => __( 'Shows only when a filter is chosen.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * The chips.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::active();
	}
}
