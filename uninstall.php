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

// Drop OTP table
$table_name = $wpdb->prefix . 'ileti_merkezi_otp';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Delete user meta data (phone numbers)
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'phone_number'");

// Clear any cached data
wp_cache_flush();
