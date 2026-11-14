<?php
/**
 * Archive header widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * Breadcrumbs, the title, the number of products and the top text of the
 * list; on a brand page with the brand's logo (PLP-10, PLP-11).
 */
final class ArchiveHeader extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'archive-header';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Archive header', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-archive-title';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-10', 'PLP-11' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Archive header', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'crumbs',
					'type'    => 'switch',
					'label'   => __( 'Show breadcrumbs', 'ultra-max-core' ),
					'default' => true,
				),
			)
		);
	}

	/**
	 * The header.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		return Archive::intro( $settings->bool( 'crumbs' ) );
	}
}
