<?php
/**
 * The shopper's reviews and questions (ACC-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Account;

use UltraMax\Core\Lib\UI\ActivityList;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Stars;
use UltraMax\Core\Product\Questions;
use UltraMax\Core\Store\Format;

/**
 * Reviews are WooCommerce's product reviews; questions are the product
 * page's own comment type (PDP-10). Both show with their state, so a
 * shopper sees what still waits for approval. Only the shopper's own are
 * read, by their user id.
 */
final class Activity {

	public const PER_PAGE = 10;

	/**
	 * One page of a shopper's reviews or questions.
	 *
	 * @param int    $user User id.
	 * @param string $kind reviews or questions.
	 * @param int    $page Page number, from 1.
	 * @return array{items: list<\WP_Comment>, total: int}
	 */
	public static function query( int $user, string $kind, int $page = 1 ): array {
		if ( $user <= 0 ) {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}
		$args  = array(
			'user_id'   => $user,
			'post_type' => 'product',
			'type'      => 'questions' === $kind ? Questions::TYPE : 'review',
			'status'    => array( 'approve', 'hold' ),
			'parent'    => 0,
		);
		$total = get_comments( array_merge( $args, array( 'count' => true ) ) );
		$items = get_comments(
			array_merge(
				$args,
				array(
					'number'  => self::PER_PAGE,
					'offset'  => ( max( 1, $page ) - 1 ) * self::PER_PAGE,
					'orderby' => 'comment_date_gmt',
					'order'   => 'DESC',
				)
			)
		);
		return array(
			'items' => array_values( array_filter( is_array( $items ) ? $items : array(), static fn ( mixed $item ): bool => $item instanceof \WP_Comment ) ),
			'total' => is_numeric( $total ) ? (int) $total : 0,
		);
	}

	/**
	 * The list of reviews or questions.
	 *
	 * @param array  $comments Comments.
	 * @param string $kind     reviews or questions.
	 * @phpstan-param list<\WP_Comment> $comments
	 */
	public static function render( array $comments, string $kind ): Html {
		$items = array();
		foreach ( $comments as $comment ) {
			$product = wc_get_product( (int) $comment->comment_post_ID );
			if ( ! $product instanceof \WC_Product ) {
				continue;
			}
			$approved = '1' === (string) $comment->comment_approved;
			$image    = $product->get_image(
				'woocommerce_gallery_thumbnail',
				array(
					'loading' => 'lazy',
					'alt'     => '',
				)
			);
			$item     = array(
				'product' => wp_strip_all_tags( $product->get_name() ),
				'href'    => (string) $product->get_permalink() . ( 'questions' === $kind ? '#questions' : '#reviews' ),
				'image'   => Html::wp( is_string( $image ) ? $image : '' ),
				'text'    => wp_strip_all_tags( $comment->comment_content ),
				'date'    => Format::date( (int) strtotime( $comment->comment_date_gmt . ' UTC' ) ),
				'status'  => $approved
					? array(
						'label' => __( 'Published', 'ultra-max-core' ),
						'tone'  => 'success',
					)
					: array(
						'label' => __( 'Waiting for approval', 'ultra-max-core' ),
						'tone'  => 'warning',
					),
			);
			if ( 'reviews' === $kind ) {
				$rating = get_comment_meta( (int) $comment->comment_ID, 'rating', true );
				$rating = is_numeric( $rating ) ? (int) $rating : 0;
				if ( $rating > 0 ) {
					/* translators: %s: rating out of 5. */
					$item['stars'] = Stars::show( (float) $rating, sprintf( __( 'Rated %s out of 5', 'ultra-max-core' ), Format::number( $rating ) ), 'size-3.5' );
				}
			} else {
				$answers = get_comments(
					array(
						'parent' => (int) $comment->comment_ID,
						'status' => 'approve',
						'count'  => true,
					)
				);
				$answers = is_numeric( $answers ) ? (int) $answers : 0;
				/* translators: %s: number of answers. */
				$item['note'] = 0 === $answers ? __( 'No answer yet', 'ultra-max-core' ) : sprintf( _n( '%s answer', '%s answers', $answers, 'ultra-max-core' ), Format::number( $answers ) );
			}
			$items[] = $item;
		}
		return ActivityList::render(
			array(
				'label' => 'questions' === $kind ? __( 'Your questions', 'ultra-max-core' ) : __( 'Your reviews', 'ultra-max-core' ),
				'items' => $items,
			)
		);
	}
}
