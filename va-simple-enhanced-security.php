<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: VA Simple Enhanced Security
Plugin URI: https://github.com/VisuAlive/va-simple-enhanced-security
Description: This plugin will enhance the security of your WordPress.
Author: KUCKLU
Version: 0.0.2
Author URI: http://visualive.jp/
Text Domain: va-simple-enhanced-security
Domain Path: /languages
GitHub Plugin URI: https://github.com/VisuAlive/va-simple-enhanced-security
GitHub Branch: master
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

VisuAlive WordPress Plugin, Copyright (C) 2014 VisuAlive and KUCKLU.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
/**
 * VA SIMPLE ENHANCED SECURITY
 *
 * @package WordPress
 * @subpackage VA Simple Enhanced Security
 * @since VA Simple Enhanced Security 0.0.1
 * @author KUCKLU <kuck1u@visualive.jp>
 * @copyright Copyright (c) 2014 KUCKLU, VisuAlive.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://visualive.jp/
 */
if ( ! class_exists( 'VA_SIMPLE_ENHANCED_SECURITY' ) ) :
$vaes_plugin_data = get_file_data( __FILE__, array('ver' => 'Version', 'langs' => 'Domain Path', 'mo' => 'Text Domain' ) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_DOMAIN', dirname( plugin_basename(__FILE__) ) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_VERSION', $vaes_plugin_data['ver'] );
define( 'VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN', $vaes_plugin_data['mo'] );

class VA_SIMPLE_ENHANCED_SECURITY {
    function __construct() {
        add_action( 'plugins_loaded', array( &$this, '_plugins_loaded') );
        register_activation_hook( __FILE__, array( &$this, '_activation' ) );
        register_deactivation_hook( __FILE__, array( &$this, '_deactivation' ) );
        register_uninstall_hook( __FILE__, array( &$this, '_uninstall' ) );
    }

    /**
     * [Activation]
     * @return [type] [description]
     */
    function _activation() {
        self::_edit_htaccess( 'activation' );
        flush_rewrite_rules();
    }

    /**
     * [Deactivation]
     * @return [type] [description]
     */
    function _deactivation() {
        delete_option( 'rewrite_rules' );
    }

    /**
     * [Uninstall]
     * @return [type] [description]
     */
    function _uninstall() {
        self::_edit_htaccess( 'uninstall' );
        delete_option( 'author_base' );
        delete_option( 'rewrite_rules' );
    }

    function _plugins_loaded() {
        add_action( 'login_init', array( &$this, '_basic_auth' ), 0 );
        add_action( 'init', array( &$this, 'set_author_base' ) );
        add_action( 'admin_init', array( &$this, 'echo_author_base_field') );
        add_action( 'load-options-permalink.php', array( &$this, 'save_author_base') );
        remove_filter( 'authenticate', array( &$this, '_wp_authenticate_username_password' ), 20, 3 );
        add_filter( 'authenticate', array( &$this, '_allow_email_login' ), 20, 3 );
        add_filter( 'body_class', array( &$this, '_remove_body_class' ) );
        add_filter( 'gettext', array( &$this, '_changed_loginform_text' ), 20, 3 );
    }

    /**
     * Remove body class
     * @param  [array] $classes [Array of the css class]
     * @return [array]          [Array of the new css class]
     */
    function _remove_body_class( $classes ) {
        $subject     = $classes;
        $pattern     = array( '/\A(author\-[\w+\-]*)\z/i' );
        $replacement = array( '' );
        $classes     = preg_replace( $pattern, $replacement, $subject );

        return array_values( array_filter( $classes ) );
    }

