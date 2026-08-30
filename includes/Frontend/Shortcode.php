<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Frontend;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Settings;

/**
 * [kitgenix_tab] – renders a saved template's content anywhere shortcodes are
 * processed (post/page content, widgets, other tabs), not just inside the
 * WooCommerce product tabs area. Templates only (not live per-product tabs or
 * global tabs, which are meaningless outside their product-page context).
 *
 * Usage: [kitgenix_tab slug="warranty-info"] or [kitgenix_tab title="Warranty Info"]
 */
final class Shortcode {
	private const TAG = 'kitgenix_tab';

	/** @var int Guards against a template whose content references itself. */
	private static $depth = 0;
	private const MAX_DEPTH = 3;

	public static function init(): void {
		add_shortcode( self::TAG, [ self::class, 'render' ] );
	}

	/**
	 * @param array<string,mixed>|string $atts
	 */
	public static function render( $atts ): string {
		if ( ! Settings::enabled() ) {
			return '';
		}

		if ( self::$depth >= self::MAX_DEPTH ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'slug'  => '',
				'title' => '',
				'heading' => '0',
			],
			is_array( $atts ) ? $atts : [],
			self::TAG
		);

		$slug  = sanitize_title( (string) $atts['slug'] );
		$title = trim( (string) $atts['title'] );

		$template = self::find_template( $slug, $title );
		if ( null === $template ) {
			return '';
		}

		$content = isset( $template['content'] ) ? (string) $template['content'] : '';
		if ( '' === $content ) {
			return '';
		}

		self::$depth++;
		if ( Settings::allow_shortcodes() ) {
			$content = do_shortcode( $content );
		}
		self::$depth--;

		$html = '';
		if ( ! empty( $atts['heading'] ) && ! empty( $template['title'] ) ) {
			$html .= '<h2 class="kitgenix-custom-tabs-for-woocommerce-shortcode-title">' . esc_html( (string) $template['title'] ) . '</h2>';
		}
		$html .= wp_kses_post( wpautop( $content ) );

		/**
		 * Filters the final HTML output of a [kitgenix_tab] shortcode.
		 *
		 * @param string               $html
		 * @param array<string,mixed>  $template
		 */
		return (string) apply_filters( 'kitgenix_custom_tabs_for_woocommerce_shortcode_html', $html, $template );
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private static function find_template( string $slug, string $title ): ?array {
		if ( '' === $slug && '' === $title ) {
			return null;
		}

		foreach ( Settings::tab_templates() as $template ) {
			if ( ! is_array( $template ) ) {
				continue;
			}
			if ( array_key_exists( 'enabled', $template ) && empty( $template['enabled'] ) ) {
				continue;
			}

			$template_slug  = isset( $template['slug'] ) ? (string) $template['slug'] : '';
			$template_title = isset( $template['title'] ) ? (string) $template['title'] : '';

			if ( '' !== $slug && $template_slug === $slug ) {
				return $template;
			}
			if ( '' === $slug && '' !== $title && 0 === strcasecmp( $template_title, $title ) ) {
				return $template;
			}
		}

		return null;
	}
}
