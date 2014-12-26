<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - Send mail.
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
class VASES_MODUL_SEND_MAIL {

    /**
     * Holds the singleton instance of this class
     */
    static $instance = false;

    public function __construct() {
        add_action( 'vases_http_auth_send_mail', array( &$this, 'send_mail_processing' ) );
        add_action( 'user_register',             array( &$this, 'send_mail_user_register' ) );
    }

    /**
     * Singleton
     * @static
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new VASES_MODUL_SEND_MAIL;
        }

        return self::$instance;
    }

    public function vases_mail( $to, $auth_user, $auth_pass ) {
        $sitename = wp_specialchars_decode( get_option( 'blogname' ) );
        /* translators: 1: HTTP auth id, 2: HTTP auth pw, 3: admin email */
        $message  = __( 'Hi,

This time, security of the site has been enhanced.
In order to open the administration screen, you must have HTTP authentication.
Will inform your HTTP authentication information.

[HTTP auth username] %1$s
[HTTP auth password] %2$s

Please contact us for any questions until administrator.
contact us : %3$s', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN );
        wp_mail( $to, sprintf( __( '[%s] HTTP authentication information.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), $sitename ), sprintf( $message, $auth_user, $auth_pass, wp_specialchars_decode( get_option( 'admin_email' ) ) ) );
    }

    public function send_mail_processing() {
        $users    = get_users();
        $settings = VASES_MODUL_SETTINGS_API::init();

        foreach( $users as $user ) {
            $auth_user = $user->user_login;
            $auth_pass = VASES_MODUL_APIS::make_rand_string();
            update_user_meta( $user->ID, 'vases_http_auth_user', $auth_user );
            update_user_meta( $user->ID, 'vases_http_auth_pass', VASES_MODUL_APIS::encrypt( $auth_user, $auth_pass ) );
            if ( isset( $user->user_email ) && !empty( $user->user_email ) ) {
                self::vases_mail( $user->user_email, $auth_user, $auth_pass );
            }
        }
    }

    public function send_mail_user_register( $user_id ) {
        $settings = VASES_MODUL_SETTINGS_API::init();
        $auth     = ( isset( $settings->args['auth'] ) && !empty( $settings->args['auth'] ) ) ? intval( $settings->args['auth'] ) : 0;
        $user     = get_userdata( $user_id );

        if ( ( 1 == $auth || 2 == $auth ) && false != $user ) {
            $user      = $user->data;
            $auth_user = $user->user_login;
            $auth_pass = VASES_MODUL_APIS::make_rand_string();
            add_user_meta( $user->ID, 'vases_http_auth_user', $auth_user );
            add_user_meta( $user->ID, 'vases_http_auth_pass', VASES_MODUL_APIS::encrypt( $auth_user, $auth_pass ) );
            if ( isset( $user->user_email ) && !empty( $user->user_email ) ) {
                self::vases_mail( $user->user_email, $auth_user, $auth_pass );
            }
        }
    }

    public static function send_mail() {
        wp_schedule_single_event( time(), 'vases_http_auth_send_mail' );
    }
}
