<?php
/**
 * The lost password page (ACC-01).
 *
 * Replaces WooCommerce's myaccount/form-lost-password.php. The field names, nonces
 * and hooks are WooCommerce's own; see Account\Login.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Login;

defined( 'ABSPATH' ) || exit;

Login::lost_password()->print();
