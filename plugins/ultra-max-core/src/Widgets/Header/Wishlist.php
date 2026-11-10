<?php
/**
 * Wishlist link widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

use UltraMax\Core\Lib\UI\HeaderAction;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A link to the wishlist page. The wishlist itself and its count arrive
 * with the wishlist feature (SAVE-01, phase 4); until then the link goes to
 * the page chosen here, or to the account page.
 */
class Wishlist extends HeaderWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'wishlist';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Wishlist', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-heart-o';
	}

	/**
	 * Icon of the action.
	 */
	protected function action_icon(): string {
		return 'heart';
	}

	/**
	 * Count key, for later phases.
	 */
	protected function count_key(): string {
		return 'wishlist';
	}

	/**
	 * Controls.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => 'link_section',
				'label'  => $this->title(),
				'fields' => array(
					$this->look_field(),
					array(
						'id'      => 'text',
						'type'    => 'text',
						'label'   => __( 'Text', 'ultra-max-core' ),
						'default' => $this->title(),
					),
					array(
						'id'    => 'link',
						'type'  => 'url',
						'label' => __( 'Link', 'ultra-max-core' ),
						'help'  => __( 'Leave empty for the account page.', 'ultra-max-core' ),
					),
				),
			),
		);
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$href = $settings->href( 'link' );
		if ( '' === $href ) {
			$href = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
		}
		/**
		 * Filters the number shown on a header link, such as the wishlist.
		 *
		 * @param int    $count Number of items.
		 * @param string $key   wishlist or compare.
		 */
		$count = apply_filters( 'ultra_max_header_count', 0, $this->count_key() );
		return HeaderAction::render(
			array(
				'label'     => $this->title(),
				'text'      => $settings->string( 'text' ),
				'icon'      => $this->action_icon(),
				'href'      => $href,
				'count'     => is_numeric( $count ) ? (int) $count : 0,
				'count_key' => $this->count_key(),
				'variant'   => self::look( $settings->string( 'look' ) ),
			)
		);
	}
}
