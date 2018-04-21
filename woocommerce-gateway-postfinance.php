<?php
/*
 * Plugin Name: WooCommerce PostFinance Gateway
 * Plugin URI: 
 * Description: Take PostFinance payments in WooComerce.
 * Author: blackmesa.ch
 * Author URI: https://blackmesa.ch
 * Version: 1.0.0
 * Text Domain: woocommerce-gateway-postfinance
 * Domain Path: /languages
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Required minimums and constants
 */
define( 'WC_POSTFINANCE_VERSION', '1.0.0' );
define( 'WC_POSTFINANCE_MIN_PHP_VER', '5.6.0' );
define( 'WC_POSTFINANCE_MIN_WC_VER', '3.0.7' );
define( 'WC_POSTFINANCE_MAIN_FILE', __FILE__ );
define( 'WC_POSTFINANCE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_POSTFINANCE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );


if ( ! class_exists( 'WC_Postfinance' ) ) :

    class WC_Postfinance {

        /**
         * @var Singleton The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * @var Reference to logging class.
         */
        private static $log;

        /**
         * Returns the *Singleton* instance of this class.
         * @return Singleton The *Singleton* instance.
         */
        public static function get_instance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         * @return void
         */
        private function __clone() {}

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         * @return void
         */
        private function __wakeup() {}

        /**
         * Notices (array)
         * @var array
         */
        public $notices = array();

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        protected function __construct() {
            add_action( 'admin_init', array( $this, 'check_environment' ) );
            add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
            add_action( 'plugins_loaded', array( $this, 'init' ) );
        }

        /**
         * Init the plugin after plugins_loaded so environment variables are set.
         */
        public function init() {
            // Don't hook anything else in the plugin if we're in an incompatible environment
            if ( self::get_environment_warning() ) {
                return;
            }

            // Init the gateway itself
            $this->init_gateways();

            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
        }

        /**
         * Allow this class and other classes to add slug keyed notices (to avoid duplication)
         */
        public function add_admin_notice( $slug, $class, $message ) {
            $this->notices[ $slug ] = array(
                'class'   => $class,
                'message' => $message,
            );
        }

        /**
         * The backup sanity check, in case the plugin is activated in a weird way,
         * or the environment changes after activation. Also handles upgrade routines.
         */
        public function check_environment() {
            if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_POSTFINANCE_VERSION !== get_option( 'wc_postfinance_version' ) ) ) {
                $this->install();
                do_action( 'wc_postfinance_updated' );
            }

            $environment_warning = self::get_environment_warning();

            if ( $environment_warning && is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                $this->add_admin_notice( 'bad_environment', 'error', $environment_warning );
            }
        }

        /**
         * Updates the plugin version in db
         * @return bool
         */
        private static function _update_plugin_version() {
            delete_option( 'wc_postfinance_version' );
            add_option( 'wc_postfinance_version', WC_POSTFINANCE_VERSION );

            return true;
        }

        /**
         * Handles upgrade routines.
         */
        public function install() {
            if ( ! defined( 'WC_POSTFINANCE_INSTALLING' ) ) {
                define( 'WC_POSTFINANCE_INSTALLING', true );
            }

            $this->_update_plugin_version();
        }

        /**
         * Checks the environment for compatibility problems.  Returns a string with the first incompatibility
         * found or false if the environment has no problems.
         */
        static function get_environment_warning() {
            if ( version_compare( phpversion(), WC_POSTFINANCE_MIN_PHP_VER, '<' ) ) {
                $message = __( 'WooCommerce PostFinance - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-postfinance' );
                return sprintf( $message, WC_POSTFINANCE_MIN_PHP_VER, phpversion() );
            }

            if ( ! defined( 'WC_VERSION' ) ) {
                return __( 'WooCommerce PostFinance requires WooCommerce to be activated to work.', 'woocommerce-gateway-postfinance' );
            }

            if ( version_compare( WC_VERSION, WC_POSTFINANCE_MIN_WC_VER, '<' ) ) {
                $message = __( 'WooCommerce PostFinance - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'woocommerce-gateway-postfinance' );
                return sprintf( $message, WC_POSTFINANCE_MIN_WC_VER, WC_VERSION );
            }

            return false;
        }

        /**
         * Adds plugin action links
         */
        public function plugin_action_links( $links ) {
            $setting_link = $this->get_setting_link();

            $plugin_links = array(
                '<a href="' . $setting_link . '">' . __( 'Settings', 'woocommerce-gateway-postfinance' ) . '</a>',
            );

            return array_merge( $plugin_links, $links );
        }

        /**
         * Get setting link.
         * @return string Setting link
         */
        public function get_setting_link() {
            $use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

            $section_slug = $use_id_as_section ? 'postfinance' : strtolower( 'WC_Gateway_Postfinance' );

            return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
        }

        /**
         * Display any notices we've collected thus far (e.g. for connection, disconnection)
         */
        public function admin_notices() {
            foreach ( (array) $this->notices as $notice_key => $notice ) {
                echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
                echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
                echo "</p></div>";
            }
        }

        /**
         * Initialize the gateway. Called very early - in the context of the plugins_loaded action
         */
        public function init_gateways() {
            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

            include_once( dirname( __FILE__ ) . '/includes/class-wc-gateway-postfinance.php' );

            load_plugin_textdomain( 'woocommerce-gateway-postfinance', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
        }

        /**
         * Add the gateways to WooCommerce
         */
        public function add_gateways( $methods ) {
            $methods[] = 'WC_Gateway_Postfinance';

            return $methods;
        }

        /**
         * What rolls down stairs
         * alone or in pairs,
         * and over your neighbor's dog?
         * What's great for a snack,
         * And fits on your back?
         * It's log, log, log
         *
         * @param string $message Log message.
         * @param string $level   Optional. Default 'info'.
         *      emergency|alert|critical|error|warning|notice|info|debug
         */
        public static function log( $message, $level = 'info' ) {
            if ( empty( self::$log ) ) {
                self::$log = wc_get_logger();
            }

            /* self::$log->add( 'woocommerce-gateway-postfinance', $message ); */
            self::$log->log($level, $message, array( 'source' => 'woocommerce-gateway-postfinance' ) );
        }
    }

    $GLOBALS['wc_postfinance'] = WC_Postfinance::get_instance();

endif;
