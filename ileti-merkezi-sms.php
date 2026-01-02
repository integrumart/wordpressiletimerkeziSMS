<?php
/**
 * Plugin Name: Ileti Merkezi SMS 2FA
 * Plugin URI: https://github.com/integrumart/wordpressiletimerkeziSMS
 * Description: WordPress SMS plugin integrated with Ileti Merkezi API with 2-step verification (2FA) for login
 * Version: 1.0.0
 * Author: IntegrumArt
 * Author URI: https://github.com/integrumart
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ileti-merkezi-sms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IMSMS_VERSION', '1.0.0');
define('IMSMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IMSMS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once IMSMS_PLUGIN_DIR . 'includes/class-ileti-merkezi-api.php';
require_once IMSMS_PLUGIN_DIR . 'includes/class-otp-manager.php';
require_once IMSMS_PLUGIN_DIR . 'includes/class-login-handler.php';
require_once IMSMS_PLUGIN_DIR . 'admin/class-admin-settings.php';

/**
 * Plugin activation hook
 */
function imsms_activate() {
    // Create necessary database tables
    global $wpdb;
    $table_name = $wpdb->prefix . 'imsms_otp';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        otp_code varchar(6) NOT NULL,
        phone_number varchar(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime NOT NULL,
        verified tinyint(1) DEFAULT 0,
        attempts tinyint(2) DEFAULT 0,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY otp_code (otp_code)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Set default options
    add_option('imsms_api_username', '');
    add_option('imsms_api_password', '');
    add_option('imsms_sender_title', '');
    add_option('imsms_otp_length', 6);
    add_option('imsms_otp_expiry', 5); // 5 minutes
    add_option('imsms_max_attempts', 3);
}
register_activation_hook(__FILE__, 'imsms_activate');

/**
 * Plugin deactivation hook
 */
function imsms_deactivate() {
    // Clean up scheduled events if any
    wp_clear_scheduled_hook('imsms_cleanup_expired_otps');
}
register_deactivation_hook(__FILE__, 'imsms_deactivate');

/**
 * Initialize plugin
 */
function imsms_init() {
    // Load text domain for translations
    load_plugin_textdomain('ileti-merkezi-sms', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize admin settings
    if (is_admin()) {
        new IMSMS_Admin_Settings();
    }
    
    // Initialize login handler
    new IMSMS_Login_Handler();
}
add_action('plugins_loaded', 'imsms_init');

/**
 * Schedule cleanup of expired OTPs
 */
function imsms_schedule_cleanup() {
    if (!wp_next_scheduled('imsms_cleanup_expired_otps')) {
        wp_schedule_event(time(), 'hourly', 'imsms_cleanup_expired_otps');
    }
}
add_action('wp', 'imsms_schedule_cleanup');

/**
 * Cleanup expired OTP codes
 */
function imsms_cleanup_expired_otps() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'imsms_otp';
    $wpdb->query("DELETE FROM $table_name WHERE expires_at < NOW()");
}
add_action('imsms_cleanup_expired_otps', 'imsms_cleanup_expired_otps');
