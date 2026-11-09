<?php
/**
 * Comments component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One comment of a discussion, and the arguments that make WordPress's
 * comment form use our Field and Button.
 *
 * WordPress walks the comment tree; the theme's `wp_list_comments()`
 * callback prints `Comments::item()` for each comment and WordPress closes
 * the `li`.
 *
 * @phpstan-type CommentProps array{
 *     id: int,
 *     author: string,
 *     author_url?: string,
 *     avatar?: string,
 *     date: string,
 *     datetime: string,
 *     content: Html,
 *     reply?: Html,
 *     edit_url?: string,
 *     pending?: bool,
 *     by_post_author?: bool,
 *     classes?: string,
 * }
 */
final class Comments {

	/**
	 * Opens one comment: the `li` and its article. WordPress closes the `li`.
	 *
	 * @param array $props Props.
	 * @phpstan-param CommentProps $props
	 */
	public static function item( array $props ): Html {
		$author = esc_html( $props['author'] );
		if ( isset( $props['author_url'] ) && '' !== $props['author_url'] ) {
			$author = sprintf( '<a href="%s" rel="external nofollow ugc" class="text-fg no-underline hover:text-primary">%s</a>', esc_url( $props['author_url'] ), $author );
		}
		$avatar = isset( $props['avatar'] ) && '' !== $props['avatar']
			? sprintf( '<img src="%s" alt="" width="44" height="44" loading="lazy" decoding="async" class="size-11 shrink-0 rounded-pill bg-surface-3">', esc_url( $props['avatar'] ) )
			: sprintf( '<span class="grid size-11 shrink-0 place-items-center rounded-pill bg-primary-soft text-primary">%s</span>', Icon::render( 'user', 'size-5' ) );
		$badge  = ( $props['by_post_author'] ?? false ) ? Badge::render(
			array(
				'children' => __( 'Author', 'ultra-max-ui' ),
				'tone'     => 'brand',
			)
		) : '';
		$notice = ( $props['pending'] ?? false ) ? Alert::render(
			array(
				'tone'     => 'info',
				'children' => __( 'Your comment is awaiting moderation.', 'ultra-max-ui' ),
				'class'    => 'mt-2',
			)
		) : '';
		$edit   = isset( $props['edit_url'] ) ? sprintf( '<a href="%s" class="text-xs text-fg-3 hover:text-primary">%s</a>', esc_url( $props['edit_url'] ), esc_html__( 'Edit', 'ultra-max-ui' ) ) : '';

		return Html::trusted(
			sprintf(
				'<li id="comment-%1$d" class="%2$s"><article class="flex gap-3 border-b border-border py-5"><div>%3$s</div><div class="grid min-w-0 flex-1 gap-2"><header class="flex flex-wrap items-center gap-x-3 gap-y-1"><b class="text-sm">%4$s</b>%5$s<time datetime="%6$s" class="text-xs text-fg-3">%7$s</time>%8$s</header>%9$s<div class="um-prose text-md">%10$s</div><div class="um-comment-actions">%11$s</div></div></article>',
				$props['id'],
				esc_attr( Attributes::classes( 'um-comment list-none', $props['classes'] ?? null ) ),
				$avatar,
				$author,
				$badge,
				esc_attr( $props['datetime'] ),
				esc_html( $props['date'] ),
				$edit,
				$notice,
				$props['content'],
				Html::from( $props['reply'] ?? null )
			)
		);
	}

	/**
	 * One field of the comment form.
	 *
	 * @param string                          $id           Field id and name.
	 * @param string                          $label        Label.
	 * @param 'text'|'email'|'url'|'textarea' $type         Field type.
	 * @param string                          $value        Saved value.
	 * @param bool                            $required     Whether the field is required.
	 * @param string                          $autocomplete Autocomplete hint.
	 */
	private static function field( string $id, string $label, string $type, string $value, bool $required, string $autocomplete ): string {
		return (string) Field::render(
			array(
				'id'           => $id,
				'name'         => $id,
				'label'        => $label,
				'type'         => $type,
				'value'        => $value,
				'required'     => $required,
				'autocomplete' => $autocomplete,
				'dir'          => 'text' === $type ? 'auto' : 'ltr',
			)
		);
	}

	/**
	 * Arguments for `comment_form()`.
	 *
	 * @param array{author: string, email: string, url: string} $commenter Current commenter's saved values.
	 * @param bool                                              $required  Whether name and email are required.
	 * @return array<string, mixed>
	 */
	public static function form_args( array $commenter, bool $required ): array {
		return array(
			'class_container'      => 'um-comment-respond mt-8',
			'class_form'           => 'grid gap-4 sm:grid-cols-2 [&>.comment-form-comment]:col-span-full [&>.form-submit]:col-span-full [&>.comment-notes]:col-span-full [&>.logged-in-as]:col-span-full [&>.comment-form-cookies-consent]:col-span-full',
			'title_reply_before'   => '<h2 id="reply-title" class="m-0 mb-4 text-xl font-heavy">',
			'title_reply_after'    => '</h2>',
			'comment_notes_before' => sprintf( '<p class="comment-notes m-0 text-sm text-fg-3">%s</p>', esc_html__( 'Your email address will not be published.', 'ultra-max-ui' ) ),
			'fields'               => array(
				'author'  => self::field( 'author', __( 'Name', 'ultra-max-ui' ), 'text', $commenter['author'], $required, 'name' ),
				'email'   => self::field( 'email', __( 'Email', 'ultra-max-ui' ), 'email', $commenter['email'], $required, 'email' ),
				'url'     => self::field( 'url', __( 'Website', 'ultra-max-ui' ), 'url', $commenter['url'], false, 'url' ),
				'cookies' => sprintf( '<p class="comment-form-cookies-consent m-0 flex items-center gap-2 text-sm text-fg-2"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes" class="size-4 accent-primary"%s><label for="wp-comment-cookies-consent">%s</label></p>', '' !== $commenter['email'] ? ' checked' : '', esc_html__( 'Save my name and email in this browser for the next time I comment.', 'ultra-max-ui' ) ),
			),
			'comment_field'        => '<div class="comment-form-comment">' . self::field( 'comment', __( 'Comment', 'ultra-max-ui' ), 'textarea', '', true, 'off' ) . '</div>',
			'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s inline-flex min-h-11 cursor-pointer items-center justify-center rounded-md border border-transparent bg-primary px-6 text-md font-bold text-on-primary hover:bg-primary-hover focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary/40">%4$s</button>',
			'submit_field'         => '<p class="form-submit m-0">%1$s %2$s</p>',
		);
	}
}
