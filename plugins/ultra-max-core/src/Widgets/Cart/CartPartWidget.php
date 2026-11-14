<?php
/**
 * Shared parts of the cart page widgets (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Cart\Cart;
use UltraMax\Core\Cart\Page;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;

/**
 * A cart widget prints one named part of the cart page; the cart behaviour
 * draws it again, with the widget's options, after every change.
 */
abstract class CartPartWidget extends CartWidget {

	/**
	 * The cart part this widget prints, one of Page::PARTS.
	 */
	abstract protected function part(): string;

	/**
	 * The part's options from the widget's settings.
	 *
	 * @param Values $settings Settings.
	 * @return array<string, mixed>
	 */
	protected function args( Values $settings ): array {
		return array();
	}

	/**
	 * Whether the cart module is on and a cart exists.
	 */
	protected function ready(): bool {
		return Cart::enabled() && null !== WC()->cart;
	}

	/**
	 * The part.
	 *
	 * @param Values $settings Settings.
	 */
	protected function piece( Values $settings ): Html {
		$part = Page::part( $this->part(), $this->args( $settings ) );
		// An empty part still holds its place, so the cart can fill it later.
		return Page::draw( $this->part(), $this->args( $settings ) )->is_empty() && is_singular( \UltraMax\Core\Builder\Layouts::POST_TYPE ) ? Html::none() : $part;
	}
}
