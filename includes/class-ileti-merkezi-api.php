<?php
/**
 * İleti Merkezi API Integration Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ileti_Merkezi_API {
    
    private $username;
    private $password;
    private $sender;
    private $api_url = 'https://api.iletimerkezi.com/v1/send-sms';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->username = get_option('ileti_merkezi_username', '');
        $this->password = get_option('ileti_merkezi_password', '');
        $this->sender = get_option('ileti_merkezi_sender', '');
    }
    
    /**
     * Send SMS via İleti Merkezi API
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return array Response array with 'success' and 'message' keys
     */
    public function send_sms($phone, $message) {
        // Validate credentials
        if (empty($this->username) || empty($this->password) || empty($this->sender)) {
            return array(
                'success' => false,
                'message' => __('API credentials not configured', 'ileti-merkezi-sms')
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
        
        // Prepare API request
        $xml_data = $this->prepare_xml_request($phone, $message);
        
        // Send request
        $response = wp_remote_post($this->api_url, array(
            'body' => $xml_data,
            'headers' => array(
                'Content-Type' => 'text/xml; charset=UTF-8',
            ),
            'timeout' => 30,
        ));
        
        // Handle response
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        // Parse XML response
        $result = $this->parse_response($body, $status_code);
        
        return $result;
    }
    
    /**
     * Clean and format phone number
     * 
     * @param string $phone Phone number
     * @return string Cleaned phone number
     */
    private function clean_phone_number($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Ensure Turkish phone number format (90XXXXXXXXXX)
        if (strlen($phone) == 10) {
            $phone = '90' . $phone;
        } elseif (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
            $phone = '9' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Prepare XML request for İleti Merkezi API
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return string XML request
     */
    private function prepare_xml_request($phone, $message) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><request></request>');
        
        $authentication = $xml->addChild('authentication');
        $authentication->addChild('username', htmlspecialchars($this->username));
        $authentication->addChild('password', htmlspecialchars($this->password));
        
        $order = $xml->addChild('order');
        $order->addChild('sender', htmlspecialchars($this->sender));
        
        $sendDateTime = $order->addChild('sendDateTime');
        
        $message_elem = $order->addChild('message');
        $message_elem->addChild('text', htmlspecialchars($message));
        
        $receivers = $order->addChild('receivers');
        $receiver = $receivers->addChild('receiver');
        $receiver->addChild('number', $phone);
        
        return $xml->asXML();
    }
    
    /**
     * Parse API response
     * 
     * @param string $body Response body
     * @param int $status_code HTTP status code
     * @return array Result array
     */
    private function parse_response($body, $status_code) {
        if ($status_code != 200) {
            return array(
                'success' => false,
                'message' => sprintf(__('API returned status code: %d', 'ileti-merkezi-sms'), $status_code)
            );
        }
        
        try {
            $xml = simplexml_load_string($body);
            
            if ($xml === false) {
                return array(
                    'success' => false,
                    'message' => __('Invalid API response', 'ileti-merkezi-sms')
                );
            }
            
            // Check for error in response
            if (isset($xml->status) && isset($xml->status->code)) {
                $code = (string)$xml->status->code;
                $message = isset($xml->status->message) ? (string)$xml->status->message : '';
                
                if ($code == '200') {
                    return array(
                        'success' => true,
                        'message' => __('SMS sent successfully', 'ileti-merkezi-sms')
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => sprintf(__('API error %s: %s', 'ileti-merkezi-sms'), $code, $message)
                    );
                }
            }
            
            return array(
                'success' => true,
                'message' => __('SMS sent successfully', 'ileti-merkezi-sms')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Test API connection
     * 
     * @return array Result array
     */
    public function test_connection() {
        $test_message = 'Test message from WordPress';
        $test_phone = get_option('admin_phone', '');
        
        if (empty($test_phone)) {
            return array(
                'success' => false,
                'message' => __('Test phone number not set', 'ileti-merkezi-sms')
            );
        }
        
        return $this->send_sms($test_phone, $test_message);
    }
}
