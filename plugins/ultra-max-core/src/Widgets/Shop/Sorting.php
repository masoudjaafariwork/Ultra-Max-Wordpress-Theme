<?php
/**
 * Sorting widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The sort bar (PLP-03) with the number of products and the view switch;
 * on phones and tablets a "Sort" button that opens the sort sheet (PLP-06).
 */
final class Sorting extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'sorting';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Sorting', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-sort-amount-desc';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-03', 'PLP-06' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Sorting', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'count',
					'type'    => 'switch',
					'label'   => __( 'Show the number of products', 'ultra-max-core' ),
					'default' => true,
				),
				array(
					'id'      => 'view',
					'type'    => 'switch',
					'label'   => __( 'Show the grid and rows switch', 'ultra-max-core' ),
					'default' => true,
				),
				array(
					'id'      => 'phone_button',
					'type'    => 'switch',
					'label'   => __( 'Show a "Sort" button on phones and tablets', 'ultra-max-core' ),
					'default' => true,
				),
			)
		);
	}

	/**
	 * The bar.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Html::from(
			array(
				Archive::sorting( $settings->bool( 'count' ), $settings->bool( 'view' ) ),
				$settings->bool( 'phone_button' ) ? Archive::bar( false, true ) : Html::none(),
			)
		);
	}
}
