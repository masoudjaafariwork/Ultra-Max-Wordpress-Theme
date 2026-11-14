<?php
/**
 * Shared parts of the customer account widgets (BLD-07).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Widgets\Widget;

/**
 * The widgets of the customer account, the wishlist, the comparison and
 * order tracking. Each prints one piece through the same renderer as the
 * built-in pages, so a page designed with Elementor behaves the same.
 */
abstract class AccountWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'account';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'account' );
	}

	/**
	 * Controls: one section of the widget's fields.
	 */
	public function controls(): array {
		return array(
			array(
				'id'     => str_replace( '-', '_', $this->id() ) . '_section',
				'label'  => $this->title(),
				'fields' => $this->fields(),
			),
		);
	}

	/**
	 * Fields of the widget.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'    => 'note',
				'type'  => 'heading',
				'label' => $this->note(),
			),
		);
	}

	/**
	 * The line shown in the widget's panel.
	 */
	protected function note(): string {
		return __( 'Shows the logged in shopper\'s own account.', 'ultra-max-core' );
	}
}
