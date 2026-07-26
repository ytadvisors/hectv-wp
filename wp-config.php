<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
require_once( ABSPATH . '/vendor/autoload.php' );

if (!class_exists('HECTV\HECTV_Admin')) {
    $autoloader = require_once(__DIR__ . '/wp-content/mu-plugins/hectv/autoloader.php');
    $autoloader('HECTV\\', __DIR__ . "/wp-content/mu-plugins/hectv/");
}

function hectv_env($name, $default = null) {
    $value = getenv($name);
    if ($value !== false) {
        return $value;
    }

    return isset($_SERVER[$name]) ? $_SERVER[$name] : $default;
}

define('DB_NAME', hectv_env('RDS_DB_NAME'));
define('DB_USER', hectv_env('RDS_USERNAME'));
define('DB_PASSWORD', hectv_env('RDS_PASSWORD'));
define('DB_HOST', hectv_env('RDS_HOSTNAME'));
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('AUTH_KEY',         hectv_env('AUTH_KEY'));
define('SECURE_AUTH_KEY',  hectv_env('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY',    hectv_env('LOGGED_IN_KEY'));
define('NONCE_KEY',        hectv_env('NONCE_KEY'));
define('AUTH_SALT',        hectv_env('AUTH_SALT'));
define('SECURE_AUTH_SALT', hectv_env('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT',   hectv_env('LOGGED_IN_SALT'));
define('NONCE_SALT',       hectv_env('NONCE_SALT'));
$jwt_auth_secret = hectv_env('JWT_AUTH_SECRET_KEY', hectv_env('AUTH_KEY'));
define('JWT_AUTH_SECRET_KEY', $jwt_auth_secret);
if (hectv_env('AWS_ACCESS_KEY_ID') !== null && hectv_env('AWS_SECRET_ACCESS_KEY') !== null) {
    define('AWS_ACCESS_KEY_ID', hectv_env('AWS_ACCESS_KEY_ID'));
    define('AWS_SECRET_ACCESS_KEY', hectv_env('AWS_SECRET_ACCESS_KEY'));
}
define('JWT_AUTH_CORS_ENABLE', true);
define('WP_DEBUG', hectv_env('WP_DEBUG', '0') === '1');
define('WP_DEBUG_LOG', hectv_env('WP_DEBUG_LOG', '0') === '1');
define('DISABLE_WP_CRON', hectv_env('DISABLE_WP_CRON', '0') === '1');
define('WP_AUTO_UPDATE_CORE', false);
define( 'COMPOSER_PATH', "vendor" );

$payments_disabled = hectv_env('HECTV_DISABLE_PAYMENTS', '0') === '1';
define('STRIPE_PUB_KEY', $payments_disabled ? '' : hectv_env('STRIPE_KEY', ''));
define('STRIPE_SECRET_KEY', $payments_disabled ? '' : hectv_env('STRIPE_SECRET_KEY', ''));

$https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
$server_port = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
$forwarded_proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : '';
$protocol = (($https && $https !== 'off') || $server_port === 443 || $forwarded_proto === 'https') ? "https://" : "http://";
$is_ssl = hectv_env('FORCE_SSL_ADMIN', '0') === '1';
define('FORCE_SSL_ADMIN', $is_ssl);
//define('FORCE_SSL_LOGIN', $is_ssl);
if($is_ssl) {
    if (!$forwarded_proto || $forwarded_proto === "https") {
        $_SERVER['HTTPS'] = 'on';
    }
}

$table_prefix  = 'wp_';

$canonical_host = hectv_env('HECTV_CANONICAL_HOST');
$allowed_hosts = array_filter(array_map('trim', explode(',', hectv_env('HECTV_ALLOWED_HOSTS', ''))));
$request_host = isset($_SERVER['HTTP_HOST']) ? strtolower(explode(':', $_SERVER['HTTP_HOST'])[0]) : '';
if ($canonical_host && $request_host && $request_host !== strtolower($canonical_host) && !in_array($request_host, array_map('strtolower', $allowed_hosts), true)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid host');
}
$http_host = $canonical_host ? $canonical_host : ($request_host ? $request_host : hectv_env('HTTP_HOST', 'localhost'));
define("WP_HOME", $protocol . $http_host );
define('WP_SITEURL',$protocol . $http_host );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
