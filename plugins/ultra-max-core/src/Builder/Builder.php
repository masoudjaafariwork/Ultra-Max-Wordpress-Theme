<?php
/**
 * The layout builder module (BLD-01 to BLD-03, BLD-09, HDR-01).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Builder;

use UltraMax\Core\Lib\Settings\Modules;

/**
 * Boots the builder when its module is on. Off, it registers nothing: no
 * post type, no query, no asset, and the theme prints its own header and
 * footer.
 */
final class Builder {

	public const MODULE = 'layout-builder';

	/**
	 * Whether the module is on.
	 */
	public static function enabled(): bool {
		return Modules::enabled( self::MODULE );
	}

	/**
	 * Registers the builder.
	 */
	public static function register(): void {
		if ( ! self::enabled() ) {
			return;
		}
		Layouts::register();
		Renderer::register();
		Rest::register();
	}
}
