<?php
/**
 * Admin Settings Class
 * 
 * @package IletiMerkeziSMS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IMSMS_Admin_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add phone number field to user profile
        add_action('show_user_profile', array($this, 'add_phone_field_to_profile'));
        add_action('edit_user_profile', array($this, 'add_phone_field_to_profile'));
        add_action('personal_options_update', array($this, 'save_phone_field'));
        add_action('edit_user_profile_update', array($this, 'save_phone_field'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Ileti Merkezi SMS Settings', 'ileti-merkezi-sms'),
            __('SMS 2FA', 'ileti-merkezi-sms'),
            'manage_options',
            'imsms-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings Section
        add_settings_section(
            'imsms_api_section',
            __('Ileti Merkezi API Settings', 'ileti-merkezi-sms'),
            array($this, 'render_api_section'),
            'imsms-settings'
        );
        
        register_setting('imsms_settings', 'imsms_api_username', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('imsms_settings', 'imsms_api_password', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('imsms_settings', 'imsms_sender_title', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        add_settings_field(
            'imsms_api_username',
            __('API Username', 'ileti-merkezi-sms'),
            array($this, 'render_text_field'),
            'imsms-settings',
            'imsms_api_section',
            array('field' => 'imsms_api_username')
        );
        
        add_settings_field(
            'imsms_api_password',
            __('API Password', 'ileti-merkezi-sms'),
            array($this, 'render_password_field'),
            'imsms-settings',
            'imsms_api_section',
            array('field' => 'imsms_api_password')
        );
        
        add_settings_field(
            'imsms_sender_title',
            __('Sender Title', 'ileti-merkezi-sms'),
            array($this, 'render_text_field'),
            'imsms-settings',
            'imsms_api_section',
            array('field' => 'imsms_sender_title')
        );
        
        // OTP Settings Section
        add_settings_section(
            'imsms_otp_section',
            __('OTP Settings', 'ileti-merkezi-sms'),
            array($this, 'render_otp_section'),
            'imsms-settings'
        );
        
        register_setting('imsms_settings', 'imsms_otp_length', array(
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('imsms_settings', 'imsms_otp_expiry', array(
            'sanitize_callback' => 'absint'
        ));
        
        register_setting('imsms_settings', 'imsms_max_attempts', array(
            'sanitize_callback' => 'absint'
        ));
        
        add_settings_field(
            'imsms_otp_length',
            __('OTP Length', 'ileti-merkezi-sms'),
            array($this, 'render_number_field'),
            'imsms-settings',
            'imsms_otp_section',
            array('field' => 'imsms_otp_length', 'default' => 6, 'min' => 4, 'max' => 8)
        );
        
        add_settings_field(
            'imsms_otp_expiry',
            __('OTP Expiry (minutes)', 'ileti-merkezi-sms'),
            array($this, 'render_number_field'),
            'imsms-settings',
            'imsms_otp_section',
            array('field' => 'imsms_otp_expiry', 'default' => 5, 'min' => 1, 'max' => 60)
        );
        
        add_settings_field(
            'imsms_max_attempts',
            __('Maximum Verification Attempts', 'ileti-merkezi-sms'),
            array($this, 'render_number_field'),
            'imsms-settings',
            'imsms_otp_section',
            array('field' => 'imsms_max_attempts', 'default' => 3, 'min' => 1, 'max' => 10)
        );
    }
    
    /**
     * Render API section description
     */
    public function render_api_section() {
        echo '<p>' . __('Configure your Ileti Merkezi API credentials. You can get these from your Ileti Merkezi account.', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Render OTP section description
     */
    public function render_otp_section() {
        echo '<p>' . __('Configure OTP (One-Time Password) settings for two-factor authentication.', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Render text field
     */
    public function render_text_field($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        echo '<input type="text" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Render password field
     */
    public function render_password_field($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        echo '<input type="password" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args) {
        $field = $args['field'];
        $default = isset($args['default']) ? $args['default'] : '';
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        $value = get_option($field, $default);
        
        echo '<input type="number" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '" ';
        if ($min !== '') echo 'min="' . esc_attr($min) . '" ';
        if ($max !== '') echo 'max="' . esc_attr($max) . '" ';
        echo 'class="small-text" />';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle test SMS
        if (isset($_POST['imsms_test_sms']) && check_admin_referer('imsms_test_sms')) {
            $this->handle_test_sms();
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('imsms_messages'); ?>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('imsms_settings');
                do_settings_sections('imsms-settings');
                submit_button(__('Save Settings', 'ileti-merkezi-sms'));
                ?>
            </form>
            
            <hr />
            
            <h2><?php _e('Test SMS', 'ileti-merkezi-sms'); ?></h2>
            <p><?php _e('Send a test SMS to verify your API configuration.', 'ileti-merkezi-sms'); ?></p>
            
            <form method="post">
                <?php wp_nonce_field('imsms_test_sms'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test_phone_number"><?php _e('Phone Number', 'ileti-merkezi-sms'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="test_phone_number" id="test_phone_number" 
                                   class="regular-text" placeholder="905xxxxxxxxx" />
                            <p class="description"><?php _e('Enter phone number with country code (e.g., 905xxxxxxxxx)', 'ileti-merkezi-sms'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Send Test SMS', 'ileti-merkezi-sms'), 'secondary', 'imsms_test_sms'); ?>
            </form>
            
            <hr />
            
            <h2><?php _e('User Instructions', 'ileti-merkezi-sms'); ?></h2>
            <p><?php _e('To enable 2FA for a user:', 'ileti-merkezi-sms'); ?></p>
            <ol>
                <li><?php _e('Go to Users → All Users and edit the user profile', 'ileti-merkezi-sms'); ?></li>
                <li><?php _e('Scroll down to the "SMS 2FA Phone Number" field', 'ileti-merkezi-sms'); ?></li>
                <li><?php _e('Enter the phone number with country code (e.g., 905xxxxxxxxx)', 'ileti-merkezi-sms'); ?></li>
                <li><?php _e('Save the profile', 'ileti-merkezi-sms'); ?></li>
            </ol>
            <p><?php _e('Once a phone number is set, the user will receive an SMS with a verification code on each login attempt.', 'ileti-merkezi-sms'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Handle test SMS
     */
    private function handle_test_sms() {
        $phone = sanitize_text_field($_POST['test_phone_number']);
        
        if (empty($phone)) {
            add_settings_error(
                'imsms_messages',
                'imsms_message',
                __('Please enter a phone number', 'ileti-merkezi-sms'),
                'error'
            );
            return;
        }
        
        $api = new IMSMS_Ileti_Merkezi_API();
        $message = __('This is a test message from Ileti Merkezi SMS plugin.', 'ileti-merkezi-sms');
        
        $result = $api->send_sms($phone, $message);
        
        if ($result['success']) {
            add_settings_error(
                'imsms_messages',
                'imsms_message',
                __('Test SMS sent successfully!', 'ileti-merkezi-sms'),
                'success'
            );
        } else {
            add_settings_error(
                'imsms_messages',
                'imsms_message',
                sprintf(__('Failed to send test SMS: %s', 'ileti-merkezi-sms'), $result['message']),
                'error'
            );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_imsms-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style('imsms-admin', IMSMS_PLUGIN_URL . 'assets/css/admin.css', array(), IMSMS_VERSION);
    }
    
    /**
     * Add phone field to user profile
     */
    public function add_phone_field_to_profile($user) {
        ?>
        <h3><?php _e('SMS Two-Factor Authentication', 'ileti-merkezi-sms'); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="imsms_phone_number"><?php _e('SMS 2FA Phone Number', 'ileti-merkezi-sms'); ?></label>
                </th>
                <td>
                    <input type="text" name="imsms_phone_number" id="imsms_phone_number" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'imsms_phone_number', true)); ?>" 
                           class="regular-text" placeholder="905xxxxxxxxx" />
                    <p class="description">
                        <?php _e('Enter your phone number with country code (e.g., 905xxxxxxxxx). When set, you will receive an SMS verification code on each login.', 'ileti-merkezi-sms'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save phone field
     */
    public function save_phone_field($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['imsms_phone_number'])) {
            $phone = sanitize_text_field($_POST['imsms_phone_number']);
            update_user_meta($user_id, 'imsms_phone_number', $phone);
        }
    }
}
