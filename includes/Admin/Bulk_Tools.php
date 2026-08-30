<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Event_Log;
use KitgenixCustomTabsForWooCommerce\Core\Settings;

/**
 * Bulk-apply a saved template to many products at once from the WooCommerce
 * Products list screen, using WordPress core's own bulk-actions UI/nonce/
 * capability plumbing (bulk_actions-edit-product / handle_bulk_actions-edit-product)
 * rather than a custom picker screen – the products list already has search,
 * filtering, and multi-select built in, so reusing it here is both the
 * lowest-risk implementation and the one merchants already know how to use.
 */
final class Bulk_Tools {
	private const ACTION_PREFIX = 'kitgenix_apply_template_';

	public static function init(): void {
		add_filter( 'bulk_actions-edit-product', [ self::class, 'register_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-product', [ self::class, 'handle_bulk_action' ], 10, 3 );
		add_action( 'admin_notices', [ self::class, 'render_result_notice' ] );
	}

	/**
	 * @param array<string,string> $actions
	 * @return array<string,string>
	 */
	public static function register_bulk_actions( array $actions ): array {
		if ( ! Settings::enabled() ) {
			return $actions;
		}

		foreach ( Settings::tab_templates() as $index => $template ) {
			if ( ! is_array( $template ) || ( array_key_exists( 'enabled', $template ) && empty( $template['enabled'] ) ) ) {
				continue;
			}
			$title = isset( $template['title'] ) ? (string) $template['title'] : '';
			if ( '' === $title ) {
				continue;
			}

			/* translators: %s: saved tab template title */
			$actions[ self::ACTION_PREFIX . $index ] = sprintf( __( 'Add Kitgenix tab: %s', 'kitgenix-custom-tabs-for-woocommerce' ), $title );
		}

		return $actions;
	}

	/**
	 * @param string        $redirect_to
	 * @param string        $action
	 * @param array<int,int> $post_ids
	 */
	public static function handle_bulk_action( string $redirect_to, string $action, array $post_ids ): string {
		if ( 0 !== strpos( $action, self::ACTION_PREFIX ) ) {
			return $redirect_to;
		}

		if ( ! Settings::enabled() || ! current_user_can( 'edit_products' ) ) {
			return $redirect_to;
		}

		$template_index = (int) substr( $action, strlen( self::ACTION_PREFIX ) );
		$templates      = Settings::tab_templates();
		$template       = $templates[ $template_index ] ?? null;
		if ( ! is_array( $template ) || empty( $template['title'] ) ) {
			return add_query_arg( 'kitgenix_bulk_tabs_error', '1', $redirect_to );
		}

		$max     = Settings::max_tabs();
		$updated = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 || 'product' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			$existing = get_post_meta( $post_id, 'kitgenix_custom_tabs_for_woocommerce_tabs', true );
			$existing = is_array( $existing ) ? $existing : [];
			if ( count( $existing ) >= $max ) {
				continue; // already at the per-product cap; skip rather than silently exceed it
			}

			$existing[] = $template;
			$sanitized  = Settings::sanitize_tabs_rows( $existing, $max, 'product' );
			update_post_meta( $post_id, 'kitgenix_custom_tabs_for_woocommerce_tabs', $sanitized );
			$updated++;
		}

		Product_Tabs::flush_usage_stats_cache();
		Event_Log::record(
			'bulk-apply-template',
			'success',
			sprintf(
				/* translators: 1: template title, 2: number of products updated */
				__( 'Applied template "%1$s" to %2$d product(s) via bulk action.', 'kitgenix-custom-tabs-for-woocommerce' ),
				(string) $template['title'],
				$updated
			),
			'bulk_apply_template'
		);

		return add_query_arg( 'kitgenix_bulk_tabs_updated', $updated, $redirect_to );
	}

	public static function render_result_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect result flag.
		if ( isset( $_GET['kitgenix_bulk_tabs_updated'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = absint( wp_unslash( $_GET['kitgenix_bulk_tabs_updated'] ) );
			echo '<div class="notice notice-success is-dismissible"><p>'
				. esc_html(
					sprintf(
						/* translators: %d: number of products updated */
						_n( 'Kitgenix tab template applied to %d product.', 'Kitgenix tab template applied to %d products.', $count, 'kitgenix-custom-tabs-for-woocommerce' ),
						$count
					)
				)
				. '</p></div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['kitgenix_bulk_tabs_error'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not apply the selected Kitgenix tab template.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p></div>';
		}
	}
}
