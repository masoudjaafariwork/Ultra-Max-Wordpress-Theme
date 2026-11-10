<?php
/**
 * Lists of posts: the blog, archives and search results.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Template;
use UltraMax\Theme\Lib\UI\Html;
use UltraMax\Theme\Lib\UI\UI;

get_header();
Template::main_open();

if ( Template::any( Template::location( 'archive' ) ) ) {

	if ( is_search() ) {
		/* translators: %s: search terms. */
		$ultra_max_title       = sprintf( __( 'Search results for "%s"', 'ultra-max' ), get_search_query() );
		$ultra_max_description = '';
	} elseif ( is_home() ) {
		$ultra_max_page        = (int) get_option( 'page_for_posts' );
		$ultra_max_title       = $ultra_max_page > 0 ? get_the_title( $ultra_max_page ) : __( 'Blog', 'ultra-max' );
		$ultra_max_description = '';
	} else {
		$ultra_max_title       = wp_strip_all_tags( get_the_archive_title() );
		$ultra_max_description = Html::wp( get_the_archive_description() );
	}
	Template::page_header( $ultra_max_title, $ultra_max_description );
	Template::layout_open();

	if ( have_posts() ) {
		$ultra_max_index = 0;
		echo is_search() ? '<div class="grid gap-4">' : '<div class="grid gap-6 sm:grid-cols-2' . ( 'none' === Template::sidebar_position() ? ' xl:grid-cols-3' : '' ) . '">';
		while ( have_posts() ) {
			the_post();
			Template::post_card( $ultra_max_index++ )->print();
		}
		echo '</div>';
		Template::pagination();
	} else {
		UI::empty_state(
			array(
				'icon'     => 'search',
				'title'    => is_search() ? __( 'Nothing matched your search', 'ultra-max' ) : __( 'Nothing has been published here yet', 'ultra-max' ),
				'text'     => is_search() ? __( 'Check the spelling or try fewer or more general words.', 'ultra-max' ) : '',
				'children' => Template::search_form( 'um-empty-search', 'outline' ),
			)
		)->print();
	}

	Template::layout_close();
}

Template::main_close();
get_footer();
