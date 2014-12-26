<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * VA Simple Enhanced Security module - Settings api.
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
class VASES_MODUL_SETTINGS_API {

    public  $args    = false;

    /**
     * Holds the singleton instance of this class
     */
    static $instance = false;

    public function __construct() {
        $this->args = self::get_settings();
        $auth       = ( isset( $this->args['auth'] ) && !empty( $this->args['auth'] ) ) ? intval( $this->args['auth'] ) : 0;

        add_action( 'admin_menu',                   array( &$this, 'admin_menu' ) );
        add_action( 'admin_init',                   array( &$this, 'admin_init' ) );
        add_action( 'update_option_vases_settings', array( &$this, 'option_vases_settings' ) );
        add_action( 'add_option_vases_settings',    array( &$this, 'option_vases_settings' ) );
        if ( ( 1 == $auth || 2 == $auth ) && ( !defined( 'VASES_AUTH_INVALIDATE' ) || !VASES_AUTH_INVALIDATE ) ) {
            add_action( 'show_user_profile',            array( &$this, 'user_profile_fields' ) );
            add_action( 'edit_user_profile',            array( &$this, 'user_profile_fields' ) );
            add_action( 'user_profile_update_errors',   array( &$this, 'user_profile_update' ), 10, 3 );
        }
    }

    /**
     * Singleton
     * @static
     */
    public static function init() {
        if ( ! self::$instance ) {
            self::$instance = new VASES_MODUL_SETTINGS_API;
        }

        return self::$instance;
    }

    /**
     * Dummy settings
     * @return array
     */
    public function dummy_settings() {
        return array(
            'auth'                     => 0,
            'email_login'              => 0,
            'author_base'              => 0,
            'remove_body_class'        => 0,
            'xmlrpc_login_disallow'    => 0,
            'xmlrpc_pingback_disallow' => 0,
            'file_edit_disallow'       => 0,
            'unfiltered_html_disallow' => 0,
            'auto_update_plugin'       => 0,
            'auto_update_theme'        => 0,
        );
    }

    /**
     * Default settings
     * @return array
     */
    public function default_settings() {
        $args = array(
            'auth'                     => 0,
            'email_login'              => 1,
            'author_base'              => 1,
            'remove_body_class'        => 1,
            'xmlrpc_login_disallow'    => 1,
            'xmlrpc_pingback_disallow' => 1,
            'file_edit_disallow'       => 1,
            'unfiltered_html_disallow' => 0,
            'auto_update_plugin'       => 1,
            'auto_update_theme'        => 1,
        );
        return wp_parse_args( apply_filters( 'vases_default_settings', array() ), $args );
    }

    /**
     * Get settings
     * @return array
     */
    public function get_settings() {
        return wp_parse_args( get_option( 'vases_settings' ), self::default_settings() );
    }

    /**
     * Sanitize
     * @param  array $settings
     * @return array
     */
    public function validation_admin_form( $settings ) {
        $settings = wp_parse_args( $settings, self::dummy_settings() );

        foreach ( $settings as $key => $value ) {
            if ( 'auth' == $key ) {
                $settings[$key] = ( 0 === intval( $value ) || 1 === intval( $value ) || 2 === intval( $value ) ) ? intval( $value ): 0;
            } else {
                $settings[$key] = ( 0 === intval( $value ) || 1 === intval( $value ) ) ? intval( $value ): 0;
            }
        }

        return $settings;
    }

    public function option_vases_settings() {
        $send_mail = get_option( 'vases_http_auth_send_mail', 0 );
        $settings  = self::get_settings();

        flush_rewrite_rules();

        if ( !$send_mail && ( 1 == intval( $settings['auth'] ) || 2 == intval( $settings['auth'] ) ) ) {
            add_option( 'vases_http_auth_send_mail', 1 );
            VASES_MODUL_SEND_MAIL::send_mail();
        }
    }

    /**
     * Add admin menu
     * @return void
     */
    public function admin_menu() {
        add_options_page( __( 'Security', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), __( 'Security', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ), 'manage_options', 'va_simple_enhanced_security', array( &$this, 'admin_page' ) );
    }

