<?php
/**
 * Amazing offers widget (HOME-03).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Countdown;
use UltraMax\Core\Lib\UI\Deals as Component;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Store\Cards;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * Timed offers: a coloured band with a countdown and a slider of products
 * on sale, each with its own timer and a "sold" bar. The band counts down
 * to a date you choose, or to the end of today for daily offers. The
 * "see all offers" link goes to the offers page.
 */
final class Deals extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'deals';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Amazing offers', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-countdown';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-03' );
	}

	/**
	 * Keywords.
	 *
	 * @return list<string>
	 */
	public function keywords(): array {
		return array( 'deal', 'offer', 'sale', 'countdown', 'timer' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'carousel', 'countdown', 'product-card', 'deals' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'band_section',
				'label'  => __( 'Band', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'      => 'title',
						'type'    => 'text',
						'label'   => __( 'Title', 'ultra-max-core' ),
						'default' => __( 'Amazing offers', 'ultra-max-core' ),
					),
					array(
						'id'      => 'end',
						'type'    => 'date',
						'label'   => __( 'Offers end', 'ultra-max-core' ),
						'help'    => __( 'Leave empty for daily offers that end at midnight.', 'ultra-max-core' ),
						'default' => '',
					),
					array(
						'id'    => 'image',
						'type'  => 'media',
						'label' => __( 'Image', 'ultra-max-core' ),
					),
					array(
						'id'      => 'more_label',
						'type'    => 'text',
						'label'   => __( '"See all" text', 'ultra-max-core' ),
						'default' => __( 'All offers', 'ultra-max-core' ),
					),
					array(
						'id'    => 'more_link',
						'type'  => 'url',
						'label' => __( '"See all" link', 'ultra-max-core' ),
					),
				),
			),
			array(
				'id'     => 'products_section',
				'label'  => __( 'Products', 'ultra-max-core' ),
				'fields' => $this->query_fields( 'sale', 10 ),
			),
		);
	}

	/**
	 * The moment the band counts down to.
	 *
	 * @param Values $settings Settings.
	 */
	private function end( Values $settings ): int {
		$end = $settings->string( 'end' );
		if ( '' !== $end ) {
			try {
				$date = new \DateTimeImmutable( $end, wp_timezone() );
				if ( $date->getTimestamp() > time() ) {
					return $date->getTimestamp();
				}
			} catch ( \Exception ) {
				return Cards::end_of_today();
			}
		}
		return Cards::end_of_today();
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$end   = $this->end( $settings );
		$cards = array();
		foreach ( Catalog::products( $this->query( $settings ) ) as $product ) {
			$cards[] = Cards::card( $product, 'deal', array( 'deal_end' => min( $end, Cards::sale_end( $product ) ) ) );
		}
		return Component::render(
			array_merge(
				self::slider_labels(),
				array(
					'id'         => 'deals',
					'title'      => $settings->string( 'title' ),
					'cards'      => $cards,
					'countdown'  => Countdown::render(
						array(
							'end'    => $end,
							'label'  => __( 'Time left for these offers', 'ultra-max-core' ),
							'digits' => Format::persian() ? 'fa' : 'en',
						)
					),
					'ends_label' => __( 'Ends in', 'ultra-max-core' ),
					'image'      => $this->image( $settings->media( 'image' ), 'medium' ),
					'more'       => array(
						'label' => $settings->string( 'more_label' ),
						'href'  => $settings->href( 'more_link' ),
					),
				)
			)
		);
	}
}
