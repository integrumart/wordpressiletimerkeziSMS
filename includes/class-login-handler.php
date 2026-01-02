<?php
/**
 * Login Handler
 *
 * @package Ileti_Merkezi_SMS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling login with 2FA
 */
class IMSMS_Login_Handler {
    
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
        // Hook into authentication process
        add_filter('authenticate', array($this, 'intercept_authentication'), 30, 3);
        
        // Handle OTP verification
        add_action('login_form', array($this, 'render_otp_form'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_scripts'));
        
        // Handle OTP verification submission
        add_action('init', array($this, 'handle_otp_verification'));
    }
    
    /**
     * Set login token cookie
     *
     * @param string $token Token value
     * @param int $expiry Expiry time in seconds
     */
    private function set_login_token_cookie($token, $expiry) {
        setcookie('imsms_login_token', $token, array(
            'expires' => time() + $expiry,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict'
        ));
    }
    
    /**
     * Clear login token cookie
     */
    private function clear_login_token_cookie() {
        setcookie('imsms_login_token', '', array(
            'expires' => time() - 3600,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict'
        ));
    }
    
    /**
     * Intercept authentication
     *
     * @param WP_User|WP_Error|null $user User object or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error
     */
    public function intercept_authentication($user, $username, $password) {
        // Check if 2FA is enabled
        if (get_option('imsms_2fa_enabled', '1') !== '1') {
            return $user;
        }
        
        // Only intercept successful authentication
        if (!is_wp_error($user) && $user instanceof WP_User) {
            // Check if we're verifying OTP
            if (isset($_POST['imsms_otp_verification']) && $_POST['imsms_otp_verification'] === '1') {
                return $user; // Let OTP verification handle this
            }
            
            // Generate and send OTP
            $otp_manager = new IMSMS_OTP_Manager();
            $result = $otp_manager->send_otp_to_user($user);
            
            if ($result['success']) {
                // Generate a unique token for this login attempt
                $token = wp_generate_password(32, false);
                
                // Store user ID with token for OTP verification (valid for OTP expiry time)
                $expiry = absint(get_option('imsms_otp_expiry', 300));
                set_transient('imsms_pending_login_' . $token, array(
                    'user_id' => $user->ID,
                    'username' => $username
                ), $expiry);
                
                // Store token in cookie for verification
                $this->set_login_token_cookie($token, $expiry);
                
                // Return error to prevent login and show OTP form
                return new WP_Error(
                    'imsms_otp_required',
                    __('A verification code has been sent to your phone. Please enter it below.', 'ileti-merkezi-sms')
                );
            } else {
                // SMS sending failed
                return new WP_Error(
                    'imsms_sms_failed',
                    $result['message']
                );
            }
        }
        
        return $user;
    }
    
    /**
     * Render OTP form
     */
    public function render_otp_form() {
        // Check if OTP verification is pending
        if (!isset($_COOKIE['imsms_login_token'])) {
            return;
        }
        
        $token = sanitize_text_field($_COOKIE['imsms_login_token']);
        $login_data = get_transient('imsms_pending_login_' . $token);
        
        if ($login_data === false) {
            // Token expired or invalid
            $this->clear_login_token_cookie();
            return;
        }
        
        $user_id = absint($login_data['user_id']);
        $otp_manager = new IMSMS_OTP_Manager();
        
        // Check if OTP exists
        if (!$otp_manager->has_otp($user_id)) {
            delete_transient('imsms_pending_login_' . $token);
            $this->clear_login_token_cookie();
            return;
        }
        
        $remaining = $otp_manager->get_remaining_expiry($user_id);
        $attempts = $otp_manager->get_attempts($user_id);
        $max_attempts = 3;
        
        ?>
        <div class="imsms-otp-container">
            <label for="imsms_otp_code">
                <?php esc_html_e('Verification Code', 'ileti-merkezi-sms'); ?>
            </label>
            <input type="text" 
                   name="imsms_otp_code" 
                   id="imsms_otp_code" 
                   class="input" 
                   maxlength="10"
                   autocomplete="off"
                   required 
                   autofocus />
            <input type="hidden" name="imsms_otp_verification" value="1" />
            <?php wp_nonce_field('imsms_otp_verify', 'imsms_otp_nonce'); ?>
            
            <div class="imsms-otp-info">
                <p>
                    <?php
                    printf(
                        esc_html__('Time remaining: %s', 'ileti-merkezi-sms'),
                        '<span class="imsms-otp-timer" data-expiry="' . esc_attr($remaining) . '">' . 
                        esc_html($this->format_time($remaining)) . 
                        '</span>'
                    );
                    ?>
                </p>
                <?php if ($attempts > 0): ?>
                    <p>
                        <?php
                        printf(
                            esc_html__('Attempts: %s', 'ileti-merkezi-sms'),
                            '<span class="imsms-otp-attempts">' . 
                            esc_html($attempts . '/' . $max_attempts) . 
                            '</span>'
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle OTP verification
     */
    public function handle_otp_verification() {
        // Check if this is OTP verification request
        if (!isset($_POST['imsms_otp_verification']) || $_POST['imsms_otp_verification'] !== '1') {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['imsms_otp_nonce']) || !wp_verify_nonce($_POST['imsms_otp_nonce'], 'imsms_otp_verify')) {
            return;
        }
        
        // Check if this is a login request
        if (!isset($_POST['log']) || !isset($_POST['pwd'])) {
            return;
        }
        
        // Check if pending login token exists
        if (!isset($_COOKIE['imsms_login_token'])) {
            return;
        }
        
        $token = sanitize_text_field($_COOKIE['imsms_login_token']);
        $login_data = get_transient('imsms_pending_login_' . $token);
        
        if ($login_data === false) {
            return;
        }
        
        $user_id = absint($login_data['user_id']);
        $otp_code = isset($_POST['imsms_otp_code']) ? sanitize_text_field($_POST['imsms_otp_code']) : '';
        
        // Verify OTP
        $otp_manager = new IMSMS_OTP_Manager();
        
        if ($otp_manager->verify_otp($user_id, $otp_code)) {
            // OTP verified - log in the user
            $user = get_user_by('id', $user_id);
            
            if ($user) {
                // Clear transient and cookie
                delete_transient('imsms_pending_login_' . $token);
                $this->clear_login_token_cookie();
                
                // Set authentication cookies
                wp_set_auth_cookie($user_id, isset($_POST['rememberme']));
                
                // Determine redirect URL with validation
                $redirect_to = isset($_REQUEST['redirect_to']) ? wp_unslash($_REQUEST['redirect_to']) : admin_url();
                $redirect_to = wp_validate_redirect($redirect_to, admin_url());
                $redirect_to = apply_filters('login_redirect', $redirect_to, $redirect_to, $user);
                
                // Redirect
                wp_safe_redirect($redirect_to);
                exit;
            }
        } else {
            // Invalid OTP - add error
            add_filter('wp_login_errors', array($this, 'add_invalid_otp_error'));
        }
    }
    
    /**
     * Add invalid OTP error message
     *
     * @param WP_Error $errors Login errors object
     * @return WP_Error
     */
    public function add_invalid_otp_error($errors) {
        $errors->add(
            'imsms_invalid_otp',
            __('Invalid verification code. Please try again.', 'ileti-merkezi-sms')
        );
        return $errors;
    }
    
    /**
     * Format time
     *
     * @param int $seconds Seconds
     * @return string Formatted time
     */
    private function format_time($seconds) {
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $mins, $secs);
    }
    
    /**
     * Enqueue login scripts
     */
    public function enqueue_login_scripts() {
        wp_enqueue_style(
            'imsms-login-style',
            IMSMS_PLUGIN_URL . 'assets/css/login.css',
            array(),
            IMSMS_VERSION
        );
        
        wp_enqueue_script(
            'imsms-otp-timer',
            IMSMS_PLUGIN_URL . 'assets/js/otp-timer.js',
            array(),
            IMSMS_VERSION,
            true
        );
    }
}
