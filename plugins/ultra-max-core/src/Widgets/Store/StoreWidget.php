<?php
/**
 * Shared parts of the store widgets.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Box;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Lib\UI\SectionHead;
use UltraMax\Core\Store\Catalog;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * Store widgets share a section head (title, icon, "see all" link), an
 * optional framed box, and the product source controls.
 *
 * @phpstan-import-type Field from Widget
 * @phpstan-import-type Query from Catalog
 */
abstract class StoreWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'store';
	}

	/**
	 * Controls of the section head.
	 *
	 * @param string $title Default title.
	 * @return list<Field>
	 */
	protected function head_fields( string $title ): array {
		return array(
			array(
				'id'      => 'title',
				'type'    => 'text',
				'label'   => __( 'Title', 'ultra-max-core' ),
				'default' => $title,
			),
			array(
				'id'      => 'title_icon',
				'type'    => 'icon',
				'label'   => __( 'Title icon', 'ultra-max-core' ),
				'default' => '',
			),
			array(
				'id'      => 'more_label',
				'type'    => 'text',
				'label'   => __( '"See all" text', 'ultra-max-core' ),
				'default' => __( 'See all', 'ultra-max-core' ),
			),
			array(
				'id'    => 'more_link',
				'type'  => 'url',
				'label' => __( '"See all" link', 'ultra-max-core' ),
				'help'  => __( 'Leave empty to hide the link.', 'ultra-max-core' ),
			),
			array(
				'id'      => 'boxed',
				'type'    => 'switch',
				'label'   => __( 'Show in a white box', 'ultra-max-core' ),
				'default' => false,
			),
		);
	}

	/**
	 * Controls that choose products.
	 *
	 * @param string $source Default source.
	 * @param int    $count  Default number of products.
	 * @return list<Field>
	 */
	protected function query_fields( string $source = 'recent', int $count = 10 ): array {
		return array(
			array(
				'id'      => 'source',
				'type'    => 'select',
				'label'   => __( 'Products', 'ultra-max-core' ),
				'options' => Catalog::sources(),
				'default' => $source,
			),
			array(
				'id'        => 'categories',
				'type'      => 'terms',
				'taxonomy'  => 'product_cat',
				'label'     => __( 'Only from these categories', 'ultra-max-core' ),
				'help'      => __( 'Leave empty for all categories.', 'ultra-max-core' ),
				'condition' => array( 'source!' => 'manual' ),
			),
			array(
				'id'        => 'products',
				'type'      => 'posts',
				'post_type' => 'product',
				'label'     => __( 'Choose products', 'ultra-max-core' ),
				'condition' => array( 'source' => 'manual' ),
			),
			array(
				'id'      => 'count',
				'type'    => 'number',
				'label'   => __( 'Number of products', 'ultra-max-core' ),
				'default' => $count,
				'min'     => 1,
				'max'     => 48,
			),
			array(
				'id'      => 'in_stock',
				'type'    => 'switch',
				'label'   => __( 'Hide out of stock products', 'ultra-max-core' ),
				'default' => true,
			),
		);
	}

	/**
	 * The product query of the settings.
	 *
	 * @param Values $settings Settings.
	 * @return Query
	 */
	protected function query( Values $settings ): array {
		$source = $settings->string( 'source' );
		return array(
			'source'     => 'category' === $source ? 'recent' : $source,
			'count'      => max( 1, $settings->int( 'count' ) ),
			'categories' => $settings->ids( 'categories' ),
			'ids'        => $settings->ids( 'products' ),
			'in_stock'   => $settings->bool( 'in_stock' ),
		);
	}

	/**
	 * Texts of slider buttons.
	 *
	 * @return array{previous: string, next: string, slide_label: string}
	 */
	public static function slider_labels(): array {
		return array(
			'previous'    => __( 'Previous', 'ultra-max-core' ),
			'next'        => __( 'Next', 'ultra-max-core' ),
			/* translators: 1: slide number, 2: number of slides. */
			'slide_label' => __( '%1$s of %2$s', 'ultra-max-core' ),
		);
	}

	/**
	 * Card design choices.
	 *
	 * @return array<string, string>
	 */
	protected function designs(): array {
		return array(
			'hover'   => __( 'Framed, second image on hover', 'ultra-max-core' ),
			'classic' => __( 'Classic with lines', 'ultra-max-core' ),
			'mini'    => __( 'Small, supermarket', 'ultra-max-core' ),
			'deal'    => __( 'Offer with countdown', 'ultra-max-core' ),
		);
	}

	/**
	 * Wraps content in the section head and the optional box.
	 *
	 * @param Values    $settings Settings.
	 * @param Html      $content  Content.
	 * @param Html|null $extra Extra content on the head row, such as tabs or chips.
	 */
	protected function section( Values $settings, Html $content, ?Html $extra = null ): Html {
		if ( $content->is_empty() ) {
			return Html::none();
		}
		$head = array(
			'title' => $settings->string( 'title' ),
			'more'  => array(
				'label' => $settings->string( 'more_label' ),
				'href'  => $settings->href( 'more_link' ),
			),
		);
		if ( '' !== $settings->string( 'title_icon' ) ) {
			$head['icon'] = $settings->string( 'title_icon' );
		}
		if ( null !== $extra ) {
			$head['extra'] = $extra;
		}
		if ( $settings->bool( 'boxed' ) ) {
			return Box::render(
				array(
					'tag'      => 'section',
					'padding'  => 'md',
					'children' => array( SectionHead::render( $head ), $content ),
				)
			);
		}
		return SectionHead::section( SectionHead::render( $head ), $content );
	}

	/**
	 * Wraps content that draws its own head in the section or the box.
	 *
	 * @param Values $settings Settings.
	 * @param Html   $content  Content.
	 */
	protected function frame( Values $settings, Html $content ): Html {
		if ( $settings->bool( 'boxed' ) ) {
			return Box::render(
				array(
					'tag'      => 'section',
					'padding'  => 'md',
					'children' => $content,
				)
			);
		}
		return SectionHead::section( Html::none(), $content );
	}

	/**
	 * An image of the media library, or of a plain address.
	 *
	 * @param array{id: int, url: string} $media Media setting.
	 * @param string                      $size  Image size.
	 * @param bool                        $eager Load first.
	 */
	protected function image( array $media, string $size = 'medium', bool $eager = false ): Html {
		return Image::render(
			array(
				'id'       => $media['id'],
				'url'      => $media['url'],
				'size'     => $size,
				'priority' => $eager,
			)
		);
	}
}
