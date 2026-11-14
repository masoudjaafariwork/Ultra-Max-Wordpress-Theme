<?php
/**
 * Result count widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * "1,248 products": how many products the filters leave.
 */
final class ResultCount extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'result-count';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Result count', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-counter';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Result count', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'note',
					'type'  => 'heading',
					'label' => __( 'Changes with the filters.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * The count.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::result_count();
	}
}
