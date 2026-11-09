<?php
/**
 * Constants the products define in their main files, for PHPStan.
 *
 * @package UltraMax
 */

define( 'ULTRA_MAX_THEME_VERSION', '0.1.0' );
foreach ( array( 'CORE', 'AUTH', 'INVOICE', 'SEO' ) as $ultra_max_product ) {
	define( "ULTRA_MAX_{$ultra_max_product}_VERSION", '0.1.0' );
	define( "ULTRA_MAX_{$ultra_max_product}_FILE", __FILE__ );
}
