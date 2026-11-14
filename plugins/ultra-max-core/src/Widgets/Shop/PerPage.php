<?php
/**
 * Products per page widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Shop\State;
use UltraMax\Core\Widgets\Values;

/**
 * Lets the shopper choose how many products a page shows.
 */
final class PerPage extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'per-page';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Products per page', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-number-field';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return $this->section(
			__( 'Products per page', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'choices',
					'type'    => 'text',
					'label'   => __( 'Choices', 'ultra-max-core' ),
					'help'    => __( 'Numbers separated by commas, from 12, 24, 36, 48, 72 and 96.', 'ultra-max-core' ),
					'default' => '24,48,96',
				),
			)
		);
	}

	/**
	 * The choices.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		$choices = array_values( array_intersect( array_map( 'intval', explode( ',', $settings->string( 'choices' ) ) ), State::PER_PAGE ) );
		return array() === $choices ? Html::none() : Archive::per_page_links( $choices );
	}
}
