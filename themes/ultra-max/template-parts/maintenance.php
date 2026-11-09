<?php
/**
 * The page visitors see in maintenance or coming-soon mode (CORE-09).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Maintenance;
use UltraMax\Theme\Template;
use UltraMax\Theme\Lib\UI\UI;

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'um-maintenance' ); ?>>
<?php wp_body_open(); ?>
<main id="um-main" class="um-scope grid min-h-dvh place-items-center px-(--um-gutter)">
	<div class="grid justify-items-center gap-2">
		<?php
		Template::logo( 'lg' )->print();
		$ultra_max_until = Maintenance::until();
		UI::empty_state(
			array(
				'icon'     => 'coming_soon' === Maintenance::mode() ? 'sparkles' : 'wrench',
				'heading'  => 'h1',
				'title'    => Maintenance::title(),
				'text'     => Maintenance::text(),
				'children' => '' === $ultra_max_until ? array() : array(
					UI::badge(
						array(
							'children' => $ultra_max_until,
							'tone'     => 'brand',
						)
					),
				),
			)
		)->print();
		?>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
