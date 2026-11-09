<?php
/**
 * Contract of the per-marketplace license module.
 *
 * @package UltraMax\License
 */

declare(strict_types=1);

namespace UltraMax\License;

/**
 * What every marketplace license module provides.
 *
 * The packaging script copies exactly one implementation, named
 * `MarketLicense`, into each product: the one for the marketplace being
 * built. Product code only talks to this interface.
 */
interface License {

	/**
	 * Machine name of the marketplace, such as "rtl-theme" or "zhaket".
	 */
	public function market(): string;

	/**
	 * Current state of the license on this site.
	 */
	public function status(): Status;

	/**
	 * Activates a purchase code on this site.
	 *
	 * @param string $code Purchase or license code the buyer entered.
	 */
	public function activate( string $code ): Status;
}
