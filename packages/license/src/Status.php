<?php
/**
 * License states.
 *
 * @package UltraMax\License
 */

declare(strict_types=1);

namespace UltraMax\License;

/**
 * State of a license on one site.
 */
enum Status: string {
	case Active       = 'active';
	case Inactive     = 'inactive';
	case Invalid      = 'invalid';
	case Development  = 'development';
	case NotAvailable = 'not-available';
}
