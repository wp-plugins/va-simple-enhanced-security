<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - Digest auth.
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
class VASES_MODUL_DIGEST_AUTH {

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
            self::$instance = new VASES_MODUL_DIGEST_AUTH;
        }

        return self::$instance;
    }

    function __construct() {
        $settings        = VASES_MODUL_SETTINGS_API::init();
        $flug_basic_auth = $settings->args['auth'];

        if ( 2 === $flug_basic_auth && ( !defined( 'VASES_AUTH_INVALIDATE' ) || !VASES_AUTH_INVALIDATE ) ) {
            add_action( 'login_init', array( &$this, 'digest_auth' ), 1 );
        }
    }

    /**
     * Digest auth
     * @link http://php.net/manual/ja/features.http-auth.php
     * @return void
     */
    public function digest_auth() {
        // now logged in.
        if ( is_user_logged_in() ) {
            return;
        }

        // Authentication information.
        if ( ! isset( $_SERVER['PHP_AUTH_DIGEST'] ) ) {
            $headers = self::getallheaders();
            if ( isset( $headers['Authorization'] ) ) {
                $_SERVER['PHP_AUTH_DIGEST'] = $headers['Authorization'];
            } elseif ( isset( $_SERVER['HTTP_AUTHENTICATION'] ) && 0 === strpos( strtolower( $_SERVER['HTTP_AUTHENTICATION'] ), 'digest' ) ) {
                $_SERVER['PHP_AUTH_DIGEST'] = substr( $_SERVER['HTTP_AUTHORIZATION'], 7 );
            }
        }

        $realm = 'Restricted area';

        if ( !isset( $_SERVER['PHP_AUTH_DIGEST'] ) ) {
            self::require_login( $realm );
        }

        $users = VASES_MODUL_APIS::wp_users();
        $data  = self::http_digest_parse( $_SERVER['PHP_AUTH_DIGEST'] );

        if ( !isset( $users[$data['username']] ) ) {
            self::require_login( $realm );
        }

        // PHP_AUTH_DIGEST
        $A1 = md5( $data['username'] . ':' . $realm . ':' . $users[$data['username']] );
        $A2 = md5( $_SERVER['REQUEST_METHOD'].':'.$data['uri'] );
        $valid_response = md5( $A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2 );

        // Successful authentication.
        if ( $data['response'] != $valid_response ) {
            self::require_login( $realm );
        }
    }

    public function require_login( $realm ) {
        // Failed to authenticate.
        header( 'HTTP/1.1 401 Authorization Required' );
        header( 'WWW-Authenticate: Digest realm="' . $realm . '", nonce="'.uniqid( rand(),true ).'", algorithm=MD5, qop="auth"');
        header( 'Content-type: text/html; charset=' . mb_internal_encoding() );
        wp_die( __( 'Authorization Required.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), __( 'Authorization Required', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), array( 'response' => 401 ) );
    }

    /**
     * Parse the http auth header
     * @param  string $txt
     * @return boolean || array
     */
    public function http_digest_parse( $txt ) {
        $needed_parts = array( 'nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1) ;
        $data         = array();
        $keys         = implode( '|', array_keys( $needed_parts ) );

        preg_match_all( '@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER );

        foreach ( $matches as $m ) {
            $data[$m[1]] = str_replace( '\"', '', $m[3] ? $m[3] : $m[4]) ;
            unset( $needed_parts[$m[1]] );
        }

        return $needed_parts ? false : $data;
    }

    public function getallheaders() {
        global $HTTPSERVERVARS;
        $headers = array();

        if ( !empty( $HTTPSERVERVARS ) && isarray( $HTTPSERVERVARS ) ) {
            reset( $HTTPSERVERVARS );
            while( $eachHTTPSERVERVARS = each( $HTTPSERVERVARS ) ) {
                $name = $eachHTTPSERVERVARS['key'];
                $value = $eachHTTPSERVERVARS['value'];
                if( substr( $name, 0, 5 ) == 'HTTP' ) {
                    $headers[strreplace( ' ', '-', ucwords( strtolower( strreplace(' ', ' ', substr( $name, 5 ) ) ) ) )] = $value;
                }
            }
        }

        return $headers;
    }
}
