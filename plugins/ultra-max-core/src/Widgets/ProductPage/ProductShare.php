<?php
/**
 * Share button widget (BLD-04).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\ProductPage;

use UltraMax\Core\Lib\UI\Button;
use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Icon;
use UltraMax\Core\Product\Page;
use UltraMax\Core\Widgets\Values;

/**
 * Copy link, WhatsApp, Telegram and the phones own share sheet.
 */
final class ProductShare extends PageWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'product-share';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Share button', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-share';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'BLD-04', 'PDP-24' );
	}

	/**
	 * Fields.
	 *
	 * @return list<array<string, mixed>>
	 */
	protected function fields(): array {
		return array(
			array(
				'id'      => 'label',
				'type'    => 'text',
				'label'   => __( 'Text', 'ultra-max-core' ),
				'default' => __( 'Share', 'ultra-max-core' ),
			),
		);
	}

	/**
	 * The piece.
	 *
	 * @param \WC_Product $product  Product.
	 * @param Values      $settings Settings.
	 */
	protected function piece( \WC_Product $product, Values $settings ): Html {
		$dialog = Page::share_dialog();
		return Html::from(
			array(
				Button::render(
					array(
						'children' => array( Icon::render( 'send', 'size-4' ), $settings->string( 'label' ) ),
						'variant'  => 'ghost',
						'size'     => 'sm',
						'opens'    => Page::SHARE,
					)
				),
				$dialog,
			)
		);
	}
}
