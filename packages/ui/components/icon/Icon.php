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
		'heart'         => '<path d="M12 20s-7.5-4.6-9-9.3C2 7.3 4.2 4.5 7.3 4.5c2 0 3.6 1.1 4.7 2.8 1.1-1.7 2.7-2.8 4.7-2.8 3.1 0 5.3 2.8 4.3 6.2C19.5 15.4 12 20 12 20z"/>',
		'compare'       => '<path d="M7 4L3 8l4 4M3 8h13M17 12l4 4-4 4M21 16H8"/>',
		'eye'           => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'arrow-left'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'grid'          => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
		'list'          => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
		'truck'         => '<path d="M2.5 6.5h10.5v9.5H2.5zM13 9.5h4.3l3.2 3.4v3.1H13"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'shield'        => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
		'return'        => '<path d="M9 14L4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
		'card'          => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6 15h4"/>',
		'cash'          => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/><path d="M6 9.5v5M18 9.5v5"/>',
		'star'          => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
		'plus'          => '<path d="M12 5v14M5 12h14"/>',
		'minus'         => '<path d="M5 12h14"/>',
		'percent'       => '<path d="M19 5L5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
		'box'           => '<path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5L12 12l8.5-4.5M12 12v9"/>',
		'store'         => '<path d="M3.5 9l1.5-5h14l1.5 5M3.5 9a2.8 2.8 0 0 0 5.7 0 2.8 2.8 0 0 0 5.6 0 2.8 2.8 0 0 0 5.7 0M5 11.5V20h14v-8.5M10 20v-5h4v5"/>',
		'play'          => '<path d="M8 5v14l11-7z"/>',
		'image'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="M21 16l-5.5-5.5L5 20"/>',
		'fire'          => '<path d="M12 21c-4 0-7-2.8-7-6.6 0-3.4 2.6-5.4 3.6-8.4.4 2 1.5 3 2.4 3.4C11 6 12.5 3.8 14.5 3c-.4 2.6.6 4.4 2 6 1.3 1.4 2.5 3 2.5 5.4 0 3.8-3 6.6-7 6.6z"/>',
		'verified'      => '<path d="M12 2.8l2.4 1.7 2.9-.1.9 2.8 2.3 1.8-.9 2.8.9 2.8-2.3 1.8-.9 2.8-2.9-.1L12 21.2l-2.4-1.7-2.9.1-.9-2.8-2.3-1.8.9-2.8-.9-2.8 2.3-1.8.9-2.8 2.9.1z"/><path d="M8.5 12l2.4 2.4 4.6-4.8"/>',
		'globe'         => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
		'insta'         => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17 7h.01"/>',
		'mobile'        => '<rect x="6.5" y="2.5" width="11" height="19" rx="2.5"/><path d="M11 18h2"/>',
		'download'      => '<path d="M12 4v11M7 10.5l5 5 5-5M4 20h16"/>',
		'dots'          => '<path d="M5 12h.01M12 12h.01M19 12h.01"/>',
		'zap'           => '<path d="M13 2.5L4.5 13.5H12l-1 8 8.5-11H12z"/>',
		'users'         => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 4.8a3.5 3.5 0 0 1 0 6.4M18.5 14.5c1.9.8 3 2.7 3 5.5"/>',
		'chart'         => '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
		'building'      => '<path d="M4 21V4h11v17M15 9h5v12M2.5 21h19M8 8h3M8 12h3M8 16h3"/>',
		'thumb-up'      => '<path d="M7 11v9H4v-9zM7 11l4-7.5c1.3 0 2.5 1 2.3 2.4L12.8 10H19a2 2 0 0 1 2 2.3l-1.2 6A2 2 0 0 1 17.8 20H7"/>',
		'bell'          => '<path d="M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15zM10 20.5a2 2 0 0 0 4 0"/>',
		'logout'        => '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M14 8l4 4-4 4M18 12H9"/>',
		'history'       => '<path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1L3.5 8.5M3.5 4v4.5H8"/><path d="M12 7.5V12l3 2"/>',
		'filter'        => '<path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/>',
		'sort'          => '<path d="M4 6.5h10M4 12h7M4 17.5h4M17.5 4.5v15M14.5 16.5l3 3 3-3"/>',
		'quote'         => '<path d="M9.5 7C6.5 7.8 5 10 5 13v4h5v-5H7.5c0-1.8 1-3 2.5-3.5zM18.5 7c-3 .8-4.5 3-4.5 6v4h5v-5h-2.5c0-1.8 1-3 2.5-3.5z"/>',
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
