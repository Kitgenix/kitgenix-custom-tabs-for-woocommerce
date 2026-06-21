<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION_NAME = 'kitgenix_custom_tabs_for_woocommerce_settings';

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return [
			'enabled'          => 1,
			'max_tabs'         => 10,
			'allow_shortcodes' => 1,
			'priority_base'    => 50,
			'priority_step'    => 10,
			'hide_tab_heading' => 0,
			'tab_templates'    => [],
			'global_tabs'      => [],
		];
	}

	/**
	 * @param mixed $raw
	 * @param int $max
	 * @return array<int,array{title:string,nickname:string,content:string,slug:string,priority:int}>
	 */
	private static function sanitize_tabs_rows( $raw, int $max ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$out = [];
		foreach ( $raw as $row ) {
			if ( count( $out ) >= $max ) {
				break;
			}
			if ( ! is_array( $row ) ) {
				continue;
			}

			$title    = isset( $row['title'] ) ? trim( sanitize_text_field( (string) $row['title'] ) ) : '';
			$nickname = isset( $row['nickname'] ) ? trim( sanitize_text_field( (string) $row['nickname'] ) ) : '';
			$content  = isset( $row['content'] ) ? trim( wp_kses_post( (string) $row['content'] ) ) : '';
			$slug     = isset( $row['slug'] ) ? trim( sanitize_title( (string) $row['slug'] ) ) : '';
			$priority = isset( $row['priority'] ) ? absint( $row['priority'] ) : 0;

			if ( $title === '' ) {
				continue;
			}

			$out[] = [
				'title'    => $title,
				'nickname' => $nickname,
				'content'  => $content,
				'slug'     => $slug,
				'priority' => $priority,
			];
		}

		return $out;
	}

	public static function ensure_defaults(): void {
		$existing = get_option( self::OPTION_NAME, null );
		if ( $existing === null ) {
			add_option( self::OPTION_NAME, self::defaults() );
			return;
		}

		// Merge in any new defaults (forward compatible).
		if ( is_array( $existing ) ) {
			$merged = array_merge( self::defaults(), $existing );
			if ( $merged !== $existing ) {
				update_option( self::OPTION_NAME, $merged );
			}
		}
	}

	public static function register_settings(): void {
		register_setting(
			'kitgenix_custom_tabs_for_woocommerce_settings_group',
			self::OPTION_NAME,
			[
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'type'              => 'array',
				'default'           => self::defaults(),
			]
		);
	}

	/**
	 * @param mixed $value
	 * @return array<string,mixed>
	 */
	public static function sanitize( $value ): array {
		$value = is_array( $value ) ? $value : [];

		// Our settings screen uses multiple forms (one per tab). Treat missing keys as
		// "no change" so saving one tab doesn't wipe the others.
		$existing = get_option( self::OPTION_NAME, [] );
		$existing = is_array( $existing ) ? $existing : [];
		$out      = array_merge( self::defaults(), $existing );

		if ( array_key_exists( 'enabled', $value ) ) {
			$out['enabled'] = empty( $value['enabled'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'allow_shortcodes', $value ) ) {
			$out['allow_shortcodes'] = empty( $value['allow_shortcodes'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'max_tabs', $value ) ) {
			$out['max_tabs'] = max( 1, min( 50, absint( $value['max_tabs'] ) ) );
		}
		if ( array_key_exists( 'priority_base', $value ) ) {
			$out['priority_base'] = absint( $value['priority_base'] );
		}
		if ( array_key_exists( 'priority_step', $value ) ) {
			$out['priority_step'] = max( 1, absint( $value['priority_step'] ) );
		}
		if ( array_key_exists( 'hide_tab_heading', $value ) ) {
			$out['hide_tab_heading'] = empty( $value['hide_tab_heading'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'tab_templates', $value ) ) {
			$out['tab_templates'] = self::sanitize_tabs_rows( $value['tab_templates'], self::template_library_max() );
		}
		if ( array_key_exists( 'global_tabs', $value ) ) {
			$out['global_tabs'] = self::sanitize_tabs_rows( $value['global_tabs'], (int) $out['max_tabs'] );
		}

		// Always enforce max tabs (even if only max_tabs was changed).
		$out['tab_templates'] = self::sanitize_tabs_rows( $out['tab_templates'], self::template_library_max() );
		$out['global_tabs'] = self::sanitize_tabs_rows( $out['global_tabs'], (int) $out['max_tabs'] );

		return $out;
	}

	/**
	 * @return array<int,array{title:string,nickname:string,content:string,slug:string,priority:int}>
	 */
	public static function tab_templates(): array {
		$s = self::get_settings();
		return isset( $s['tab_templates'] ) && is_array( $s['tab_templates'] ) ? $s['tab_templates'] : [];
	}

	public static function template_library_max(): int {
		return 100;
	}

	/**
	 * @return array<int,array{title:string,nickname:string,content:string,slug:string,priority:int}>
	 */
	public static function global_tabs(): array {
		$s = self::get_settings();
		return isset( $s['global_tabs'] ) && is_array( $s['global_tabs'] ) ? $s['global_tabs'] : [];
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function get_settings(): array {
		$raw = get_option( self::OPTION_NAME, [] );
		$raw = is_array( $raw ) ? $raw : [];
		return array_merge( self::defaults(), $raw );
	}

	public static function enabled(): bool {
		$s = self::get_settings();
		return ! empty( $s['enabled'] );
	}

	public static function max_tabs(): int {
		$s = self::get_settings();
		return isset( $s['max_tabs'] ) ? (int) $s['max_tabs'] : (int) self::defaults()['max_tabs'];
	}

	public static function allow_shortcodes(): bool {
		$s = self::get_settings();
		return ! empty( $s['allow_shortcodes'] );
	}

	public static function priority_base(): int {
		$s = self::get_settings();
		return isset( $s['priority_base'] ) ? (int) $s['priority_base'] : (int) self::defaults()['priority_base'];
	}

	public static function priority_step(): int {
		$s = self::get_settings();
		return isset( $s['priority_step'] ) ? (int) $s['priority_step'] : (int) self::defaults()['priority_step'];
	}

	public static function compute_priority_for_index( int $base, int $step, int $index ): int {
		$base = max( 0, $base );
		$step = max( 1, $step );
		$index = max( 0, $index );

		$priority = $base + ( $index * $step );

		// If the base priority is in the gaps between default tabs, keep the
		// auto-generated priorities within that same gap.
		// Default tab priorities: Description=10, Additional Information=20, Reviews=30.
		$max = null;
		if ( $base > 10 && $base < 20 ) {
			$max = 19;
		} elseif ( $base > 20 && $base < 30 ) {
			$max = 29;
		}

		if ( $max !== null && $priority > $max ) {
			$priority = $max;
		}

		return $priority;
	}

	public static function hide_tab_heading(): bool {
		$s = self::get_settings();
		return ! empty( $s['hide_tab_heading'] );
	}
}
