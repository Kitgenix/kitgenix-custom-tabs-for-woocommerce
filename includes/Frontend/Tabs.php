<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Settings;
use KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher;

final class Tabs {
	private const META_KEY = 'kitgenix_custom_tabs_for_woocommerce_tabs';

	public static function init(): void {
		add_filter( 'woocommerce_product_tabs', [ self::class, 'inject_tabs' ], 20 );
	}

	/**
	 * @param array<string,mixed> $tabs
	 * @return array<string,mixed>
	 */
	public static function inject_tabs( array $tabs ): array {
		if ( ! Settings::enabled() ) {
			return $tabs;
		}

		global $product;
		if ( ! $product || ! is_a( $product, \WC_Product::class ) ) {
			return $tabs;
		}

		$product_id = (int) $product->get_id();
		if ( $product_id <= 0 ) {
			return $tabs;
		}

		$base = Settings::priority_base();
		$step = Settings::priority_step();
		$max  = Settings::max_tabs();

		$idx     = 0;
		$rendered = 0;

		$global = Settings::global_tabs();
		if ( is_array( $global ) && ! empty( $global ) ) {
			self::add_rows_to_tabs( $tabs, $global, $idx, $base, $step, $product, $max, $rendered );
		}

		$stored = get_post_meta( $product_id, self::META_KEY, true );
		if ( is_array( $stored ) && ! empty( $stored ) ) {
			self::add_rows_to_tabs( $tabs, $stored, $idx, $base, $step, $product, $max, $rendered );
		}

		return $tabs;
	}

	/**
	 * @param array<string,mixed> $tabs
	 * @param array<int,mixed>    $rows
	 */
	private static function add_rows_to_tabs( array &$tabs, array $rows, int &$idx, int $base, int $step, \WC_Product $product, int $max, int &$rendered ): void {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			// Max tabs is a per-product-page cap across global + product-specific
			// tabs combined, not two independent caps – otherwise a store with
			// max_tabs=10 could still render up to 20 custom tabs (10 global + 10
			// product) on one product, defeating the point of the limit.
			if ( $rendered >= $max ) {
				return;
			}

			$title      = isset( $row['title'] ) ? (string) $row['title'] : '';
			$content    = isset( $row['content'] ) ? (string) $row['content'] : '';
			$slug       = isset( $row['slug'] ) ? (string) $row['slug'] : '';
			$hide_title = ! empty( $row['hide_title'] );

			if ( $title === '' || $content === '' ) {
				continue;
			}

			if ( ! Tab_Matcher::is_eligible( $row, $product ) ) {
				continue;
			}

			/**
			 * Filters whether a resolved tab row should render for this product/visitor,
			 * after the built-in enabled/target/visibility checks already passed.
			 *
			 * @param bool                 $eligible
			 * @param array<string,mixed>  $row
			 * @param \WC_Product          $product
			 */
			if ( ! apply_filters( 'kitgenix_custom_tabs_for_woocommerce_tab_eligible', true, $row, $product ) ) {
				continue;
			}

			$sanitized_slug = $slug !== '' ? sanitize_key( $slug ) : '';
			$key_base       = $sanitized_slug !== '' ? 'kitgenix_custom_tabs_for_woocommerce_tab_' . $sanitized_slug : 'kitgenix_custom_tabs_for_woocommerce_tab_' . (string) $idx;
			$key            = $key_base;
			$dupe           = 2;
			while ( isset( $tabs[ $key ] ) ) {
				$key = $key_base . '_' . (string) $dupe;
				$dupe++;
			}

			$priority = 0;
			if ( isset( $row['priority'] ) ) {
				$priority = absint( $row['priority'] );
			}
			if ( $priority <= 0 ) {
				$priority = Settings::compute_priority_for_index( $base, $step, $idx );
			}

			/**
			 * Filters the resolved priority (ordering) for one custom tab.
			 *
			 * @param int                  $priority
			 * @param array<string,mixed>  $row
			 * @param \WC_Product          $product
			 */
			$priority = (int) apply_filters( 'kitgenix_custom_tabs_for_woocommerce_tab_priority', $priority, $row, $product );

			/**
			 * Filters a tab's rendered content before it's stored for output.
			 * Runs before shortcode processing / wpautop (see render_tab()).
			 *
			 * @param string               $content
			 * @param array<string,mixed>  $row
			 * @param \WC_Product          $product
			 */
			$content = (string) apply_filters( 'kitgenix_custom_tabs_for_woocommerce_tab_content', $content, $row, $product );

			$tabs[ $key ] = [
				'title'               => $title,
				'priority'            => $priority,
				'callback'            => [ self::class, 'render_tab' ],
				'kitgenix_content'    => $content,
				'kitgenix_title'      => $title,
				'kitgenix_hide_title' => $hide_title,
			];

			$idx++;
			$rendered++;
		}
	}

	/**
	 * @param string $key
	 * @param array<string,mixed> $tab
	 */
	public static function render_tab( string $key, array $tab ): void {
		$content = isset( $tab['kitgenix_content'] ) ? (string) $tab['kitgenix_content'] : '';
		if ( $content === '' ) {
			return;
		}

		$hide_title = ! empty( $tab['kitgenix_hide_title'] );
		if ( ! Settings::hide_tab_heading() && ! $hide_title ) {
			$title = isset( $tab['kitgenix_title'] ) ? (string) $tab['kitgenix_title'] : '';
			if ( $title !== '' ) {
				echo '<h2 class="woocommerce-Tabs-panel__title">' . esc_html( $title ) . '</h2>';
			}
		}

		if ( Settings::allow_shortcodes() ) {
			$content = do_shortcode( $content );
		}

		echo wp_kses_post( wpautop( $content ) );
	}
}
