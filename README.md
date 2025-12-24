# Agile Cyber Solutions Gateway with Koyn for WooCommerce

Adds Koyn redirect payment gateway to WooCommerce for seamless payment processing.

## Description

Adds Koyn redirect payment gateway to WooCommerce.

## Installation

1. Upload the `agile-cyber-solutions-gateway-koyn-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Settings > Payments** and manage **Koyn**.
4. Enable the gateway and enter your API credentials.

## Configuration

1. **Merchant ID**: Your Koyn Merchant ID.
2. **API Key**: Your Koyn API Key (get this from your Koyn dashboard).
3. **Webhook Secret**: Secret used for verifying webhooks (if provided).
4. **Sandbox Mode**: Enable for testing with sandbox credentials.

## Webhook Setup

To receive payment confirmations, configure the Webhook URL in your Koyn Dashboard.

**Webhook URL:**  
`https://www.yoursite.com/?wc-api=koyn_gateway`  
(Replace the domain with your actual site URL)

## Logging

Enable logging in the plugin settings to debug API requests and webhooks.  
Logs appear in **WooCommerce > Status > Logs**.

## External Services

This plugin connects to a 3rd party external service (Koyn Payment Gateway) to process payments.

**Service**: Koyn Payment Gateway  
**Usage**: To initialize payment sessions and redirect users for secure payment.  
**Data Sent**:
- Order Amount and Currency
- Order ID
- Customer Name, Email, and Phone
- Return and Webhook URLs
**Privacy Policy**: https://koynupi.link/ (Available via popup on the website)  
**Terms of Service**: https://koynupi.link/ (Available via popup on the website)
