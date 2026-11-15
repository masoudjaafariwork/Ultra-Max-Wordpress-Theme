<?php
/**
 * The new password page, reached from the emailed link (ACC-01).
 *
 * Replaces WooCommerce's myaccount/form-reset-password.php. The field names, nonces
 * and hooks are WooCommerce's own; see Account\Login.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Login;

defined( 'ABSPATH' ) || exit;

Login::reset_password( $args ?? array() )->print();
