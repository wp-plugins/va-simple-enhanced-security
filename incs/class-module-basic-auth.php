<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - Basic auth.
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
class VASES_MODUL_BASIC_AUTH {

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
            self::$instance = new VASES_MODUL_BASIC_AUTH;
        }

        return self::$instance;
    }

    function __construct() {
        $settings        = VASES_MODUL_SETTINGS_API::init();
        $flug_basic_auth = $settings->args['auth'];

        if ( 1 === $flug_basic_auth && ( !defined( 'VASES_AUTH_INVALIDATE' ) || !VASES_AUTH_INVALIDATE ) ) {
            add_action( 'login_init', array( &$this, 'basic_auth' ), 1 );
        }
    }

    /**
     * Basic auth
     * @link http://php.net/manual/ja/features.http-auth.php
     * @return void
     */
    public function basic_auth() {
        nocache_headers();

        // now logged in.
        if ( is_user_logged_in() ) {
            return;
        }

        // Authentication information.
        $php_user = ( isset( $_SERVER['PHP_AUTH_USER'] ) ) ? $_SERVER['PHP_AUTH_USER'] : '';
        $php_pass = ( isset( $_SERVER['PHP_AUTH_PW'] ) ) ? $_SERVER['PHP_AUTH_PW'] : '';
        $users    = VASES_MODUL_APIS::wp_users();

        if ( empty( $php_user )
             && empty( $php_pass )
             && ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) || isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) )
             && preg_match( '/Basic\s+(.*)\z/i', $_SERVER['HTTP_AUTHORIZATION'], $matches )
        ) {
            list( $php_user, $php_pass ) = explode( ':', base64_decode( $matches[1] ) );
            $php_user                    = strip_tags( $php_user );
            $php_pass                    = strip_tags( $php_pass );
        }

        // Successful authentication.
        if ( !empty( $php_user ) && !empty( $php_pass ) && ( $users[$php_user] === $php_pass ) ) {
            return;
        }

        // Failed to authenticate.
        header( 'WWW-Authenticate: Basic realm="Private Page"' );
        header( 'HTTP/1.0 401 Unauthorized' );
        header( 'Content-type: text/html; charset=' . mb_internal_encoding() );
        wp_die( __( 'Authorization Required.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), __( 'Authorization Required', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), array( 'response' => 401 ) );
    }
}
