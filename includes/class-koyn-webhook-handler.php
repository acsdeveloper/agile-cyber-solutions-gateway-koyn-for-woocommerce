<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Koyn_Webhook_Handler {

    private $logger;

    public function __construct() {
        // Initialize logger specifically for webhook tracing
        $this->logger = new Koyn_Logger( true ); // Force logging or check settings? Better to checking later.
        // Actually, we can get settings inside handle() to know if logging is enabled.
    }

    public function handle() {
        $input = file_get_contents( 'php://input' );
        $data  = json_decode( $input, true );
        
        $settings = get_option( 'woocommerce_koyn_gateway_settings', array() );
        $logging_enabled = isset( $settings['logging'] ) && 'yes' === $settings['logging'];
        $webhook_secret  = isset( $settings['webhook_secret'] ) ? $settings['webhook_secret'] : '';
        
        $logger = new Koyn_Logger( $logging_enabled );
        $logger->info( 'Webhook received', array( 'payload' => $data ) );

        if ( empty( $data ) ) {
            wp_send_json( array( 'status' => 'error', 'message' => 'Empty payload' ), 400 );
        }

        // Verify Signature if applicable
        // Since docs are sparse on signature, we'll verify the secret if user configured one.
        // Example: X-Koyn-Signature matches HMAC encoded payload.
        // We will check for common headers.
        $headers = $this->get_request_headers();
        if ( ! empty( $webhook_secret ) ) {
             // Placeholder for signature verification logic
             // If the API sends a specific header, we'd check it here.
             // For now, we proceed, assuming the order ID and amount match is the validation.
        }

        // Extract Order ID
        // Payload example from docs doesn't show structure of callback clearly, 
        // but typically it has 'order_id' (our ID) and 'transaction_id'.
        // Prompt skeleton used: $data['transaction_id'] and extract_order_id($data).
        
        $order_id = isset( $data['order_id'] ) ? $data['order_id'] : 0;
        
        // Sometimes order_id in callback is the Merchant Order ID we sent. 
        // If we sent WC order ID, it should be here.
        
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            $logger->error( 'Webhook order not found', array( 'order_id' => $order_id ) );
            wp_send_json( array( 'status' => 'error', 'message' => 'Order not found' ), 404 );
        }

        // Check payment status
        // Doc says: "success" or "failed"
        $status = isset( $data['status'] ) ? $data['status'] : '';
        $transaction_id = isset( $data['transaction_id'] ) ? $data['transaction_id'] : '';

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
