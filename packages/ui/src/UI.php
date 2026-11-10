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
 * @phpstan-import-type LogoProps from Logo
 * @phpstan-import-type MenuProps from Menu
 * @phpstan-import-type SearchFormProps from SearchForm
 * @phpstan-import-type SiteHeaderProps from SiteHeader
 * @phpstan-import-type SiteFooterProps from SiteFooter
 * @phpstan-import-type BreadcrumbsProps from Breadcrumbs
 * @phpstan-import-type PageHeaderProps from PageHeader
 * @phpstan-import-type PostMetaProps from PostMeta
 * @phpstan-import-type PostCardProps from PostCard
 * @phpstan-import-type PaginationProps from Pagination
 * @phpstan-import-type BadgeProps from Badge
 * @phpstan-import-type AlertProps from Alert
 * @phpstan-import-type EmptyStateProps from EmptyState
 * @phpstan-import-type BoxProps from Box
 * @phpstan-import-type FieldProps from Field
 * @phpstan-import-type ProseProps from Prose
 * @phpstan-import-type CommentProps from Comments
 * @phpstan-import-type NoticeBarProps from NoticeBar
 * @phpstan-import-type ContactButtonsProps from ContactButtons
 * @phpstan-import-type BackToTopProps from BackToTop
 * @phpstan-import-type MediaProps from Media
 * @phpstan-import-type LayoutRegionProps from LayoutRegion
 */
final class UI {

	/**
	 * Button.
	 *
	 * @param array $props Props.
	 * @phpstan-param ButtonProps $props
	 */
	public static function button( array $props ): Html {
		return Button::render( $props );
	}

	/**
	 * Modal dialog.
	 *
	 * @param array $props Props.
	 * @phpstan-param DialogProps $props
	 */
	public static function dialog( array $props ): Html {
		return Dialog::render( $props );
	}

	/**
	 * Side drawer.
	 *
	 * @param array $props Props.
	 * @phpstan-param DrawerProps $props
	 */
	public static function drawer( array $props ): Html {
		return Drawer::render( $props );
	}

	/**
	 * Tabs.
	 *
	 * @param array $props Props.
	 * @phpstan-param TabsProps $props
	 */
	public static function tabs( array $props ): Html {
		return Tabs::render( $props );
	}

	/**
	 * Accordion.
	 *
	 * @param array $props Props.
	 * @phpstan-param AccordionProps $props
	 */
	public static function accordion( array $props ): Html {
		return Accordion::render( $props );
	}

	/**
	 * Dropdown list of links.
	 *
	 * @param array $props Props.
	 * @phpstan-param DropdownProps $props
	 */
	public static function dropdown( array $props ): Html {
		return Dropdown::render( $props );
	}

	/**
	 * Tooltip around a trigger.
	 *
	 * @param array $props Props.
	 * @phpstan-param TooltipProps $props
	 */
	public static function tooltip( array $props ): Html {
		return Tooltip::render( $props );
	}

	/**
	 * Decorative icon.
	 *
	 * @param string $name    Icon name; see Icon::names().
	 * @param string $classes Size classes.
	 */
	public static function icon( string $name, string $classes = 'size-5' ): Html {
		return Icon::render( $name, $classes );
	}
	/**
	 * Store logo.
	 *
	 * @param array $props Props.
	 * @phpstan-param LogoProps $props
	 */
	public static function logo( array $props ): Html {
		return Logo::render( $props );
	}

	/**
	 * Navigation menu.
	 *
	 * @param array $props Props.
	 * @phpstan-param MenuProps $props
	 */
	public static function menu( array $props ): Html {
		return Menu::render( $props );
	}

	/**
	 * Site search form.
	 *
	 * @param array $props Props.
	 * @phpstan-param SearchFormProps $props
	 */
	public static function search_form( array $props ): Html {
		return SearchForm::render( $props );
	}

	/**
	 * Default site header.
	 *
	 * @param array $props Props.
	 * @phpstan-param SiteHeaderProps $props
	 */
	public static function site_header( array $props ): Html {
		return SiteHeader::render( $props );
	}

	/**
	 * Default site footer.
	 *
	 * @param array $props Props.
	 * @phpstan-param SiteFooterProps $props
	 */
	public static function site_footer( array $props ): Html {
		return SiteFooter::render( $props );
	}

