=== Agile Cyber Solutions Gateway with Koyn for WooCommerce ===
Contributors: agilecybersolutions
Tags: woocommerce, payment, gateway, koyn, card
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Koyn redirect payment gateway to WooCommerce for seamless payment processing.

== Description ==

Adds Koyn redirect payment gateway to WooCommerce.

== Installation ==

1. Upload the `agile-cyber-solutions-gateway-koyn-for-woocommerce` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce > Settings > Payments** and manage **Koyn**.
4. Enable the gateway and enter your API credentials.

== Configuration ==

1. Merchant ID: Your Koyn Merchant ID.
2. API Key: Your Koyn API Key (get this from your Koyn dashboard).
3. Webhook Secret: Secret used for verifying webhooks (if provided).
4. Sandbox Mode: Enable for testing with sandbox credentials.

== Webhook Setup ==

To receive payment confirmations, configure the Webhook URL in your Koyn Dashboard.

**Webhook URL:**  
`https://www.yoursite.com/?wc-api=koyn_gateway`  
(Replace the domain with your actual site URL)

== Logging ==

Enable logging in the plugin settings to debug API requests and webhooks.  
Logs appear in **WooCommerce > Status > Logs**.

== License ==

This plugin is free software, released under the terms of the GNU General Public License, version 2 or any later version published by the Free Software Foundation.

You may use, modify, and redistribute this plugin, provided that any derivative works are also licensed under the GNU General Public License.

Full license text: https://www.gnu.org/lice
