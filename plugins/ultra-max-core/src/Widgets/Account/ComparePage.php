<?php
/**
 * Compare table widget (BLD-07, SAVE-02).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Account;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Saved\Compare;
use UltraMax\Core\Widgets\Values;

/**
 * The products the shopper compares, as on the compare page.
 */
final class ComparePage extends AccountWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'compare-page';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Product comparison', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-exchange';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-07', 'SAVE-02', 'SAVE-03' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'saved', 'product-card' );
	}

	/**
	 * The line shown in the widget's panel.
	 */
	protected function note(): string {
		return __( 'Shows the products the shopper compares.', 'ultra-max-core' );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Compare::page();
	}
}
