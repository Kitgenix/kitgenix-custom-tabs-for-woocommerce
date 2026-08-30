<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Core;

defined( 'ABSPATH' ) || exit;

final class Settings {
	public const OPTION_NAME = 'kitgenix_custom_tabs_for_woocommerce_settings';

	/**
	 * Reusable tab templates live in their own option, separate from the main
	 * settings option. Templates are admin-only (never read on the frontend)
	 * and can grow into a sizeable library of rich-HTML snippets, so keeping
	 * them out of the main autoloaded option avoids loading that whole
	 * library into memory on every single request – see migrate_templates_option().
	 */
	public const TEMPLATES_OPTION_NAME = 'kitgenix_custom_tabs_for_woocommerce_templates';

	public const SETTINGS_GROUP  = 'kitgenix_custom_tabs_for_woocommerce_settings_group';
	public const TEMPLATES_GROUP = 'kitgenix_custom_tabs_for_woocommerce_templates_group';

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
			'global_tabs'      => [],
		];
	}

	/**
	 * Default shape for one saved tab row (before context-specific fields are added).
	 *
	 * @return array{title:string,nickname:string,content:string,slug:string,priority:int,enabled:int,hide_title:int}
	 */
	public static function default_row(): array {
		return [
			'title'      => '',
			'nickname'   => '',
			'content'    => '',
			'slug'       => '',
			'priority'   => 0,
			'enabled'    => 1,
			'hide_title' => 0,
		];
	}

	/**
	 * Sanitize a list of saved tab/template rows.
	 *
	 * @param mixed  $raw
	 * @param int    $max     Maximum rows to keep.
	 * @param string $context One of 'product', 'global', 'template' – controls which
	 *                        extra fields are sanitized: 'global' gets `target` (product/
	 *                        category/tag/type rules); 'product' and 'global' both get
	 *                        `visibility`; 'template' gets neither (a saved template is a
	 *                        reusable snippet, not a live placement rule).
	 * @return array<int,array<string,mixed>>
	 */
	public static function sanitize_tabs_rows( $raw, int $max, string $context = 'product' ): array {
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

			// A row saved before 2.0.0 (or one this sanitizer built for a context that
			// doesn't collect the field) has no 'enabled' key at all – that absence
			// must default to enabled (1), matching every tab's behavior before this
			// field existed. Only an explicitly-submitted falsy value turns it off.
			$enabled    = array_key_exists( 'enabled', $row ) ? ( empty( $row['enabled'] ) ? 0 : 1 ) : 1;
			$hide_title = empty( $row['hide_title'] ) ? 0 : 1;

			$sanitized = [
				'title'      => $title,
				'nickname'   => $nickname,
				'content'    => $content,
				'slug'       => $slug,
				'priority'   => $priority,
				'enabled'    => $enabled,
				'hide_title' => $hide_title,
			];

			if ( in_array( $context, [ 'product', 'global' ], true ) ) {
				$sanitized['visibility'] = Tab_Matcher::sanitize_visibility( self::maybe_json_decode( $row['visibility'] ?? null ) );
			}
			if ( 'global' === $context ) {
				$sanitized['target'] = Tab_Matcher::sanitize_target( self::maybe_json_decode( $row['target'] ?? null ) );
			}

			$out[] = $sanitized;
		}

		return $out;
	}

	/**
	 * The `visibility`/`target` sub-structures are transported from the admin UI
	 * as a single JSON-encoded hidden field (far simpler than dozens of individual
	 * bracket-notation inputs for nested include/exclude ID lists) rather than as
	 * native PHP array-notation fields. Accepts either shape so a value already
	 * decoded elsewhere (e.g. an imported settings file) also works unchanged.
	 *
	 * @param mixed $raw
	 * @return mixed
	 */
	private static function maybe_json_decode( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}

		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	public static function ensure_defaults(): void {
		$existing = get_option( self::OPTION_NAME, null );
		if ( $existing === null ) {
			add_option( self::OPTION_NAME, self::defaults() );
		} elseif ( is_array( $existing ) ) {
			// Merge in any new defaults (forward compatible).
			$merged = array_merge( self::defaults(), $existing );
			if ( $merged !== $existing ) {
				update_option( self::OPTION_NAME, $merged );
			}
		}

		self::migrate_templates_option();
	}

	/**
	 * One-time, idempotent migration: move `tab_templates` out of the main
	 * settings option into its own non-autoloaded option. Safe to run on every
	 * request – it only acts once (the source key is removed after migrating),
	 * and does nothing at all on a fresh 2.0.0+ install that never had the old key.
	 */
	private static function migrate_templates_option(): void {
		$existing = get_option( self::OPTION_NAME, null );
		if ( ! is_array( $existing ) || ! array_key_exists( 'tab_templates', $existing ) ) {
			return;
		}

		$old_templates = is_array( $existing['tab_templates'] ) ? $existing['tab_templates'] : [];

		if ( ! empty( $old_templates ) ) {
			$current_new = get_option( self::TEMPLATES_OPTION_NAME, null );
			if ( ! is_array( $current_new ) || empty( $current_new ) ) {
				update_option( self::TEMPLATES_OPTION_NAME, $old_templates, false );
			}
		}

		unset( $existing['tab_templates'] );
		update_option( self::OPTION_NAME, $existing );
	}

	public static function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			[
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'type'              => 'array',
				'default'           => self::defaults(),
			]
		);

		register_setting(
			self::TEMPLATES_GROUP,
			self::TEMPLATES_OPTION_NAME,
			[
				'sanitize_callback' => [ self::class, 'sanitize_templates_option' ],
				'type'              => 'array',
				'default'           => [],
			]
		);

		// register_setting()'s args array only gained an 'autoload' key in WP 6.6,
		// and this plugin supports WP 6.0+, so autoload is instead enforced
		// explicitly on every create/update of this option – see
		// force_templates_autoload_off() for why.
		add_action( 'add_option_' . self::TEMPLATES_OPTION_NAME, [ self::class, 'force_templates_autoload_off' ] );
		add_action( 'update_option_' . self::TEMPLATES_OPTION_NAME, [ self::class, 'force_templates_autoload_off' ] );
	}

	/**
	 * Ensure the templates option never autoloads, regardless of WordPress
	 * core version. Templates are a potentially large, admin-only library that
	 * is never read on the frontend, so it must not be pulled into the
	 * alloptions cache loaded on every request.
	 */
	public static function force_templates_autoload_off(): void {
		if ( function_exists( 'wp_set_option_autoload' ) ) {
			wp_set_option_autoload( self::TEMPLATES_OPTION_NAME, false );
			return;
		}

		global $wpdb;
		if ( ! isset( $wpdb ) || ! ( $wpdb instanceof \wpdb ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- no public API exists on WP < 6.4 to change an existing option's autoload flag.
		$wpdb->update( $wpdb->options, [ 'autoload' => 'no' ], [ 'option_name' => self::TEMPLATES_OPTION_NAME ] );
		wp_cache_delete( 'alloptions', 'options' );
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
			$requested        = absint( $value['max_tabs'] );
			$out['max_tabs']  = max( 1, min( 50, $requested ) );
			if ( $out['max_tabs'] !== $requested ) {
				Event_Log::record( 'settings-saved', 'info', __( 'Max tabs value was outside the allowed range (1–50) and was adjusted back into range.', 'kitgenix-custom-tabs-for-woocommerce' ), 'value_clamped' );
			}
		}
		if ( array_key_exists( 'priority_base', $value ) ) {
			$out['priority_base'] = absint( $value['priority_base'] );
		}
		if ( array_key_exists( 'priority_step', $value ) ) {
			$requested            = absint( $value['priority_step'] );
			$out['priority_step'] = max( 1, $requested );
			if ( $out['priority_step'] !== $requested ) {
				Event_Log::record( 'settings-saved', 'info', __( 'Priority step value was below the minimum of 1 and was adjusted back into range.', 'kitgenix-custom-tabs-for-woocommerce' ), 'value_clamped' );
			}
		}
		if ( array_key_exists( 'hide_tab_heading', $value ) ) {
			$out['hide_tab_heading'] = empty( $value['hide_tab_heading'] ) ? 0 : 1;
		}
		if ( array_key_exists( 'global_tabs', $value ) ) {
			$out['global_tabs'] = self::sanitize_tabs_rows( $value['global_tabs'], (int) $out['max_tabs'], 'global' );
		}

		// Always enforce max tabs (even if only max_tabs was changed).
		$out['global_tabs'] = self::sanitize_tabs_rows( $out['global_tabs'], (int) $out['max_tabs'], 'global' );

		// A pre-2.0.0 option still carrying its old 'tab_templates' key would
		// otherwise be re-added by array_merge() with defaults() above; strip it
		// so the dedicated templates option (see migrate_templates_option()) stays
		// the single source of truth going forward.
		unset( $out['tab_templates'] );

		return $out;
	}

	/**
	 * @param mixed $value
	 * @return array<int,array<string,mixed>>
	 */
	public static function sanitize_templates_option( $value ): array {
		return self::sanitize_tabs_rows( $value, self::template_library_max(), 'template' );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function tab_templates(): array {
		$raw = get_option( self::TEMPLATES_OPTION_NAME, [] );
		return is_array( $raw ) ? $raw : [];
	}

	public static function template_library_max(): int {
		return 100;
	}

	/**
	 * @return array<int,array<string,mixed>>
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
