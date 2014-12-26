<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: VA Simple Enhanced Security
Plugin URI: https://github.com/VisuAlive/va-simple-enhanced-security
Description: This plugin will enhance the security of your WordPress.
Author: KUCKLU
Version: 0.3.4
Author URI: http://visualive.jp/
Text Domain: va-simple-enhanced-security
Domain Path: /langs
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
$vases_plugin_data = get_file_data( __FILE__, array('ver' => 'Version', 'langs' => 'Domain Path', 'mo' => 'Text Domain' ) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_URL',  plugin_dir_url(__FILE__) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_DOMAIN',      dirname( plugin_basename(__FILE__) ) );
define( 'VA_SIMPLE_ENHANCED_SECURITY_VERSION',     $vases_plugin_data['ver'] );
define( 'VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN',  $vases_plugin_data['mo'] );

/**
 * Include files
 */
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-settings-api.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-send-mail.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-apis.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-basic-auth.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-digest-auth.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-email-login.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-author-base.php' );
require_once( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_PATH . 'incs/class-module-etc.php' );

class VA_SIMPLE_ENHANCED_SECURITY {

    public $settings = array();

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
            self::$instance = new VA_SIMPLE_ENHANCED_SECURITY;
        }

        return self::$instance;
    }

    function __construct() {
        $settings_api   = VASES_MODUL_SETTINGS_API::init();
        $send_mail      = VASES_MODUL_SEND_MAIL::init();
        $basic_auth     = VASES_MODUL_BASIC_AUTH::init();
        $digest_auth    = VASES_MODUL_DIGEST_AUTH::init();
        $email_login    = VASES_MODUL_EMAIL_LOGIN::init();
        $author_base    = VASES_MODUL_AUTHOR_BASE::init();
        $etc            = VASES_MODUL_ETC::init();

        add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
    }

    public function plugins_loaded() {
        load_plugin_textdomain( VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN, false, VA_SIMPLE_ENHANCED_SECURITY_DOMAIN . '/langs' );
    }

    /**
     * Activation
     * @return void
     */
    public static function activation() {
        VASES_MODUL_APIS::edit_htaccess( 'activation' );
        flush_rewrite_rules();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall() {
        VASES_MODUL_APIS::edit_htaccess( 'uninstall' );
        delete_option( 'vases_settings' );
        delete_option( 'vases_http_auth_send_mail' );
        wp_clear_scheduled_hook( 'vases_http_auth_send_mail' );
        flush_rewrite_rules();
    }
}
VA_SIMPLE_ENHANCED_SECURITY::init();

register_activation_hook( __FILE__,   array( 'VA_SIMPLE_ENHANCED_SECURITY', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'VA_SIMPLE_ENHANCED_SECURITY', 'uninstall' ) );
register_uninstall_hook( __FILE__,    array( 'VA_SIMPLE_ENHANCED_SECURITY', 'uninstall' ) );
