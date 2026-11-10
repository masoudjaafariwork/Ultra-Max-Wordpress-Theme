<?php
/**
 * A layout on its own, for editing and preview: no site header or footer.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'um-layout-canvas' ); ?>>
<?php
wp_body_open();
echo '<div id="page" class="um-scope">';
while ( have_posts() ) {
	the_post();
	the_content();
}
echo '</div>';
wp_footer();
?>
</body>
</html>
