<?php
/**
 * OTP Manager Class
 * 
 * @package IletiMerkeziSMS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IMSMS_OTP_Manager {
    
    /**
     * Table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'imsms_otp';
    }
    
    /**
     * Generate OTP code
     * 
     * @param int $length OTP length
     * @return string Generated OTP
     */
    public function generate_otp($length = 6) {
        $otp_length = get_option('imsms_otp_length', $length);
        $otp = '';
        
        for ($i = 0; $i < $otp_length; $i++) {
            $otp .= wp_rand(0, 9);
        }
        
        return $otp;
    }
    
    /**
     * Create and store OTP for user
     * 
     * @param int $user_id User ID
     * @param string $phone_number Phone number
     * @return string|false Generated OTP or false on failure
     */
    public function create_otp($user_id, $phone_number) {
        global $wpdb;
        
        // Delete any existing OTP for this user
        $this->delete_user_otp($user_id);
        
        // Generate new OTP
        $otp = $this->generate_otp();
        
        // Calculate expiry time
        $expiry_minutes = get_option('imsms_otp_expiry', 5);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
        
        // Insert into database
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'otp_code' => $otp,
                'phone_number' => $phone_number,
                'expires_at' => $expires_at,
                'verified' => 0,
                'attempts' => 0
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $otp;
    }
    
    /**
     * Verify OTP code
     * 
     * @param int $user_id User ID
     * @param string $otp_code OTP code to verify
     * @return array Result with success status and message
     */
    public function verify_otp($user_id, $otp_code) {
        global $wpdb;
        
        // Get OTP record
        $otp_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d AND verified = 0 ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
        
        if (!$otp_record) {
            return array(
                'success' => false,
                'message' => __('No OTP found. Please request a new one.', 'ileti-merkezi-sms')
            );
        }
        
        // Check if OTP has expired
        if (strtotime($otp_record->expires_at) < time()) {
            return array(
                'success' => false,
                'message' => __('OTP has expired. Please request a new one.', 'ileti-merkezi-sms')
            );
        }
        
        // Check max attempts
        $max_attempts = get_option('imsms_max_attempts', 3);
        if ($otp_record->attempts >= $max_attempts) {
            return array(
                'success' => false,
                'message' => __('Maximum verification attempts exceeded. Please request a new OTP.', 'ileti-merkezi-sms')
            );
        }
        
        // Increment attempts
        $wpdb->update(
            $this->table_name,
            array('attempts' => $otp_record->attempts + 1),
            array('id' => $otp_record->id),
            array('%d'),
            array('%d')
        );
        
        // Verify OTP code
        if ($otp_record->otp_code !== $otp_code) {
            $remaining_attempts = $max_attempts - ($otp_record->attempts + 1);
            return array(
                'success' => false,
                'message' => sprintf(
                    __('Invalid OTP code. %d attempts remaining.', 'ileti-merkezi-sms'),
                    max(0, $remaining_attempts)
                )
            );
        }
        
        // Mark as verified
        $wpdb->update(
            $this->table_name,
            array('verified' => 1),
            array('id' => $otp_record->id),
            array('%d'),
            array('%d')
        );
        
        return array(
            'success' => true,
            'message' => __('OTP verified successfully', 'ileti-merkezi-sms')
        );
    }
    
    /**
     * Delete OTP for user
     * 
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function delete_user_otp($user_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            array('user_id' => $user_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get user's phone number from user meta
     * 
     * @param int $user_id User ID
     * @return string|false Phone number or false if not found
     */
    public function get_user_phone($user_id) {
        $phone = get_user_meta($user_id, 'imsms_phone_number', true);
        
        if (empty($phone)) {
            // Try to get from billing phone if WooCommerce is active
            if (class_exists('WooCommerce')) {
                $phone = get_user_meta($user_id, 'billing_phone', true);
            }
        }
        
        return !empty($phone) ? $phone : false;
    }
    
    /**
     * Send OTP via SMS
     * 
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @param string $phone_number Phone number
     * @return array Result with success status and message
     */
    public function send_otp_sms($user_id, $otp, $phone_number) {
        $api = new IMSMS_Ileti_Merkezi_API();
        
        // Get user info
        $user = get_userdata($user_id);
        
        // Prepare message
        $message = sprintf(
            __('Hello %s, Your verification code is: %s. Valid for %d minutes.', 'ileti-merkezi-sms'),
            $user->display_name,
            $otp,
            get_option('imsms_otp_expiry', 5)
        );
        
        // Send SMS
        return $api->send_sms($phone_number, $message);
    }
}
