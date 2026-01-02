<?php
/**
 * Uninstall script for İleti Merkezi SMS plugin
 * 
 * This file is executed when the plugin is deleted via the WordPress admin panel.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('ileti_merkezi_username');
delete_option('ileti_merkezi_password');
delete_option('ileti_merkezi_sender');
delete_option('ileti_merkezi_enable_2fa');
delete_option('ileti_merkezi_otp_length');
delete_option('ileti_merkezi_otp_expiry');

// Drop OTP table - validate table name before dropping
$table_name = $wpdb->prefix . 'ileti_merkezi_otp';
// Ensure table name only contains valid characters
if (preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
}

// Delete user meta data (phone numbers)
delete_metadata('user', 0, 'phone_number', '', true);
delete_metadata('user', 0, 'billing_phone', '', true);

// Clear any cached data
wp_cache_flush();
