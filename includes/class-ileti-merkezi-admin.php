<?php
/**
 * Admin Settings Page Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ileti_Merkezi_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('show_user_profile', array($this, 'add_phone_field'));
        add_action('edit_user_profile', array($this, 'add_phone_field'));
        add_action('personal_options_update', array($this, 'save_phone_field'));
        add_action('edit_user_profile_update', array($this, 'save_phone_field'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('İleti Merkezi SMS Settings', 'ileti-merkezi-sms'),
            __('İleti Merkezi SMS', 'ileti-merkezi-sms'),
            'manage_options',
            'ileti-merkezi-sms',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_username', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_password', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_sender', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        // 2FA Settings
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_enable_2fa', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox')
        ));
        
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_otp_length', array(
            'sanitize_callback' => 'absint',
            'default' => 6
        ));
        
        register_setting('ileti_merkezi_settings', 'ileti_merkezi_otp_expiry', array(
            'sanitize_callback' => 'absint',
            'default' => 5
        ));
        
        // Add settings sections and fields
        add_settings_section(
            'ileti_merkezi_api_section',
            __('API Credentials', 'ileti-merkezi-sms'),
            array($this, 'api_section_callback'),
            'ileti-merkezi-sms'
        );
        
        add_settings_field(
            'ileti_merkezi_username',
            __('API Username', 'ileti-merkezi-sms'),
            array($this, 'username_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_api_section'
        );
        
        add_settings_field(
            'ileti_merkezi_password',
            __('API Password', 'ileti-merkezi-sms'),
            array($this, 'password_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_api_section'
        );
        
        add_settings_field(
            'ileti_merkezi_sender',
            __('Sender Title', 'ileti-merkezi-sms'),
            array($this, 'sender_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_api_section'
        );
        
        // 2FA Settings Section
        add_settings_section(
            'ileti_merkezi_2fa_section',
            __('2FA Settings', 'ileti-merkezi-sms'),
            array($this, 'twofa_section_callback'),
            'ileti-merkezi-sms'
        );
        
        add_settings_field(
            'ileti_merkezi_enable_2fa',
            __('Enable 2FA', 'ileti-merkezi-sms'),
            array($this, 'enable_2fa_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_2fa_section'
        );
        
        add_settings_field(
            'ileti_merkezi_otp_length',
            __('OTP Length', 'ileti-merkezi-sms'),
            array($this, 'otp_length_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_2fa_section'
        );
        
        add_settings_field(
            'ileti_merkezi_otp_expiry',
            __('OTP Expiry (minutes)', 'ileti-merkezi-sms'),
            array($this, 'otp_expiry_field_callback'),
            'ileti-merkezi-sms',
            'ileti_merkezi_2fa_section'
        );
    }
    
    /**
     * Sanitize checkbox
     */
    public function sanitize_checkbox($input) {
        return ($input == '1' || $input == 'on') ? '1' : '0';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if form is submitted for test SMS
        if (isset($_POST['test_sms_submit']) && check_admin_referer('ileti_merkezi_test_sms')) {
            $this->handle_test_sms();
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('ileti_merkezi_settings');
                do_settings_sections('ileti-merkezi-sms');
                submit_button(__('Save Settings', 'ileti-merkezi-sms'));
                ?>
            </form>
            
            <hr>
            
            <h2><?php _e('Test SMS', 'ileti-merkezi-sms'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('ileti_merkezi_test_sms'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test_phone"><?php _e('Phone Number', 'ileti-merkezi-sms'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="test_phone" name="test_phone" class="regular-text" 
                                   placeholder="+90XXXXXXXXXX" required>
                            <p class="description"><?php _e('Enter phone number to send test SMS', 'ileti-merkezi-sms'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Send Test SMS', 'ileti-merkezi-sms'), 'secondary', 'test_sms_submit'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle test SMS
     */
    private function handle_test_sms() {
        if (!isset($_POST['test_phone'])) {
            return;
        }
        
        $phone = sanitize_text_field($_POST['test_phone']);
        $message = sprintf(__('Test message from %s', 'ileti-merkezi-sms'), get_bloginfo('name'));
        
        $api = new Ileti_Merkezi_API();
        $result = $api->send_sms($phone, $message);
        
        if ($result['success']) {
            add_settings_error('ileti_merkezi_messages', 'ileti_merkezi_message', 
                $result['message'], 'success');
        } else {
            add_settings_error('ileti_merkezi_messages', 'ileti_merkezi_message', 
                $result['message'], 'error');
        }
        
        settings_errors('ileti_merkezi_messages');
    }
    
    /**
     * Section callbacks
     */
    public function api_section_callback() {
        echo '<p>' . __('Enter your İleti Merkezi API credentials below.', 'ileti-merkezi-sms') . '</p>';
    }
    
    public function twofa_section_callback() {
        echo '<p>' . __('Configure 2-step verification settings.', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function username_field_callback() {
        $value = get_option('ileti_merkezi_username', '');
        echo '<input type="text" name="ileti_merkezi_username" value="' . esc_attr($value) . '" class="regular-text" required>';
    }
    
    public function password_field_callback() {
        $value = get_option('ileti_merkezi_password', '');
        echo '<input type="password" name="ileti_merkezi_password" value="' . esc_attr($value) . '" class="regular-text" required>';
    }
    
    public function sender_field_callback() {
        $value = get_option('ileti_merkezi_sender', '');
        echo '<input type="text" name="ileti_merkezi_sender" value="' . esc_attr($value) . '" class="regular-text" required>';
        echo '<p class="description">' . __('Sender name that will appear in SMS', 'ileti-merkezi-sms') . '</p>';
    }
    
    public function enable_2fa_field_callback() {
        $value = get_option('ileti_merkezi_enable_2fa', '1');
        echo '<input type="checkbox" name="ileti_merkezi_enable_2fa" value="1" ' . checked($value, '1', false) . '>';
        echo '<label>' . __('Enable 2-step verification for login', 'ileti-merkezi-sms') . '</label>';
    }
    
    public function otp_length_field_callback() {
        $value = get_option('ileti_merkezi_otp_length', 6);
        echo '<input type="number" name="ileti_merkezi_otp_length" value="' . esc_attr($value) . '" min="4" max="8" class="small-text">';
        echo '<p class="description">' . __('Length of OTP code (4-8 digits)', 'ileti-merkezi-sms') . '</p>';
    }
    
    public function otp_expiry_field_callback() {
        $value = get_option('ileti_merkezi_otp_expiry', 5);
        echo '<input type="number" name="ileti_merkezi_otp_expiry" value="' . esc_attr($value) . '" min="1" max="30" class="small-text">';
        echo '<p class="description">' . __('OTP expiry time in minutes', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Add phone field to user profile
     */
    public function add_phone_field($user) {
        $phone = get_user_meta($user->ID, 'phone_number', true);
        ?>
        <h3><?php _e('SMS 2FA Settings', 'ileti-merkezi-sms'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone_number"><?php _e('Phone Number', 'ileti-merkezi-sms'); ?></label></th>
                <td>
                    <input type="text" name="phone_number" id="phone_number" 
                           value="<?php echo esc_attr($phone); ?>" class="regular-text" 
                           placeholder="+90XXXXXXXXXX">
                    <p class="description"><?php _e('Required for 2-step verification via SMS', 'ileti-merkezi-sms'); ?></p>
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
        
        if (isset($_POST['phone_number'])) {
            update_user_meta($user_id, 'phone_number', sanitize_text_field($_POST['phone_number']));
        }
    }
}
