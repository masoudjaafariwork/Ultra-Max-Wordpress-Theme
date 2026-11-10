<?php
/**
 * Site header: document head, page frame, announcement bar and header.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Template;

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
wp_body_open();
Template::frame_open();

/**
 * Fires above the site header, where the announcement bar goes.
 */
do_action( 'ultra_max_before_header' );

$ultra_max_devices = Template::location( 'header' );
if ( Template::any( $ultra_max_devices ) ) {
	Template::header( $ultra_max_devices );
}
unset( $ultra_max_devices );