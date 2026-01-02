<?php
/**
 * Plugin Name: İleti Merkezi SMS 2FA
 * Plugin URI: https://github.com/integrumart/wordpressiletimerkeziSMS
 * Description: WordPress SMS plugin with 2-step verification (2FA) using İleti Merkezi API
 * Version: 1.0.0
 * Author: Integrum Art
 * Author URI: https://github.com/integrumart
 * Text Domain: ileti-merkezi-sms
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ILETI_MERKEZI_SMS_VERSION', '1.0.0');
define('ILETI_MERKEZI_SMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ILETI_MERKEZI_SMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once ILETI_MERKEZI_SMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-api.php';
require_once ILETI_MERKEZI_SMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-otp.php';
require_once ILETI_MERKEZI_SMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-admin.php';
require_once ILETI_MERKEZI_SMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-auth.php';

/**
 * Main plugin class
 */
class Ileti_Merkezi_SMS {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize admin settings
        if (is_admin()) {
            Ileti_Merkezi_Admin::get_instance();
        }
        
        // Initialize authentication
        Ileti_Merkezi_Auth::get_instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ileti_merkezi_otp';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            otp_code varchar(10) NOT NULL,
            phone_number varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            expires_at datetime NOT NULL,
            is_used tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY otp_code (otp_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Set default options
        add_option('ileti_merkezi_username', '');
        add_option('ileti_merkezi_password', '');
        add_option('ileti_merkezi_sender', '');
        add_option('ileti_merkezi_enable_2fa', '1');
        add_option('ileti_merkezi_otp_length', '6');
        add_option('ileti_merkezi_otp_expiry', '5'); // 5 minutes
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }
}

// Initialize plugin
Ileti_Merkezi_SMS::get_instance();
