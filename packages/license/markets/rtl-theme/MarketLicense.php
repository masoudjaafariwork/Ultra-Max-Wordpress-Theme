<?php
/**
 * License module for the Rtl-theme marketplace.
 *
 * @package UltraMax\License
 */

declare(strict_types=1);

namespace UltraMax\License;

/**
 * Connects the product to the Rtl-theme license service.
 *
 * The marketplace's license API is wired in phase 11, once the owner has a
 * seller account and the marketplace's official integration guide. Until
 * then this module reports that licensing is not available, so a test
 * package can be installed and reviewed without blocking anything.
 */
final class MarketLicense implements License {

	/**
	 * {@inheritDoc}
	 */
	public function market(): string {
		return 'rtl-theme';
	}

	/**
	 * {@inheritDoc}
	 */
	public function status(): Status {
		return Status::NotAvailable;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $code Purchase code; unused until the API is wired.
	 */
	public function activate( string $code ): Status {
		unset( $code );
		return Status::NotAvailable;
	}
}
