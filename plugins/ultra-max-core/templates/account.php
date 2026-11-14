<?php
/**
 * The customer account of a logged in shopper (ACC-01 to ACC-08).
 *
 * Replaces WooCommerce's myaccount/my-account.php. WooCommerce's
 * navigation and content hooks still run inside Account\Page, so other
 * plugins' additions keep showing.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Page;

defined( 'ABSPATH' ) || exit;

Page::frame()->print();
