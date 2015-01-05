<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - changed author base.
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
class VASES_MODUL_AUTHOR_BASE {

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
            self::$instance = new VASES_MODUL_AUTHOR_BASE;
        }

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
        $settings         = VASES_MODUL_SETTINGS_API::init();
        $flug_author_base = $settings->args['author_base'];

        if ( $flug_author_base == true ) {
            add_action( 'init',                       array( &$this, 'set_author_base' ) );
            add_action( 'admin_init',                 array( &$this, 'echo_author_base_field' ) );
            add_action( 'load-options-permalink.php', array( &$this, 'save_author_base' ) );
        }
    }

    /**
     * set author base
     * @link http://wordpress.stackexchange.com/questions/77228
     * @param string $base
     */
    public function set_author_base( $base = null ) {
        global $wp_rewrite;
        $base = get_option('author_base');

        if ( isset( $base ) && $base ) {
            $wp_rewrite->author_base = $base;
            $wp_rewrite->author_structure = '/' . $wp_rewrite->author_base . '/%author%';
        }
    }

    /**
     * echo author base field
     * @link http://wordpress.stackexchange.com/questions/77228
     * @return void
     */
    public function echo_author_base_field() {
        add_settings_field( 'author_base', __( 'Author base', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), array( &$this, 'add_author_base_field' ), 'permalink', 'optional', array( 'label_for' => 'author_base' ) );
    }

    /**
     * save author base
     * @link http://wordpress.stackexchange.com/questions/77228
     * @return void
     */
    public function save_author_base() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
            return;
        }

        if ( isset( $_POST['author_base'] ) && ! empty( $_POST['author_base'] ) ) {
            check_admin_referer('update-permalink');
            // $res = sanitize_title_with_dashes( $_POST['author_base'] );
            // $res = stripslashes_deep( $_POST['author_base'] );
            $res = wp_strip_all_tags( $_POST['author_base'] );
            update_option( 'author_base', $res );
            self::set_author_base( $res );
        } else {
            delete_option( 'author_base' );
        }
    }

    /**
     * add author base field
     * @link http://wordpress.stackexchange.com/questions/77228
     * @return string
     */
    public function add_author_base_field() {
        printf( '<input type="text" class="regular-text" name="%1$s" id="%1$s" value="%2$s" />%3$s', esc_attr( 'author_base' ), esc_attr( get_option( 'author_base' ) ), PHP_EOL );
    }
}
