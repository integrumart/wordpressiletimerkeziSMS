<?php
/**
 * Plugin Name: İleti Merkezi SMS 2FA
 * Plugin URI: https://github.com/integrumart/wordpressiletimerkeziSMS
 * Description: WordPress SMS eklentisi - İleti Merkezi API ile 2 faktörlü doğrulama (2FA)
 * Version: 1.0.0
 * Author: Integrumart
 * Author URI: https://github.com/integrumart
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ileti-merkezi-sms
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMSMS_VERSION', '1.0.0');
define('IMSMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMSMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IMSMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once IMSMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-api.php';
require_once IMSMS_PLUGIN_DIR . 'includes/class-otp-manager.php';
require_once IMSMS_PLUGIN_DIR . 'includes/class-settings.php';
require_once IMSMS_PLUGIN_DIR . 'includes/class-login-handler.php';

/**
 * Main plugin class
 */
class Ileti_Merkezi_SMS {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('ileti-merkezi-sms', false, dirname(IMSMS_PLUGIN_BASENAME) . '/languages');
        
        // Initialize components
        IMSMS_Settings::get_instance();
        IMSMS_Login_Handler::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create options with default values
        add_option('imsms_api_username', '');
        add_option('imsms_api_password', '');
        add_option('imsms_sender_title', '');
        add_option('imsms_2fa_enabled', '1');
        add_option('imsms_otp_length', '6');
        add_option('imsms_otp_expiry', '300'); // 5 minutes
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up temporary OTP data
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_imsms_otp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_imsms_otp_%'");
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Ileti_Merkezi_SMS::get_instance();
