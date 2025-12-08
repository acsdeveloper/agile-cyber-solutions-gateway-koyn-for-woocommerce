<?php
/**
 * Class Test_Koyn_Gateway
 *
 * Unit tests for Koyn Gateway.
 */
class Test_Koyn_Gateway extends WP_UnitTestCase {

    public function setUp(): void {
        parent::setUp();
        // Setup WooCommerce and Gateway
        update_option( 'woocommerce_koyn_gateway_settings', array(
            'enabled' => 'yes',
            'api_key' => 'test_key',
            'merchant_id' => 'test_id',
            'sandbox' => 'yes'
        ) );
    }

    /**
     * Test process_payment returns redirect on success.
     */
    public function test_process_payment_success() {
        // Mock HTTP response
        add_filter( 'pre_http_request', array( $this, 'mock_api_success' ), 10, 3 );

        $order = WC_Helper_Order::create_order(); // Helper from WC test suite
        $gateway = new WC_Gateway_Koyn();
        $result = $gateway->process_payment( $order->get_id() );

        $this->assertEquals( 'success', $result['result'] );
        $this->assertStringContainsString( 'https://api.koynupi.link', $result['redirect'] );
        
        $order = wc_get_order( $order->get_id() );
        $this->assertEquals( '12345', $order->get_meta( '_koyn_session_id' ) );

        remove_filter( 'pre_http_request', array( $this, 'mock_api_success' ) );
    }

    /**
     * Mock successful API response.
     */
    public function mock_api_success( $preempt, $args, $url ) {
        // Mock Auth Request
        if ( strpos( $url, '/user/auth' ) !== false ) {
            return array(
                'response' => array( 'code' => 200, 'message' => 'OK' ),
                'body'     => json_encode( array(
                    'status' => 'success',
                    'token'  => 'mock_token_123',
                ) ),
            );
        }

        // Mock Payin Request
        if ( strpos( $url, '/auth/payin/send_payin_request' ) !== false ) {
            return array(
                'response' => array( 'code' => 200, 'message' => 'OK' ),
                'body'     => json_encode( array(
                    'status' => '0000',
                    'message' => 'Success',
                    'data' => array(
                        'payin_url' => 'https://api.koynupi.link/pay/123',
                        'transaction_id' => '12345'
                    )
                ) ),
            );
        }

        return $preempt;
    }
}
