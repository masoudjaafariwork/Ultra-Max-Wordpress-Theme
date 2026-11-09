<?php
/**
 * In-dashboard help (ADM-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Admin;

/**
 * The help articles of docs/help/fa, converted to HTML at build time into
 * assets/build/help/fa.json. The same files make the Persian help.pdf, so
 * the dashboard and the PDF never disagree. Articles contain no outside
 * links (Rtl-theme rule).
 */
final class Help {

	/**
	 * Every article.
	 *
	 * @return list<array{id: string, title: string, html: string}>
	 */
	public static function articles(): array {
		$file = plugin_dir_path( ULTRA_MAX_CORE_FILE ) . 'assets/build/help/fa.json';
		$data = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local build file.
		$out  = array();
		foreach ( is_array( $data ) ? $data : array() as $article ) {
			if ( is_array( $article ) && is_string( $article['id'] ?? null ) && is_string( $article['title'] ?? null ) && is_string( $article['html'] ?? null ) ) {
				$out[] = array(
					'id'    => $article['id'],
					'title' => $article['title'],
					'html'  => wp_kses_post( $article['html'] ),
				);
			}
		}
		return $out;
	}
}
