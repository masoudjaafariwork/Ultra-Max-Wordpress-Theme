<?php
/**
 * The discussion of a post or page.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Content;
use UltraMax\Theme\Lib\UI\Comments;
use UltraMax\Theme\Lib\UI\UI;

if ( post_password_required() ) {
	return;
}

$ultra_max_count = (int) get_comments_number();
?>
<section id="comments" class="um-comments mt-10">
	<?php if ( have_comments() ) : ?>
		<h2 class="m-0">
			<?php
			/* translators: %s: number of comments. */
			echo esc_html( sprintf( _n( '%s comment', '%s comments', $ultra_max_count, 'ultra-max' ), number_format_i18n( $ultra_max_count ) ) );
			?>
		</h2>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'callback'    => array( Content::class, 'comment' ),
					'avatar_size' => 44,
				)
			);
			?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php
	if ( ! comments_open() && $ultra_max_count > 0 ) {
		UI::alert(
			array(
				'children' => __( 'Comments are closed.', 'ultra-max' ),
				'class'    => 'mt-6',
			)
		)->print();
	}
	comment_form( Comments::form_args( wp_get_current_commenter(), (bool) get_option( 'require_name_email' ) ) );
	?>
</section>
