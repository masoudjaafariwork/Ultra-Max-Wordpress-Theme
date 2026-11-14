<?php
/**
 * Archive description widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The short text at the top of a category, tag or brand, or the long text
 * under its products (PLP-10).
 */
final class ArchiveDescription extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'archive-description';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Archive description', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-text-area';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-10' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Description', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'where',
					'type'    => 'select',
					'label'   => __( 'Which text', 'ultra-max-core' ),
					'default' => 'bottom',
					'options' => array(
						'top'    => __( 'Short text at the top', 'ultra-max-core' ),
						'bottom' => __( 'Long text under the products', 'ultra-max-core' ),
					),
				),
			)
		);
	}

	/**
	 * The text.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::description( 'top' === $settings->string( 'where' ) ? 'top' : 'bottom' );
	}
}
