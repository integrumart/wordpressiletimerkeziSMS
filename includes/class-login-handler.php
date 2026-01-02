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
                // Store user ID in session for OTP verification
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['imsms_pending_user_id'] = $user->ID;
                $_SESSION['imsms_pending_username'] = $username;
                
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
        if (!session_id()) {
            session_start();
        }
        
        // Check if OTP verification is pending
        if (!isset($_SESSION['imsms_pending_user_id'])) {
            return;
        }
        
        $user_id = absint($_SESSION['imsms_pending_user_id']);
        $otp_manager = new IMSMS_OTP_Manager();
        
        // Check if OTP exists
        if (!$otp_manager->has_otp($user_id)) {
            unset($_SESSION['imsms_pending_user_id']);
            unset($_SESSION['imsms_pending_username']);
            return;
        }
        
        $remaining = $otp_manager->get_remaining_expiry($user_id);
        $attempts = $otp_manager->get_attempts($user_id);
        $max_attempts = 3;
        
        ?>
        <style>
            .imsms-otp-container {
                margin: 20px 0;
                padding: 15px;
                background: #f0f0f1;
                border-radius: 4px;
            }
            .imsms-otp-container label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .imsms-otp-container input[type="text"] {
                width: 100%;
                padding: 8px;
                font-size: 24px;
                letter-spacing: 10px;
                text-align: center;
                border: 2px solid #2271b1;
                border-radius: 4px;
                margin-bottom: 10px;
            }
            .imsms-otp-info {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .imsms-otp-timer {
                color: #d63638;
                font-weight: 600;
            }
            .imsms-otp-attempts {
                color: #d63638;
                font-weight: 600;
            }
        </style>
        
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
        
        <script>
        (function() {
            var timer = document.querySelector('.imsms-otp-timer');
            if (!timer) return;
            
            var remaining = parseInt(timer.getAttribute('data-expiry'));
            
            function formatTime(seconds) {
                var mins = Math.floor(seconds / 60);
                var secs = seconds % 60;
                return mins + ':' + (secs < 10 ? '0' : '') + secs;
            }
            
            function updateTimer() {
                remaining--;
                if (remaining <= 0) {
                    timer.textContent = '0:00';
                    timer.style.color = '#d63638';
                    return;
                }
                timer.textContent = formatTime(remaining);
                setTimeout(updateTimer, 1000);
            }
            
            if (remaining > 0) {
                setTimeout(updateTimer, 1000);
            }
        })();
        </script>
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
        
        // Check if this is a login request
        if (!isset($_POST['log']) || !isset($_POST['pwd'])) {
            return;
        }
        
        if (!session_id()) {
            session_start();
        }
        
        // Check if pending user ID exists
        if (!isset($_SESSION['imsms_pending_user_id'])) {
            return;
        }
        
        $user_id = absint($_SESSION['imsms_pending_user_id']);
        $otp_code = isset($_POST['imsms_otp_code']) ? sanitize_text_field($_POST['imsms_otp_code']) : '';
        
        // Verify OTP
        $otp_manager = new IMSMS_OTP_Manager();
        
        if ($otp_manager->verify_otp($user_id, $otp_code)) {
            // OTP verified - log in the user
            $user = get_user_by('id', $user_id);
            
            if ($user) {
                // Clear session data
                unset($_SESSION['imsms_pending_user_id']);
                unset($_SESSION['imsms_pending_username']);
                
                // Set authentication cookies
                wp_set_auth_cookie($user_id, isset($_POST['rememberme']));
                
                // Determine redirect URL
                $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url();
                $redirect_to = apply_filters('login_redirect', $redirect_to, $redirect_to, $user);
                
                // Redirect
                wp_safe_redirect($redirect_to);
                exit;
            }
        } else {
            // Invalid OTP - add error
            add_filter('wp_login_errors', function($errors) {
                $errors->add(
                    'imsms_invalid_otp',
                    __('Invalid verification code. Please try again.', 'ileti-merkezi-sms')
                );
                return $errors;
            });
        }
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
    }
}
