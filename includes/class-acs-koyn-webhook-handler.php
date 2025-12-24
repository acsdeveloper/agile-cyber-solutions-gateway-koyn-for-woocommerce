<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ACS_Koyn_Webhook_Handler {

    private $logger;

    public function __construct() {
        // Initialize logger specifically for webhook tracing
        // We can get settings inside handle() to know if logging is enabled.
    }

    public function handle() {
        $input = file_get_contents( 'php://input' );
        $data  = json_decode( $input, true );

        // Validate JSON
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json( array( 'status' => 'error', 'message' => 'Invalid JSON' ), 400 );
        }
        
        $settings = get_option( 'woocommerce_koyn_gateway_settings', array() );
        $logging_enabled = isset( $settings['logging'] ) && 'yes' === $settings['logging'];
        $webhook_secret  = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';
        
        $logger = new ACS_Koyn_Logger( $logging_enabled );

        // Security: Enforce Webhook Secret Configuration
        if ( empty( $webhook_secret ) ) {
            // Log this as a critical error so admin knows to configure it
            $logger->error( 'Webhook secret is not configured in settings. Request rejected.' );
            wp_send_json( array( 'status' => 'error', 'message' => 'Configuration error' ), 500 );
        }

        // Security: Verify Signature
        // We expect a header 'X-Koyn-Signature' containing the HMAC SHA256 signature of the payload using the secret.
        $headers = array_change_key_case( $this->get_request_headers(), CASE_LOWER );
        $received_signature = isset( $headers['x-koyn-signature'] ) ? $headers['x-koyn-signature'] : '';
        
        // Calculate our signature
        $calculated_signature = hash_hmac( 'sha256', $input, $webhook_secret );
        
        if ( ! hash_equals( $calculated_signature, $received_signature ) ) {
            // Log partial signature to help debugging but not full secret
            $logger->error( 'Webhook signature verification failed', array( 'received' => $received_signature ) );
            wp_send_json( array( 'status' => 'error', 'message' => 'Invalid signature' ), 403 );
        }

        // Sanitize and Validate Inputs for Logic and Logging
        // Prepare a sanitized array for logging to avoid log injection
        $sanitized_log_data = array();
        if ( isset( $data['order_id'] ) ) {
            $sanitized_log_data['order_id'] = absint( $data['order_id'] );
        }
        if ( isset( $data['status'] ) ) {
            $sanitized_log_data['status'] = sanitize_text_field( $data['status'] );
        }
        if ( isset( $data['transaction_id'] ) ) {
            $sanitized_log_data['transaction_id'] = sanitize_text_field( $data['transaction_id'] );
        }

        $logger->info( 'Webhook received', array( 'payload' => $sanitized_log_data ) );

        if ( empty( $data ) ) {
            wp_send_json( array( 'status' => 'error', 'message' => 'Empty payload' ), 400 );
        }

        $order_id = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
        $transaction_id = isset( $data['transaction_id'] ) ? sanitize_text_field( $data['transaction_id'] ) : '';
        $status = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '';
        
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            $logger->error( 'Webhook order not found', array( 'order_id' => $order_id ) );
            wp_send_json( array( 'status' => 'error', 'message' => 'Order not found' ), 404 );
        }

        if ( 'success' === $status ) {
            // Check if already paid to avoid double processing
            if ( ! $order->is_paid() ) {
                $order->payment_complete( $transaction_id );
                $order->add_order_note( sprintf( 'Koyn payment success. Transaction ID: %s', $transaction_id ) );
                $logger->info( 'Order completed via Webhook', array( 'order_id' => $order_id ) );
            }
        } elseif ( 'failed' === $status ) {
            $order->update_status( 'failed', 'Koyn payment failed via Webhook.' );
            $logger->warning( 'Order marked as failed via Webhook', array( 'order_id' => $order_id ) );
        } else {
            $logger->warning( 'Unknown webhook status', array( 'status' => $status ) );
        }

        wp_send_json( array( 'status' => 'ok' ), 200 );
    }

    private function get_request_headers() {
        if ( ! function_exists( 'getallheaders' ) ) {
            $headers = array();
            foreach ( $_SERVER as $name => $value ) {
                if ( substr( $name, 0, 5 ) == 'HTTP_' ) {
                    $headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
                }
            }
            return $headers;
        }
        return getallheaders();
    }
}
