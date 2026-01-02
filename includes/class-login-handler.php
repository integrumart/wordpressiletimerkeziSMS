<?php
/**
 * Login Handler Class
 * 
 * @package IletiMerkeziSMS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IMSMS_Login_Handler {
    
    /**
     * OTP Manager instance
     */
    private $otp_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->otp_manager = new IMSMS_OTP_Manager();
        
        // Hook into WordPress authentication
        add_filter('authenticate', array($this, 'intercept_authentication'), 30, 3);
        
        // Handle OTP verification form submission
        add_action('login_form', array($this, 'render_otp_form'));
        add_action('init', array($this, 'handle_otp_verification'));
        
        // Add custom login error messages
        add_filter('login_errors', array($this, 'custom_login_errors'));
        
        // Enqueue styles for OTP form
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_styles'));
    }
    
    /**
     * Intercept authentication process
     * 
     * @param WP_User|WP_Error|null $user User object or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error Modified user or error
     */
    public function intercept_authentication($user, $username, $password) {
        // Skip if already an error
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Skip if user is null (no credentials provided)
        if (is_null($user)) {
            return $user;
        }
        
        // Skip if OTP verification is in progress
        if (isset($_POST['imsms_otp_code']) && isset($_POST['imsms_user_id'])) {
            return $user;
        }
        
        // Skip if already verified in this session
        if (isset($_SESSION['imsms_verified_' . $user->ID]) && $_SESSION['imsms_verified_' . $user->ID] === true) {
            unset($_SESSION['imsms_verified_' . $user->ID]);
            return $user;
        }
        
        // Get user's phone number
        $phone_number = $this->otp_manager->get_user_phone($user->ID);
        
        if (empty($phone_number)) {
            // If no phone number, allow login without 2FA
            // (Optional: you can make this mandatory by returning an error)
            return $user;
        }
        
        // Generate and send OTP
        $otp = $this->otp_manager->create_otp($user->ID, $phone_number);
        
        if ($otp === false) {
            return new WP_Error('otp_creation_failed', __('Failed to create OTP. Please try again.', 'ileti-merkezi-sms'));
        }
        
        // Send OTP via SMS
        $sms_result = $this->otp_manager->send_otp_sms($user->ID, $otp, $phone_number);
        
        if (!$sms_result['success']) {
            return new WP_Error('sms_send_failed', __('Failed to send verification code. Please try again.', 'ileti-merkezi-sms'));
        }
        
        // Store user ID in session for OTP verification
        if (!session_id()) {
            session_start();
        }
        $_SESSION['imsms_pending_user_id'] = $user->ID;
        $_SESSION['imsms_pending_username'] = $username;
        
        // Return error to prevent automatic login
        return new WP_Error('otp_required', __('Verification code sent to your phone. Please enter the code to continue.', 'ileti-merkezi-sms'));
    }
    
    /**
     * Render OTP verification form
     */
    public function render_otp_form() {
        if (!session_id()) {
            session_start();
        }
        
        // Only show OTP form if there's a pending verification
        if (!isset($_SESSION['imsms_pending_user_id'])) {
            return;
        }
        
        $user_id = intval($_SESSION['imsms_pending_user_id']);
        $phone_number = $this->otp_manager->get_user_phone($user_id);
        $masked_phone = $this->mask_phone_number($phone_number);
        
        ?>
        <style>
            .imsms-otp-container {
                margin: 20px 0;
                padding: 15px;
                background: #f0f0f1;
                border-radius: 4px;
            }
            .imsms-otp-container h3 {
                margin-top: 0;
                color: #2271b1;
            }
            .imsms-otp-input {
                width: 100%;
                padding: 10px;
                font-size: 24px;
                letter-spacing: 10px;
                text-align: center;
                border: 2px solid #2271b1;
                border-radius: 4px;
            }
            .imsms-otp-info {
                margin: 10px 0;
                font-size: 14px;
                color: #50575e;
            }
        </style>
        <div class="imsms-otp-container">
            <h3><?php _e('Two-Factor Authentication', 'ileti-merkezi-sms'); ?></h3>
            <p class="imsms-otp-info">
                <?php printf(__('A verification code has been sent to %s', 'ileti-merkezi-sms'), $masked_phone); ?>
            </p>
            <p>
                <label for="imsms_otp_code"><?php _e('Enter Verification Code:', 'ileti-merkezi-sms'); ?></label>
                <input type="text" name="imsms_otp_code" id="imsms_otp_code" 
                       class="imsms-otp-input" maxlength="6" pattern="[0-9]*" 
                       inputmode="numeric" autocomplete="one-time-code" required />
                <input type="hidden" name="imsms_user_id" value="<?php echo esc_attr($user_id); ?>" />
                <input type="hidden" name="imsms_verify_nonce" value="<?php echo wp_create_nonce('imsms_verify_otp'); ?>" />
            </p>
        </div>
        <?php
    }
    
    /**
     * Handle OTP verification
     */
    public function handle_otp_verification() {
        // Check if OTP verification is being submitted
        if (!isset($_POST['imsms_otp_code']) || !isset($_POST['imsms_user_id']) || !isset($_POST['imsms_verify_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['imsms_verify_nonce'], 'imsms_verify_otp')) {
            return;
        }
        
        if (!session_id()) {
            session_start();
        }
        
        $user_id = intval($_POST['imsms_user_id']);
        $otp_code = sanitize_text_field($_POST['imsms_otp_code']);
        
        // Verify OTP
        $result = $this->otp_manager->verify_otp($user_id, $otp_code);
        
        if ($result['success']) {
            // OTP verified, log user in
            $_SESSION['imsms_verified_' . $user_id] = true;
            
            // Get user
            $user = get_user_by('id', $user_id);
            
            if ($user) {
                // Clean up session variables
                unset($_SESSION['imsms_pending_user_id']);
                unset($_SESSION['imsms_pending_username']);
                
                // Log the user in
                wp_clear_auth_cookie();
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true);
                
                // Redirect to admin or intended page
                $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url();
                wp_safe_redirect($redirect_to);
                exit;
            }
        } else {
            // OTP verification failed
            add_filter('login_errors', function($errors) use ($result) {
                return $result['message'];
            });
        }
    }
    
    /**
     * Custom login error messages
     * 
     * @param string $errors Error messages
     * @return string Modified error messages
     */
    public function custom_login_errors($errors) {
        // You can customize error messages here if needed
        return $errors;
    }
    
    /**
     * Enqueue login page styles
     */
    public function enqueue_login_styles() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['imsms_pending_user_id'])) {
            wp_enqueue_style('imsms-login', IMSMS_PLUGIN_URL . 'assets/css/login.css', array(), IMSMS_VERSION);
        }
    }
    
    /**
     * Mask phone number for display
     * 
     * @param string $phone Phone number
     * @return string Masked phone number
     */
    private function mask_phone_number($phone) {
        if (strlen($phone) < 4) {
            return $phone;
        }
        
        $visible = 4;
        $masked_length = strlen($phone) - $visible;
        return str_repeat('*', $masked_length) . substr($phone, -$visible);
    }
}