    /**
     * [Allow email login]
     * @param  [object] $user     [description]
     * @param  [string] $username [description]
     * @param  [string] $password [description]
     * @return [boolean]          [description]
     */
    function _allow_email_login( $user, $username, $password ) {
        if ( ! empty( $username ) && ! empty( $password ) && is_email( $username ) ) {
            $user = get_user_by( 'email', $username );

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
     * [Changed login form text]
     * @param  [string] $translated_text [translated text]
     * @param  [string] $text            [original text]
     * @param  [string] $domain          [text domain]
     * @return [string]                  []
     */
    function _changed_loginform_text( $translated_text, $text, $domain ) {
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
     * [Edit htaccess]
     * @link https://github.com/wokamoto/wp-basic-auth
     * @param  boolean $action [description]
     * @return [type]          [description]
     */
    public function _edit_htaccess( $action = false ) {
        $htaccess_rewrite_rule = <<< EOM
# BEGIN VA Enhanced Security
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
</IfModule>
# END VA Enhanced Security


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
                    file_put_contents( ABSPATH.'.htaccess', $htaccess_rewrite_rule . $htaccess );
                break;
            case 'uninstall':
                    if ( strpos( $htaccess, $htaccess_rewrite_rule ) === false ) {
                        return;
                    }
                    file_put_contents( ABSPATH.'.htaccess', str_replace( $htaccess_rewrite_rule, '', $htaccess ) );
                break;
        }
    }

    /**
     * [Basic auth]
     * @link http://php.net/manual/ja/features.http-auth.php
     * @return [type] [description]
     */
    function _basic_auth() {
        $auth_user = ( isset( $_SERVER['PHP_AUTH_USER'] ) ) ? $_SERVER['PHP_AUTH_USER'] : '';
        $auth_pw   = ( isset( $_SERVER['PHP_AUTH_PW'] ) ) ? $_SERVER['PHP_AUTH_PW'] : '';
        if ( empty( $auth_user )
             && empty( $auth_pw )
             && isset( $_SERVER['HTTP_AUTHORIZATION'] )
             && preg_match( '/Basic\s+(.*)\z/i', $_SERVER['HTTP_AUTHORIZATION'], $matches )
        ) {
            list( $auth_user, $auth_pw ) = explode( ':', base64_decode( $matches[1] ) );
            $auth_user                   = strip_tags( $auth_user );
            $auth_pw                     = strip_tags( $auth_pw );
        }

        nocache_headers();

        if ( preg_match( '/\Aweb-proxy.lolipop.jp\z/i', $_SERVER['HTTP_X_FORWARDED_SERVER'] ) || is_user_logged_in() || ! is_wp_error( wp_authenticate( $auth_user, $auth_pw ) ) ) {
            return;
        }
        header( 'WWW-Authenticate: Basic realm="Private Page"' );
        header( 'HTTP/1.0 401 Unauthorized' );
        die( __( 'Authorization Required.', VA_SIMPLE_BASIC_AUTH_TEXTDOMAIN ) );
    }

    /**
     * [set_author_base]
     * @link http://wordpress.stackexchange.com/questions/77228
     * @param [type] $base [description]
     */
    function set_author_base( $base = null ) {
        global $wp_rewrite;
        $base = get_option('author_base');

        if ( isset( $base ) && $base ) {
            $wp_rewrite->author_base = $base;
            $wp_rewrite->author_structure = '/' . $wp_rewrite->author_base . '/%author%';
        }
    }

    /**
     * [echo_author_base_field]
     * @link http://wordpress.stackexchange.com/questions/77228
     * @return [type] [description]
     */
    function echo_author_base_field() {
        add_settings_field( 'author_base', __( 'Author Base', 'custom-author-base' ), array( &$this, 'add_author_base_field' ), 'permalink', 'optional', array( 'label_for' => 'author_base' ) );
    }

    /**
     * [save_author_base]
     * @link http://wordpress.stackexchange.com/questions/77228
     * @return [type] [description]
     */
    function save_author_base() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        if ( ! empty( $_POST['author_base'] ) ) {
            check_admin_referer('update-permalink');
            $res = sanitize_title_with_dashes( $_POST['author_base'] );
            // $res = stripslashes_deep( $_POST['author_base'] );
            update_option( 'author_base', $res );
            $this->set_author_base( $res );
        } else {
            delete_option( 'author_base' );
        }
    }

    /**
     * [add_author_base_field]
     * @link http://wordpress.stackexchange.com/questions/77228
     */
    function add_author_base_field() {
        printf( '<input type="text" class="regular-text" name="%1$s" id="%1$s" value="%2$s" />%3$s', esc_attr( 'author_base' ), esc_attr( get_option( 'author_base' ) ), PHP_EOL );
    }
}
new VA_SIMPLE_ENHANCED_SECURITY;
endif;
