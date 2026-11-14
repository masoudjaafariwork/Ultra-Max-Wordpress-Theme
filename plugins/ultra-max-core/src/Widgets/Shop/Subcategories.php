<?php
/**
 * Subcategories widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The child categories with their pictures (PLP-09).
 */
final class Subcategories extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'subcategories';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Subcategories', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-product-categories';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-09' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Subcategories', 'ultra-max-core' ),
			array(
				array(
					'id'    => 'note',
					'type'  => 'heading',
					'label' => __( 'Shows the children of the category, or the top categories on the shop page. Pictures come from each category.', 'ultra-max-core' ),
				),
			)
		);
	}

	/**
	 * The row.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::subcategories();
	}
}
