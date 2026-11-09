<?php
/**
 * Ultra Max theme bootstrap.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'ULTRA_MAX_THEME_VERSION', '0.1.0' );

$ultra_max_autoload = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $ultra_max_autoload ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The Ultra Max theme is missing its build files. Run "npm run build" in the development repository, or install the release package.', 'ultra-max' ) . '</p></div>';
		}
	);
	return;
}
require_once $ultra_max_autoload;
unset( $ultra_max_autoload );

UltraMax\Theme\Theme::instance()->boot();
