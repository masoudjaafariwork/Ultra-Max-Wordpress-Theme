<?php
/**
 * Content of posts, pages and comments.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\UI\Html;
use UltraMax\Theme\Lib\UI\UI;

/**
 * Prints the parts of a single post or page and its discussion.
 */
final class Content {

	/**
	 * The current post's content inside Prose, with its page links.
	 */
	public static function body(): void {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
		$content = (string) apply_filters( 'the_content', get_the_content() );
		$content = str_replace( ']]>', ']]&gt;', $content );
		$pages   = wp_link_pages(
			array(
				'before' => '<nav class="post-page-links"><p>' . esc_html__( 'Pages:', 'ultra-max' ) . ' ',
				'after'  => '</p></nav>',
				'echo'   => false,
			)
		);
		UI::prose( array( 'children' => Html::wp( $content . $pages ) ) )->print();
	}

	/**
	 * The featured image at the top of a post or page.
	 */
	public static function featured_image(): void {
		$id = (int) get_post_thumbnail_id();
		if ( $id <= 0 ) {
			return;
		}
		UI::media(
			array(
				'image_id' => $id,
				'priority' => true,
				'caption'  => wp_strip_all_tags( (string) wp_get_attachment_caption( $id ) ),
				'class'    => 'mb-6',
			)
		)->print();
	}

	/**
	 * The tags of the current post.
	 */
	public static function tags(): void {
		$tags = get_the_tags();
		if ( ! is_array( $tags ) || array() === $tags ) {
			return;
		}
		$items = array();
		foreach ( $tags as $tag ) {
			$link    = get_tag_link( $tag );
			$items[] = array(
				'icon'  => 'tag',
				'label' => $tag->name,
				'href'  => $link,
			);
		}
		UI::post_meta(
			array(
				'items' => $items,
				'class' => 'mt-8',
			)
		)->print();
	}

	/**
	 * Prints one comment for `wp_list_comments()`; WordPress closes the `li`.
	 *
	 * @param \WP_Comment $comment Comment.
	 * @param array       $args    List arguments.
	 * @param int         $depth   Depth.
	 * @phpstan-param array<string, mixed> $args
	 */
	public static function comment( \WP_Comment $comment, array $args, int $depth ): void {
		$reply = get_comment_reply_link(
			array(
				'depth'     => $depth,
				'max_depth' => is_numeric( $args['max_depth'] ?? null ) ? (int) $args['max_depth'] : 5,
			),
			$comment
		);
		$post  = get_post( (int) $comment->comment_post_ID );
		$props = array(
			'id'             => (int) $comment->comment_ID,
			'author'         => get_comment_author( $comment ),
			'author_url'     => get_comment_author_url( $comment ),
			'avatar'         => (string) get_avatar_url( $comment, array( 'size' => 88 ) ),
			'date'           => get_comment_date( '', $comment ),
			'datetime'       => get_comment_date( 'c', $comment ),
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core filter.
			'content'        => Html::wp( (string) apply_filters( 'comment_text', get_comment_text( $comment ), $comment, $args ) ),
			'reply'          => Html::wp( is_string( $reply ) ? $reply : '' ),
			'pending'        => '0' === $comment->comment_approved,
			'by_post_author' => $post instanceof \WP_Post && (int) $comment->user_id > 0 && (int) $comment->user_id === (int) $post->post_author,
			'classes'        => implode( ' ', get_comment_class( '', $comment ) ),
		);
		if ( current_user_can( 'edit_comment', (int) $comment->comment_ID ) ) {
			$props['edit_url'] = (string) get_edit_comment_link( $comment );
		}
		UI::comment( $props )->print();
	}
}
