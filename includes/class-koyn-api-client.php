<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Koyn_API_Client
 *
 * Handles communication with the Koyn API.
 */
class Koyn_API_Client {

    private $api_key;
    private $merchant_id;
    private $sandbox;
    private $logger;
    
    // Base URL from the provided documentation
    const API_URL = 'https://api.koynupi.link';

    /**
     * Constructor.
     */
    public function __construct( $api_key, $merchant_id, $sandbox = false, $logger = null ) {
        $this->api_key     = $api_key;
        $this->merchant_id = $merchant_id;
        $this->sandbox     = $sandbox;
        $this->logger      = $logger;
    }

    /**
     * Authenticate and get token.
     *
     * @return string|WP_Error Token or error.
     */
    public function get_token() {
        $endpoint = self::API_URL . '/user/auth';

        // Based on User provided Postman JSON:
        // Header: authkey
        // Body: { "merchant_id": "..." }
        
        $payload = array(
            'merchant_id' => $this->merchant_id,
        );

        $headers = array(
            'Content-Type' => 'application/json',
            'authkey'      => $this->api_key, 
        );

        $this->log( 'info', 'Requesting Auth Token', array( 'endpoint' => $endpoint, 'headers' => $headers, 'payload' => $payload ) );

        $response = wp_remote_post( $endpoint, array(
            'method'    => 'POST',
            'body'      => json_encode( $payload ),
            'headers'   => $headers,
            'timeout'   => 30,
            'sslverify' => false,
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'error', 'Auth Request failed', array( 'error' => $response->get_error_message() ) );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code >= 400 || empty( $data['data']['token'] ) ) {
            $this->log( 'error', 'Auth API Error', array( 'code' => $code, 'body' => $data ) );
            return new WP_Error( 'auth_error', 'Authentication failed', $data );
        }

        $token = $data['data']['token'];
        
        return $token;
    }

    /**
     * Create a payment session.
     *
     * @param array $payload Data for the payment request.
     * @return array|WP_Error Response data or error.
     */
    public function create_payment( $payload ) {
        // 1. Get Token
        $token = $this->get_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $endpoint = self::API_URL . '/auth/payin/send_payin_request';
        
        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token, 
            'token'         => $token, // Some APIs expect it in a specific header key 'token'
            'apiKey'        => $this->api_key, // Sending api_key as well based on Postman examples just in case
        );

        $this->log( 'info', 'Creating payment request', array( 'endpoint' => $endpoint, 'payload' => $payload ) );

        $response = wp_remote_post( $endpoint, array(
            'method'    => 'POST',
            'body'      => json_encode( $payload ),
            'headers'   => $headers,
            'timeout'   => 45,
            'sslverify' => false,
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log( 'error', 'HTTP Request failed', array( 'error' => $response->get_error_message() ) );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $this->log( 'debug', 'API Response', array( 'code' => $code, 'body' => $data ) );

        if ( $code >= 400 ) {
            return new WP_Error( 'api_error', 'API request failed with status ' . $code, $data );
        }

        return $data;
    }

    /**
     * Log helper.
     */
    private function log( $level, $message, $context = array() ) {
        if ( $this->logger ) {
            $this->logger->log( $level, $message, $context );
        }
    }
}
