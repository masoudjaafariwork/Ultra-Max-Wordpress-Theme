<?php
/**
 * Site header.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="um-skip-link screen-reader-text" href="#um-main"><?php esc_html_e( 'Skip to content', 'ultra-max' ); ?></a>
<header class="um-scope um-site-header">
	<div class="mx-auto flex max-w-(--um-container) items-center justify-between gap-4 px-4 py-3">
		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			printf( '<a href="%s" rel="home">%s</a>', esc_url( home_url( '/' ) ), esc_html( get_bloginfo( 'name' ) ) );
		}
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => 'nav',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);
		?>
	</div>
</header>
<main id="um-main" class="um-scope">