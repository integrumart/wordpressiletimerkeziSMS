<?php
/**
 * Ileti Merkezi API Integration Class
 * 
 * @package IletiMerkeziSMS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class IMSMS_Ileti_Merkezi_API {
    
    /**
     * API endpoint
     */
    private $api_url = 'https://api.iletimerkezi.com/v1/send-sms';
    
    /**
     * API credentials
     */
    private $username;
    private $password;
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
     * Send SMS via Ileti Merkezi API
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return array Response with status and message
     */
    public function send_sms($phone, $message) {
        // Validate credentials
        if (empty($this->username) || empty($this->password) || empty($this->sender)) {
            return array(
                'success' => false,
                'message' => __('API credentials are not configured', 'ileti-merkezi-sms')
            );
        }
        
        // Clean phone number
        $phone = $this->clean_phone_number($phone);
        
        if (empty($phone)) {
            return array(
                'success' => false,
                'message' => __('Invalid phone number', 'ileti-merkezi-sms')
            );
        }
        
        // Prepare XML request
        $xml = $this->build_xml_request($phone, $message);
        
        // Send request
        $response = wp_remote_post($this->api_url, array(
            'body' => $xml,
            'headers' => array(
                'Content-Type' => 'text/xml; charset=UTF-8'
            ),
            'timeout' => 30
        ));
        
        // Handle response
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        // Parse XML response
        $result = $this->parse_response($body);
        
        // Log for debugging (optional)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Ileti Merkezi SMS Response: ' . print_r($result, true));
        }
        
        return $result;
    }
    
    /**
     * Build XML request for Ileti Merkezi API
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return string XML request
     */
    private function build_xml_request($phone, $message) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
        
        $authentication = $xml->addChild('authentication');
        $authentication->addChild('username', $this->username);
        $authentication->addChild('password', $this->password);
        
        $order = $xml->addChild('order');
        $order->addChild('sender', $this->sender);
        
        $message_node = $order->addChild('sendDateTime');
        
        $receivers = $order->addChild('receivers');
        $receiver = $receivers->addChild('receiver');
        $receiver->addChild('number', $phone);
        
        $order->addChild('text', htmlspecialchars($message, ENT_XML1, 'UTF-8'));
        
        return $xml->asXML();
    }
    
    /**
     * Parse XML response from API
     * 
     * @param string $xml_string XML response
     * @return array Parsed response
     */
    private function parse_response($xml_string) {
        try {
            $xml = simplexml_load_string($xml_string);
            
            if ($xml === false) {
                return array(
                    'success' => false,
                    'message' => __('Failed to parse API response', 'ileti-merkezi-sms')
                );
            }
            
            $status = (string) $xml->status->code;
            $message = (string) $xml->status->message;
            
            if ($status == '200') {
                return array(
                    'success' => true,
                    'message' => $message,
                    'order_id' => (string) $xml->order->id
                );
            } else {
                return array(
                    'success' => false,
                    'message' => $message,
                    'code' => $status
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => __('Error processing API response', 'ileti-merkezi-sms')
            );
        }
    }
    
    /**
     * Clean and validate phone number
     * 
     * @param string $phone Phone number
     * @return string Cleaned phone number
     */
    private function clean_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Turkish phone numbers should be 10 digits (without country code) or 12 digits (with 90)
        if (strlen($phone) == 10) {
            $phone = '90' . $phone;
        }
        
        // Validate length
        if (strlen($phone) != 12) {
            return '';
        }
        
        return $phone;
    }
    
    /**
     * Test API connection
     * 
     * @return array Test result
     */
    public function test_connection() {
        $test_message = __('This is a test message from Ileti Merkezi SMS plugin', 'ileti-merkezi-sms');
        $test_phone = get_option('imsms_test_phone', '');
        
        if (empty($test_phone)) {
            return array(
                'success' => false,
                'message' => __('Test phone number is not set', 'ileti-merkezi-sms')
            );
        }
        
        return $this->send_sms($test_phone, $test_message);
    }
}
