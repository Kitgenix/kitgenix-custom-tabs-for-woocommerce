<?php

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Event_Log;
use KitgenixCustomTabsForWooCommerce\Core\Settings;

/**
 * JSON/CSV import & export for settings, global tabs, and templates.
 *
 * JSON is the full-fidelity round-trip format (settings + global tabs +
 * templates, including visibility/target rules). CSV is a simpler, flattened
 * export of one row list at a time (title/nickname/slug/priority/content/
 * enabled only) for admins who want to bulk-edit content in a spreadsheet –
 * visibility and targeting rules aren't representable in a flat CSV row, so
 * CSV import only ever touches the basic fields and always preserves
 * whatever visibility/target rules a matching existing row already has.
 */
final class Portability {
	private const EXPORT_ACTION = 'kitgenix_custom_tabs_for_woocommerce_export';
	private const IMPORT_ACTION = 'kitgenix_custom_tabs_for_woocommerce_import';
	private const CSV_EXPORT_ACTION = 'kitgenix_custom_tabs_for_woocommerce_export_csv';
	private const CSV_IMPORT_ACTION = 'kitgenix_custom_tabs_for_woocommerce_import_csv';
	private const NONCE = 'kitgenix_custom_tabs_for_woocommerce_portability';
	private const PLUGIN_ID = 'kitgenix-custom-tabs-for-woocommerce';
	private const SCHEMA_VERSION = 1;

	public static function init(): void {
		add_action( 'admin_post_' . self::EXPORT_ACTION, [ self::class, 'handle_export_json' ] );
		add_action( 'admin_post_' . self::IMPORT_ACTION, [ self::class, 'handle_import_json' ] );
		add_action( 'admin_post_' . self::CSV_EXPORT_ACTION, [ self::class, 'handle_export_csv' ] );
		add_action( 'admin_post_' . self::CSV_IMPORT_ACTION, [ self::class, 'handle_import_csv' ] );
	}

	public static function get_export_action(): string {
		return self::EXPORT_ACTION;
	}

	public static function get_import_action(): string {
		return self::IMPORT_ACTION;
	}

	public static function get_csv_export_action(): string {
		return self::CSV_EXPORT_ACTION;
	}

	public static function get_csv_import_action(): string {
		return self::CSV_IMPORT_ACTION;
	}

	public static function get_nonce_action(): string {
		return self::NONCE;
	}

