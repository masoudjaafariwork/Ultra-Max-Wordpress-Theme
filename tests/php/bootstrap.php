<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library and our products.
 *
 * @package UltraMax\Tests
 */

$ultra_max_root  = dirname( __DIR__, 2 );
$ultra_max_tests = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $ultra_max_root . '/vendor/wp-phpunit/wp-phpunit';

// The wp-phpunit package reads its configuration path from this variable.
putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv

require_once $ultra_max_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
require_once $ultra_max_tests . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		foreach ( array( 'ultra-max-core', 'ultra-max-auth', 'ultra-max-invoice', 'ultra-max-seo' ) as $plugin ) {
			require_once WP_PLUGIN_DIR . "/{$plugin}/{$plugin}.php";
		}
	}
);

require $ultra_max_tests . '/includes/bootstrap.php';
