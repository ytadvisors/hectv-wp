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
define('DB_NAME', $_SERVER['RDS_DB_NAME']);
define('DB_USER', $_SERVER['RDS_USERNAME']);
define('DB_PASSWORD', $_SERVER['RDS_PASSWORD']);
define('DB_HOST', $_SERVER['RDS_HOSTNAME']);
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('AUTH_KEY',         $_SERVER['AUTH_KEY']);
define('SECURE_AUTH_KEY',  $_SERVER['SECURE_AUTH_KEY']);
define('LOGGED_IN_KEY',    $_SERVER['LOGGED_IN_KEY']);
define('NONCE_KEY',        $_SERVER['NONCE_KEY']);
define('AUTH_SALT',        $_SERVER['AUTH_SALT']);
define('SECURE_AUTH_SALT', $_SERVER['SECURE_AUTH_SALT']);
define('LOGGED_IN_SALT',   $_SERVER['LOGGED_IN_SALT']);
define('NONCE_SALT',       $_SERVER['NONCE_SALT']);
define('AWS_ACCESS_KEY_ID',$_SERVER['AWS_ACCESS_KEY_ID']);
define('AWS_SECRET_ACCESS_KEY',	$_SERVER['AWS_SECRET_ACCESS_KEY']);
define('JWT_AUTH_SECRET_KEY', 'R[/(_(.9s(y[YT.|C]3eH,ukOIk y|bH<n`8TvBN:GnttP5_Z|`d!t|t6E>$Qpp,');
define('JWT_AUTH_CORS_ENABLE', true);
define('WP_DEBUG', $_SERVER['WP_DEBUG'] == 1);
define('WP_DEBUG_LOG', $_SERVER['WP_DEBUG_LOG'] == 1);
define('WP_AUTO_UPDATE_CORE', false);
define( 'COMPOSER_PATH', "vendor" );

define('STRIPE_PUB_KEY',$_SERVER['STRIPE_KEY']);
define('STRIPE_SECRET_KEY',	$_SERVER['STRIPE_SECRET_KEY']);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$is_ssl = $_SERVER['FORCE_SSL_ADMIN'] == 1;
define('FORCE_SSL_ADMIN', $is_ssl);
//define('FORCE_SSL_LOGIN', $is_ssl);
if($is_ssl) {
    if (!$_SERVER['HTTP_X_FORWARDED_PROTO'] || $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https") {
        $_SERVER['HTTPS'] = 'on';
    }
}

$table_prefix  = 'wp_';

define("WP_HOME", $protocol . $_SERVER['HTTP_HOST'] );
define('WP_SITEURL',$protocol . $_SERVER['HTTP_HOST'] );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');