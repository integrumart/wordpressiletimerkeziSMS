<?php
/**
 * İleti Merkezi API Client
 *
 * @package Ileti_Merkezi_SMS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling İleti Merkezi API requests
 */
class IMSMS_Ileti_Merkezi_API {
    
    /**
     * API endpoint URL
     */
    private $api_url = 'https://api.iletimerkezi.com/v1/send-sms/get';
    
    /**
     * API username
     */
    private $username;
    
    /**
     * API password
     */
    private $password;
    
    /**
     * Sender title
     */
    private $sender;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->username = get_option('imsms_api_username', '');
        $this->password = get_option('imsms_api_password', '');
        $this->sender = get_option('imsms_sender_title', '');
    }
    
    /**
     * Check if API is configured
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->username) && !empty($this->password) && !empty($this->sender);
    }
    
    /**
     * Send SMS
     *
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return array Response with 'success' and 'message' keys
     */
    public function send_sms($phone, $message) {
        // Validate inputs
        if (empty($phone) || empty($message)) {
            return array(
                'success' => false,
                'message' => __('Phone number and message are required.', 'ileti-merkezi-sms')
            );
        }
        
        // Check if API is configured
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => __('İleti Merkezi API is not configured. Please configure it in settings.', 'ileti-merkezi-sms')
            );
        }
        
        // Clean phone number (remove non-numeric characters)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure phone number starts with country code
        if (substr($phone, 0, 1) !== '9') {
            // Assume Turkish number if not starts with country code
            if (substr($phone, 0, 1) === '0') {
                $phone = '90' . substr($phone, 1);
            } else {
                $phone = '90' . $phone;
            }
        }
        
        // Prepare XML request body
        $xml_data = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <authentication>
        <username>' . htmlspecialchars($this->username, ENT_XML1, 'UTF-8') . '</username>
        <password>' . htmlspecialchars($this->password, ENT_XML1, 'UTF-8') . '</password>
    </authentication>
    <order>
        <sender>' . htmlspecialchars($this->sender, ENT_XML1, 'UTF-8') . '</sender>
        <sendDateTime></sendDateTime>
        <message>
            <text>' . htmlspecialchars($message, ENT_XML1, 'UTF-8') . '</text>
            <recipients>
                <number>' . htmlspecialchars($phone, ENT_XML1, 'UTF-8') . '</number>
            </recipients>
        </message>
    </order>
</request>';
        
        // Send request
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'text/xml; charset=utf-8',
            ),
            'body' => $xml_data,
        ));
        
        // Check for errors
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => sprintf(
                    __('API request failed: %s', 'ileti-merkezi-sms'),
                    $response->get_error_message()
                )
            );
        }
        
        // Get response body
        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Parse XML response
        $xml = @simplexml_load_string($response_body);
        
        if ($xml === false) {
            return array(
                'success' => false,
                'message' => __('Invalid API response format.', 'ileti-merkezi-sms')
            );
        }
        
        // Check response status
        if (isset($xml->status) && isset($xml->status->code)) {
            $status_code = (string) $xml->status->code;
            $status_message = isset($xml->status->message) ? (string) $xml->status->message : '';
            
            if ($status_code === '200') {
                return array(
                    'success' => true,
                    'message' => __('SMS sent successfully.', 'ileti-merkezi-sms'),
                    'order_id' => isset($xml->order->id) ? (string) $xml->order->id : ''
                );
            } else {
                return array(
                    'success' => false,
                    'message' => sprintf(
                        __('API error (%s): %s', 'ileti-merkezi-sms'),
                        $status_code,
                        $status_message
                    )
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => __('Unexpected API response.', 'ileti-merkezi-sms')
        );
    }
    
    /**
     * Test API connection
     *
     * @return array Response with 'success' and 'message' keys
     */
    public function test_connection() {
        return $this->send_sms('905551234567', 'Test message from WordPress');
    }
}
