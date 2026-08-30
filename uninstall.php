<?php
/**
 * Uninstall handler.
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove all plugin-owned options/transients and product tab meta for the
 * current site (single-site, or one site within a multisite network when
 * called per-blog below).
 */
function kitgenix_custom_tabs_for_woocommerce_remove_site_data(): void {
	delete_option( 'kitgenix_custom_tabs_for_woocommerce_settings' );
	delete_option( 'kitgenix_custom_tabs_for_woocommerce_templates' );
	delete_option( 'kitgenix_custom_tabs_for_woocommerce_event_log' );
	delete_option( 'kitgenix_custom_tabs_for_woocommerce_do_activation_redirect' );
	delete_transient( 'kitgenix_custom_tabs_for_woocommerce_usage_stats' );

	// Delete product meta for all products (single query).
	global $wpdb;
	if ( isset( $wpdb ) && $wpdb instanceof \wpdb ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", 'kitgenix_custom_tabs_for_woocommerce_tabs' ) );
	}
}

if ( is_multisite() ) {
	$kitgenix_custom_tabs_for_woocommerce_site_ids = get_sites( [ 'fields' => 'ids' ] );
	foreach ( (array) $kitgenix_custom_tabs_for_woocommerce_site_ids as $kitgenix_custom_tabs_for_woocommerce_site_id ) {
		switch_to_blog( (int) $kitgenix_custom_tabs_for_woocommerce_site_id );
		kitgenix_custom_tabs_for_woocommerce_remove_site_data();
		restore_current_blog();
	}
} else {
	kitgenix_custom_tabs_for_woocommerce_remove_site_data();
}
