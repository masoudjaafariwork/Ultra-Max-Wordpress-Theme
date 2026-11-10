<?php
/**
 * Blog posts widget (HOME-10).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Store;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\PostTiles;
use UltraMax\Core\Store\Format;
use UltraMax\Core\Widgets\Values;

/**
 * The latest articles of the blog, with a Jalali date and reading time.
 */
final class Posts extends StoreWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'posts';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Blog posts', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-posts-grid';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-10' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'post-tiles' );
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'head_section',
				'label'  => __( 'Section', 'ultra-max-core' ),
				'fields' => $this->head_fields( __( 'Worth reading', 'ultra-max-core' ) ),
			),
			array(
				'id'     => 'posts_section',
				'label'  => __( 'Posts', 'ultra-max-core' ),
				'fields' => array(
					array(
						'id'       => 'categories',
						'type'     => 'terms',
						'taxonomy' => 'category',
						'label'    => __( 'Only from these categories', 'ultra-max-core' ),
					),
					array(
						'id'      => 'count',
						'type'    => 'number',
						'label'   => __( 'Number of posts', 'ultra-max-core' ),
						'default' => 4,
						'min'     => 1,
						'max'     => 12,
					),
					array(
						'id'      => 'columns',
						'type'    => 'select',
						'label'   => __( 'Columns on desktop', 'ultra-max-core' ),
						'default' => '4',
						'options' => array(
							'2' => '2',
							'3' => '3',
							'4' => '4',
						),
					),
				),
			),
		);
	}

	/**
	 * Minutes needed to read a text.
	 *
	 * @param string $content Post content.
	 */
	public static function minutes( string $content ): int {
		$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $content ) ), -1, PREG_SPLIT_NO_EMPTY );
		$words = is_array( $words ) ? count( $words ) : 0;
		return max( 1, (int) ceil( $words / 200 ) );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$args = array(
			'post_type'           => 'post',
			'posts_per_page'      => max( 1, $settings->int( 'count' ) ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( array() !== $settings->ids( 'categories' ) ) {
			$args['category__in'] = $settings->ids( 'categories' );
		}
		$items = array();
		foreach ( get_posts( $args ) as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$time    = (int) get_post_time( 'U', true, $post );
			$minutes = self::minutes( $post->post_content );
			$items[] = array(
				'title'    => wp_strip_all_tags( get_the_title( $post ) ),
				'href'     => (string) get_permalink( $post ),
				'image'    => $this->image(
					array(
						'id'  => (int) get_post_thumbnail_id( $post ),
						'url' => '',
					),
					'medium_large'
				),
				'date'     => Format::date( $time ),
				'datetime' => (string) get_post_time( 'c', true, $post ),
				/* translators: %s: number of minutes. */
				'meta'     => sprintf( _n( '%s minute read', '%s minutes read', $minutes, 'ultra-max-core' ), Format::number( $minutes ) ),
			);
		}
		$columns = $settings->int( 'columns' );
		return $this->section(
			$settings,
			PostTiles::render(
				array(
					'label'   => '' === $settings->string( 'title' ) ? __( 'Blog posts', 'ultra-max-core' ) : $settings->string( 'title' ),
					'items'   => $items,
					'columns' => 2 === $columns ? 2 : ( 3 === $columns ? 3 : 4 ),
				)
			)
		);
	}
}
