<?php
/**
 * The sidebar.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Template;

if ( ! is_active_sidebar( 'sidebar' ) ) {
	return;
}
?>
<aside class="<?php echo esc_attr( Template::sidebar_classes() ); ?>" aria-label="<?php esc_attr_e( 'Sidebar', 'ultra-max' ); ?>">
	<?php dynamic_sidebar( 'sidebar' ); ?>
</aside>
