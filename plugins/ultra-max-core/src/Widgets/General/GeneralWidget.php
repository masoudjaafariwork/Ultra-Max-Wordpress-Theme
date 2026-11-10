<?php
/**
 * Shared parts of the general widgets (HOME-12).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\General;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\Image;
use UltraMax\Core\Widgets\Widget;

/**
 * General widgets are drawn with the store's own design tokens, so they
 * follow the colour, font and corner settings like every other part.
 */
abstract class GeneralWidget extends Widget {

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'general';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HOME-12' );
	}

	/**
	 * An image from a media setting.
	 *
	 * @param array{id: int, url: string} $media Media setting.
	 * @param string                      $size  Image size.
	 */
	protected function image( array $media, string $size = 'medium' ): Html {
		return Image::render(
			array(
				'id'   => $media['id'],
				'url'  => $media['url'],
				'size' => $size,
			)
		);
	}

	/**
	 * A one-section control list.
	 *
	 * @param string                     $label  Section label.
	 * @param list<array<string, mixed>> $fields Fields.
	 * @return list<array{id: string, label: string, fields: list<array<string, mixed>>}>
	 */
	protected function one( string $label, array $fields ): array {
		return array(
			array(
				'id'     => 'content_section',
				'label'  => $label,
				'fields' => $fields,
			),
		);
	}
}
