<?php
/**
 * Settings Page
 *
 * @package Ileti_Merkezi_SMS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling plugin settings
 */
class IMSMS_Settings {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
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
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // API Settings
        register_setting('imsms_settings', 'imsms_api_username', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('imsms_settings', 'imsms_api_password', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('imsms_settings', 'imsms_sender_title', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // 2FA Settings
        register_setting('imsms_settings', 'imsms_2fa_enabled', array(
            'type' => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '1'
        ));
        
        register_setting('imsms_settings', 'imsms_otp_length', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 6
        ));
        
        register_setting('imsms_settings', 'imsms_otp_expiry', array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300
        ));
        
        // Add settings sections
        add_settings_section(
            'imsms_api_section',
            __('İleti Merkezi API Settings', 'ileti-merkezi-sms'),
            array($this, 'render_api_section'),
            'ileti-merkezi-sms'
        );
        
        add_settings_section(
            'imsms_2fa_section',
            __('2FA Settings', 'ileti-merkezi-sms'),
            array($this, 'render_2fa_section'),
            'ileti-merkezi-sms'
        );
        
        // Add settings fields
        add_settings_field(
            'imsms_api_username',
            __('API Username', 'ileti-merkezi-sms'),
            array($this, 'render_text_field'),
            'ileti-merkezi-sms',
            'imsms_api_section',
            array('field' => 'imsms_api_username', 'type' => 'text')
        );
        
        add_settings_field(
            'imsms_api_password',
            __('API Password', 'ileti-merkezi-sms'),
            array($this, 'render_text_field'),
            'ileti-merkezi-sms',
            'imsms_api_section',
            array('field' => 'imsms_api_password', 'type' => 'password')
        );
        
        add_settings_field(
            'imsms_sender_title',
            __('Sender Title', 'ileti-merkezi-sms'),
            array($this, 'render_text_field'),
            'ileti-merkezi-sms',
            'imsms_api_section',
            array('field' => 'imsms_sender_title', 'type' => 'text')
        );
        
        add_settings_field(
            'imsms_2fa_enabled',
            __('Enable 2FA', 'ileti-merkezi-sms'),
            array($this, 'render_checkbox_field'),
            'ileti-merkezi-sms',
            'imsms_2fa_section',
            array('field' => 'imsms_2fa_enabled')
        );
        
        add_settings_field(
            'imsms_otp_length',
            __('OTP Length', 'ileti-merkezi-sms'),
            array($this, 'render_number_field'),
            'ileti-merkezi-sms',
            'imsms_2fa_section',
            array('field' => 'imsms_otp_length', 'min' => 4, 'max' => 10)
        );
        
        add_settings_field(
            'imsms_otp_expiry',
            __('OTP Expiry (seconds)', 'ileti-merkezi-sms'),
            array($this, 'render_number_field'),
            'ileti-merkezi-sms',
            'imsms_2fa_section',
            array('field' => 'imsms_otp_expiry', 'min' => 60, 'max' => 600)
        );
    }
    
    /**
     * Sanitize checkbox
     */
    public function sanitize_checkbox($value) {
        return $value ? '1' : '0';
    }
    
    /**
     * Render API section
     */
    public function render_api_section() {
        echo '<p>' . esc_html__('Configure your İleti Merkezi API credentials here.', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Render 2FA section
     */
    public function render_2fa_section() {
        echo '<p>' . esc_html__('Configure two-factor authentication settings.', 'ileti-merkezi-sms') . '</p>';
    }
    
    /**
     * Render text field
     */
    public function render_text_field($args) {
        $field = $args['field'];
        $type = isset($args['type']) ? $args['type'] : 'text';
        $value = get_option($field, '');
        
        printf(
            '<input type="%s" name="%s" id="%s" value="%s" class="regular-text" />',
            esc_attr($type),
            esc_attr($field),
            esc_attr($field),
            esc_attr($value)
        );
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $field = $args['field'];
        $value = get_option($field, '1');
        
        printf(
            '<input type="checkbox" name="%s" id="%s" value="1" %s />',
            esc_attr($field),
            esc_attr($field),
            checked($value, '1', false)
        );
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args) {
        $field = $args['field'];
        $value = get_option($field, '');
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        printf(
            '<input type="number" name="%s" id="%s" value="%s" min="%s" max="%s" class="small-text" />',
            esc_attr($field),
            esc_attr($field),
            esc_attr($value),
            esc_attr($min),
            esc_attr($max)
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle test connection
        $test_result = null;
        if (isset($_POST['test_connection']) && check_admin_referer('imsms_test_connection')) {
            $api = new IMSMS_Ileti_Merkezi_API();
            $test_result = $api->test_connection();
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if ($test_result): ?>
                <div class="notice notice-<?php echo esc_attr($test_result['success'] ? 'success' : 'error'); ?> is-dismissible">
                    <p><?php echo esc_html($test_result['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('imsms_settings');
                do_settings_sections('ileti-merkezi-sms');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('Test API Connection', 'ileti-merkezi-sms'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('imsms_test_connection'); ?>
                <p>
                    <input type="submit" name="test_connection" class="button button-secondary" 
                           value="<?php esc_attr_e('Test Connection', 'ileti-merkezi-sms'); ?>" />
                </p>
                <p class="description">
                    <?php esc_html_e('This will send a test SMS to the number 905551234567. Make sure to save your settings before testing.', 'ileti-merkezi-sms'); ?>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_ileti-merkezi-sms') {
            return;
        }
        
        wp_enqueue_style(
            'imsms-admin-style',
            IMSMS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            IMSMS_VERSION
        );
    }
}
