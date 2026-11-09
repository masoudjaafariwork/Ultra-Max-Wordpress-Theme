<?php
/**
 * The theme's switchable modules: announcement bar, contact buttons and
 * back to top (CORE-10, CORE-11, CORE-12, CORE-13).
 *
 * @package UltraMax\Theme
 */

declare(strict_types=1);

namespace UltraMax\Theme;

use UltraMax\Theme\Lib\Settings\Modules;
use UltraMax\Theme\Lib\Settings\Store;
use UltraMax\Theme\Lib\UI\UI;
use UltraMax\Theme\Lib\UI\Visibility;

/**
 * Each module hooks in only when it is switched on and has something to
 * show, so a switched-off module adds no markup, CSS or script.
 */
final class Extras {

	/**
	 * Registers the modules that are on.
	 */
	public static function register(): void {
		if ( Modules::enabled( 'notice-bar' ) && '' !== Store::string( 'notice_text' ) ) {
			add_action( 'ultra_max_before_header', array( self::class, 'notice_bar' ) );
			Assets::module( 'notice-bar' );
		}
		if ( Modules::enabled( 'contact-buttons' ) && array() !== ContactLinks::items() ) {
			add_action( 'wp_footer', array( self::class, 'contact_buttons' ), 5 );
			Assets::module( 'contact-buttons' );
		}
		if ( Modules::enabled( 'back-to-top' ) && Visibility::any( Store::devices( 'back_to_top_devices' ) ) ) {
			add_action( 'wp_footer', array( self::class, 'back_to_top' ), 5 );
			Assets::module( 'back-to-top' );
		}
	}

	/**
	 * The announcement bar, when today is inside its dates.
	 */
	public static function notice_bar(): void {
		$devices = Store::devices( 'notice_devices' );
		$start   = Dates::setting_timestamp( 'notice_start' );
		$end     = Dates::setting_timestamp( 'notice_end', true );
		$now     = time();
		if ( ! Visibility::any( $devices ) || ( null !== $start && $now < $start ) || ( null !== $end && $now > $end ) ) {
			return;
		}
		$props = array(
			'id'          => 'um-notice-bar',
			'text'        => Store::string( 'notice_text' ),
			'close_label' => __( 'Close the announcement', 'ultra-max' ),
			'tone'        => in_array( Store::string( 'notice_tone' ), array( 'brand', 'accent', 'dark' ), true ) ? Store::string( 'notice_tone' ) : 'brand',
			'dismissible' => Store::bool( 'notice_dismissible' ),
			'class'       => UI::visibility( $devices ),
			'link'        => array(
				'label' => Store::string( 'notice_link_label' ),
				'href'  => Store::string( 'notice_link_url' ),
			),
		);
		if ( null !== $start ) {
			$props['start'] = $start;
		}
		if ( null !== $end ) {
			$props['end'] = $end;
		}
		UI::notice_bar( $props )->print();
	}

	/**
	 * The floating contact buttons.
	 */
	public static function contact_buttons(): void {
		$devices = Store::devices( 'contact_devices' );
		if ( ! Visibility::any( $devices ) ) {
			return;
		}
		UI::contact_buttons(
			array(
				'id'    => 'um-contact',
				'label' => __( 'Contact us', 'ultra-max' ),
				'items' => ContactLinks::items(),
				'side'  => 'start' === Store::string( 'contact_side' ) ? 'start' : 'end',
				'class' => UI::visibility( $devices ),
			)
		)->print();
	}

	/**
	 * The back to top button, on the side opposite the contact buttons.
	 */
	public static function back_to_top(): void {
		UI::back_to_top(
			array(
				'label'  => __( 'Back to top', 'ultra-max' ),
				'target' => 'page',
				'side'   => 'start' === Store::string( 'contact_side' ) ? 'end' : 'start',
				'class'  => UI::visibility( Store::devices( 'back_to_top_devices' ) ),
			)
		)->print();
	}
}
