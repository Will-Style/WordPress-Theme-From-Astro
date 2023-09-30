<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'password' );

/** Database hostname */
define( 'DB_HOST', 'mysql' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         's#0Ld*Ln=QytA@`(9zR w:d4P=T*be,bJRvol{o{6JH>2JPn$j|nn[:8Eul~(b2}' );
define( 'SECURE_AUTH_KEY',  'DjK_N71J6AtrG|;<Zp,NWT{c3&0%qWbfd#q4Ix,zQX`Xj==16VM`X^?x7i3KUFP0' );
define( 'LOGGED_IN_KEY',    'j[`Q`MvqUuDH8@f<bOSU[=385q;pVbb$tE:8%u Xo]tt5$,Gu-rck@qwd=d8q.k8' );
define( 'NONCE_KEY',        ' &NVR,>eJ2f/D]*.xm4A!.&Hl}AMfMH98j7XOYSl9G3+dTAGQs^>CF<tSoS4a.ah' );
define( 'AUTH_SALT',        'E<1^aH&Iyq(yc-|Q.0eXfW[6^n_}K*s(w-Te#|rpth%.|t~PI6uF6C%i;7#LqGK<' );
define( 'SECURE_AUTH_SALT', '&{JuJ9tHcn|Yh?A.sYxHqi(2g3 U8A4%lbBHXAAlzzIbr=Z78m.b1[0SgtNft*={' );
define( 'LOGGED_IN_SALT',   'DS^2}.!]iC=/MUSQ5>+)g@}I7aJ][tDZ$=eQYKFyiKeFJ4ev $2bk0<k9./N3e.X' );
define( 'NONCE_SALT',       '&DXu^Nkn+p3fNJo=4f68CtV8v~OvUVtVUWwTtw3.U  v@/_?SeP^|Af7a /31asz' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

define('WP_HOME','https://wp-test2.local.localhost');
define('WP_SITEURL','https://wp-test2.local.localhost/app');

/** HTTPS強制を正しく有効化 **/
define('FORCE_SSL_ADMIN', true);
if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) 
     && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) 
{
    $_SERVER['HTTPS']='on';
}
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
