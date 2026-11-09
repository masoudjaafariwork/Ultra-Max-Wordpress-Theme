<?php
/**
 * Page not found (CORE-09).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Template;
use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\UI;

get_header();
Template::main_open();

$ultra_max_actions = array();
if ( Store::bool( 'error_search' ) ) {
	$ultra_max_actions[] = Template::search_form( 'um-404-search', 'outline' );
}
$ultra_max_actions[] = UI::button(
	array(
		'href'     => home_url( '/' ),
		'variant'  => 'outline',
		'children' => __( 'Back to the home page', 'ultra-max' ),
	)
);
$ultra_max_title     = Store::string( 'error_title' );
$ultra_max_text      = Store::string( 'error_text' );
?>
<div class="mx-auto w-full max-w-(--um-container) px-(--um-gutter)">
	<?php
	UI::empty_state(
		array(
			'code'     => '404',
			'heading'  => 'h1',
			'title'    => '' !== $ultra_max_title ? $ultra_max_title : __( 'This page could not be found', 'ultra-max' ),
			'text'     => '' !== $ultra_max_text ? $ultra_max_text : __( 'The link may be broken or the page may have moved. Try searching or go back to the home page.', 'ultra-max' ),
			'children' => $ultra_max_actions,
		)
	)->print();
	?>
</div>
<?php
Template::main_close();
get_footer();
