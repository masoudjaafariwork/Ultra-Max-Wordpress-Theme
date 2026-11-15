<?php
/**
 * The login page, and the register form when the store allows it (ACC-01).
 *
 * Replaces WooCommerce's myaccount/form-login.php. The field names, nonces
 * and hooks are WooCommerce's own; see Account\Login.
 *
 * @package UltraMax\Core
 */

declare(strict_types=1);

use UltraMax\Core\Account\Login;

defined( 'ABSPATH' ) || exit;

Login::login()->print();
