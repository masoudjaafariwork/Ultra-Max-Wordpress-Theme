<?php
/**
 * Shared parts of the cart, checkout and thank you widgets (BLD-06).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Cart;

use UltraMax\Core\Builder\Layouts;
use UltraMax\Core\Lib\UI\Alert;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * The widgets of a cart, checkout or thank you layout. Each one prints one
 * piece of its page through the page's own renderer (Cart\Page,
 * Checkout\Page, Checkout\ThankYou), so a page designed with Elementor
 * behaves exactly like the built-in one. While a layout is designed and
 * the piece has nothing to show, such as an empty cart, a note says what
 * will show there.
 */
abstract class CartWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'cart';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-06' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'cart', 'product-card', 'carousel' );
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
				'label' => $this->where(),
			),
		);
	}

	/**
	 * Where the widget belongs, for its note.
	 */
	protected function where(): string {
		return __( 'For a cart layout. Shows the shopper\'s cart.', 'ultra-max-core' );
	}

	/**
	 * Whether the page's module is on and WooCommerce runs.
	 */
	abstract protected function ready(): bool;

	/**
	 * The piece of the page.
	 *
	 * @param Values $settings Settings.
	 */
	abstract protected function piece( Values $settings ): Html;

	/**
	 * Renders the piece, or while a layout is designed a note in its place.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$piece = $this->ready() ? $this->piece( $settings ) : Html::none();
		if ( ! $piece->is_empty() || ! is_singular( Layouts::POST_TYPE ) ) {
			return $piece;
		}
		return Alert::render(
			array(
				'tone'     => 'info',
				'title'    => $this->title(),
				'children' => $this->where(),
			)
		);
	}

	/**
	 * A switch field.
	 *
	 * @param string $id      Id.
	 * @param string $label   Label.
	 * @param bool   $initial Default.
	 * @return array<string, mixed>
	 */
	protected function toggle( string $id, string $label, bool $initial = true ): array {
		return array(
			'id'      => $id,
			'type'    => 'switch',
			'label'   => $label,
			'default' => $initial,
		);
	}
}
