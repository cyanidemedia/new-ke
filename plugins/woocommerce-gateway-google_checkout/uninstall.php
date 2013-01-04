<?php
/**
 * WooCommerce Google Checkout Gateway
 * By Niklas Högefjord (niklas@krokedil.se)
 * Based on PayPal Standard Gateway by WooCommerce
 * 
 * Uninstall - removes all Google Checkout options from DB when user deletes the plugin via WordPress backend.
 * @since 0.3
 **/
 
if ( !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}
	delete_option( 'woocommerce_googlecheckout_settings' );
?>