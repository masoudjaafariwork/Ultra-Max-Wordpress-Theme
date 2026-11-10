<?php
/**
 * Store advantages widget (HDR-11).
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

namespace UltraMax\Core\Widgets\Footer;

use UltraMax\Core\Lib\UI\Html;
use UltraMax\Core\Lib\UI\TrustMarks;
use UltraMax\Core\Widgets\Store\TrustMarks as TrustWidget;
use UltraMax\Core\Widgets\Values;

/**
 * The row of advantages at the top of the footer: fast delivery, cash on
 * delivery, returns, genuine products and support.
 */
final class Perks extends TrustWidget {

	/**
	 * Id.
	 */
	public function id(): string {
		return 'perks';
	}

	/**
	 * Title.
	 */
	public function title(): string {
		return __( 'Store advantages', 'ultra-max-core' );
	}

	/**
	 * Icon.
	 */
	public function icon(): string {
		return 'eicon-info-box';
	}

	/**
	 * Panel group.
	 */
	public function group(): string {
		return 'footer';
	}

	/**
	 * Features.
	 *
	 * @return list<string>
	 */
	public function features(): array {
		return array( 'HDR-11' );
	}

	/**
	 * Controls: only the advantages section of the trust widget.
	 */
	public function controls(): array {
		$sections = parent::controls();
		return array_values( array_filter( $sections, static fn ( array $section ): bool => 'perks_section' === $section['id'] ) );
	}

	/**
	 * Renders.
	 *
	 * @param Values $settings Settings.
	 */
	public function render( Values $settings ): Html {
		$perks = array();
		foreach ( $settings->rows( 'perks' ) as $row ) {
			if ( '' !== $row->string( 'title' ) ) {
				$perks[] = array(
					'icon'  => '' === $row->string( 'icon' ) ? 'check' : $row->string( 'icon' ),
					'title' => $row->string( 'title' ),
					'text'  => $row->string( 'text' ),
				);
			}
		}
		return TrustMarks::render( array( 'perks' => $perks ) );
	}
}
