<?php
/**
 * WordPress test suite configuration for the Docker site.
 * Uses its own database so tests never touch the development site.
 *
 * @package UltraMax\Tests
 */

define( 'ABSPATH', '/var/www/html/' );
define( 'WP_DEFAULT_THEME', 'ultra-max' );
define( 'WP_DEBUG', true );
define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'password' );
define( 'DB_HOST', 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Ultra Max Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
