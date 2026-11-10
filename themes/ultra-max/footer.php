<?php
/**
 * Site footer: closes the page frame.
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

use UltraMax\Theme\Template;

$ultra_max_devices = Template::location( 'footer' );
if ( Template::any( $ultra_max_devices ) ) {
	Template::footer( $ultra_max_devices );
}
unset( $ultra_max_devices );

/**
 * Fires after the site footer, inside the page frame, where the mobile bottom bar goes.
 */
do_action( 'ultra_max_after_footer' );

Template::frame_close();
wp_footer();
?>
</body>
</html>