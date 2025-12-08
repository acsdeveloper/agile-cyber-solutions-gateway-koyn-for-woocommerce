# Koyn Gateway for WooCommerce

Adds Koyn redirect payment gateway to WooCommerce.

## Installation

1. Upload the `koyn-woocommerce-gateway` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Settings > Payments** and manage **Koyn**.
4. Enable the gateway and enter your API credentials.

## Configuration

1. **Merchant ID**: Your Koyn Merchant ID.
2. **API Key**: Your Koyn API Key (get this from your Koyn dashboard).
3. **Webhook Secret**: Secret used for verifying webhooks (if provided).
4. **Sandbox Mode**: Enable for testing with sandbox credentials.

## Webhook Setup

To receive payment confirmations, you must configure the Webhook URL in your Koyn Dashboard.

**Webhook URL:**
`https://www.yoursite.com/?wc-api=koyn_gateway`
*(Replace `https://www.yoursite.com/` with your actual domain)*

## Logging

Enable logging in the settings to debug API requests and webhooks. Logs can be found in **WooCommerce > Status > Logs**.