	/**
	 * Breadcrumb trail.
	 *
	 * @param array $props Props.
	 * @phpstan-param BreadcrumbsProps $props
	 */
	public static function breadcrumbs( array $props ): Html {
		return Breadcrumbs::render( $props );
	}

	/**
	 * Page title area.
	 *
	 * @param array $props Props.
	 * @phpstan-param PageHeaderProps $props
	 */
	public static function page_header( array $props ): Html {
		return PageHeader::render( $props );
	}

	/**
	 * Post facts line.
	 *
	 * @param array $props Props.
	 * @phpstan-param PostMetaProps $props
	 */
	public static function post_meta( array $props ): Html {
		return PostMeta::render( $props );
	}

	/**
	 * Post in a list.
	 *
	 * @param array $props Props.
	 * @phpstan-param PostCardProps $props
	 */
	public static function post_card( array $props ): Html {
		return PostCard::render( $props );
	}

	/**
	 * Page links.
	 *
	 * @param array $props Props.
	 * @phpstan-param PaginationProps $props
	 */
	public static function pagination( array $props ): Html {
		return Pagination::render( $props );
	}

	/**
	 * Short label.
	 *
	 * @param array $props Props.
	 * @phpstan-param BadgeProps $props
	 */
	public static function badge( array $props ): Html {
		return Badge::render( $props );
	}

	/**
	 * Message box.
	 *
	 * @param array $props Props.
	 * @phpstan-param AlertProps $props
	 */
	public static function alert( array $props ): Html {
		return Alert::render( $props );
	}

	/**
	 * Nothing-to-show message.
	 *
	 * @param array $props Props.
	 * @phpstan-param EmptyStateProps $props
	 */
	public static function empty_state( array $props ): Html {
		return EmptyState::render( $props );
	}

	/**
	 * Surface panel.
	 *
	 * @param array $props Props.
	 * @phpstan-param BoxProps $props
	 */
	public static function box( array $props ): Html {
		return Box::render( $props );
	}

	/**
	 * Labelled input or text area.
	 *
	 * @param array $props Props.
	 * @phpstan-param FieldProps $props
	 */
	public static function field( array $props ): Html {
		return Field::render( $props );
	}

	/**
	 * Editor content typography.
	 *
	 * @param array $props Props.
	 * @phpstan-param ProseProps $props
	 */
	public static function prose( array $props ): Html {
		return Prose::render( $props );
	}

	/**
	 * Announcement bar.
	 *
	 * @param array $props Props.
	 * @phpstan-param NoticeBarProps $props
	 */
	public static function notice_bar( array $props ): Html {
		return NoticeBar::render( $props );
	}

	/**
	 * Floating contact buttons.
	 *
	 * @param array $props Props.
	 * @phpstan-param ContactButtonsProps $props
	 */
	public static function contact_buttons( array $props ): Html {
		return ContactButtons::render( $props );
	}

	/**
	 * Back to top button.
	 *
	 * @param array $props Props.
	 * @phpstan-param BackToTopProps $props
	 */
	public static function back_to_top( array $props ): Html {
		return BackToTop::render( $props );
	}

	/**
	 * Featured image.
	 *
	 * @param array $props Props.
	 * @phpstan-param MediaProps $props
	 */
	public static function media( array $props ): Html {
		return Media::render( $props );
	}

	/**
	 * Frame of a layout made with the layout builder.
	 *
	 * @param array $props Props.
	 * @phpstan-param LayoutRegionProps $props
	 */
	public static function layout_region( array $props ): Html {
		return LayoutRegion::render( $props );
	}

	/**
	 * One comment of a discussion; WordPress closes its `li`.
	 *
	 * @param array $props Props.
	 * @phpstan-param CommentProps $props
	 */
	public static function comment( array $props ): Html {
		return Comments::item( $props );
	}

	/**
	 * Classes that hide an element on the devices switched off (CORE-13).
	 *
	 * @param array{desktop?: bool, tablet?: bool, mobile?: bool} $devices Devices to show on.
	 */
	public static function visibility( array $devices ): string {
		return Visibility::classes( $devices );
	}
}
