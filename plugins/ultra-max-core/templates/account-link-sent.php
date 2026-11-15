<?php
/**
 * The page after the password reset link was sent (ACC-01).
 *
 * Replaces WooCommerce's myaccount/lost-password-confirmation.php. The field names, nonces
 * and hooks are WooCommerce's own; see Account\Login.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Login;

defined( 'ABSPATH' ) || exit;

Login::link_sent()->print();
