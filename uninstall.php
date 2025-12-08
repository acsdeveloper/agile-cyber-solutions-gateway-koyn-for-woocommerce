<?php
/**
 * Uninstall Koyn Gateway
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete options
delete_option( 'woocommerce_koyn_gateway_settings' );
