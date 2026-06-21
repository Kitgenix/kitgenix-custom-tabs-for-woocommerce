<?php
/**
 * Uninstall handler.
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'kitgenix_custom_tabs_for_woocommerce_settings' );
delete_option( 'kitgenix_custom_tabs_for_woocommerce_event_log' );
delete_transient( 'kitgenix_custom_tabs_for_woocommerce_usage_stats' );

// Delete product meta for all products (single query).
global $wpdb;
if ( isset( $wpdb ) && $wpdb instanceof \wpdb ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", 'kitgenix_custom_tabs_for_woocommerce_tabs' ) );
}
