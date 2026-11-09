<?php
/**
 * Plugin Name:       Ultra Max SEO
 * Description:       Meta tags, sitemaps, structured data, redirects and SEO analysis that coexist with Yoast and Rank Math.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            Ultra Max
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ultra-max-seo
 * Domain Path:       /languages
 * WC requires at least: 10.0
 *
 * @package UltraMax\Seo
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'ULTRA_MAX_SEO_VERSION', '0.1.0' );
define( 'ULTRA_MAX_SEO_FILE', __FILE__ );

$ultra_max_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $ultra_max_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'This plugin is missing its build files. Run "npm run build" in the development repository, or install the release package.', 'ultra-max-seo' ) . '</p></div>';
		}
	);
	return;
}
require_once $ultra_max_autoload;
unset( $ultra_max_autoload );

UltraMax\Seo\Plugin::instance()->boot();