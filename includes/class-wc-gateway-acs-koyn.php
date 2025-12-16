<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WC_Gateway_Koyn
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class WC_Gateway_ACS_Koyn extends WC_Payment_Gateway {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id                 = 'koyn_gateway';
        $this->icon               = ''; // URL to an icon if available
        $this->has_fields         = false;
        $this->method_title       = __( 'Koyn', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' );
        $this->method_description = __( 'Redirect customers to Koyn for payment.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' );

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->enabled      = $this->get_option( 'enabled' );
        $this->testmode     = 'yes' === $this->get_option( 'sandbox' );
        $this->api_key      = $this->get_option( 'api_key' );
        $this->merchant_id  = $this->get_option( 'merchant_id' );
        $this->logging      = 'yes' === $this->get_option( 'logging' );

        // Actions
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Initialize Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable Koyn Payment', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default' => 'yes',
            ),
            'title' => array(
                'title'       => __( 'Title', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => __( 'Koyn Payment', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your checkout.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => __( 'Pay securely via Koyn.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'desc_tip'    => true,
            ),
            'merchant_id' => array(
                'title'       => __( 'Merchant ID', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Your Koyn Merchant ID.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => '',
            ),
            'api_key' => array(
                'title'       => __( 'API Key', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'password',
                'description' => __( 'Your Koyn API Key.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => '',
            ),
            'webhook_secret' => array(
                'title'       => __( 'Webhook Secret', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Secret used to verify webhooks from Koyn (if applicable).', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => '',
            ),
            'sandbox' => array(
                'title'       => __( 'Sandbox Mode', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'checkbox',
                'label'   => __( 'Enable Sandbox/Test Mode', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default' => 'no',
            ),
            'logging' => array(
                'title'       => __( 'Logging', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'type'        => 'checkbox',
                'label'       => __( 'Log debug messages', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ),
                'default'     => 'no',
                // translators: %s: Path to the log file location
                'description' => sprintf( __( 'Log events to %s', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ), '<code>woocommerce/logs/koyn_gateway...</code>' ),
            ),
        );
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        
        // Initialize Logger
        $logger = new ACS_Koyn_Logger( $this->logging );

        // 1. Set Order to On Hold
        $order->update_status( 'on-hold', __( 'Awaiting Koyn payment', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ) );

        // 2. Prepare Payload
        // Using structure from prompt skeleton and API expectations
        $return_url  = $this->get_return_url( $order );
        $webhook_url = home_url( '/?wc-api=koyn_gateway' );
        
        // Basic user info for the API
        // Doc example: "username", "phone", "customer_email", "amount", "currency"
        $payload = array(
            'amount'         => (float) $order->get_total(),
            'currency'       => $order->get_currency(),
            'order_id'       => $order->get_id(), // Pass our Order ID so we get it back
            'username'       => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'phone'          => $order->get_billing_phone(),
            'customer_email' => $order->get_billing_email(),
            
            // Extra fields from skeleton
            'return_url'     => $return_url,
            'webhook_url'    => $webhook_url,
        );

        // 3. Call Koyn API
        $client = new ACS_Koyn_API_Client( $this->api_key, $this->merchant_id, $this->testmode, $logger );
        $response = $client->create_payment( $payload );

        // 4. Handle Response
        if ( is_wp_error( $response ) ) {
            wc_add_notice( __( 'Payment error:', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ) . ' ' . $response->get_error_message(), 'error' );
            return array( 'result' => 'failure' );
        }

        // Check for 'payin_url' or 'payment_url' based on doc/skeleton
        // Doc: "payin_url"
        // Skeleton: "payment_url"
        // We'll check both or preference. Doc is source of truth.
        
        $payment_url = '';
        $session_id  = '';
        
        if ( isset( $response['data']['payin_url'] ) ) {
            $payment_url = $response['data']['payin_url'];
            // Using transaction_id as session_id
            $session_id  = isset( $response['data']['transaction_id'] ) ? $response['data']['transaction_id'] : '';
        } elseif ( isset( $response['payment_url'] ) ) { // Fallback to skeleton's key
             $payment_url = $response['payment_url'];
             $session_id  = isset( $response['session_id'] ) ? $response['session_id'] : '';
        }

        if ( $payment_url ) {
            // Save meta
            $order->update_meta_data( '_koyn_session_id', $session_id );
            $order->update_meta_data( '_koyn_payment_url', $payment_url );
            $order->save();

            return array(
                'result'   => 'success',
                'redirect' => $payment_url,
            );
        }

        // If we get here, API call succeeded but no URL
        $error_msg = isset( $response['message'] ) ? $response['message'] : __( 'Unknown error from payment provider.', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' );
        wc_add_notice( __( 'Payment error:', 'agile-cyber-solutions-gateway-koyn-for-woocommerce' ) . ' ' . $error_msg, 'error' );
        return array( 'result' => 'failure' );
    }
}

