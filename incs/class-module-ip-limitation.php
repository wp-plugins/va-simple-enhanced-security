<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - IP limitation.
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
class VASES_MODUL_IP_LIMITATION {

    /**
     * Holds the singleton instance of this class
     */
    static $instance = false;

    /**
     * Singleton
     * @static
     */
    public static function init() {
        if ( ! self::$instance )
            self::$instance = new VASES_MODUL_IP_LIMITATION;

        return self::$instance;
    }

    function __construct() {
        add_action( 'plugins_loaded', array( &$this,  'plugins_loaded' ) );
    }

    /**
     * Plugin settings
     * @return array
     */
    public function plugins_loaded() {
        $settings           = VASES_MODUL_SETTINGS_API::init();
        $flug_ip_limitation = $settings->args['ip_limitation'];

        add_action( 'admin_print_footer_scripts', array( &$this, 'admin_print_footer_scripts' ), 21 );
        if ( isset( $flug_ip_limitation ) && is_array( $flug_ip_limitation ) && !empty( $flug_ip_limitation ) ) {
            add_action( 'login_init',                 array( &$this, 'ip_limitation' ), 0 );
            add_action( 'pre_option_enable_xmlrpc',   array( &$this, 'ip_limitation_xmlrpc' ), 0 );
        }
    }

    public function ip_limitation() {
        $check = self::ip_and_hostname_check();

        if ( $check )
            return false;

        self::ip_limitation_header();
    }

    public function ip_and_hostname_check() {
        $settings    = VASES_MODUL_SETTINGS_API::init();
        if ( is_user_logged_in() || !isset( $settings->args['ip_limitation'] ) || empty( $settings->args['ip_limitation'] ) || !is_array( $settings->args['ip_limitation'] ) )
            return true;

        $remote_addr = $_SERVER['REMOTE_ADDR'];
        $remote_host = @gethostbyaddr( $remote_addr );
        if ( !$remote_addr )
            $remote_addr = false;
        if ( !$remote_host || $remote_host == $remote_addr )
            $remote_host = false;

        if ( $remote_addr || $remote_host ) {
            if ( in_array( $remote_addr, $settings->args['ip_limitation'] ) || in_array( $remote_host, $settings->args['ip_limitation'] ) )
                return true;
        }

        return false;
    }

    public function ip_limitation_header() {
        // Failed to authenticate.
        header( 'HTTP/1.1 403 Forbidden' );
        header( 'Content-type: text/html; charset=' . mb_internal_encoding() );
        wp_die( __( '403 Forbidden.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), __( '403 Forbidden', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), array( 'response' => 403 ) );
    }

    public function admin_print_footer_scripts() {
        $screen = get_current_screen();

        if ( $screen->id === 'settings_page_va_simple_enhanced_security' ) :
        ?>
<script>
var vases_clickTarget = document.getElementById("vases-clear-button");
var vases_clickCount  = 0;
vases_clickTarget.onclick = function() {
    var cloneElement   = document.getElementById('vases_settings_ip_limitation_field_0'),
        appendNElement = document.getElementById('vases_settings_ip_limitation'),
        newNElement    = cloneElement.cloneNode(true),
        br             = document.createElement('br');

    newNElement.id    = 'vases_settings_ip_limitation_field_0_clone_' + vases_clickCount;
    newNElement.value = '';
    appendNElement.appendChild(newNElement);
    appendNElement.insertBefore(br, newNElement);
    vases_clickCount++;
};
</script>
        <?php
        endif;
    }
}
