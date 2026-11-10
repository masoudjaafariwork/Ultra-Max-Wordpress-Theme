<?php
/**
 * Stories widget (HOME-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Stories as Component;
use UltraMax\Core\Widgets\Values;

/**
 * Round story covers that open a full-screen viewer of images and videos,
 * each with an optional link, for example to a product.
 */
final class Stories extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'stories';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Stories', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-gallery-grid';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-02' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'stories' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'stories_section',
				'label'  => __( 'Stories', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'          => 'stories',
						'type'        => 'repeater',
						'label'       => __( 'Stories', 'ultra-max-core' ),
						'title_field' => 'title',
						'fields'      => array(
							array(
								'id'      => 'title',
								'type'    => 'text',
								'label'   => __( 'Title', 'ultra-max-core' ),
								'default' => __( 'New arrivals', 'ultra-max-core' ),
							),
							array(
								'id'    => 'cover',
								'type'  => 'media',
								'label' => __( 'Cover image', 'ultra-max-core' ),
							),
							array(
								'id'    => 'media',
								'type'  => 'media',
								'label' => __( 'Story image or video', 'ultra-max-core' ),
								'help'  => __( 'A tall image or an MP4 video. Without it the cover is shown.', 'ultra-max-core' ),
							),
							array(
								'id'        => 'product',
								'type'      => 'posts',
								'post_type' => 'product',
								'multiple'  => false,
								'label'     => __( 'Link to a product', 'ultra-max-core' ),
							),
							array(
								'id'    => 'link',
								'type'  => 'url',
								'label' => __( 'Or link to an address', 'ultra-max-core' ),
							),
							array(
								'id'      => 'link_label',
								'type'    => 'text',
								'label'   => __( 'Link button text', 'ultra-max-core' ),
								'default' => __( 'View', 'ultra-max-core' ),
							),
						),
						'default'     => array(
							array( 'title' => __( 'New arrivals', 'ultra-max-core' ) ),
						),
					),
					array(
						'id'      => 'duration',
						'type'    => 'number',
						'label'   => __( 'Seconds per image', 'ultra-max-core' ),
						'default' => 5,
						'min'     => 2,
						'max'     => 20,
					),
				),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$stories = array();
		foreach ( $settings->rows( 'stories' ) as $index => $row ) {
			$cover = $row->media( 'cover' );
			$media = $row->media( 'media' );
			$url   = '' !== $media['url'] ? $media['url'] : $cover['url'];
			if ( '' === $url ) {
				continue;
			}
			$href     = $row->href( 'link' );
			$products = $row->ids( 'product' );
			if ( array() !== $products ) {
				$permalink = get_permalink( $products[0] );
				$href      = is_string( $permalink ) ? $permalink : $href;
			}
			$type      = wp_check_filetype( (string) wp_parse_url( $url, PHP_URL_PATH ) );
			$stories[] = array(
				'id'         => substr( md5( $url . $index ), 0, 10 ),
				'title'      => $row->string( 'title' ),
				'cover'      => '' !== $cover['url'] ? $cover['url'] : $url,
				'media'      => $url,
				'type'       => is_string( $type['type'] ) && str_starts_with( $type['type'], 'video/' ) ? 'video' : 'image',
				'href'       => $href,
				'link_label' => $row->string( 'link_label' ),
			);
		}
		return Component::render(
			array(
				'label'    => __( 'Stories', 'ultra-max-core' ),
				'stories'  => $stories,
				'close'    => __( 'Close', 'ultra-max-core' ),
				'previous' => __( 'Previous story', 'ultra-max-core' ),
				'next'     => __( 'Next story', 'ultra-max-core' ),
				'duration' => max( 2, $settings->int( 'duration' ) ) * 1000,
			)
		);
	}
}
