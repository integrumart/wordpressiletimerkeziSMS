<?php
/**
 * OTP Manager
 *
 * @package Ileti_Merkezi_SMS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for managing One-Time Passwords
 */
class IMSMS_OTP_Manager {
    
    /**
     * Generate OTP
     *
     * @param int $length OTP length (default: 6)
     * @return string Generated OTP
     */
    public function generate_otp($length = 6) {
        $length = absint($length);
        if ($length < 4) {
            $length = 4;
        }
        if ($length > 10) {
            $length = 10;
        }
        
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= wp_rand(0, 9);
        }
        
        return $otp;
    }
    
    /**
     * Store OTP for user
     *
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @param int $expiry Expiry time in seconds (default: 300)
     * @return bool Success
     */
    public function store_otp($user_id, $otp, $expiry = 300) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return false;
        }
        
        $expiry = absint($expiry);
        if ($expiry < 60) {
            $expiry = 60;
        }
        
        $data = array(
            'otp' => wp_hash($otp), // Store hashed OTP for security
            'attempts' => 0,
            'created' => time()
        );
        
        return set_transient('imsms_otp_' . $user_id, $data, $expiry);
    }
    
    /**
     * Verify OTP for user
     *
     * @param int $user_id User ID
     * @param string $otp OTP code to verify
     * @return bool True if valid, false otherwise
     */
    public function verify_otp($user_id, $otp) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return false;
        }
        
        $stored_data = get_transient('imsms_otp_' . $user_id);
        
        if ($stored_data === false) {
            return false;
        }
        
        // Check attempts limit (max 3 attempts)
        if (isset($stored_data['attempts']) && $stored_data['attempts'] >= 3) {
            $this->delete_otp($user_id);
            return false;
        }
        
        // Increment attempts
        $stored_data['attempts'] = isset($stored_data['attempts']) ? $stored_data['attempts'] + 1 : 1;
        
        // Verify OTP
        if (isset($stored_data['otp']) && hash_equals($stored_data['otp'], wp_hash($otp))) {
            // Valid OTP - delete it
            $this->delete_otp($user_id);
            return true;
        }
        
        // Update attempts count
        $expiry = $this->get_remaining_expiry($user_id);
        if ($expiry > 0) {
            set_transient('imsms_otp_' . $user_id, $stored_data, $expiry);
        }
        
        return false;
    }
    
    /**
     * Delete OTP for user
     *
     * @param int $user_id User ID
     * @return bool Success
     */
    public function delete_otp($user_id) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return false;
        }
        
        return delete_transient('imsms_otp_' . $user_id);
    }
    
    /**
     * Check if OTP exists for user
     *
     * @param int $user_id User ID
     * @return bool True if OTP exists
     */
    public function has_otp($user_id) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return false;
        }
        
        return get_transient('imsms_otp_' . $user_id) !== false;
    }
    
    /**
     * Get remaining expiry time
     *
     * @param int $user_id User ID
     * @return int Remaining seconds or 0
     */
    public function get_remaining_expiry($user_id) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return 0;
        }
        
        $timeout = get_option('_transient_timeout_imsms_otp_' . $user_id);
        if ($timeout === false) {
            return 0;
        }
        
        $remaining = $timeout - time();
        return max(0, $remaining);
    }
    
    /**
     * Get attempts count
     *
     * @param int $user_id User ID
     * @return int Attempts count
     */
    public function get_attempts($user_id) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return 0;
        }
        
        $stored_data = get_transient('imsms_otp_' . $user_id);
        
        if ($stored_data === false) {
            return 0;
        }
        
        return isset($stored_data['attempts']) ? absint($stored_data['attempts']) : 0;
    }
    
    /**
     * Send OTP to user
     *
     * @param WP_User $user User object
     * @return array Response with 'success' and 'message' keys
     */
    public function send_otp_to_user($user) {
        if (!$user || !$user->ID) {
            return array(
                'success' => false,
                'message' => __('Invalid user.', 'ileti-merkezi-sms')
            );
        }
        
        // Get user phone number
        $phone = get_user_meta($user->ID, 'billing_phone', true);
        if (empty($phone)) {
            $phone = get_user_meta($user->ID, 'phone', true);
        }
        
        if (empty($phone)) {
            return array(
                'success' => false,
                'message' => __('No phone number found for this user. Please add a phone number to your profile.', 'ileti-merkezi-sms')
            );
        }
        
        // Generate OTP
        $otp_length = absint(get_option('imsms_otp_length', 6));
        $otp = $this->generate_otp($otp_length);
        
        // Store OTP
        $expiry = absint(get_option('imsms_otp_expiry', 300));
        if (!$this->store_otp($user->ID, $otp, $expiry)) {
            return array(
                'success' => false,
                'message' => __('Failed to store verification code. Please try again.', 'ileti-merkezi-sms')
            );
        }
        
        // Prepare SMS message
        $message = sprintf(
            __('Your verification code is: %s. Valid for %d minutes.', 'ileti-merkezi-sms'),
            $otp,
            ceil($expiry / 60)
        );
        
        // Send SMS
        $api = new IMSMS_Ileti_Merkezi_API();
        $result = $api->send_sms($phone, $message);
        
        if (!$result['success']) {
            $this->delete_otp($user->ID);
        }
        
        return $result;
    }
}
