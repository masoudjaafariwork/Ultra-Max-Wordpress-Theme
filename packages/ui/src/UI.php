<?php
/**
 * Entry point to every component.
 *
 * @package UltraMax\UI
 */

declare(strict_types=1);

namespace UltraMax\UI;

/**
 * One call per component, like a JSX tag:
 *
 *     UI::button( array( 'variant' => 'primary', 'children' => __( 'Add to cart', 'ultra-max' ) ) )->print();
 *
 * Each method returns Html, so components compose by passing one as the
 * `children` of another. Prop shapes are documented on each component
 * class and checked by PHPStan.
 *
 * @phpstan-import-type ButtonProps from Button
 * @phpstan-import-type DialogProps from Dialog
 * @phpstan-import-type DrawerProps from Drawer
 * @phpstan-import-type TabsProps from Tabs
 * @phpstan-import-type AccordionProps from Accordion
 * @phpstan-import-type DropdownProps from Dropdown
 * @phpstan-import-type TooltipProps from Tooltip
 */
final class UI {

	/**
	 * Button.
	 *
	 * @param ButtonProps $props Props.
	 */
	public static function button( array $props ): Html {
		return Button::render( $props );
	}

	/**
	 * Modal dialog.
	 *
	 * @param DialogProps $props Props.
	 */
	public static function dialog( array $props ): Html {
		return Dialog::render( $props );
	}

	/**
	 * Side drawer.
	 *
	 * @param DrawerProps $props Props.
	 */
	public static function drawer( array $props ): Html {
		return Drawer::render( $props );
	}

	/**
	 * Tabs.
	 *
	 * @param TabsProps $props Props.
	 */
	public static function tabs( array $props ): Html {
		return Tabs::render( $props );
	}

	/**
	 * Accordion.
	 *
	 * @param AccordionProps $props Props.
	 */
	public static function accordion( array $props ): Html {
		return Accordion::render( $props );
	}

	/**
	 * Dropdown list of links.
	 *
	 * @param DropdownProps $props Props.
	 */
	public static function dropdown( array $props ): Html {
		return Dropdown::render( $props );
	}

	/**
	 * Tooltip around a trigger.
	 *
	 * @param TooltipProps $props Props.
	 */
	public static function tooltip( array $props ): Html {
		return Tooltip::render( $props );
	}

	/**
	 * Decorative icon.
	 *
	 * @param 'close'|'chevron-down'|'menu' $name  Icon name.
	 * @param string                         $class Size classes.
	 */
	public static function icon( string $name, string $class = 'size-5' ): Html {
		return Icon::render( $name, $class );
	}
}