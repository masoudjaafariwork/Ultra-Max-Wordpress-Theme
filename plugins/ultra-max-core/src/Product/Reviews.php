<?php
/**
 * Product reviews (PDP-07, PDP-08, PDP-09).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Product;

use UltraMax\Core\Admin\Rest as AdminRest;
use UltraMax\Core\Lib\Settings\Store;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\ReviewForm;
use UltraMax\Core\Lib\UI\ReviewList;
use UltraMax\Core\Lib\UI\ReviewSummary;
use UltraMax\Core\Store\Format;

/**
 * Reviews stay WooCommerce reviews: comments of the product with their
 * rating, WooCommerce's "verified owner" mark and its moderation. We add
 * to each review a title, strengths and weaknesses, a score per criterion,
 * whether the author recommends the product, photos, and "helpful" votes.
 *
 * The summary (average, stars, criteria, recommend share and photos) is
 * kept with the product and rebuilt whenever its reviews change.
 *
 * @phpstan-import-type ReviewItem from ReviewList
 * @phpstan-type Stats array{count: int, average: float, bars: array<int, int>, criteria: array<string, float>, recommend: int, recommend_count: int, photos: list<int>}
 */
final class Reviews {

	public const NONCE     = 'ultra_max_review';
	public const STATS     = '_um_review_stats';
	public const TITLE     = 'um_title';
	public const PROS      = 'um_pros';
	public const CONS      = 'um_cons';
	public const CRITERIA  = 'um_criteria';
	public const RECOMMEND = 'um_recommend';
	public const IMAGES    = 'um_images';
	public const UP        = 'um_up';
	public const DOWN      = 'um_down';
	public const VOTERS    = '_um_voters';
	public const PER_PAGE  = 5;

	/**
	 * Hooks reviews into WooCommerce and the product page.
	 */
	public static function register(): void {
		add_filter( 'preprocess_comment', array( self::class, 'guard' ), 5 );
		add_action( 'comment_post', array( self::class, 'save' ), 20 );
		add_action( 'wp_update_comment_count', array( self::class, 'flush' ) );
		add_filter( 'comment_post_redirect', array( self::class, 'redirect' ), 20, 2 );
		add_filter( 'ultra_max_product_sections', array( self::class, 'section_item' ), 20, 2 );
		add_filter( 'ultra_max_product_heading', array( self::class, 'heading' ), 10, 2 );
		add_filter( 'wp_robots', array( self::class, 'robots' ) );
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Whether a product takes reviews.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function open( \WC_Product $product ): bool {
		return 'yes' === get_option( 'woocommerce_enable_reviews', 'yes' ) && $product->get_reviews_allowed();
	}

	/**
	 * The criteria shoppers score, one per line in the settings.
	 *
	 * @return list<string>
	 */
	public static function criteria(): array {
		$lines = preg_split( '/\R/', Store::string( 'product_review_criteria' ) );
		$out   = array();
		foreach ( false === $lines ? array() : $lines as $line ) {
			$line = trim( sanitize_text_field( $line ) );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}
		return array_slice( array_values( array_unique( $out ) ), 0, 6 );
	}

	/**
	 * Lines of a text field, cleaned.
	 *
	 * @param string $text Text.
	 * @param int    $max  Most lines.
	 * @return list<string>
	 */
	public static function lines( string $text, int $max = 5 ): array {
		$lines = preg_split( '/\R/', $text );
		$out   = array();
		foreach ( false === $lines ? array() : $lines as $line ) {
			$line = trim( sanitize_text_field( $line ), " \t-+•*" );
			if ( '' !== $line ) {
				$out[] = mb_substr( $line, 0, 120 );
			}
		}
		return array_slice( $out, 0, $max );
	}

	/**
	 * A posted text field of the review form.
	 *
	 * @param string $key Field name.
	 */
	private static function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Callers check the nonce and sanitize.
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		return is_string( $value ) ? $value : '';
	}