    /**
     * Add admin page
     * @return string
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h2><span class="vasn-icon--VisuAlive"></span><?php _e( 'Security settings', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></h2>
            <form method="post" action="options.php" novalidate="novalidate">
                <?php
                    settings_fields( 'security_settings' );
                    do_settings_sections( 'security_settings' );
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add admin page init
     * @return void
     */
    public function admin_init() {
        $donation = apply_filters( 'vases_show_donation', true );

        register_setting( 'security_settings', 'vases_settings' );
        register_setting( 'validation_security_settings', 'vases_settings', array( &$this, 'validation_admin_form') );

        add_settings_section(
            'security_setting_sections',
            // __( 'Your section description', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            null,
            // array( &$this, 'settings_section_callback' ),
            null,
            'security_settings'
        );

        add_settings_field(
            'vases_auth',
            __( 'HTTP auth type', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_auth' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_email_login',
            __( 'Auth method of<br>login form', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_email_login' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_author_base',
            __( 'Author base', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_author_base' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_remove_body_class',
            __( 'Remove css class', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_remove_body_class' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_xmlrpc_login_disallow',
            __( 'XMLRPC login', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_xmlrpc_login_disallow' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_xmlrpc_pingback_disallow',
            __( 'XMLRPC pingback', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_xmlrpc_pingback_disallow' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_file_edit_disallow',
            __( 'File edit', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_file_edit_disallow' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_unfiltered_html_disallow',
            __( 'Unfiltered html', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_unfiltered_html_disallow' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_auto_update_plugin',
            __( 'Auto update plugin', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_auto_update_plugin' ),
            'security_settings',
            'security_setting_sections'
        );
        add_settings_field(
            'vases_auto_update_theme',
            __( 'Auto update theme', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
            array( &$this, 'render_auto_update_theme' ),
            'security_settings',
            'security_setting_sections'
        );

        if ( $donation == true ) {
            add_settings_field(
                'vases_donation',
                __( 'Donation', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ),
                array( &$this, 'render_donation' ),
                'security_settings',
                'security_setting_sections'
            );
        }
    }

    public function render_auth() {
        $settings = self::get_settings();
        $selected = ( isset( $settings['auth'] ) && !empty( $settings['auth'] ) ) ? $settings['auth']: 0;
        ?>
        <select name='vases_settings[auth]'>
            <option value="0" <?php selected( $selected, 0 ); ?>><?php _e( 'None', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></option>
            <option value="1" <?php selected( $selected, 1 ); ?>><?php _e( 'Basic auth', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></option>
            <option value="2" <?php selected( $selected, 2 ); ?>><?php _e( 'Digest auth', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></option>
        </select>
        <?php if ( defined( 'VASES_AUTH_INVALIDATE' ) && VASES_AUTH_INVALIDATE ) : ?>
        <br>
        <?php _e( '<span style="color: #f00; font-weight: bold;">※Because VASES_AUTH_INVALIDATE has been defined HTTP authentication function does not work.</span>', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        <?php endif; ?>
        <br>
        <?php _e( '※Protect the login page in HTTP authentication.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        <br>
        <?php _e( '※Username and password for exclusive use of the HTTP certification are transmitted to all users automatically for the first time only.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        <br>
        <?php _e( '※May not function properly depending on the environment.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        <?php
    }

    public function render_email_login() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['email_login'] ) && !empty( $settings['email_login'] ) ) ? intval( $settings['email_login'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[email_login]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Login in with email address and password.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?><br>
            <?php _e( '※Cannot login in the username and password.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_author_base() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['author_base'] ) && !empty( $settings['author_base'] ) ) ? intval( $settings['author_base'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[author_base]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Change base URL of author archive.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?><br>
            <?php _e( '※Change base URL in the permalink page.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_remove_body_class() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['remove_body_class'] ) && !empty( $settings['remove_body_class'] ) ) ? intval( $settings['remove_body_class'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[remove_body_class]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Remove css class of author info from body_class().', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_xmlrpc_login_disallow() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['xmlrpc_login_disallow'] ) && !empty( $settings['xmlrpc_login_disallow'] ) ) ? intval( $settings['xmlrpc_login_disallow'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[xmlrpc_login_disallow]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Disallow XMLRPC login', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_xmlrpc_pingback_disallow() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['xmlrpc_pingback_disallow'] ) && !empty( $settings['xmlrpc_pingback_disallow'] ) ) ? intval( $settings['xmlrpc_pingback_disallow'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[xmlrpc_pingback_disallow]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Disallow XMLRPC pingback', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_file_edit_disallow() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['file_edit_disallow'] ) && !empty( $settings['file_edit_disallow'] ) ) ? intval( $settings['file_edit_disallow'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[file_edit_disallow]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Disallow file edit.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_unfiltered_html_disallow() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['unfiltered_html_disallow'] ) && !empty( $settings['unfiltered_html_disallow'] ) ) ? intval( $settings['unfiltered_html_disallow'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[unfiltered_html_disallow]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Disallow unfiltered html.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_auto_update_plugin() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['auto_update_plugin'] ) && !empty( $settings['auto_update_plugin'] ) ) ? intval( $settings['auto_update_plugin'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[auto_update_plugin]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Automatically update the plugins that are installed on WordPress.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_auto_update_theme() {
        $settings = self::get_settings();
        $checked  = ( isset( $settings['auto_update_theme'] ) && !empty( $settings['auto_update_theme'] ) ) ? intval( $settings['auto_update_theme'] ): 0;
        ?>
        <label>
            <input type='checkbox' name='vases_settings[auto_update_theme]' <?php checked( intval( $checked ), 1 ); ?> value='1' />
            <?php _e( 'Automatically update the themes that are installed on WordPress.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?>
        </label>
        <?php
    }

    public function render_donation() {
        ?>
        <table>
            <tr valign="top">
                <td style="vertical-align: top; padding-bottom: 0; padding-left: 0;">
                    <p><img src="<?php echo esc_url( VA_SIMPLE_ENHANCED_SECURITY_PLUGIN_URL ); ?>assets/images/Im8FlIkh.jpeg" width="92" height="92" alt=""></p>
                </td>
                <td style="vertical-align: top; padding-bottom: 0;">
                    <p><?php _e( 'Do you like this plugin?', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></p>
                    <p><?php _e( 'VA Simple Enhanced Security plugin need your help.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></p>
                    <p><?php _e( 'This plugin may become better by your donation.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></p>
                    <p style="margin-top: 20px;"><a href="<?php echo esc_url( 'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=TNPKX9MAJL7C8' ); ?>" target="_blank"><img src="<?php echo esc_url( 'https://www.paypalobjects.com/en_US/GB/i/btn/btn_buynowCC_LG.gif'); ?>" alt="Buy Now"></a></p>
                    <p><?php _e( '※ Donations are voluntary.', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Show custom user profile fields
     * @param  obj $user The user object.
     * @return void
     */
    public function user_profile_fields( $user ) {
        ?>
        <h3><?php _e( 'HTTP auth info', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></h3>
        <table class="form-table">
        <tr>
            <th>
                <label for="vases_http_auth_user"><?php _e('HTTP auth username', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></label>
            </th>
            <td>
                <input disabled type="text" name="vases_http_auth_user" id="vases_http_auth_user" value="<?php echo esc_attr( get_the_author_meta( 'vases_http_auth_user', $user->ID ) ); ?>" class="regular-text">
                <span class="description"><?php _e('Usernames cannot be changed.'); ?></span>
            </td>
        </tr>
        <tr>
            <th>
                <label for="vases_http_auth_pass_1"><?php _e('HTTP auth password', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN ); ?></label>
            </th>
            <td>
                <input type="password" name="vases_http_auth_pass_1" id="vases_http_auth_pass_1" value="" class="regular-text">
                <br><span class="description"><?php _e('If you would like to change the password type a new one. Otherwise leave this blank.'); ?></span>
            </td>
        </tr>
        <tr>
            <th>
                <label for="vases_http_auth_pass_2"><?php _e('Repeat HTTP auth password', VA_SIMPLE_ENHANCED_SECURITY_TEXTDOMAIN); ?></label>
            </th>
            <td>
                <input type="password" name="vases_http_auth_pass_2" id="vases_http_auth_pass_2" value="" class="regular-text">
                <br><span class="description"><?php _e('Type your new password again.'); ?></span>
            </td>
        </tr>
        </table>
        <?php
    }

    public function user_profile_update( $errors, $update, $user ) {
        $auth_user   = get_user_meta( $user->ID, 'vases_http_auth_user', true );
        $auth_pass_1 = $auth_pass_2 = '';

        if ( !empty( $_POST['vases_http_auth_pass_1'] ) ) {
            $auth_pass_1 = sanitize_text_field( $_POST['vases_http_auth_pass_1'] );
        }
        if ( !empty( $_POST['vases_http_auth_pass_2'] ) ) {
            $auth_pass_2 = sanitize_text_field( $_POST['vases_http_auth_pass_2'] );
        }

        /* checking the error */
        if ( $auth_pass_1 !== $auth_pass_2 ) {
            $errors->add( 'vases_http_auth_pass', __( '<strong>ERROR</strong>: Please enter the same password in the two password fields.' ), array( 'form-field' => 'vases_http_auth_pass_1' ) );
        } elseif ( empty( $auth_pass_1 ) && !empty( $auth_pass_2 ) ) {
            $errors->add( 'vases_http_auth_pass', __( '<strong>ERROR</strong>: You entered your new password only once.' ), array( 'form-field' => 'vases_http_auth_pass_1' ) );
        } elseif ( !empty( $auth_pass_1 ) && empty( $auth_pass_2 ) ) {
            $errors->add( 'vases_http_auth_pass', __( '<strong>ERROR</strong>: You entered your new password only once.' ), array( 'form-field' => 'vases_http_auth_pass_2' ) );
        }
        if ( false !== strpos( wp_unslash( $auth_pass_1 ), "\\" ) ) {
            $errors->add( 'vases_http_auth_pass', __( '<strong>ERROR</strong>: Passwords may not contain the character "\\".' ), array( 'form-field' => 'vases_http_auth_pass_1' ) );
        }

        if ( $errors->get_error_codes() ) {
            return;
        }

        if ( preg_match( '/\A[¥x20-¥x7F].+\z/i', $auth_pass_1 ) && !empty( $auth_pass_1 ) ) {
            $auth_pass_1 = VASES_MODUL_APIS::encrypt( $auth_user, $auth_pass_1 );
            update_user_meta( $user->ID, 'vases_http_auth_pass', $auth_pass_1 );
        }
    }
}
