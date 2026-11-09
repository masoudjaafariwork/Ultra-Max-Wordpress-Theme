<?php
/**
 * The breadcrumb trail of the current page (CORE-08).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

/**
 * Builds the list of links from the home page to the current page. The
 * Breadcrumbs component renders it; the SEO plugin of phase 7 adds the
 * structured data for the same trail.
 */
final class Breadcrumbs {

	/**
	 * The trail.
	 *
	 * @return list<array{label: string, href?: string}>
	 */
	public static function trail(): array {
		$trail = array(
			array(
				'label' => __( 'Home', 'ultra-max' ),
				'href'  => home_url( '/' ),
			),
		);
		if ( is_front_page() ) {
			return array();
		}

		$posts_page = Template::int( get_option( 'page_for_posts' ) );
		$blog       = $posts_page > 0 && 'page' === get_option( 'show_on_front' ) ? array(
			'label' => get_the_title( $posts_page ),
			'href'  => (string) get_permalink( $posts_page ),
		) : null;

		if ( is_home() ) {
			$trail[] = array( 'label' => null === $blog ? __( 'Blog', 'ultra-max' ) : $blog['label'] );
			return $trail;
		}

		if ( is_singular() ) {
			$post = get_queried_object();
			if ( ! $post instanceof \WP_Post ) {
				return $trail;
			}
			if ( 'post' === $post->post_type ) {
				if ( null !== $blog ) {
					$trail[] = $blog;
				}
				$categories = get_the_category( $post->ID );
				if ( array() !== $categories ) {
					$trail = array_merge( $trail, self::terms( $categories[0], true ) );
				}
			} elseif ( is_post_type_hierarchical( $post->post_type ) ) {
				foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
					$trail[] = array(
						'label' => get_the_title( $ancestor ),
						'href'  => (string) get_permalink( $ancestor ),
					);
				}
			} else {
				$archive = get_post_type_archive_link( $post->post_type );
				$object  = get_post_type_object( $post->post_type );
				if ( is_string( $archive ) && null !== $object ) {
					$trail[] = array(
						'label' => $object->labels->name,
						'href'  => $archive,
					);
				}
			}
			$trail[] = array( 'label' => get_the_title( $post ) );
			return $trail;
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				if ( null !== $blog && in_array( $term->taxonomy, array( 'category', 'post_tag' ), true ) ) {
					$trail[] = $blog;
				}
				$trail = array_merge( $trail, self::terms( $term, false ) );
			}
			return $trail;
		}

		if ( is_search() ) {
			/* translators: %s: search terms. */
			$trail[] = array( 'label' => sprintf( __( 'Search results for "%s"', 'ultra-max' ), get_search_query() ) );
			return $trail;
		}

		if ( is_404() ) {
			$trail[] = array( 'label' => __( 'Page not found', 'ultra-max' ) );
			return $trail;
		}

		if ( is_author() || is_date() || is_post_type_archive() ) {
			$trail[] = array( 'label' => wp_strip_all_tags( get_the_archive_title() ) );
		}
		return $trail;
	}

	/**
	 * A term and its ancestors.
	 *
	 * @param \WP_Term $term      Term.
	 * @param bool     $link_last Whether the term itself is a link.
	 * @return list<array{label: string, href?: string}>
	 */
	private static function terms( \WP_Term $term, bool $link_last ): array {
		$items = array();
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			if ( $ancestor instanceof \WP_Term ) {
				$link    = get_term_link( $ancestor );
				$items[] = is_string( $link ) ? array(
					'label' => $ancestor->name,
					'href'  => $link,
				) : array( 'label' => $ancestor->name );
			}
		}
		$link    = get_term_link( $term );
		$items[] = $link_last && is_string( $link ) ? array(
			'label' => $term->name,
			'href'  => $link,
		) : array( 'label' => $term->name );
		return $items;
	}
}
