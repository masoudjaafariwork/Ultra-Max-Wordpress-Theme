<?php
/**
 * Comments template.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-area">
	<?php if ( have_comments() ) : ?>
		<h2><?php echo esc_html( get_comments_number_text() ); ?></h2>
		<ol class="comment-list">
			<?php wp_list_comments( array( 'style' => 'ol' ) ); ?>
		</ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
