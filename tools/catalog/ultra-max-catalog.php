<?php
/**
 * Plugin Name:       Ultra Max Component Catalog
 * Description:       Development only. Live catalog of the Ultra Max components, rendered by the same code the products use. Never shipped to buyers.
 * Version:           0.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Requires Plugins:  ultra-max-core
 * Author:            Ultra Max
 * License:           GPL-2.0-or-later
 * Text Domain:       ultra-max-catalog
 *
 * @package UltraMax\Catalog
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Catalog.php';

UltraMax\Catalog\Catalog::boot( __FILE__ );