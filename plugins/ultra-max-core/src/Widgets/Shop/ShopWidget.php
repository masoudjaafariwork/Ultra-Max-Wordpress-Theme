<?php
/**
 * Shared parts of the shop layout widgets (BLD-05).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Shop;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Shop\Shop;
use UltraMax\Core\Widgets\Values;
use UltraMax\Core\Widgets\Widget;

/**
 * The widgets of a shop list layout. Each one prints one piece of the
 * product list through the list's renderer (Shop\Archive), so a list
 * designed with Elementor filters, sorts and loads more in place exactly
 * like the built-in one. Off a list, they show the newest products.
 */
abstract class ShopWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'shop';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-05' );
	}

	/**
	 * Assets.
	 *
	 * @return list<string>
	 */
	public function assets(): array {
		return array( 'shop', 'product-card' );
	}

	/**
	 * Renders when the product list module is on.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		return Shop::enabled() ? $this->piece( $settings ) : Html::none();
	}

	/**
	 * The piece of the list.
	 *
	 * @param Values $settings Settings.
	 */
	abstract protected function piece( Values $settings ): Html;

	/**
	 * A section of controls.
	 *
	 * @param string                     $label  Section name.
	 * @param list<array<string, mixed>> $fields Fields.
	 * @return list<array{id: string, label: string, fields: list<array<string, mixed>>}>
	 */
	protected function section( string $label, array $fields ): array {
		return array(
			array(
				'id'     => str_replace( '-', '_', $this->id() ) . '_section',
				'label'  => $label,
				'fields' => $fields,
			),
		);
	}
}
