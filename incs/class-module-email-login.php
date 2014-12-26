<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - allow email login.
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
class VASES_MODUL_EMAIL_LOGIN {

    /**
     * Holds the singleton instance of this class
     */
    static $instance = false;

    public $setting  = false;

    /**
     * Singleton
     * @static
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new VASES_MODUL_EMAIL_LOGIN;
        }

        return self::$instance;
    }

    function __construct() {
        $settings         = VASES_MODUL_SETTINGS_API::init();
        $flug_email_login = $settings->args['email_login'];

        if ( $flug_email_login == true ) {
            $this->setting = true;
            add_action( 'login_init', array( &$this, 'login_init'), 0 );
        }
    }

    function login_init() {
        $this->setting = true;
        add_action( 'login_enqueue_scripts', array( &$this, 'login_enqueue_scripts' ) );
        remove_filter( 'authenticate',       'wp_authenticate_username_password', 20, 3 );
        add_filter( 'authenticate',          array( &$this, 'allow_email_login' ), 20, 3 );
        add_filter( 'gettext',               array( &$this, 'changed_loginform_text' ), 20, 3 );
        add_filter( 'wp_login_errors',       array( &$this, 'override_incorrect_password' ) );
    }

    function login_enqueue_scripts() {
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'vases-login', VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_URL . 'assets/css/vases-login.css', array( 'dashicons' ) );
        wp_print_styles();
    }

    /**
     * Allow email login
     * @param  object  $user
     * @param  string  $username
     * @param  string  $password
     * @return boolean
     */
    function allow_email_login( $user, $username, $password ) {
        if ( ! empty( $username ) && ! empty( $password ) ) {
            if ( is_email( $username ) ) {
                $user = get_user_by( 'email', $username );
            } else {
                $user = get_user_by( 'login', $username );
            }

            if ( isset( $user, $user->user_login ) ) {
                $username = $user->user_login;
                return wp_authenticate_username_password( null, $username, $password );
            } else {
                return false;
            }
        }

        if ( ! empty( $username ) || ! empty( $password ) ) {
            return false;
        } else {
            return wp_authenticate_username_password( null, '', '' );
        }
    }

    /**
     * Changed login form text
     * @param  string $translated_text translated text
     * @param  string $text            original text
     * @param  string $domain          text domain
     * @return string
     */
    function changed_loginform_text( $translated_text, $text, $domain ) {
        global $pagenow;

        if ( is_user_logged_in() || 'wp-login.php' !== $pagenow ) {
            return $translated_text;
        }

        if ( 'Username' === $translated_text ) {
            $translated_text = 'Email address';
        }
        if ( 'ユーザー名' === $translated_text ) {
            $translated_text = 'メールアドレス';
        }

        return $translated_text;
    }

    /**
     * Override incorrect password message
     * @param  object $errors
     * @return object $errors
     */
    function override_incorrect_password( $errors ) {
        if( isset( $errors->errors['incorrect_password'] ) ) {
            $errors->errors = array( 'incorrect_password' => array( __( '<strong>ERROR</strong>: The password you entered for the username is incorrect.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ) ) );
        }

        return $errors;
    }
}


if ( VASES_MODUL_EMAIL_LOGIN::init()->setting == true && !function_exists( 'wp_authenticate' ) ) :
/**
 * Checks a user's login information and logs them in if it checks out.
 * wp_authenticate of wp-includes/pluggable.php.
 *
 * @since 2.5.0
 *
 * @param string $username User's username
 * @param string $password User's password
 * @return WP_User|WP_Error WP_User object if login successful, otherwise WP_Error object.
 */
function wp_authenticate( $username, $password ) {
    $username = sanitize_email( $username );
    $password = trim( $password );

    if ( ! empty( $username ) && ! empty( $password ) && is_email( $username ) ) {
        $user = get_user_by( 'email', $username );
        if ( isset( $user, $user->user_login ) ) {
            $username = $user->user_login;
        }
    }

    /**
     * Filter the user to authenticate.
     *
     * If a non-null value is passed, the filter will effectively short-circuit
     * authentication, returning an error instead.
     *
     * @since 2.8.0
     *
     * @param null|WP_User $user     User to authenticate.
     * @param string       $username User login.
     * @param string       $password User password
     */
    $user = apply_filters( 'authenticate', null, $username, $password );

    if ( $user == null ) {
        // TODO what should the error message be? (Or would these even happen?)
        // Only needed if all authentication handlers fail to return anything.
        $user = new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Invalid username or incorrect password.' ) );
    }

    $ignore_codes = array( 'empty_username', 'empty_password' );

    if ( is_wp_error( $user ) && !in_array( $user->get_error_code(), $ignore_codes ) ) {
        /**
         * Fires after a user login has failed.
         *
         * @since 2.5.0
         *
         * @param string $username User login.
         */
        do_action( 'wp_login_failed', $username );
    }

    return $user;
}
endif;
