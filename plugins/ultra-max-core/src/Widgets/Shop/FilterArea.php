<?php
/**
 * Filter area widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Widgets\Values;

/**
 * The filter panel of the list (PLP-01, PLP-02). On phones and tablets a
 * "Filter" button takes its place and opens the filter sheet (PLP-06).
 */
final class FilterArea extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'filter-area';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Filter area', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-filter';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-01', 'PLP-02', 'PLP-06' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Filters', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'show',
					'type'    => 'select',
					'label'   => __( 'Show', 'ultra-max-core' ),
					'help'    => __( 'The filters a category shows are chosen in its edit screen or in the shop settings.', 'ultra-max-core' ),
					'default' => 'both',
					'options' => array(
						'both'   => __( 'Panel on desktop, "Filter" button on phones and tablets', 'ultra-max-core' ),
						'panel'  => __( 'Panel on desktop only', 'ultra-max-core' ),
						'button' => __( '"Filter" button on phones and tablets only', 'ultra-max-core' ),
					),
				),
			)
		);
	}

	/**
	 * The panel.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		$show  = $settings->string( 'show' );
		$panel = 'button' === $show ? Html::none() : Archive::filters( 'sidebar' );
		return Html::from(
			array(
				$panel->is_empty() ? $panel : Html::trusted( '<div class="max-lg:hidden">' . $panel . '</div>' ),
				'panel' === $show ? Html::none() : Archive::bar( true, false ),
			)
		);
	}
}