	private static function require_capability(): void {
		$cap = class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'kitgenix-custom-tabs-for-woocommerce' ) );
		}
	}

	private static function settings_page_url( string $tab = 'settings' ): string {
		return admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=' . rawurlencode( $tab ) );
	}

	/* =========================================================
	 * JSON export/import – full fidelity (settings + global tabs + templates)
	 * ========================================================= */

	public static function handle_export_json(): void {
		self::require_capability();
		check_admin_referer( self::NONCE );

		$settings = Settings::get_settings();
		unset( $settings['global_tabs'] ); // exported separately below for clarity

		$payload = [
			'plugin'        => self::PLUGIN_ID,
			'schema'        => self::SCHEMA_VERSION,
			'plugin_version'=> defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION : '',
			'exported_at'   => function_exists( 'current_time' ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' ),
			'settings'      => $settings,
			'global_tabs'   => Settings::global_tabs(),
			'templates'     => Settings::tab_templates(),
		];

		$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $json ) || '' === $json ) {
			wp_safe_redirect( add_query_arg( [ 'kitgenix_portability' => 'error', 'reason' => 'export' ], self::settings_page_url( 'settings' ) ) );
			exit;
		}

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=kitgenix-custom-tabs-' . gmdate( 'Ymd-His' ) . '.json' );
		header( 'Content-Length: ' . strlen( $json ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON file download, not HTML output.
		echo $json;
		exit;
	}

	public static function handle_import_json(): void {
		self::require_capability();
		check_admin_referer( self::NONCE );

		$file = self::require_uploaded_file( 'kitgenix_custom_tabs_for_woocommerce_import_file' );
		if ( null === $file ) {
			return;
		}

		$contents = file_get_contents( $file );
		if ( ! is_string( $contents ) || '' === $contents ) {
			self::redirect_error( 'empty' );
		}

		$data = json_decode( (string) $contents, true );
		if ( ! is_array( $data ) ) {
			self::redirect_error( 'json' );
		}

		if ( isset( $data['plugin'] ) && self::PLUGIN_ID !== $data['plugin'] ) {
			self::redirect_error( 'plugin' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated above via check_admin_referer().
		$mode = isset( $_POST['kitgenix_custom_tabs_for_woocommerce_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['kitgenix_custom_tabs_for_woocommerce_import_mode'] ) ) : 'replace';
		$mode = in_array( $mode, [ 'merge', 'replace' ], true ) ? $mode : 'replace';

		$imported_settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : [];
		$imported_global   = isset( $data['global_tabs'] ) && is_array( $data['global_tabs'] ) ? $data['global_tabs'] : [];
		$imported_templates = isset( $data['templates'] ) && is_array( $data['templates'] ) ? $data['templates'] : [];

		$current = Settings::get_settings();
		$max     = isset( $imported_settings['max_tabs'] ) ? max( 1, min( 50, absint( $imported_settings['max_tabs'] ) ) ) : (int) $current['max_tabs'];

		$settings_to_save = 'merge' === $mode ? array_merge( $current, $imported_settings ) : array_merge( Settings::defaults(), $imported_settings );
		$settings_to_save['global_tabs'] = Settings::sanitize_tabs_rows(
			'merge' === $mode ? array_merge( $current['global_tabs'], $imported_global ) : $imported_global,
			$max,
			'global'
		);
		$settings_to_save = self::apply_settings_scalars( $settings_to_save, $current, $imported_settings, $mode );

		update_option( Settings::OPTION_NAME, Settings::sanitize( $settings_to_save ) );

		$templates_to_save = 'merge' === $mode
			? array_merge( Settings::tab_templates(), $imported_templates )
			: $imported_templates;
		update_option( Settings::TEMPLATES_OPTION_NAME, Settings::sanitize_tabs_rows( $templates_to_save, Settings::template_library_max(), 'template' ), false );

		Event_Log::record( 'import', 'success', sprintf(
			/* translators: 1: merge or replace, 2: number of global tabs imported, 3: number of templates imported */
			__( 'Settings imported (%1$s mode): %2$d global tab(s), %3$d template(s).', 'kitgenix-custom-tabs-for-woocommerce' ),
			$mode,
			count( $imported_global ),
			count( $imported_templates )
		), 'import_success' );

		wp_safe_redirect( add_query_arg( [ 'kitgenix_portability' => 'success', 'mode' => $mode ], self::settings_page_url( 'settings' ) ) );
		exit;
	}

	/**
	 * @param array<string,mixed> $settings_to_save
	 * @param array<string,mixed> $current
	 * @param array<string,mixed> $imported
	 */
	private static function apply_settings_scalars( array $settings_to_save, array $current, array $imported, string $mode ): array {
		if ( 'merge' === $mode ) {
			foreach ( [ 'enabled', 'max_tabs', 'allow_shortcodes', 'priority_base', 'priority_step', 'hide_tab_heading' ] as $key ) {
				if ( ! array_key_exists( $key, $imported ) ) {
					$settings_to_save[ $key ] = $current[ $key ];
				}
			}
		}

		return $settings_to_save;
	}

	/* =========================================================
	 * CSV export/import – flattened row lists (global tabs OR templates)
	 * ========================================================= */

	public static function handle_export_csv(): void {
		self::require_capability();
		check_admin_referer( self::NONCE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated above via check_admin_referer().
		$which = isset( $_GET['which'] ) ? sanitize_key( wp_unslash( $_GET['which'] ) ) : 'global_tabs';
		$rows  = 'templates' === $which ? Settings::tab_templates() : Settings::global_tabs();

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ) );
		header( 'Content-Disposition: attachment; filename=kitgenix-custom-tabs-' . sanitize_key( $which ) . '-' . gmdate( 'Ymd-His' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			exit;
		}

		fputcsv( $out, [ 'title', 'nickname', 'slug', 'priority', 'enabled', 'content' ] );
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			fputcsv( $out, [
				(string) ( $row['title'] ?? '' ),
				(string) ( $row['nickname'] ?? '' ),
				(string) ( $row['slug'] ?? '' ),
				(string) ( $row['priority'] ?? 0 ),
				( ! array_key_exists( 'enabled', $row ) || ! empty( $row['enabled'] ) ) ? '1' : '0',
				(string) ( $row['content'] ?? '' ),
			] );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing a php://output stream handle (not a real file); WP_Filesystem doesn't apply to stream wrappers.
		exit;
	}

	public static function handle_import_csv(): void {
		self::require_capability();
		check_admin_referer( self::NONCE );

		$file = self::require_uploaded_file( 'kitgenix_custom_tabs_for_woocommerce_import_csv_file' );
		if ( null === $file ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated above via check_admin_referer().
		$which = isset( $_POST['which'] ) ? sanitize_key( wp_unslash( $_POST['which'] ) ) : 'global_tabs';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated above via check_admin_referer().
		$mode  = isset( $_POST['kitgenix_custom_tabs_for_woocommerce_import_mode'] ) ? sanitize_key( wp_unslash( $_POST['kitgenix_custom_tabs_for_woocommerce_import_mode'] ) ) : 'replace';
		$mode  = in_array( $mode, [ 'merge', 'replace' ], true ) ? $mode : 'replace';

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$contents = $wp_filesystem->get_contents( $file );
		if ( false === $contents ) {
			self::redirect_error( 'upload' );
		}

		// Parse via an in-memory stream (not a real filesystem file, so fgetcsv()'s
		// RFC4180 handling of quoted multi-line fields still applies) fed from the
		// WP_Filesystem-read contents above, rather than fopen()'ing $file directly.
		// maxmemory (5MB) matches the upload size cap in require_uploaded_file(), so
		// this never spills to a disk-backed temp file.
		$handle = fopen( 'php://temp/maxmemory:5242880', 'r+' );
		fwrite( $handle, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- writing into an in-memory php://temp stream, not a real file.
		rewind( $handle );

		$header = fgetcsv( $handle );
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing an in-memory php://temp stream, not a real file.
			self::redirect_error( 'csv' );
		}
		$header = array_map( static function ( $h ) {
			return sanitize_key( (string) $h );
		}, $header );

		$imported = [];
		while ( ( $line = fgetcsv( $handle ) ) !== false ) {
			$row = [];
			foreach ( $header as $i => $key ) {
				$row[ $key ] = $line[ $i ] ?? '';
			}
			$imported[] = $row;
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing an in-memory php://temp stream, not a real file.

		$is_templates = 'templates' === $which;
		$max          = $is_templates ? Settings::template_library_max() : Settings::max_tabs();
		$context      = $is_templates ? 'template' : 'global';

		$current = $is_templates ? Settings::tab_templates() : Settings::global_tabs();
		$new_rows = Settings::sanitize_tabs_rows( $imported, $max, $context );
		$final    = 'merge' === $mode ? array_merge( $current, $new_rows ) : $new_rows;
		$final    = Settings::sanitize_tabs_rows( $final, $max, $context );

		if ( $is_templates ) {
			update_option( Settings::TEMPLATES_OPTION_NAME, $final, false );
		} else {
			$settings = Settings::get_settings();
			$settings['global_tabs'] = $final;
			update_option( Settings::OPTION_NAME, Settings::sanitize( $settings ) );
		}

		Event_Log::record( 'import', 'success', sprintf(
			/* translators: 1: "global tabs" or "templates", 2: merge or replace, 3: row count imported */
			__( 'CSV import (%1$s, %2$s mode): %3$d row(s) imported.', 'kitgenix-custom-tabs-for-woocommerce' ),
			$is_templates ? 'templates' : 'global_tabs',
			$mode,
			count( $new_rows )
		), 'import_success' );

		wp_safe_redirect( add_query_arg( [ 'kitgenix_portability' => 'success', 'mode' => $mode ], self::settings_page_url( $is_templates ? 'templates' : 'global-tabs' ) ) );
		exit;
	}

	/**
	 * Validate a single-file upload and return its tmp path, or redirect with
	 * an error and return null.
	 */
	private static function require_uploaded_file( string $field ): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- validated by check_admin_referer() in both callers (handle_import_json(), handle_import_csv()) before this private helper runs.
		if ( ! isset( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) {
			self::redirect_error( 'nofile' );
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- validated field-by-field below; nonce validated by callers (see above).
		$file = $_FILES[ $field ];
		if ( ! empty( $file['error'] ) ) {
			self::redirect_error( 'upload' );
			return null;
		}

		if ( ! empty( $file['size'] ) && (int) $file['size'] > 5 * 1024 * 1024 ) {
			self::redirect_error( 'size' );
			return null;
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			self::redirect_error( 'upload' );
			return null;
		}

		return $tmp_name;
	}

	private static function redirect_error( string $reason ): void {
		wp_safe_redirect( add_query_arg( [ 'kitgenix_portability' => 'error', 'reason' => $reason ], self::settings_page_url( 'settings' ) ) );
		exit;
	}

	/**
	 * Render the notices for a completed import/export (success or error).
	 * Called from Settings_UI's admin_notices-equivalent render path.
	 */
	public static function render_notice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of a redirect result flag.
		$state = isset( $_GET['kitgenix_portability'] ) ? sanitize_key( wp_unslash( $_GET['kitgenix_portability'] ) ) : '';
		if ( '' === $state ) {
			return;
		}

		if ( 'success' === $state ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$mode = isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'replace';
			$message = 'merge' === $mode
				? __( 'Import completed using merge mode.', 'kitgenix-custom-tabs-for-woocommerce' )
				: __( 'Import completed using replace mode.', 'kitgenix-custom-tabs-for-woocommerce' );
			echo '<div class="kitgenix-notice kitgenix-notice-success"><div class="kitgenix-notice-body"><p class="kitgenix-notice-text">' . esc_html( $message ) . '</p></div></div>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$reason = isset( $_GET['reason'] ) ? sanitize_key( wp_unslash( $_GET['reason'] ) ) : 'unknown';
		$messages = [
			'nofile'  => __( 'Import failed: no file was uploaded.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'upload'  => __( 'Import failed: the upload could not be processed.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'size'    => __( 'Import failed: the file is too large.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'empty'   => __( 'Import failed: the uploaded file was empty.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'json'    => __( 'Import failed: the file does not contain valid JSON.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'csv'     => __( 'Import failed: the file does not contain valid CSV data.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'plugin'  => __( 'Import failed: this file was not exported from Kitgenix Custom Tabs for WooCommerce.', 'kitgenix-custom-tabs-for-woocommerce' ),
			'export'  => __( 'Export failed: the payload could not be generated.', 'kitgenix-custom-tabs-for-woocommerce' ),
		];
		$message = $messages[ $reason ] ?? __( 'Import failed.', 'kitgenix-custom-tabs-for-woocommerce' );
		echo '<div class="kitgenix-notice kitgenix-notice-error"><div class="kitgenix-notice-body"><p class="kitgenix-notice-text">' . esc_html( $message ) . '</p></div></div>';
	}
}
