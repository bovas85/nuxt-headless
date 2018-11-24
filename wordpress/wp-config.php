<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 * You can get Mysql setttings from your web host.
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
define('DB_NAME', 'nuxt-wordpress');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'toor');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'nDZ$F9)H~FW88-5|{D.!%,m:B*+1.nRcMp]fOH9@lg4H#eo8{`Y&r`|io%=f,_Hq');
define('SECURE_AUTH_KEY',  'o^oIqE4XK(X5Ol709iMMQ%uGW[feZ;1]<HsB6#!t(bQ|~Q 0FcG/Om>Um$?2@UZ ');
define('LOGGED_IN_KEY',    ')Y!+{7ovys0n;#sQ}}+d`qI$X>3@]N9(Wh^kb[kTj2hC)`y+*k^j~Wv72kfyym=h');
define('NONCE_KEY',        'EuB>oFg>rt~RV[!=;|T|]fdJfqI+Qg}s8Y_KyA$f ^[tTJ->irb6VbAh,8!Llf{i');
define('AUTH_SALT',        'RJf0*A3Nz)Zd F!08,/Zqn]=q#{,:1i8eP@m[7/~MU0Z(dPj=l%|FaBMXdz+@R`P');
define('SECURE_AUTH_SALT', '`1,(MMA-Gz@C1Upc~&L#W|-Hj=<[oQOnuO+nN0J+?|dKVL;In_)~tI.7ujt;0>Gc');
define('LOGGED_IN_SALT',   'j6YUy/+^:$Cu2|hu^-2)pP^^6S>ZbD5bJm-AQ2|.u|k|C-2Yc)-|{O =)<?G2E+%');
define('NONCE_SALT',       '6UpMVQ0kk>AHq[0au=HdM6.1+<fLg9>++|q$.~*_HO(aH*1B?4~Dd6.f,sD;1+07');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'api_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', 'en_GB');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/**
 * Prevent file editing from WP admin.
 * Just set to false if you want to edit templates and plugins from WP admin.
 */
define('DISALLOW_FILE_EDIT', false);


/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
