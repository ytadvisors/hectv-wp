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

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'hectv_wordpress');

/** MySQL database username */
define('DB_USER', 'wordpressuser');

/** MySQL database password */
define('DB_PASSWORD', 'moneytalks101');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'P+T!xVj[iQ u&U&VLeZ{U`c-i&d:|Fyuzt$uA?9?Ee6rOqbeQ%]b*%B>Ov`HJZE]');
define('SECURE_AUTH_KEY',  'yJ9om-PwvJZ[)32Mdu5..HA]C&11:EgLrEwuXz!yZ#*w?ok]zg>IgN.(h}L>}(E^');
define('LOGGED_IN_KEY',    '&U.e#W[4Tf7E~@TRa]}|dfbpo>$w{[XZR2}25tm|!xhSMWfKI8GPk<_UMYGxE0Uz');
define('NONCE_KEY',        '/4U;;>B?JAxw}HPeR*$}[hY{}ud g8{{,Yq)Zh8Son#>&%@FB>nJNTBXQb{J5hp9');
define('AUTH_SALT',        'rJj3nk4F3NrT=zW+M:levt|ZDd1,mgIEow$|HIX{I.mkz/]%mXH41] *6X6:CApD');
define('SECURE_AUTH_SALT', '8E0>SzRk;!RDE4Lfm%[Dl>=R,}9k{Qx1sDX,XL7]F$w)-fZ~9r[EaHC:`i|miB%[');
define('LOGGED_IN_SALT',   '$fW1;93!K,a-g]T)$43M*FaDN1XrZ}XQag&y//#;Nb)WJKO)3k$W)bSV)(rwT)zY');
define('NONCE_SALT',       'uACi($x]8[0?aN/;^I m$1xX]R5x@?<e!h>`LQhxJ[wwOt~Dq&PMDLLlnxo<sj^d');

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
