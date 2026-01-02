<?php
/**
 * Authentication and 2FA Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ileti_Merkezi_Auth {
    
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
        // Hook into WordPress authentication
        add_filter('authenticate', array($this, 'intercept_authentication'), 30, 3);
        add_action('login_form', array($this, 'display_otp_form'));
        add_action('login_enqueue_scripts', array($this, 'enqueue_login_scripts'));
        add_action('init', array($this, 'handle_otp_verification'));
    }
    
    /**
     * Intercept authentication process
     */
    public function intercept_authentication($user, $username, $password) {
        // If 2FA is not enabled, return user as-is
        if (get_option('ileti_merkezi_enable_2fa', '1') != '1') {
            return $user;
        }
        
        // If user is WP_Error or null, return as-is (login failed for other reasons)
        if (is_wp_error($user) || empty($user)) {
            return $user;
        }
        
        // If already verified with OTP, allow login
        if (isset($_POST['ileti_merkezi_otp_code']) && isset($_POST['ileti_merkezi_user_id'])) {
            return $this->verify_and_authenticate($user);
        }
        
        // Check if user has phone number
        $otp_manager = Ileti_Merkezi_OTP::get_instance();
        $phone = $otp_manager->get_user_phone($user->ID);
        
        if (!$phone) {
            return new WP_Error(
                'no_phone_number',
                __('<strong>ERROR</strong>: Please add your phone number in your profile to use 2-step verification.', 'ileti-merkezi-sms')
            );
        }
        
        // Send OTP via SMS
        $result = $otp_manager->send_otp($user->ID);
        
        if (!$result['success']) {
            return new WP_Error(
                'sms_send_failed',
                sprintf(__('<strong>ERROR</strong>: Failed to send verification code: %s', 'ileti-merkezi-sms'), $result['message'])
            );
        }
        
        // Store user ID in session for OTP verification
        if (!session_id()) {
            session_start();
        }
        $_SESSION['ileti_merkezi_pending_user_id'] = $user->ID;
        $_SESSION['ileti_merkezi_pending_username'] = $username;
        
        // Return error to prevent immediate login and show OTP form
        return new WP_Error(
            'otp_required',
            __('<strong>Verification Required</strong>: A verification code has been sent to your phone. Please enter it below.', 'ileti-merkezi-sms'),
            array('otp_sent' => true, 'user_id' => $user->ID)
        );
    }
    
    /**
     * Verify OTP and authenticate user
     */
    private function verify_and_authenticate($user) {
        if (!isset($_POST['ileti_merkezi_otp_nonce']) || 
            !wp_verify_nonce($_POST['ileti_merkezi_otp_nonce'], 'ileti_merkezi_verify_otp')) {
            return new WP_Error(
                'invalid_nonce',
                __('<strong>ERROR</strong>: Security verification failed.', 'ileti-merkezi-sms')
            );
        }
        
        $otp_code = sanitize_text_field($_POST['ileti_merkezi_otp_code']);
        $user_id = absint($_POST['ileti_merkezi_user_id']);
        
        // Verify OTP
        $otp_manager = Ileti_Merkezi_OTP::get_instance();
        if ($otp_manager->verify_otp($user_id, $otp_code)) {
            // OTP is valid, clear session
            if (!session_id()) {
                session_start();
            }
            unset($_SESSION['ileti_merkezi_pending_user_id']);
            unset($_SESSION['ileti_merkezi_pending_username']);
            
            // Return user to complete login
            return $user;
        } else {
            return new WP_Error(
                'invalid_otp',
                __('<strong>ERROR</strong>: Invalid or expired verification code. Please try again.', 'ileti-merkezi-sms'),
                array('otp_sent' => true, 'user_id' => $user_id)
            );
        }
    }
    
    /**
     * Display OTP verification form
     */
    public function display_otp_form() {
        if (!session_id()) {
            session_start();
        }
        
        // Only show OTP form if there's a pending verification
        if (!isset($_SESSION['ileti_merkezi_pending_user_id'])) {
            return;
        }
        
        $user_id = $_SESSION['ileti_merkezi_pending_user_id'];
        ?>
        <style>
            #ileti-merkezi-otp-form {
                margin-top: 20px;
                padding: 15px;
                background: #f0f0f1;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }
            #ileti-merkezi-otp-form p {
                margin: 10px 0;
            }
            #ileti-merkezi-otp-form input[type="text"] {
                width: 100%;
                font-size: 24px;
                text-align: center;
                letter-spacing: 10px;
                padding: 10px;
            }
            #ileti-merkezi-otp-form .button {
                width: 100%;
                margin-top: 10px;
            }
            .ileti-merkezi-info {
                background: #d7f0ff;
                border-left: 4px solid #0073aa;
                padding: 10px;
                margin: 10px 0;
            }
        </style>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var loginForm = document.getElementById('loginform');
                var usernameField = document.getElementById('user_login');
                var passwordField = document.getElementById('user_pass');
                
                if (loginForm && usernameField && passwordField) {
                    // Hide username and password fields when OTP form is shown
                    if (document.getElementById('ileti-merkezi-otp-form')) {
                        usernameField.parentElement.style.display = 'none';
                        passwordField.parentElement.style.display = 'none';
                        document.querySelector('.forgetmenot')?.remove();
                        document.getElementById('wp-submit')?.remove();
                    }
                }
            });
        </script>
        <div id="ileti-merkezi-otp-form">
            <div class="ileti-merkezi-info">
                <p><strong><?php _e('2-Step Verification', 'ileti-merkezi-sms'); ?></strong></p>
                <p><?php _e('A verification code has been sent to your phone number. Please enter it below.', 'ileti-merkezi-sms'); ?></p>
            </div>
            <p>
                <label for="ileti_merkezi_otp_code">
                    <?php _e('Verification Code', 'ileti-merkezi-sms'); ?>
                </label>
                <input type="text" name="ileti_merkezi_otp_code" id="ileti_merkezi_otp_code" 
                       class="input" size="20" pattern="[0-9]*" inputmode="numeric" 
                       maxlength="<?php echo esc_attr(get_option('ileti_merkezi_otp_length', 6)); ?>"
                       autocomplete="off" required autofocus>
            </p>
            <input type="hidden" name="ileti_merkezi_user_id" value="<?php echo esc_attr($user_id); ?>">
            <?php wp_nonce_field('ileti_merkezi_verify_otp', 'ileti_merkezi_otp_nonce'); ?>
            <p class="submit">
                <button type="submit" name="wp-submit" class="button button-primary button-large">
                    <?php _e('Verify Code', 'ileti-merkezi-sms'); ?>
                </button>
            </p>
            <p style="text-align: center; margin-top: 15px;">
                <a href="<?php echo esc_url(wp_login_url() . '?ileti_merkezi_resend=1'); ?>">
                    <?php _e('Resend verification code', 'ileti-merkezi-sms'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Handle OTP verification
     */
    public function handle_otp_verification() {
        // Handle resend request
        if (isset($_GET['ileti_merkezi_resend']) && $_GET['ileti_merkezi_resend'] == '1') {
            if (!session_id()) {
                session_start();
            }
            
            if (isset($_SESSION['ileti_merkezi_pending_user_id'])) {
                $user_id = $_SESSION['ileti_merkezi_pending_user_id'];
                $otp_manager = Ileti_Merkezi_OTP::get_instance();
                $otp_manager->send_otp($user_id);
                
                // Redirect back to login page
                wp_redirect(wp_login_url());
                exit;
            }
        }
        
        // Cleanup expired OTPs periodically
        if (wp_rand(1, 100) == 1) {
            $otp_manager = Ileti_Merkezi_OTP::get_instance();
            $otp_manager->cleanup_expired_otps();
        }
    }
    
    /**
     * Enqueue login scripts
     */
    public function enqueue_login_scripts() {
        // Add any custom styles or scripts for login page if needed
    }
}
