<?php
/**
 * Icon component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * Inline SVG icon drawn for this project (design/assets/js/shell.js). Decorative:
 * hidden from screen readers, so the surrounding control must carry the
 * accessible name.
 */
final class Icon {

	/**
	 * Shapes of every icon, on a 24 by 24 grid, stroked.
	 */
	private const SHAPES = array(
		'close'         => '<path d="M6 6l12 12M18 6L6 18"/>',
		'chevron-down'  => '<path d="M6 9l6 6 6-6"/>',
		'chevron-up'    => '<path d="M6 15l6-6 6 6"/>',
		'chevron-left'  => '<path d="M15 5l-7 7 7 7"/>',
		'chevron-right' => '<path d="M9 5l7 7-7 7"/>',
		'arrow-up'      => '<path d="M12 20V5M6 11l6-6 6 6"/>',
		'menu'          => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'search'        => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
		'cart'          => '<path d="M3 4h2l2.2 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 8H6.2"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>',
		'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>',
		'home'          => '<path d="M3 10.5L12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
		'calendar'      => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'folder'        => '<path d="M3.5 6.5a1.5 1.5 0 0 1 1.5-1.5h4l2 2.5h8a1.5 1.5 0 0 1 1.5 1.5v9.5a1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5z"/>',
		'tag'           => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'chat'          => '<path d="M4 5h16v11H9l-5 4z"/><path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/>',
		'send'          => '<path d="M21 3L3 10.5l7 3 3 7z"/><path d="M10 13.5L21 3"/>',
		'phone'         => '<path d="M5 3.5h3.5l1.8 4.5-2.3 1.5a11 11 0 0 0 6.5 6.5l1.5-2.3 4.5 1.8V19a1.5 1.5 0 0 1-1.6 1.5C10.7 20 4 13.3 3.5 5.1A1.5 1.5 0 0 1 5 3.5z"/>',
		'mail'          => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6l8.5 7 8.5-7"/>',
		'link'          => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'headset'       => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="13.5" width="4" height="6" rx="1.5"/><rect x="17" y="13.5" width="4" height="6" rx="1.5"/><path d="M19 19.5c0 1-1.5 2-4 2h-2"/>',
		'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.2"/>',
		'alert'         => '<path d="M12 3.5l9.5 16.5h-19z"/><path d="M12 10v4.5M12 17.3v.2"/>',
		'check'         => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
		'sparkles'      => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8zM19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/>',
		'wrench'        => '<path d="M14.5 4.5a4.5 4.5 0 0 0-5.6 5.6L3.5 15.5l5 5 5.4-5.4a4.5 4.5 0 0 0 5.6-5.6l-2.8 2.8-3-1-1-3z"/>',
		'file'          => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5"/>',
		'gift'          => '<rect x="3" y="8" width="18" height="4.5" rx="1"/><path d="M5 12.5V21h14v-8.5M12 8v13M12 8S10.5 3.5 8 3.5a2.25 2.25 0 0 0 0 4.5zM12 8s1.5-4.5 4-4.5a2.25 2.25 0 0 1 0 4.5z"/>',
		'pin'           => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'lock'          => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
	);

	/**
	 * Whether an icon exists.
	 *
	 * @param string $name Icon name.
	 */
	public static function exists( string $name ): bool {
		return isset( self::SHAPES[ $name ] );
	}

	/**
	 * Every icon name.
	 *
	 * @return list<string>
	 */
	public static function names(): array {
		return array_keys( self::SHAPES );
	}

	/**
	 * Renders an icon.
	 *
	 * @param string $name    Icon name; an unknown name renders "info".
	 * @param string $classes Extra classes for size and direction.
	 */
	public static function render( string $name, string $classes = 'size-5' ): Html {
		return Html::trusted(
			sprintf(
				'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
				esc_attr( Attributes::classes( 'shrink-0', $classes ) ),
				self::SHAPES[ $name ] ?? self::SHAPES['info']
			)
		);
	}
}
