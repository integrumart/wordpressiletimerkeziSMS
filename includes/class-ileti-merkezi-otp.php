<?php
/**
 * OTP Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ileti_Merkezi_OTP {
    
    private static $instance = null;
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ileti_merkezi_otp';
    }
    
    /**
     * Generate OTP code
     * 
     * @param int $user_id User ID
     * @param string $phone_number Phone number
     * @return string|false OTP code or false on failure
     */
    public function generate_otp($user_id, $phone_number) {
        global $wpdb;
        
        // Generate random OTP
        $otp_length = get_option('ileti_merkezi_otp_length', 6);
        $otp_code = $this->generate_random_code($otp_length);
        
        // Calculate expiry time
        $expiry_minutes = get_option('ileti_merkezi_otp_expiry', 5);
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
        
        // Invalidate previous OTPs for this user
        $wpdb->update(
            $this->table_name,
            array('is_used' => 1),
            array('user_id' => $user_id, 'is_used' => 0),
            array('%d'),
            array('%d', '%d')
        );
        
        // Insert new OTP
        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'otp_code' => $otp_code,
                'phone_number' => $phone_number,
                'expires_at' => $expires_at,
                'is_used' => 0
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        
        if ($inserted) {
            return $otp_code;
        }
        
        return false;
    }
    
    /**
     * Generate random numeric code
     * 
     * @param int $length Code length
     * @return string Random code
     */
    private function generate_random_code($length = 6) {
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string)wp_rand($min, $max);
    }
    
    /**
     * Verify OTP code
     * 
     * @param int $user_id User ID
     * @param string $otp_code OTP code to verify
     * @return bool True if valid, false otherwise
     */
    public function verify_otp($user_id, $otp_code) {
        global $wpdb;
        
        $current_time = current_time('mysql');
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE user_id = %d 
            AND otp_code = %s 
            AND is_used = 0 
            AND expires_at > %s 
            ORDER BY created_at DESC 
            LIMIT 1",
            $user_id,
            $otp_code,
            $current_time
        ));
        
        if ($result) {
            // Mark OTP as used
            $wpdb->update(
                $this->table_name,
                array('is_used' => 1),
                array('id' => $result->id),
                array('%d'),
                array('%d')
            );
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Clean expired OTPs
     */
    public function cleanup_expired_otps() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$this->table_name} 
            WHERE expires_at < NOW() 
            OR is_used = 1"
        );
    }
    
    /**
     * Get user phone number
     * 
     * @param int $user_id User ID
     * @return string|false Phone number or false
     */
    public function get_user_phone($user_id) {
        $phone = get_user_meta($user_id, 'phone_number', true);
        
        if (empty($phone)) {
            $phone = get_user_meta($user_id, 'billing_phone', true);
        }
        
        return !empty($phone) ? $phone : false;
    }
    
    /**
     * Set user phone number
     * 
     * @param int $user_id User ID
     * @param string $phone Phone number
     * @return bool Success
     */
    public function set_user_phone($user_id, $phone) {
        return update_user_meta($user_id, 'phone_number', sanitize_text_field($phone));
    }
    
    /**
     * Send OTP via SMS
     * 
     * @param int $user_id User ID
     * @return array Result array with 'success' and 'message' keys
     */
    public function send_otp($user_id) {
        $phone = $this->get_user_phone($user_id);
        
        if (!$phone) {
            return array(
                'success' => false,
                'message' => __('Phone number not found for user', 'ileti-merkezi-sms')
            );
        }
        
        $otp_code = $this->generate_otp($user_id, $phone);
        
        if (!$otp_code) {
            return array(
                'success' => false,
                'message' => __('Failed to generate OTP', 'ileti-merkezi-sms')
            );
        }
        
        // Prepare SMS message
        $site_name = get_bloginfo('name');
        $message = sprintf(
            __('Your verification code for %s is: %s. This code will expire in %d minutes.', 'ileti-merkezi-sms'),
            $site_name,
            $otp_code,
            get_option('ileti_merkezi_otp_expiry', 5)
        );
        
        // Send SMS
        $api = new Ileti_Merkezi_API();
        $result = $api->send_sms($phone, $message);
        
        return $result;
    }
}
