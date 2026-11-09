<?php
/**
 * Fallback template. Phase 2 adds the real templates.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

get_header();
?>
<div class="mx-auto max-w-(--um-container) px-4 py-6">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php
				if ( is_singular() ) {
					the_title( '<h1>', '</h1>' );
				} else {
					the_title( sprintf( '<h2><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' );
				}
				the_content();
				wp_link_pages();
				?>
			</article>
			<?php
			if ( is_singular() && ( comments_open() || get_comments_number() ) ) {
				comments_template();
			}
		endwhile;
		the_posts_pagination();
		?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing was found.', 'ultra-max' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