	/**
	 * Holds back a review from our form when the store takes reviews only
	 * from buyers and the author has not bought the product.
	 *
	 * @param mixed $data Comment data.
	 * @return mixed
	 */
	public static function guard( mixed $data ): mixed {
		if ( ! is_array( $data ) || '' === self::posted( 'um_review' ) ) {
			return $data;
		}
		$post_id = is_numeric( $data['comment_post_ID'] ?? null ) ? (int) $data['comment_post_ID'] : 0;
		if ( 'product' !== get_post_type( $post_id ) || 'yes' !== get_option( 'woocommerce_review_rating_verification_required' ) ) {
			return $data;
		}
		$email = is_string( $data['comment_author_email'] ?? null ) ? $data['comment_author_email'] : '';
		if ( ! wc_customer_bought_product( $email, get_current_user_id(), $post_id ) ) {
			wp_die(
				esc_html__( 'Only shoppers who bought this product can review it.', 'ultra-max-core' ),
				esc_html__( 'Review not sent', 'ultra-max-core' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
		return $data;
	}

	/**
	 * Saves our fields of a review sent from our form.
	 *
	 * @param mixed $comment_id Comment id.
	 */
	public static function save( mixed $comment_id ): void {
		$comment_id = is_numeric( $comment_id ) ? (int) $comment_id : 0;
		$comment    = get_comment( $comment_id );
		$nonce      = sanitize_text_field( self::posted( self::NONCE ) );
		if ( ! $comment instanceof \WP_Comment || 'product' !== get_post_type( (int) $comment->comment_post_ID ) || ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}
		$title = mb_substr( sanitize_text_field( self::posted( self::TITLE ) ), 0, 120 );
		if ( '' !== $title ) {
			update_comment_meta( $comment_id, self::TITLE, $title );
		}
		foreach ( array( self::PROS, self::CONS ) as $key ) {
			$lines = self::lines( self::posted( $key ) );
			if ( array() !== $lines ) {
				update_comment_meta( $comment_id, $key, $lines );
			}
		}
		$recommend = self::posted( self::RECOMMEND );
		if ( in_array( $recommend, array( 'yes', 'no', 'unsure' ), true ) ) {
			update_comment_meta( $comment_id, self::RECOMMEND, $recommend );
		}
		$scores = array();
		foreach ( self::criteria() as $index => $label ) {
			$score = absint( self::posted( 'um_criterion_' . $index ) );
			if ( $score >= 1 && $score <= 5 ) {
				$scores[ $label ] = $score;
			}
		}
		if ( array() !== $scores ) {
			update_comment_meta( $comment_id, self::CRITERIA, $scores );
		}
		$images = self::upload( (int) $comment->comment_post_ID, $comment_id );
		if ( array() !== $images ) {
			update_comment_meta( $comment_id, self::IMAGES, $images );
		}
		update_comment_meta( $comment_id, self::UP, 0 );
		update_comment_meta( $comment_id, self::DOWN, 0 );
		self::flush( (int) $comment->comment_post_ID );
	}

	/**
	 * Saves the photos of a review: logged in shoppers only, at most four
	 * pictures of three megabytes, JPEG, PNG or WebP checked by content.
	 *
	 * @param int $product_id Product id.
	 * @param int $comment_id Review id.
	 * @return list<int> Attachment ids.
	 */
	private static function upload( int $product_id, int $comment_id ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The caller checked the nonce; each file is checked by name, size and content below.
		$files = isset( $_FILES['um_review_images'] ) && is_array( $_FILES['um_review_images'] ) ? $_FILES['um_review_images'] : array();
		if ( ! Store::bool( 'product_review_images' ) || ! is_user_logged_in() || ! is_array( $files['name'] ?? null ) ) {
			return array();
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$ids   = array();
		$names = $files['name'];
		foreach ( array_keys( $names ) as $index ) {
			if ( count( $ids ) >= 4 ) {
				break;
			}
			$file = array(
				'name'     => is_string( $names[ $index ] ?? null ) ? sanitize_file_name( $names[ $index ] ) : '',
				'type'     => is_array( $files['type'] ?? null ) && is_string( $files['type'][ $index ] ?? null ) ? $files['type'][ $index ] : '',
				'tmp_name' => is_array( $files['tmp_name'] ?? null ) && is_string( $files['tmp_name'][ $index ] ?? null ) ? $files['tmp_name'][ $index ] : '',
				'error'    => is_array( $files['error'] ?? null ) && is_numeric( $files['error'][ $index ] ?? null ) ? (int) $files['error'][ $index ] : 4,
				'size'     => is_array( $files['size'] ?? null ) && is_numeric( $files['size'][ $index ] ?? null ) ? (int) $files['size'][ $index ] : 0,
			);
			if ( UPLOAD_ERR_OK !== $file['error'] || '' === $file['tmp_name'] || $file['size'] <= 0 || $file['size'] > 3 * MB_IN_BYTES ) {
				continue;
			}
			$check = wp_check_filetype_and_ext(
				$file['tmp_name'],
				$file['name'],
				array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
				)
			);
			if ( empty( $check['type'] ) ) {
				continue;
			}
			$id = media_handle_sideload(
				array(
					'name'     => $file['name'],
					'tmp_name' => $file['tmp_name'],
				),
				$product_id,
				'',
				array( 'post_author' => get_current_user_id() )
			);
			if ( is_int( $id ) ) {
				update_post_meta( $id, '_um_review_image', $comment_id );
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * After a review is sent, the page comes back to the reviews.
	 *
	 * @param mixed $location Address.
	 * @param mixed $comment  Comment.
	 * @return mixed
	 */
	public static function redirect( mixed $location, mixed $comment ): mixed {
		if ( ! is_string( $location ) || ! $comment instanceof \WP_Comment || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
			return $location;
		}
		$base = (string) strtok( $location, '#' );
		return '1' === $comment->comment_approved ? $base . '#review-' . $comment->comment_ID : add_query_arg( 'um_review_sent', '1', $base ) . '#reviews';
	}

	/**
	 * Forgets the summary of a product after its reviews change.
	 *
	 * @param mixed $post_id Post id.
	 */
	public static function flush( mixed $post_id ): void {
		if ( is_numeric( $post_id ) ) {
			delete_post_meta( (int) $post_id, self::STATS );
		}
	}

	/**
	 * The approved reviews of a product, top level only.
	 *
	 * @param int                  $product_id Product id.
	 * @param array<string, mixed> $args       More query arguments.
	 * @return list<\WP_Comment>
	 */
	private static function comments( int $product_id, array $args = array() ): array {
		$found = self::query(
			array_merge(
				array(
					'post_id'  => $product_id,
					'status'   => 'approve',
					'type__in' => array( 'review', 'comment' ),
					'parent'   => 0,
				),
				$args
			)
		);
		return is_array( $found ) ? $found : array();
	}

	/**
	 * A review's meta value as a list of strings.
	 *
	 * @param int    $comment_id Comment id.
	 * @param string $key        Meta key.
	 * @return list<string>
	 */
	private static function meta_list( int $comment_id, string $key ): array {
		$value = get_comment_meta( $comment_id, $key, true );
		return is_array( $value ) ? array_values( array_filter( array_map( static fn ( mixed $item ): string => is_scalar( $item ) ? (string) $item : '', $value ) ) ) : array();
	}

	/**
	 * The summary of a product's reviews.
	 *
	 * @param \WC_Product $product Product.
	 * @return Stats
	 */
	public static function stats( \WC_Product $product ): array {
		$cached = get_post_meta( $product->get_id(), self::STATS, true );
		if ( is_array( $cached ) && isset( $cached['count'], $cached['average'] ) ) {
			/**
			 * The summary saved by an earlier request.
			 *
			 * @var Stats $cached
			 */
			return $cached;
		}
		$bars     = array_fill( 1, 5, 0 );
		$criteria = array();
		$yes      = 0;
		$answered = 0;
		$photos   = array();
		$sum      = 0;
		$count    = 0;
		foreach ( self::comments( $product->get_id() ) as $comment ) {
			$id     = (int) $comment->comment_ID;
			$rating = self::meta_int( $id, 'rating' );
			if ( $rating >= 1 && $rating <= 5 ) {
				++$bars[ $rating ];
				$sum += $rating;
				++$count;
			}
			$scores = get_comment_meta( $id, self::CRITERIA, true );
			foreach ( is_array( $scores ) ? $scores : array() as $label => $score ) {
				if ( is_string( $label ) && is_numeric( $score ) ) {
					$criteria[ $label ][] = (int) $score;
				}
			}
			$recommend = get_comment_meta( $id, self::RECOMMEND, true );
			if ( is_string( $recommend ) && '' !== $recommend ) {
				++$answered;
				$yes += 'yes' === $recommend ? 1 : 0;
			}
			foreach ( self::meta_list( $id, self::IMAGES ) as $image ) {
				$photos[] = (int) $image;
			}
		}
		$averages = array();
		foreach ( $criteria as $label => $scores ) {
			$averages[ $label ] = round( array_sum( $scores ) / count( $scores ), 1 );
		}
		$stats = array(
			'count'           => $count,
			'average'         => $count > 0 ? round( $sum / $count, 1 ) : 0.0,
			'bars'            => $bars,
			'criteria'        => $averages,
			'recommend'       => $answered > 0 ? (int) round( $yes / $answered * 100 ) : 0,
			'recommend_count' => $answered,
			'photos'          => array_values( array_unique( $photos ) ),
		);
		update_post_meta( $product->get_id(), self::STATS, $stats );
		return $stats;
	}

	/**
	 * The share of buyers who recommend a product, as text.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function recommend_text( \WC_Product $product ): string {
		$stats = self::stats( $product );
		if ( $stats['recommend_count'] < 3 ) {
			return '';
		}
		/* translators: %s: percent such as 87%. */
		return sprintf( __( '%s of buyers recommend it', 'ultra-max-core' ), Format::percent( $stats['recommend'] ) );
	}

	/**
	 * Adds the recommend share to the product heading.
	 *
	 * @param mixed $props   Heading props.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function heading( mixed $props, mixed $product ): mixed {
		if ( is_array( $props ) && $product instanceof \WC_Product && self::open( $product ) ) {
			$text = self::recommend_text( $product );
			if ( '' !== $text ) {
				$props['recommend'] = $text;
			}
		}
		return $props;
	}

	/**
	 * The list choices of the current request.
	 *
	 * @param array<string, mixed>|null $source Query values; the request when null.
	 * @return array{sort: string, filter: string, page: int}
	 */
	public static function state( ?array $source = null ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list choices.
		$source = $source ?? $_GET;
		$sort   = is_string( $source['review_sort'] ?? null ) ? sanitize_key( $source['review_sort'] ) : '';
		$filter = is_string( $source['review_filter'] ?? null ) ? sanitize_key( $source['review_filter'] ) : '';
		$page   = is_numeric( $source['review_page'] ?? null ) ? max( 1, (int) $source['review_page'] ) : 1;
		return array(
			'sort'   => in_array( $sort, array( 'helpful', 'newest', 'high', 'low' ), true ) ? $sort : 'helpful',
			'filter' => in_array( $filter, array( 'all', 'buyers', 'photos', '1', '2', '3', '4', '5' ), true ) ? $filter : 'all',
			'page'   => min( 200, $page ),
		);
	}

	/**
	 * Query arguments of a list state.
	 *
	 * @param array{sort: string, filter: string, page: int} $state State.
	 * @return array<string, mixed>
	 */
	public static function query_args( array $state ): array {
		$meta = array();
		if ( 'buyers' === $state['filter'] ) {
			$meta[] = array(
				'key'   => 'verified',
				'value' => '1',
			);
		} elseif ( 'photos' === $state['filter'] ) {
			$meta[] = array(
				'key'     => self::IMAGES,
				'compare' => 'EXISTS',
			);
		} elseif ( is_numeric( $state['filter'] ) ) {
			$meta[] = array(
				'key'   => 'rating',
				'value' => $state['filter'],
			);
		}
		$args = array();
		switch ( $state['sort'] ) {
			case 'helpful':
				$meta['votes']   = array(
					'relation' => 'OR',
					'up'       => array(
						'key'     => self::UP,
						'type'    => 'NUMERIC',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => self::UP,
						'compare' => 'NOT EXISTS',
					),
				);
				$args['orderby'] = array(
					'up'               => 'DESC',
					'comment_date_gmt' => 'DESC',
				);
				break;
			case 'high':
			case 'low':
				$meta['stars']   = array(
					'key'  => 'rating',
					'type' => 'NUMERIC',
				);
				$args['orderby'] = array(
					'stars'            => 'high' === $state['sort'] ? 'DESC' : 'ASC',
					'comment_date_gmt' => 'DESC',
				);
				break;
			default:
				$args['orderby'] = 'comment_date_gmt';
				$args['order']   = 'DESC';
		}
		if ( array() !== $meta ) {
			$args['meta_query'] = array_merge( array( 'relation' => 'AND' ), $meta ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Comments of one product.
		}
		return $args;
	}

	/**
	 * One page of reviews and the total for a state.
	 *
	 * @param \WC_Product                                    $product Product.
	 * @param array{sort: string, filter: string, page: int} $state   State.
	 * @return array{items: list<\WP_Comment>, total: int}
	 */
	public static function page( \WC_Product $product, array $state ): array {
		$args  = self::query_args( $state );
		$per   = max( 3, Store::int( 'product_review_per_page' ) );
		$total = self::query(
			array_merge(
				$args,
				array(
					'post_id'  => $product->get_id(),
					'status'   => 'approve',
					'type__in' => array( 'review', 'comment' ),
					'parent'   => 0,
					'count'    => true,
				)
			)
		);
		$items = self::comments(
			$product->get_id(),
			array_merge(
				$args,
				array(
					'number' => $per,
					'offset' => ( $state['page'] - 1 ) * $per,
				)
			)
		);
		return array(
			'items' => $items,
			'total' => is_int( $total ) ? $total : count( $total ),
		);
	}

	/**
	 * The props of one review.
	 *
	 * @param \WP_Comment $comment Review.
	 * @return ReviewItem
	 */
	public static function item( \WP_Comment $comment ): array {
		$id     = (int) $comment->comment_ID;
		$rating = max( 0, min( 5, self::meta_int( $id, 'rating' ) ) );
		$time   = (int) strtotime( $comment->comment_date_gmt . ' UTC' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own comment filter.
		$text  = (string) apply_filters( 'comment_text', get_comment_text( $comment ), $comment, array() );
		$item  = array(
			'id'           => $id,
			'author'       => '' === trim( $comment->comment_author ) ? __( 'Shopper', 'ultra-max-core' ) : $comment->comment_author,
			'date'         => Format::date( $time ),
			'datetime'     => gmdate( 'c', $time ),
			'rating'       => $rating,
			'rating_text'  => Format::digits( number_format( $rating, 1, Format::persian() ? '٫' : '.', '' ) ),
			/* translators: %s: rating. */
			'rating_label' => sprintf( __( 'Rated %s out of 5', 'ultra-max-core' ), Format::digits( (string) $rating ) ),
			'text'         => Html::wp( $text ),
			'votes'        => array(
				'up'   => Format::number( self::meta_int( $id, self::UP ) ),
				'down' => Format::number( self::meta_int( $id, self::DOWN ) ),
			),
		);
		$title = get_comment_meta( $id, self::TITLE, true );
		if ( is_string( $title ) && '' !== $title ) {
			$item['title'] = $title;
		}
		if ( 1 === self::meta_int( $id, 'verified' ) ) {
			$item['buyer'] = __( 'Buyer', 'ultra-max-core' );
		}
		foreach ( array(
			'pros' => self::PROS,
			'cons' => self::CONS,
		) as $key => $meta ) {
			$lines = self::meta_list( $id, $meta );
			if ( array() !== $lines ) {
				$item[ $key ] = $lines;
			}
		}
		$recommend = get_comment_meta( $id, self::RECOMMEND, true );
		$labels    = array(
			'yes'    => __( 'I recommend buying it', 'ultra-max-core' ),
			'no'     => __( 'I do not recommend it', 'ultra-max-core' ),
			'unsure' => __( 'I am not sure about buying it', 'ultra-max-core' ),
		);
		if ( is_string( $recommend ) && isset( $labels[ $recommend ] ) ) {
			$item['recommend'] = array(
				'label' => $labels[ $recommend ],
				'tone'  => $recommend,
			);
		}
		$images = array();
		foreach ( self::meta_list( $id, self::IMAGES ) as $image ) {
			$full = wp_get_attachment_image_url( (int) $image, 'large' );
			if ( is_string( $full ) ) {
				$images[] = array(
					'thumb' => Html::wp(
						wp_get_attachment_image(
							(int) $image,
							'thumbnail',
							false,
							array(
								'loading' => 'lazy',
								/* translators: %s: author name. */
								'alt'     => sprintf( __( 'Photo by %s', 'ultra-max-core' ), $item['author'] ),
							)
						)
					),
					'full'  => $full,
				);
			}
		}
		if ( array() !== $images ) {
			$item['images'] = $images;
		}
		$replies = array();
		foreach ( get_comments(
			array(
				'parent' => $id,
				'status' => 'approve',
				'order'  => 'ASC',
			)
		) as $reply ) {
			if ( $reply instanceof \WP_Comment ) {
				$staff     = (int) $reply->user_id > 0 && user_can( (int) $reply->user_id, 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce's own capability.
				$replies[] = array(
					'author' => $staff ? get_bloginfo( 'name' ) : $reply->comment_author,
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own comment filter.
					'text'   => Html::wp( (string) apply_filters( 'comment_text', get_comment_text( $reply ), $reply, array() ) ),
				);
			}
		}
		if ( array() !== $replies ) {
			$item['replies'] = $replies;
		}
		return $item;
	}

	/**
	 * The address of a list state.
	 *
	 * @param \WC_Product          $product Product.
	 * @param array<string, mixed> $state   State values to set.
	 */
	public static function link( \WC_Product $product, array $state ): string {
		$args = array();
		foreach ( array( 'sort', 'filter', 'page' ) as $key ) {
			$value = $state[ $key ] ?? null;
			$base  = array(
				'sort'   => 'helpful',
				'filter' => 'all',
				'page'   => 1,
			);
			if ( null !== $value && $base[ $key ] !== $value ) {
				$args[ 'review_' . $key ] = $value;
			}
		}
		return add_query_arg( $args, (string) $product->get_permalink() ) . '#reviews';
	}

	/**
	 * Texts of the list.
	 *
	 * @return array{filters: string, sort: string, helpful: string, up: string, down: string, pros: string, cons: string, reply: string, empty: string, pending: string}
	 */
	private static function labels(): array {
		return array(
			'filters' => __( 'Show reviews of', 'ultra-max-core' ),
			'sort'    => __( 'Sort reviews', 'ultra-max-core' ),
			'helpful' => __( 'Was this helpful?', 'ultra-max-core' ),
			'up'      => __( 'Yes, helpful', 'ultra-max-core' ),
			'down'    => __( 'Not helpful', 'ultra-max-core' ),
			'pros'    => __( 'Strength', 'ultra-max-core' ),
			'cons'    => __( 'Weakness', 'ultra-max-core' ),
			'reply'   => __( 'Store\'s reply', 'ultra-max-core' ),
			'empty'   => __( 'No review matches this choice yet.', 'ultra-max-core' ),
			'pending' => __( 'Waiting for approval', 'ultra-max-core' ),
		);
	}

	/**
	 * The "show more" link of a state, or null on the last page.
	 *
	 * @param \WC_Product                                    $product Product.
	 * @param array{sort: string, filter: string, page: int} $state   State.
	 * @param int                                            $total   Reviews in the state.
	 * @return array{label: string, href: string}|null
	 */
	private static function more( \WC_Product $product, array $state, int $total ): ?array {
		$per = max( 3, Store::int( 'product_review_per_page' ) );
		if ( $state['page'] * $per >= $total ) {
			return null;
		}
		return array(
			/* translators: %s: number of reviews left. */
			'label' => sprintf( __( 'Show more reviews (%s more)', 'ultra-max-core' ), Format::number( $total - $state['page'] * $per ) ),
			'href'  => self::link( $product, array_merge( $state, array( 'page' => $state['page'] + 1 ) ) ),
		);
	}

	/**
	 * The list of a state.
	 *
	 * @param \WC_Product                                         $product Product.
	 * @param array{sort: string, filter: string, page: int}|null $state   State; the request's when null.
	 */
	public static function list( \WC_Product $product, ?array $state = null ): Html {
		$state   = $state ?? self::state();
		$found   = self::page( $product, $state );
		$filters = array();
		foreach ( array(
			'all'    => __( 'All', 'ultra-max-core' ),
			'buyers' => __( 'Buyers', 'ultra-max-core' ),
			'photos' => __( 'With photos', 'ultra-max-core' ),
		) as $value => $label ) {
			$filters[] = array(
				'label'   => $label,
				'value'   => $value,
				'href'    => self::link(
					$product,
					array_merge(
						$state,
						array(
							'filter' => $value,
							'page'   => 1,
						)
					)
				),
				'current' => $value === $state['filter'],
			);
		}
		$sorts = array();
		foreach ( array(
			'helpful' => __( 'Most helpful', 'ultra-max-core' ),
			'newest'  => __( 'Newest', 'ultra-max-core' ),
			'high'    => __( 'Highest rated', 'ultra-max-core' ),
			'low'     => __( 'Lowest rated', 'ultra-max-core' ),
		) as $value => $label ) {
			$sorts[] = array(
				'label'   => $label,
				'value'   => $value,
				'href'    => self::link(
					$product,
					array_merge(
						$state,
						array(
							'sort' => $value,
							'page' => 1,
						)
					)
				),
				'current' => $value === $state['sort'],
			);
		}
		$props = array(
			'id'       => 'um-reviews-' . $product->get_id(),
			'product'  => $product->get_id(),
			'items'    => array_map( array( self::class, 'item' ), $found['items'] ),
			'labels'   => self::labels(),
			'filters'  => $filters,
			'sorts'    => $sorts,
			'endpoint' => rest_url( AdminRest::NAMESPACE . '/reviews' ),
			'vote'     => rest_url( AdminRest::NAMESPACE . '/reviews/%d/vote' ),
		);
		$more  = self::more( $product, $state, $found['total'] );
		if ( null !== $more ) {
			$props['more'] = $more;
		}
		return ReviewList::render( $props );
	}

	/**
	 * The summary column.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function summary( \WC_Product $product ): Html {
		$stats = self::stats( $product );
		$state = self::state();
		$bars  = array();
		for ( $star = 5; $star >= 1; $star-- ) {
			$count  = $stats['bars'][ $star ] ?? 0;
			$bars[] = array(
				/* translators: %s: number of stars. */
				'label' => sprintf( _n( '%s star', '%s stars', $star, 'ultra-max-core' ), Format::number( $star ) ),
				'share' => $stats['count'] > 0 ? (int) round( $count / $stats['count'] * 100 ) : 0,
				'count' => Format::number( $count ),
				'href'  => self::link(
					$product,
					array_merge(
						$state,
						array(
							'filter' => (string) $star,
							'page'   => 1,
						)
					)
				),
			);
		}
		$criteria = array();
		foreach ( $stats['criteria'] as $label => $value ) {
			$criteria[] = array(
				'label' => $label,
				'value' => Format::digits( number_format( $value, 1, Format::persian() ? '٫' : '.', '' ) ),
				'share' => (int) round( $value / 5 * 100 ),
			);
		}
		$score     = Format::digits( number_format( $stats['average'], 1, Format::persian() ? '٫' : '.', '' ) );
		$props     = array(
			'score'    => $score,
			'value'    => $stats['average'],
			'out_of'   => __( 'out of 5', 'ultra-max-core' ),
			/* translators: %s: rating such as 4.5. */
			'label'    => sprintf( __( 'Rated %s out of 5', 'ultra-max-core' ), $score ),
			/* translators: %s: number of ratings. */
			'count'    => sprintf( _n( 'From %s rating', 'From %s ratings', $stats['count'], 'ultra-max-core' ), Format::number( $stats['count'] ) ),
			'bars'     => $bars,
			'criteria' => $criteria,
			'write'    => array(
				'label' => __( 'Write a review', 'ultra-max-core' ),
				'href'  => '#um-review-form',
				'note'  => __( 'Tell other shoppers what you think of this product.', 'ultra-max-core' ),
			),
		);
		$recommend = self::recommend_text( $product );
		if ( '' !== $recommend ) {
			$props['recommend'] = $recommend;
		}
		$photos = array();
		foreach ( array_slice( array_reverse( $stats['photos'] ), 0, 4 ) as $image ) {
			$full = wp_get_attachment_image_url( $image, 'large' );
			if ( is_string( $full ) ) {
				$photos[] = array(
					'thumb' => Html::wp(
						wp_get_attachment_image(
							$image,
							'thumbnail',
							false,
							array(
								'loading' => 'lazy',
								'alt'     => '',
							)
						)
					),
					'full'  => $full,
				);
			}
		}
		if ( array() !== $photos ) {
			$props['photos']       = $photos;
			$props['photos_label'] = __( 'Shoppers\' photos', 'ultra-max-core' );
			if ( count( $stats['photos'] ) > 4 ) {
				$props['more_photos'] = '+' . Format::number( count( $stats['photos'] ) - 4 );
			}
		}
		return ReviewSummary::render( $props );
	}

	/**
	 * The review form, or why the shopper cannot review.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function form( \WC_Product $product ): Html {
		if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
			return Alert::render(
				array(
					'tone'     => 'info',
					'children' => Html::trusted(
						sprintf(
							'%1$s <a href="%2$s">%3$s</a>',
							esc_html__( 'Log in to write a review.', 'ultra-max-core' ),
							esc_url( wp_login_url( (string) $product->get_permalink() . '#reviews' ) ),
							esc_html__( 'Log in', 'ultra-max-core' )
						)
					),
				)
			);
		}
		$user = wp_get_current_user();
		if ( 'yes' === get_option( 'woocommerce_review_rating_verification_required' ) && ! wc_customer_bought_product( $user->user_email, $user->ID, $product->get_id() ) ) {
			return Alert::render(
				array(
					'tone'     => 'info',
					'children' => __( 'Only shoppers who bought this product can review it.', 'ultra-max-core' ),
				)
			);
		}
		$criteria = array();
		foreach ( self::criteria() as $index => $label ) {
			$criteria[] = array(
				'name'  => 'um_criterion_' . $index,
				'label' => $label,
			);
		}
		ob_start();
		wp_nonce_field( self::NONCE, self::NONCE, false );
		/** This action is documented in wp-includes/comment-template.php */
		do_action( 'comment_form', $product->get_id() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress's own hook, for spam protection plugins.
		$extra = (string) ob_get_clean();
		$props = array(
			'id'        => 'um-review-form',
			'action'    => site_url( '/wp-comments-post.php' ),
			'summary'   => __( 'Write a review', 'ultra-max-core' ),
			'rating'    => array(
				'legend'   => __( 'Your rating', 'ultra-max-core' ),
				'labels'   => array( __( 'Very bad', 'ultra-max-core' ), __( 'Bad', 'ultra-max-core' ), __( 'Fair', 'ultra-max-core' ), __( 'Good', 'ultra-max-core' ), __( 'Excellent', 'ultra-max-core' ) ),
				'required' => wc_review_ratings_required(),
			),
			'labels'    => array(
				'title'     => __( 'Title', 'ultra-max-core' ),
				'text'      => __( 'Your review', 'ultra-max-core' ),
				'pros'      => __( 'Strengths', 'ultra-max-core' ),
				'cons'      => __( 'Weaknesses', 'ultra-max-core' ),
				'lines'     => __( 'One per line.', 'ultra-max-core' ),
				'recommend' => __( 'Would you recommend it?', 'ultra-max-core' ),
				'submit'    => __( 'Send review', 'ultra-max-core' ),
			),
			'recommend' => array(
				'yes'    => __( 'Yes', 'ultra-max-core' ),
				'unsure' => __( 'Not sure', 'ultra-max-core' ),
				'no'     => __( 'No', 'ultra-max-core' ),
			),
			'hidden'    => array(
				'comment_post_ID' => (string) $product->get_id(),
				'comment_parent'  => '0',
				'um_review'       => '1',
			),
			'criteria'  => $criteria,
			'extra'     => Html::wp( $extra ),
		);
		if ( ! is_user_logged_in() ) {
			$commenter      = wp_get_current_commenter();
			$props['guest'] = array(
				'name'        => __( 'Name', 'ultra-max-core' ),
				'email'       => __( 'Email', 'ultra-max-core' ),
				'name_value'  => $commenter['comment_author'],
				'email_value' => $commenter['comment_author_email'],
				'required'    => (bool) get_option( 'require_name_email' ),
			);
		} elseif ( Store::bool( 'product_review_images' ) ) {
			$props['images'] = array(
				'label' => __( 'Photos', 'ultra-max-core' ),
				'help'  => __( 'Up to four JPEG, PNG or WebP pictures, three megabytes each.', 'ultra-max-core' ),
				'max'   => 4,
			);
		}
		return ReviewForm::render( $props );
	}

	/**
	 * The whole reviews section: summary beside the list, then the form.
	 *
	 * @param \WC_Product $product Product.
	 */
	public static function section( \WC_Product $product ): Html {
		if ( ! self::open( $product ) ) {
			return Html::none();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only shows a thank-you note.
		$sent = isset( $_GET['um_review_sent'] ) ? Alert::render(
			array(
				'tone'     => 'success',
				'children' => __( 'Thank you. Your review shows here once it is approved.', 'ultra-max-core' ),
			)
		) : Html::none();
		return Html::trusted(
			sprintf(
				'<div class="grid gap-4">%1$s<div class="grid items-start gap-8 max-lg:gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">%2$s<div class="grid min-w-0 gap-2">%3$s%4$s</div></div></div>',
				$sent,
				self::summary( $product ),
				self::list( $product ),
				self::form( $product )
			)
		);
	}

	/**
	 * Adds the reviews to the product page's sections.
	 *
	 * @param mixed $items   Sections.
	 * @param mixed $product Product.
	 * @return mixed
	 */
	public static function section_item( mixed $items, mixed $product ): mixed {
		if ( ! is_array( $items ) || ! $product instanceof \WC_Product || ! self::open( $product ) ) {
			return $items;
		}
		$count   = $product->get_review_count();
		$items[] = array(
			'id'      => 'reviews',
			'title'   => __( 'Reviews', 'ultra-max-core' ),
			'content' => self::section( $product ),
			'count'   => $count > 0 ? Format::number( $count ) : '',
		);
		return $items;
	}

	/**
	 * Review lists sorted or filtered are not for search engines.
	 *
	 * @param mixed $robots Directives.
	 * @return mixed
	 */
	public static function robots( mixed $robots ): mixed {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only.
		if ( is_array( $robots ) && function_exists( 'is_product' ) && is_product() && ( isset( $_GET['review_sort'] ) || isset( $_GET['review_filter'] ) || isset( $_GET['review_page'] ) ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}
		return $robots;
	}

	/**
	 * The REST routes: the list in place and votes.
	 */
	public static function routes(): void {
		register_rest_route(
			AdminRest::NAMESPACE,
			'/reviews',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_list' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'product' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			AdminRest::NAMESPACE,
			'/reviews/(?P<id>\d+)/vote',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'rest_vote' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * A public product that takes reviews, or null.
	 *
	 * @param int $id Product id.
	 */
	private static function public_product( int $id ): ?\WC_Product {
		$product = wc_get_product( $id );
		if ( ! $product instanceof \WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() || ! self::open( $product ) ) {
			return null;
		}
		return '' === (string) get_post_field( 'post_password', $product->get_id() ) ? $product : null;
	}

	/**
	 * A whole number from a review's meta.
	 *
	 * @param int    $comment_id Comment id.
	 * @param string $key        Meta key.
	 */
	private static function meta_int( int $comment_id, string $key ): int {
		$value = get_comment_meta( $comment_id, $key, true );
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Runs a comment query.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return list<\WP_Comment>|int
	 */
	private static function query( array $args ): array|int {
		$found = ( new \WP_Comment_Query() )->query( $args );
		if ( is_numeric( $found ) ) {
			return (int) $found;
		}
		return is_array( $found ) ? array_values( array_filter( $found, static fn ( mixed $item ): bool => $item instanceof \WP_Comment ) ) : array();
	}

	/**
	 * GET /reviews: the list of a state, or its next reviews.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function rest_list( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id      = $request->get_param( 'product' );
		$product = self::public_product( is_numeric( $id ) ? (int) $id : 0 );
		if ( null === $product ) {
			return new \WP_Error( 'ultra_max_no_product', __( 'This product could not be found.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		$state = self::state( $request->get_params() );
		$found = self::page( $product, $state );
		return new \WP_REST_Response(
			array(
				'list'  => (string) self::list( $product, $state ),
				'items' => ReviewList::items( array_map( array( self::class, 'item' ), $found['items'] ), self::labels() ),
				'more'  => ReviewList::more( self::more( $product, $state, $found['total'] ) ),
			)
		);
	}

	/**
	 * Who is voting: the user, or a hash of the address for guests.
	 */
	private static function voter(): string {
		$user = get_current_user_id();
		if ( $user > 0 ) {
			return 'u' . $user;
		}
		$address = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return 'h' . substr( hash_hmac( 'sha256', $address, wp_salt( 'nonce' ) ), 0, 16 );
	}

	/**
	 * POST /reviews/{id}/vote: one helpful vote per shopper and review.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public static function rest_vote( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$id      = $request->get_param( 'id' );
		$comment = get_comment( is_numeric( $id ) ? (int) $id : 0 );
		$value   = 'down' === $request->get_param( 'value' ) ? self::DOWN : self::UP;
		if ( ! $comment instanceof \WP_Comment || '1' !== $comment->comment_approved || null === self::public_product( (int) $comment->comment_post_ID ) ) {
			return new \WP_Error( 'ultra_max_no_review', __( 'This review could not be found.', 'ultra-max-core' ), array( 'status' => 404 ) );
		}
		$voter = self::voter();
		$limit = 'um_votes_' . md5( $voter );
		$used  = get_transient( $limit );
		$used  = is_numeric( $used ) ? (int) $used : 0;
		if ( $used >= 60 ) {
			return new \WP_Error( 'ultra_max_votes', __( 'Too many votes. Please try again later.', 'ultra-max-core' ), array( 'status' => 429 ) );
		}
		$comment_id = (int) $comment->comment_ID;
		$voters     = get_comment_meta( $comment_id, self::VOTERS, true );
		$voters     = is_array( $voters ) ? $voters : array();
		if ( ! isset( $voters[ $voter ] ) ) {
			$voters[ $voter ] = self::UP === $value ? 1 : -1;
			update_comment_meta( $comment_id, self::VOTERS, array_slice( $voters, -2000, null, true ) );
			update_comment_meta( $comment_id, $value, self::meta_int( $comment_id, $value ) + 1 );
			set_transient( $limit, $used + 1, HOUR_IN_SECONDS );
		}
		return new \WP_REST_Response(
			array(
				'up'   => Format::number( self::meta_int( $comment_id, self::UP ) ),
				'down' => Format::number( self::meta_int( $comment_id, self::DOWN ) ),
			)
		);
	}
}
