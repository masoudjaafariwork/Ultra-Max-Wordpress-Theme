<?php
/**
 * License module used in development builds.
 *
 * @package UltraMax\License
 */

declare(strict_types=1);

namespace UltraMax\License;

/**
 * Always reports the development state. Never shipped to buyers.
 */
final class MarketLicense implements License {

	/**
	 * {@inheritDoc}
	 */
	public function market(): string {
		return 'dev';
	}

	/**
	 * {@inheritDoc}
	 */
	public function status(): Status {
		return Status::Development;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $code Ignored in development builds.
	 */
	public function activate( string $code ): Status {
		unset( $code );
		return Status::Development;
	}
}
