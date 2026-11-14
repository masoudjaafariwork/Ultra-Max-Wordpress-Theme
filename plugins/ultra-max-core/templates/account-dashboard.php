<?php
/**
 * The account dashboard (ACC-01, ACC-04, ACC-07, ACC-08).
 *
 * Replaces WooCommerce's myaccount/dashboard.php and keeps its hooks.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Page;

defined( 'ABSPATH' ) || exit;

Page::dashboard()->print();

/** This action is documented in WooCommerce's myaccount/dashboard.php template. */
do_action( 'woocommerce_account_dashboard' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.
