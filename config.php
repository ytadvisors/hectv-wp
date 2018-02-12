<?php
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


define('STRIPE_PUB_KEY',$_SERVER['STRIPE_KEY']);
define('STRIPE_SECRET_KEY',	$_SERVER['STRIPE_SECRET_KEY']);

$table_prefix  = 'wp_';
define('WP_DEBUG', $_SERVER['WP_DEBUG'] == 1);
define('WP_DEBUG_LOG', $_SERVER['WP_DEBUG_LOG'] == 1);
define('WP_AUTO_UPDATE_CORE', false);
if ( !defined('ABSPATH') )
    define('ABSPATH', dirname(__FILE__) . '/');
require_once(ABSPATH . 'wp-settings.php');
