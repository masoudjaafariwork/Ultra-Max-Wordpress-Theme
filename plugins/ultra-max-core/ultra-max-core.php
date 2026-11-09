<?php
/**
 * Plugin Name:       Ultra Max Core
 * Description:       Layout builder, Elementor widgets, store modules and demo importer for the Ultra Max theme.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Ultra Max
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ultra-max-core
 * Domain Path:       /languages
 * WC requires at least: 10.0
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'ULTRA_MAX_CORE_VERSION', '0.1.0' );
define( 'ULTRA_MAX_CORE_FILE', __FILE__ );

$ultra_max_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $ultra_max_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'This plugin is missing its build files. Run "npm run build" in the development repository, or install the release package.', 'ultra-max-core' ) . '</p></div>';
		}
	);
	return;
}
require_once $ultra_max_autoload;
unset( $ultra_max_autoload );

UltraMax\Core\Plugin::instance()->boot();
