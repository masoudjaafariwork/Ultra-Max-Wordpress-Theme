<?php
/**
 * A single post.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Content;
use UltraMax\Theme\Template;

get_header();
Template::main_open();

while ( have_posts() ) {
	the_post();
	$ultra_max_post = get_post();
	if ( ! $ultra_max_post instanceof WP_Post ) {
		continue;
	}
	Template::page_header( get_the_title(), '', Template::post_meta( $ultra_max_post, true ), true );
	Template::layout_open();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'min-w-0' ); ?>>
		<?php
		Content::featured_image();
		Content::body();
		Content::tags();
		?>
	</article>
	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
	Template::layout_close();
}

Template::main_close();
get_footer();
