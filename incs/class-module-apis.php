<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - Apis.
 *
 * @package WordPress
 * @subpackage VA Simple Enhanced Security
 * @author KUCKLU <kuck1u@visualive.jp>
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPLv2.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-2.0.txt
 */
class VASES_MODUL_APIS {

    /**
     * Holds the singleton instance of this class
     */
    static $instance = false;

    /**
     * Singleton
     * @static
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new VASES_MODUL_APIS;
        }

        return self::$instance;
    }

    function __construct() {
    }

    /**
     * Edit htaccess
     * @link https://github.com/wokamoto/wp-basic-auth
     * @param  boolean $action
     * @return void
     */
    public static function edit_htaccess( $action = false ) {
        $htaccess_rewrite_rule = <<< EOM

# BEGIN VA Simple Enhanced Security
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
<IfModule mod_setenvif.c>
SetEnvIfNoCase ^Authorization$ "(.+)" PHP_AUTH_DIGEST=$1
</IfModule>

# END VA Simple Enhanced Security

EOM;

        if ( ! file_exists( ABSPATH.'.htaccess' ) || ! $action ) {
            return;
        }
        $htaccess = file_get_contents( ABSPATH.'.htaccess' );

        switch ( $action ) {
            case 'activation':
                    if ( strpos( $htaccess, $htaccess_rewrite_rule ) !== false ) {
                        return;
                    }
                    file_put_contents( ABSPATH.'.htaccess', $htaccess_rewrite_rule, FILE_APPEND | LOCK_EX );
                break;
            case 'uninstall':
                    if ( strpos( $htaccess, $htaccess_rewrite_rule ) === false ) {
                        return;
                    }
                    file_put_contents( ABSPATH.'.htaccess', str_replace( $htaccess_rewrite_rule, '', $htaccess ) );
                break;
        }
    }

    public static function wp_users() {
        $users  = get_users();
        $result = array();

        foreach( $users as $user ) {
            $auth_user          = get_user_meta( $user->ID, 'vases_http_auth_user', TRUE );
            $auth_pass          = get_user_meta( $user->ID, 'vases_http_auth_pass', TRUE );
            $result[$auth_user] = self::decrypt( $auth_user, $auth_pass );
        }

        return $result;
    }

    /**
     * encrypt
     * @link   https://wordpress.org/plugins/http-digest-auth/ thanks!
     * @param  string $key
     * @param  string $string
     * @return string
     */
    public static function encrypt( $key, $string ) {
        if( !function_exists( 'mcrypt_encrypt' ) ) {
            return $string;
        }

        return base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $key ), $string, MCRYPT_MODE_CBC, md5( md5( $key ) ) ) );
    }

    /**
     * decrypt
     * @link   https://wordpress.org/plugins/http-digest-auth/ thanks!
     * @param  string $key
     * @param  string $string
     * @return string
     */
    public static function decrypt( $key, $string ) {
        if( !function_exists( 'mcrypt_decrypt' ) ) {
            return $string;
        }

        $decrypted = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $key ), base64_decode( $string ), MCRYPT_MODE_CBC, md5( md5( $key ) ) ), "\0" );
        if( $string == self::encrypt( $key, $decrypted ) ) {
            return $decrypted;
        } else {
            return $string;
        }
    }

    /**
     * Generate a random string.
     * @param  integer $length
     * @return string
     */
    public static function make_rand_string( $length = 16 ) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789,.;:!?#@$%^&*(){}[]_=+/\-';
        $str   = '';

        for ( $i = 0; $i < $length; ++$i ) {
            $str .= $chars[mt_rand( 0, 86 ) ];
        }

        return $str;
    }
}
