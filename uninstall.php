<?php
/**
 * Smartex for WooCommerce Uninstall
 *
 * @author 		smartex
 */

// Prevents script from being called directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wpdb;

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_smartex%';");
