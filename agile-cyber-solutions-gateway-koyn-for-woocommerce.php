<?php
/*
Plugin Name: Agile Cyber Solutions Gateway with Koyn for WooCommerce
Plugin URI:  https://agilecyber.com/
Description: Adds Koyn redirect payment gateway to WooCommerce.
Version:     1.0.0
Author:      Agile Cyber Solutions
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: agile-cyber-solutions-gateway-koyn-for-woocommerce
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'koyn_init_gateway', 11 );

function koyn_init_gateway() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }

    // Include required classes
    require_once dirname( __FILE__ ) . '/includes/class-koyn-logger.php';
    require_once dirname( __FILE__ ) . '/includes/class-koyn-api-client.php';
    require_once dirname( __FILE__ ) . '/includes/class-koyn-webhook-handler.php';
    require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-koyn.php';

    // Register the gateway
    add_filter( 'woocommerce_payment_gateways', 'koyn_add_gateway_class' );

    // Initialize the webhook handler
    // We hook to woocommerce_api_{key} which is the standard way to handle WC webhooks for gateways
    // The URL will be home_url( '/?wc-api=koyn_gateway' )
    add_action( 'woocommerce_api_koyn_gateway', array( new Koyn_Webhook_Handler(), 'handle' ) );
}

function koyn_add_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_Koyn';
    return $methods;
}
