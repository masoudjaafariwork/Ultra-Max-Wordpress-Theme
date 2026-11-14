<?php
/**
 * Archive products widget (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Archive;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Widgets\Values;

/**
 * The products of the list, in a card design, with "show more" and page
 * links under them (PLP-04, PLP-07).
 */
final class ArchiveProducts extends ShopWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'archive-products';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Archive products', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-products';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05', 'PLP-04', 'PLP-07', 'PLP-08' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'shop', 'product-card', 'countdown' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		$columns = static fn ( string $id, string $label, int $min, int $max ): array => array(
			'id'      => $id,
			'type'    => 'number',
			'label'   => $label,
			'help'    => __( '0 uses the shop settings.', 'ultra-max-core' ),
			'default' => 0,
			'min'     => 0,
			'max'     => $max,
		);
		return $this->section(
			__( 'Products', 'ultra-max-core' ),
			array(
				array(
					'id'      => 'design',
					'type'    => 'select',
					'label'   => __( 'Card design', 'ultra-max-core' ),
					'default' => '',
					'options' => array_merge( array( '' => __( 'As in the shop settings', 'ultra-max-core' ) ), Cards::designs(), Cards::layouts() ),
				),
				$columns( 'columns', __( 'Columns on desktop', 'ultra-max-core' ), 0, 6 ),
				$columns( 'columns_tablet', __( 'Columns on tablet', 'ultra-max-core' ), 0, 4 ),
				$columns( 'columns_mobile', __( 'Columns on mobile', 'ultra-max-core' ), 0, 2 ),
				array(
					'id'      => 'more',
					'type'    => 'switch',
					'label'   => __( 'Show "show more" and page links', 'ultra-max-core' ),
					'default' => true,
				),
			)
		);
	}

	/**
	 * The products.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		$options = array();
		if ( '' !== $settings->string( 'design' ) ) {
			$options['design'] = $settings->string( 'design' );
		}
		foreach ( array( 'columns', 'columns_tablet', 'columns_mobile' ) as $key ) {
			if ( $settings->int( $key ) > 0 ) {
				$options[ $key ] = $settings->int( $key );
			}
		}
		return Html::from( array( Archive::products( $options ), $settings->bool( 'more' ) ? Archive::more() : Html::none() ) );
	}
}
