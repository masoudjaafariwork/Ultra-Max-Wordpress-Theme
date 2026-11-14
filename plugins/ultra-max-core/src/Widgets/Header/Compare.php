<?php
/**
 * Compare link widget (HDR-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Header;

/**
 * A link to the compare page (SAVE-02) with the number of products in the
 * comparison, or to the page chosen here.
 */
final class Compare extends Wishlist {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'compare';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Compare', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-exchange';
	}

	/**
	 * Icon of the action.
	 */
	protected function action_icon(): string {
		return 'compare';
	}

	/**
	 * Count key.
	 */
	protected function count_key(): string {
		return 'compare';
	}
}
