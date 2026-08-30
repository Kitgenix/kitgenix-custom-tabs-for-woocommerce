<?php

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Event_Log;
use KitgenixCustomTabsForWooCommerce\Core\Settings;
use KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher;

final class Settings_UI {
	use Tab_Modal_Fields;
	/** @var string|null */
	private static $page_hook = null;

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'register_menu' ], 50 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ], 20 );
		add_action( 'admin_notices', [ self::class, 'render_admin_notices' ] );
		add_action( 'admin_post_kitgenix_custom_tabs_for_woocommerce_clear_event_log', [ self::class, 'handle_clear_event_log' ] );
		add_action( 'update_option_' . Settings::OPTION_NAME, [ self::class, 'on_settings_saved' ], 10, 3 );
	}

	private static function is_settings_screen_now(): bool {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && ! empty( self::$page_hook ) && $screen->id === self::$page_hook ) {
				return true;
			}
		}

		// Fallback via `page` query arg.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		return $page === 'kitgenix-custom-tabs-for-woocommerce';
	}

	public static function render_admin_notices(): void {
		$allowed = current_user_can( 'manage_options' ) || ( class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' ) );
		if ( ! $allowed ) {
			return;
		}

		if ( ! self::is_settings_screen_now() ) {
			return;
		}

		if ( function_exists( 'settings_errors' ) ) {
			settings_errors();
		}
	}

	public static function register_menu(): void {
		if ( function_exists( '\\kitgenix_custom_tabs_for_woocommerce_ensure_admin_menu' ) ) {
			\kitgenix_custom_tabs_for_woocommerce_ensure_admin_menu();
		}

		$cap = ( class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options' );

		self::$page_hook = add_submenu_page(
			'kitgenix',
			__( 'Kitgenix Custom Tabs for WooCommerce', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Custom Tabs', 'kitgenix-custom-tabs-for-woocommerce' ),
			$cap,
			'kitgenix-custom-tabs-for-woocommerce',
			[ self::class, 'render_page' ]
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( empty( self::$page_hook ) || $hook !== self::$page_hook ) {
			return;
		}

		$ver = defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION : null;
		$base_dir = defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_DIR' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_DIR : '';
		$admin_css_file = $base_dir ? $base_dir . 'assets/css/admin.css' : '';
		$admin_js_file  = $base_dir ? $base_dir . 'assets/js/admin.js' : '';
		$ui_js_file  = $base_dir ? $base_dir . 'assets/js/kitgenix-admin-tabs.js' : '';
		$admin_css_ver = ( $admin_css_file && file_exists( $admin_css_file ) ) ? (string) filemtime( $admin_css_file ) : $ver;
		$admin_js_ver  = ( $admin_js_file && file_exists( $admin_js_file ) ) ? (string) filemtime( $admin_js_file ) : $ver;
		$ui_js_ver   = ( $ui_js_file && file_exists( $ui_js_file ) ) ? (string) filemtime( $ui_js_file ) : $ver;

		wp_enqueue_style( 'kitgenix-custom-tabs-for-woocommerce-admin-ui' );

		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-admin',
			plugins_url( 'assets/css/admin.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[ 'kitgenix-custom-tabs-for-woocommerce-admin-ui' ],
			$admin_css_ver
		);

		// Quill is bundled locally (assets/vendor/quill/) rather than loaded from a
		// third-party CDN – see the matching enqueue in Product_Tabs::enqueue_assets()
		// for why.
		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			plugins_url( 'assets/vendor/quill/quill.snow.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			'1.3.7'
		);

		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			plugins_url( 'assets/vendor/quill/quill.min.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			'1.3.7',
			true
		);

		if ( class_exists( 'WooCommerce' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			// Powers the "Products" targeting search on the Global Tabs modal –
			// the same AJAX-backed product search WooCommerce's own Linked
			// Products fields use (woocommerce_json_search_products), so no
			// custom search endpoint is needed here.
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'select2' );
		}

		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-admin',
			plugins_url( 'assets/js/admin.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[ 'kitgenix-custom-tabs-for-woocommerce-quill', 'wc-enhanced-select' ],
			$admin_js_ver,
			true
		);

		wp_enqueue_script(
			'kitgenix-admin-tabs',
			plugins_url( 'assets/js/kitgenix-admin-tabs.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[],
			$ui_js_ver,
			true
		);

		wp_localize_script(
			'kitgenix-custom-tabs-for-woocommerce-admin',
			'kitgenix_custom_tabs_for_woocommerce_admin',
			[
				'maxTabs' => Settings::max_tabs(),
				'priorityBase' => Settings::priority_base(),
				'priorityStep' => Settings::priority_step(),
				'i18n'    => [
					'confirmRemove' => __( 'Remove this tab?', 'kitgenix-custom-tabs-for-woocommerce' ),
					'maxReached'    => __( 'You have reached the maximum number of tabs.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'titleRequired' => __( 'Please enter a tab title.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'contentRequired' => __( 'Please enter tab content.', 'kitgenix-custom-tabs-for-woocommerce' ),
					'duplicateTab' => __( 'Duplicate', 'kitgenix-custom-tabs-for-woocommerce' ),
					'chooseTemplate' => __( 'Choose a saved template', 'kitgenix-custom-tabs-for-woocommerce' ),
					'insertTemplate' => __( 'Insert template', 'kitgenix-custom-tabs-for-woocommerce' ),
					'templateToolbarLabel' => __( 'Template library', 'kitgenix-custom-tabs-for-woocommerce' ),
					'copyLabel' => __( 'Copy', 'kitgenix-custom-tabs-for-woocommerce' ),
					'moveUp' => __( 'Move up', 'kitgenix-custom-tabs-for-woocommerce' ),
					'moveDown' => __( 'Move down', 'kitgenix-custom-tabs-for-woocommerce' ),
					'disableTab' => __( 'Disable', 'kitgenix-custom-tabs-for-woocommerce' ),
					'enableTab' => __( 'Enable', 'kitgenix-custom-tabs-for-woocommerce' ),
					'disabledBadge' => __( 'Disabled', 'kitgenix-custom-tabs-for-woocommerce' ),
				],
			]
		);
	}

	public static function render_page(): void {
		$allowed = current_user_can( 'manage_options' ) || ( class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' ) );
		if ( ! $allowed ) {
			return;
		}

		$settings = Settings::get_settings();
		$ver      = defined( 'KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION' ) ? (string) KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION : '';

		$default_tab = 'settings';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['tab'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$maybe_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
			if ( in_array( $maybe_tab, [ 'settings', 'templates', 'global-tabs', 'portability', 'support', 'log' ], true ) ) {
				$default_tab = $maybe_tab;
			}
		}

		$logo = plugins_url( 'assets/images/logos/kitgenix-primary-favicon.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );
		$social_base = plugins_url( 'assets/images/social-media/', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );
		$base_tab_url = admin_url( 'admin.php?page=' . rawurlencode( 'kitgenix-custom-tabs-for-woocommerce' ) );

		echo '<div class="wrap kitgenix-admin-app kitgenix-custom-tabs-for-woocommerce-use-top-tabs kitgenix-custom-tabs-for-woocommerce-scope" data-kitgenix-tabs data-kitgenix-default-tab="' . esc_attr( $default_tab ) . '" id="kitgenix-custom-tabs-for-woocommerce-admin-app">';

		echo '<div class="kitgenix-topbar">'
			. '<div class="kitgenix-topbar-left">'
			. '<a class="kitgenix-topbar-brand" href="' . esc_url( admin_url( 'admin.php?page=kitgenix' ) ) . '" title="Kitgenix">'
			. '<img class="kitgenix-topbar-logo" src="' . esc_url( $logo ) . '" alt="Kitgenix" width="28" height="28" />'
			. '</a>'
			. '<span class="kitgenix-topbar-divider" aria-hidden="true"></span>'
			. '<div class="kitgenix-topbar-plugin-info">'
			. '<span class="kitgenix-topbar-title">' . esc_html__( 'Custom Tabs for WooCommerce', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>'
			. '<span class="kitgenix-topbar-version">v' . esc_html( $ver ) . '</span>'
			. '</div>'
			. '</div>'
			. '<div class="kitgenix-topbar-center">'
			. '<ul class="kitgenix-topbar-menu" role="tablist">'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'settings' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'settings' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=settings#kitgenix-tab-settings' ) . '" data-kitgenix-tab="settings">' . esc_html__( 'Settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'templates' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'templates' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=templates#kitgenix-tab-templates' ) . '" data-kitgenix-tab="templates">' . esc_html__( 'Templates', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'global-tabs' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'global-tabs' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=global-tabs#kitgenix-tab-global-tabs' ) . '" data-kitgenix-tab="global-tabs">' . esc_html__( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'portability' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'portability' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=portability#kitgenix-tab-portability' ) . '" data-kitgenix-tab="portability">' . esc_html__( 'Portability', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'log' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'log' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=log#kitgenix-tab-log' ) . '" data-kitgenix-tab="log">' . esc_html__( 'Log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '<li class="kitgenix-menu-item ' . ( $default_tab === 'support' ? 'kitgenix-active' : '' ) . '"><a class="kitgenix-menu-link kitgenix-tab-trigger ' . ( $default_tab === 'support' ? 'kitgenix-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=support#kitgenix-tab-support' ) . '" data-kitgenix-tab="support">' . esc_html__( 'Support', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></li>'
			. '</ul>'
			. '</div>'
			. '<div class="kitgenix-topbar-right" aria-label="Topbar actions">'
			. '<div class="kitgenix-topbar-search">'
			. '<button type="button" class="kitgenix-topbar-icon-btn kitgenix-search-toggle" aria-label="' . esc_attr__( 'Search settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '" aria-expanded="false"><svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5232 13.4627L17.7355 16.6742L16.6742 17.7355L13.4627 14.5232C12.2678 15.4812 10.7815 16.0022 9.25 16C5.524 16 2.5 12.976 2.5 9.25C2.5 5.524 5.524 2.5 9.25 2.5C12.976 2.5 16 5.524 16 9.25C16.0022 10.7815 15.4812 12.2678 14.5232 13.4627ZM13.0187 12.9062C13.9706 11.9274 14.5021 10.6153 14.5 9.25C14.5 6.349 12.1502 4 9.25 4C6.349 4 4 6.349 4 9.25C4 12.1502 6.349 14.5 9.25 14.5C10.6153 14.5021 11.9274 13.9706 12.9062 13.0187L13.0187 12.9062V12.9062Z" fill="currentColor"></path></svg></button>'
			. '<div class="kitgenix-search-panel">'
			. '<span class="kitgenix-search-icon" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5232 13.4627L17.7355 16.6742L16.6742 17.7355L13.4627 14.5232C12.2678 15.4812 10.7815 16.0022 9.25 16C5.524 16 2.5 12.976 2.5 9.25C2.5 5.524 5.524 2.5 9.25 2.5C12.976 2.5 16 5.524 16 9.25C16.0022 10.7815 15.4812 12.2678 14.5232 13.4627ZM13.0187 12.9062C13.9706 11.9274 14.5021 10.6153 14.5 9.25C14.5 6.349 12.1502 4 9.25 4C6.349 4 4 6.349 4 9.25C4 12.1502 6.349 14.5 9.25 14.5C10.6153 14.5021 11.9274 13.9706 12.9062 13.0187L13.0187 12.9062V12.9062Z" fill="currentColor"></path></svg></span>'
			. '<input type="search" class="kitgenix-topbar-search-input" placeholder="' . esc_attr__( 'Search settings…', 'kitgenix-custom-tabs-for-woocommerce' ) . '" aria-label="' . esc_attr__( 'Search settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '" autocomplete="off" />'
			. '<kbd class="kitgenix-search-kbd" title="Press / or ⌘K to search">/</kbd>'
			. '<button type="button" class="kitgenix-search-clear" aria-label="' . esc_attr__( 'Clear search', 'kitgenix-custom-tabs-for-woocommerce' ) . '" style="display:none;">&times;</button>'
			. '</div>'
			. '</div>'
			. '<button type="button" class="kitgenix-topbar-icon-btn kitgenix-theme-toggle" aria-label="' . esc_attr__( 'Toggle color theme', 'kitgenix-custom-tabs-for-woocommerce' ) . '" title="' . esc_attr__( 'Toggle color theme', 'kitgenix-custom-tabs-for-woocommerce' ) . '">'
			. '<svg class="kitgenix-theme-icon-light" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="3.25" stroke="currentColor" stroke-width="1.3"></circle><path d="M10 2.5v2M10 15.5v2M17.5 10h-2M4.5 10h-2M15.3 4.7l-1.4 1.4M6.1 13.9l-1.4 1.4M15.3 15.3l-1.4-1.4M6.1 6.1 4.7 4.7" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path></svg>'
			. '<svg class="kitgenix-theme-icon-dark" width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 12.3A6.5 6.5 0 0 1 7.7 3.5a6.5 6.5 0 1 0 8.8 8.8Z" fill="currentColor"></path></svg>'
			. '</button>'
			. '<a class="kitgenix-topbar-icon-btn" href="' . esc_url( admin_url( 'admin.php?page=kitgenix' ) ) . '" title="' . esc_attr__( 'Kitgenix Hub', 'kitgenix-custom-tabs-for-woocommerce' ) . '" aria-label="Kitgenix Hub"><svg width="15" height="15" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.25 3.25H9.25V9.25H3.25V3.25ZM10.75 3.25H16.75V9.25H10.75V3.25ZM3.25 10.75H9.25V16.75H3.25V10.75ZM10.75 10.75H16.75V16.75H10.75V10.75Z" fill="currentColor"></path></svg></a>'
			. '<button type="button" class="kitgenix-topbar-hamburger" aria-label="' . esc_attr__( 'Toggle navigation', 'kitgenix-custom-tabs-for-woocommerce' ) . '"><span></span><span></span><span></span></button>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-settings-layout kitgenix-settings-layout"><div class="kitgenix-custom-tabs-for-woocommerce-settings-content kitgenix-settings-content" id="kitgenix-custom-tabs-for-woocommerce-settings-content" tabindex="-1">';

		echo '<div id="kitgenix-tab-settings" data-kitgenix-tab-panel="settings"' . ( $default_tab !== 'settings' ? ' hidden="hidden"' : '' ) . '>';
		self::render_settings_tab( $settings );
		echo '</div>';

		echo '<div id="kitgenix-tab-templates" data-kitgenix-tab-panel="templates"' . ( $default_tab !== 'templates' ? ' hidden="hidden"' : '' ) . '>';
		self::render_templates_tab( $settings, Settings::tab_templates() );
		echo '</div>';

		echo '<div id="kitgenix-tab-global-tabs" data-kitgenix-tab-panel="global-tabs"' . ( $default_tab !== 'global-tabs' ? ' hidden="hidden"' : '' ) . '>';
		self::render_global_tabs_tab( $settings );
		echo '</div>';

		echo '<div id="kitgenix-tab-portability" data-kitgenix-tab-panel="portability"' . ( $default_tab !== 'portability' ? ' hidden="hidden"' : '' ) . '>';
		self::render_portability_tab();
		echo '</div>';

		echo '<div id="kitgenix-tab-support" data-kitgenix-tab-panel="support"' . ( $default_tab !== 'support' ? ' hidden="hidden"' : '' ) . '>';
		self::render_support_tab( $settings );
		echo '</div>';

		echo '<div id="kitgenix-tab-log" data-kitgenix-tab-panel="log"' . ( $default_tab !== 'log' ? ' hidden="hidden"' : '' ) . '>';
		self::render_log_tab();
		echo '</div>';

		// Shared modal/editor instance for manager UIs on this screen.
		// Keeping a single instance avoids duplicate DOM IDs and modal lifecycle conflicts.
		self::render_backbone_modal_template();
		self::render_editor_dock();

		echo '</div>';
		self::render_sidebar();
		echo '</div>';
		echo '</div>'; // .wrap.kitgenix-admin-app
	}

	private static function render_sidebar(): void {
		$social_base = plugins_url( 'assets/images/social-media/', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );

		echo '<aside class="kitgenix-settings-sidebar" aria-label="' . esc_attr__( 'Help and links', 'kitgenix-custom-tabs-for-woocommerce' ) . '">';

		echo '<div class="kitgenix-card">'
			. '<div class="kitgenix-card-body">'
			. '<h2>' . esc_html__( 'Need Help?', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Open the documentation for setup guidance or send us a support request if you need help configuring the plugin.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-button-group kitgenix-button-group-stack">'
			. '<a class="button button-secondary" href="' . esc_url( 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="button button-primary" href="' . esc_url( 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/support' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Request Support', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card">'
			. '<div class="kitgenix-card-body">'
			. '<h2>' . esc_html__( 'Visit Our Official Facebook Group', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Join the Kitgenix community to ask questions, share feedback, and keep up with product updates.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-button-group kitgenix-button-group-stack">'
			. '<a class="button button-secondary" href="' . esc_url( 'https://www.facebook.com/groups/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Join Group', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card">'
			. '<div class="kitgenix-card-body">'
			. '<h2>' . esc_html__( 'Follow Us', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Keep up with new releases, tutorials, and product news across our channels.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-sidebar-social-grid">'
			. '<a class="kitgenix-sidebar-social-link" href="https://kitgenix.com" target="_blank" rel="noopener noreferrer" aria-label="Website" title="Website"><img src="' . esc_url( $social_base . 'globe-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.facebook.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><img src="' . esc_url( $social_base . 'facebook-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.instagram.com/kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><img src="' . esc_url( $social_base . 'instagram-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.youtube.com/@Kitgenix" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><img src="' . esc_url( $social_base . 'youtube-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.reddit.com/r/Kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Reddit" title="Reddit"><img src="' . esc_url( $social_base . 'reddit-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.linkedin.com/company/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"><img src="' . esc_url( $social_base . 'linkedin-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://x.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><img src="' . esc_url( $social_base . 'x-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://www.tiktok.com/@kitgenix" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><img src="' . esc_url( $social_base . 'tiktok-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '<a class="kitgenix-sidebar-social-link" href="https://github.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><img src="' . esc_url( $social_base . 'github-solid.svg' ) . '" alt="" width="16" height="16" aria-hidden="true" /></a>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '</aside>';
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function render_settings_tab( array $settings ): void {
		echo '<form method="post" action="options.php" autocomplete="off" novalidate>';
		settings_fields( 'kitgenix_custom_tabs_for_woocommerce_settings_group' );
		wp_nonce_field( 'kitgenix_custom_tabs_for_woocommerce_settings_save', 'kitgenix_custom_tabs_for_woocommerce_settings_nonce' );

		// Ensure unchecked checkboxes still submit a value.
		echo '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[enabled]" value="0" />';
		echo '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[allow_shortcodes]" value="0" />';
		echo '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[hide_tab_heading]" value="0" />';

		echo '<div class="kitgenix-card">';

		echo '<div class="kitgenix-card-head">'
			. '<div class="kitgenix-card-head-main">'
			. '<span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'settings' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<div class="kitgenix-card-head-text">'
			. '<h2>' . esc_html__( 'General', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Control how the custom tabs feature behaves across your store.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card-body"><div class="kitgenix-settings-group">';

		echo '<div class="kitgenix-setting-row">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_enabled">' . esc_html__( 'Enable custom tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<label class="kitgenix-toggle">'
			. '<input type="checkbox" id="kitgenix_custom_tabs_for_woocommerce_enabled" class="kitgenix-toggle-input" name="' . esc_attr( Settings::OPTION_NAME ) . '[enabled]" value="1" ' . checked( ! empty( $settings['enabled'] ), true, false ) . ' />'
			. '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
			. '<span class="kitgenix-toggle-label">' . esc_html__( 'Enable the custom tabs feature', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>'
			. '</label>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-setting-row">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_max_tabs">' . esc_html__( 'Max tabs per product', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '<p class="kitgenix-setting-row-desc">' . esc_html__( 'Keeps the product editor fast and prevents accidental huge tab lists.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<input type="number" min="1" max="50" id="kitgenix_custom_tabs_for_woocommerce_max_tabs" name="' . esc_attr( Settings::OPTION_NAME ) . '[max_tabs]" value="' . esc_attr( (string) ( $settings['max_tabs'] ?? 10 ) ) . '" class="small-text" />'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-setting-row">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_allow_shortcodes">' . esc_html__( 'Allow shortcodes', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<label class="kitgenix-toggle">'
			. '<input type="checkbox" id="kitgenix_custom_tabs_for_woocommerce_allow_shortcodes" class="kitgenix-toggle-input" name="' . esc_attr( Settings::OPTION_NAME ) . '[allow_shortcodes]" value="1" ' . checked( ! empty( $settings['allow_shortcodes'] ), true, false ) . ' />'
			. '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
			. '<span class="kitgenix-toggle-label">' . esc_html__( 'Process shortcodes in tab content on the frontend', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>'
			. '</label>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_default_position_preset">' . esc_html__( 'Default Tab Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '<p class="kitgenix-setting-row-desc">' . esc_html__( 'This value will be auto-filled into the tab position field when creating a new tab.', 'kitgenix-custom-tabs-for-woocommerce' ) . ' '
			. '<a href="' . esc_url( 'https://woocommerce.com/document/woocommerce-product-tabs/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more about arranging tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</p>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<select id="kitgenix_custom_tabs_for_woocommerce_default_position_preset" data-kitgenix-custom-tabs-for-woocommerce-position-preset="1">'
			. '<option value="before_description">' . esc_html__( 'Before description tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="between_description_additional">' . esc_html__( 'In between Description and Additional Information', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="between_additional_reviews">' . esc_html__( 'In between Additional Information and Reviews', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="after_all">' . esc_html__( 'After all default tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="custom">' . esc_html__( 'Custom', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '</select>'
			. '<p class="kitgenix-setting-row-desc">' . esc_html__( 'Enter a custom position value or choose one of the options above.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<input type="number" min="0" id="kitgenix_custom_tabs_for_woocommerce_priority_base" name="' . esc_attr( Settings::OPTION_NAME ) . '[priority_base]" value="' . esc_attr( (string) ( $settings['priority_base'] ?? 50 ) ) . '" class="small-text" />'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-setting-row">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_priority_step">' . esc_html__( 'Default priority step', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '<p class="kitgenix-setting-row-desc">' . esc_html__( 'Each new tab will use base + (index × step) if no priority is set.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<input type="number" min="1" id="kitgenix_custom_tabs_for_woocommerce_priority_step" name="' . esc_attr( Settings::OPTION_NAME ) . '[priority_step]" value="' . esc_attr( (string) ( $settings['priority_step'] ?? 10 ) ) . '" class="small-text" />'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-setting-row">'
			. '<div class="kitgenix-setting-row-label">'
			. '<label for="kitgenix_custom_tabs_for_woocommerce_hide_tab_heading">' . esc_html__( 'Hide Tab Heading Inside the Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '</div>'
			. '<div class="kitgenix-setting-row-control">'
			. '<label class="kitgenix-toggle">'
			. '<input type="checkbox" id="kitgenix_custom_tabs_for_woocommerce_hide_tab_heading" class="kitgenix-toggle-input" name="' . esc_attr( Settings::OPTION_NAME ) . '[hide_tab_heading]" value="1" ' . checked( ! empty( $settings['hide_tab_heading'] ), true, false ) . ' />'
			. '<span class="kitgenix-toggle-track"><span class="kitgenix-toggle-thumb"></span></span>'
			. '<span class="kitgenix-toggle-label">' . esc_html__( 'Yes', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>'
			. '</label>'
			. '</div>'
			. '</div>';

		echo '</div></div>'; // .kitgenix-settings-group, .kitgenix-card-body
		echo '</div>'; // .kitgenix-card

		echo '<div class="kitgenix-button-group">';
		submit_button( __( 'Save Settings', 'kitgenix-custom-tabs-for-woocommerce' ) );
		echo '</div>';
		echo '</form>';
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function render_global_tabs_tab( array $settings ): void {
		$max_tabs = Settings::template_library_max();
		$base     = isset( $settings['priority_base'] ) ? (int) $settings['priority_base'] : 50;
		$step     = isset( $settings['priority_step'] ) ? (int) $settings['priority_step'] : 10;
		$tabs     = ( isset( $settings['global_tabs'] ) && is_array( $settings['global_tabs'] ) ) ? $settings['global_tabs'] : [];
		$empty_message = __( 'No global tabs yet. Click Add Tab to create one.', 'kitgenix-custom-tabs-for-woocommerce' );

		echo '<form method="post" action="options.php" autocomplete="off" novalidate>';
		settings_fields( 'kitgenix_custom_tabs_for_woocommerce_settings_group' );
		wp_nonce_field( 'kitgenix_custom_tabs_for_woocommerce_settings_save', 'kitgenix_custom_tabs_for_woocommerce_settings_nonce' );

		echo '<div class="kitgenix-card">';

		echo '<div class="kitgenix-card-head">'
			. '<div class="kitgenix-card-head-main">'
			. '<span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'globe' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<div class="kitgenix-card-head-text">'
			. '<h2>' . esc_html__( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'These tabs are added to every product. Product-specific tabs (set in the product editor) are added in addition to these.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card-body">';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-manager kitgenix-custom-tabs-for-woocommerce-scope"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager="1"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager-type="global"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-base="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs]"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-max="' . esc_attr( (string) $max_tabs ) . '"'
			. self::get_templates_dataset_attribute( $settings ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped data-* attributes only.
			. ' data-kitgenix-custom-tabs-for-woocommerce-empty-message="' . esc_attr( $empty_message ) . '"'
			. '>';

		echo '<p class="kitgenix-custom-tabs-for-woocommerce-manager-actions kitgenix-button-group"><button type="button" class="button button-primary" data-kitgenix-custom-tabs-for-woocommerce-add="1">' . esc_html__( 'Add Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</button></p>';

		echo '<div class="kitgenix-search-bar"><div class="kitgenix-search-bar-input">'
			. '<span class="kitgenix-search-bar-icon" aria-hidden="true">' . self::icon( 'search' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<input type="search" placeholder="' . esc_attr__( 'Search global tabs…', 'kitgenix-custom-tabs-for-woocommerce' ) . '" data-kitgenix-table-search data-kitgenix-table-search-target="#kitgenix-ctw-global-tabs-table" />'
			. '</div></div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-table-wrap kitgenix-custom-tabs-for-woocommerce-manager-table kitgenix-table-wrap" id="kitgenix-ctw-global-tabs-table">'
			. '<table class="kitgenix-table" data-kitgenix-sortable-table="1">'
			. '<thead><tr>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="title"><span>' . esc_html__( 'Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="slug"><span>' . esc_html__( 'Slug', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="priority"><span>' . esc_html__( 'Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col">' . esc_html__( 'Scope', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Actions', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '</tr></thead>'
			. '<tbody data-kitgenix-custom-tabs-for-woocommerce-body="1">';

		$index = 0;
		$fields_html = '';
		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}
			$title    = isset( $tab['title'] ) ? (string) $tab['title'] : '';
			$nickname = isset( $tab['nickname'] ) ? (string) $tab['nickname'] : '';
			$slug     = isset( $tab['slug'] ) ? (string) $tab['slug'] : '';
			$priority = isset( $tab['priority'] ) ? (int) $tab['priority'] : Settings::compute_priority_for_index( $base, $step, $index );
			$content  = isset( $tab['content'] ) ? (string) $tab['content'] : '';
			$enabled  = ! array_key_exists( 'enabled', $tab ) || ! empty( $tab['enabled'] );
			$visibility = isset( $tab['visibility'] ) && is_array( $tab['visibility'] ) ? $tab['visibility'] : null;
			$target     = isset( $tab['target'] ) && is_array( $tab['target'] ) ? $tab['target'] : null;
			$hide_title = ! empty( $tab['hide_title'] );

			self::render_global_table_row( $index, $title, $nickname, $slug, $priority, $enabled, self::describe_target_scope( $target ) );
			ob_start();
			self::render_global_hidden_fields( $index, $title, $nickname, $slug, $priority, $content, $enabled, $visibility, $target, $hide_title );
			$fields_html .= (string) ob_get_clean();
			$index++;
		}
		if ( $index === 0 ) {
			echo '<tr class="kitgenix-custom-tabs-for-woocommerce-empty-row" data-kitgenix-custom-tabs-for-woocommerce-empty="1">'
				. '<td class="kitgenix-custom-tabs-for-woocommerce-empty-cell" colspan="5">'
				. self::render_empty_state_markup( $empty_message )  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_empty_state_markup() escapes its own dynamic content and returns pre-escaped markup.
				. '</td>'
				. '</tr>';
		}

		echo '</tbody></table></div>';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1" class="kitgenix-custom-tabs-for-woocommerce-tabs-fields-wrap">'
			. $fields_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. '</div>';

		echo '</div>'; // .kitgenix-custom-tabs-for-woocommerce-manager
		echo '</div>'; // .kitgenix-card-body
		echo '</div>'; // .kitgenix-card

		echo '<div class="kitgenix-button-group">';
		submit_button( __( 'Save Settings', 'kitgenix-custom-tabs-for-woocommerce' ) );
		echo '</div>';
		echo '</form>';
	}

	/**
	 * @param array<string,mixed>        $settings
	 * @param array<int,array<string,mixed>> $tabs Saved templates (from the dedicated templates option).
	 */
	private static function render_templates_tab( array $settings, array $tabs ): void {
		$max_tabs = Settings::template_library_max();
		$base     = isset( $settings['priority_base'] ) ? (int) $settings['priority_base'] : 50;
		$step     = isset( $settings['priority_step'] ) ? (int) $settings['priority_step'] : 10;
		$empty_message = __( 'No saved templates yet. Click Add template to create a reusable tab snippet.', 'kitgenix-custom-tabs-for-woocommerce' );

		echo '<form method="post" action="options.php" autocomplete="off" novalidate>';
		settings_fields( Settings::TEMPLATES_GROUP );
		wp_nonce_field( 'kitgenix_custom_tabs_for_woocommerce_templates_save', 'kitgenix_custom_tabs_for_woocommerce_templates_nonce' );

		echo '<div class="kitgenix-card">';

		echo '<div class="kitgenix-card-head">'
			. '<div class="kitgenix-card-head-main">'
			. '<span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'templates' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<div class="kitgenix-card-head-text">'
			. '<h2>' . esc_html__( 'Tab Templates', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Save reusable warranty text, sizing guides, ingredient lists, care instructions, and other snippets here. Product editors and global tabs can then insert these templates in one click and customize them per use.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card-body">';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-manager kitgenix-custom-tabs-for-woocommerce-scope"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager="1"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager-type="template"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-base="' . esc_attr( Settings::TEMPLATES_OPTION_NAME ) . '"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-max="' . esc_attr( (string) $max_tabs ) . '"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-empty-message="' . esc_attr( $empty_message ) . '"'
			. '>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-toolbar">'
			. '<p class="kitgenix-custom-tabs-for-woocommerce-manager-actions kitgenix-button-group"><button type="button" class="button button-primary" data-kitgenix-custom-tabs-for-woocommerce-add="1">' . esc_html__( 'Add template', 'kitgenix-custom-tabs-for-woocommerce' ) . '</button></p>'
			. '<p class="description kitgenix-custom-tabs-for-woocommerce-toolbar__hint">' . esc_html__( 'Templates are editable copies. Inserting one into a product or global tab does not change the saved template.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>';

		echo '<div class="kitgenix-search-bar"><div class="kitgenix-search-bar-input">'
			. '<span class="kitgenix-search-bar-icon" aria-hidden="true">' . self::icon( 'search' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<input type="search" placeholder="' . esc_attr__( 'Search templates…', 'kitgenix-custom-tabs-for-woocommerce' ) . '" data-kitgenix-table-search data-kitgenix-table-search-target="#kitgenix-ctw-templates-table" />'
			. '</div></div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-table-wrap kitgenix-custom-tabs-for-woocommerce-manager-table kitgenix-table-wrap" id="kitgenix-ctw-templates-table">'
			. '<table class="kitgenix-table" data-kitgenix-sortable-table="1">'
			. '<thead><tr>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="title"><span>' . esc_html__( 'Template', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="slug"><span>' . esc_html__( 'Slug', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col" class="kitgenix-table-sortable" data-kitgenix-sort-key="priority"><span>' . esc_html__( 'Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span></th>'
			. '<th scope="col">' . esc_html__( 'Actions', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '</tr></thead>'
			. '<tbody data-kitgenix-custom-tabs-for-woocommerce-body="1">';

		$index = 0;
		$fields_html = '';
		foreach ( $tabs as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}
			$title    = isset( $tab['title'] ) ? (string) $tab['title'] : '';
			$nickname = isset( $tab['nickname'] ) ? (string) $tab['nickname'] : '';
			$slug     = isset( $tab['slug'] ) ? (string) $tab['slug'] : '';
			$priority = isset( $tab['priority'] ) ? (int) $tab['priority'] : Settings::compute_priority_for_index( $base, $step, $index );
			$content  = isset( $tab['content'] ) ? (string) $tab['content'] : '';
			$enabled  = ! array_key_exists( 'enabled', $tab ) || ! empty( $tab['enabled'] );
			$hide_title = ! empty( $tab['hide_title'] );

			self::render_global_table_row( $index, $title, $nickname, $slug, $priority, $enabled );
			ob_start();
			self::render_template_hidden_fields( $index, $title, $nickname, $slug, $priority, $content, $enabled, $hide_title );
			$fields_html .= (string) ob_get_clean();
			$index++;
		}
		if ( $index === 0 ) {
			echo '<tr class="kitgenix-custom-tabs-for-woocommerce-empty-row" data-kitgenix-custom-tabs-for-woocommerce-empty="1">'
				. '<td class="kitgenix-custom-tabs-for-woocommerce-empty-cell" colspan="4">'
				. self::render_empty_state_markup( $empty_message )  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_empty_state_markup() escapes its own dynamic content and returns pre-escaped markup.
				. '</td>'
				. '</tr>';
		}

		echo '</tbody></table></div>';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1" class="kitgenix-custom-tabs-for-woocommerce-tabs-fields-wrap">'
			. $fields_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from render_template_hidden_fields() which escapes field values.
			. '</div>';

		echo '</div>'; // .kitgenix-custom-tabs-for-woocommerce-manager
		echo '</div>'; // .kitgenix-card-body
		echo '</div>'; // .kitgenix-card

		echo '<div class="kitgenix-button-group">';
		submit_button( __( 'Save Templates', 'kitgenix-custom-tabs-for-woocommerce' ) );
		echo '</div>';
		echo '</form>';
	}

	/**
	 * Renders a compact empty-state block for use inside a table cell.
	 */
	private static function render_empty_state_markup( string $message ): string {
		return '<div class="kitgenix-empty-state">'
			. '<span class="kitgenix-empty-state-icon" aria-hidden="true">' . self::icon( 'inbox' ) . '</span>'
			. '<p class="kitgenix-empty-state-desc">' . esc_html( $message ) . '</p>'
			. '</div>';
	}

	/**
	 * Small inline icon set used in card headers and empty states.
	 * Static, trusted markup – intentionally unescaped.
	 */
	private static function icon( string $name ): string {
		switch ( $name ) {
			case 'settings':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 12.75a2.75 2.75 0 1 0 0-5.5 2.75 2.75 0 0 0 0 5.5Z" stroke="currentColor" stroke-width="1.4"/><path d="M16.2 12.5a1.35 1.35 0 0 0 .27 1.49l.05.05a1.63 1.63 0 1 1-2.31 2.31l-.05-.05a1.35 1.35 0 0 0-1.49-.27 1.35 1.35 0 0 0-.82 1.24v.14a1.63 1.63 0 0 1-3.26 0v-.07a1.35 1.35 0 0 0-.88-1.24 1.35 1.35 0 0 0-1.49.27l-.05.05a1.63 1.63 0 1 1-2.31-2.31l.05-.05a1.35 1.35 0 0 0 .27-1.49 1.35 1.35 0 0 0-1.24-.82h-.14a1.63 1.63 0 0 1 0-3.26h.07a1.35 1.35 0 0 0 1.24-.88 1.35 1.35 0 0 0-.27-1.49l-.05-.05A1.63 1.63 0 1 1 5.9 3.19l.05.05a1.35 1.35 0 0 0 1.49.27H7.5a1.35 1.35 0 0 0 .82-1.24v-.14a1.63 1.63 0 0 1 3.26 0v.07a1.35 1.35 0 0 0 .82 1.24 1.35 1.35 0 0 0 1.49-.27l.05-.05a1.63 1.63 0 1 1 2.31 2.31l-.05.05a1.35 1.35 0 0 0-.27 1.49v.06a1.35 1.35 0 0 0 1.24.82h.14a1.63 1.63 0 0 1 0 3.26h-.07a1.35 1.35 0 0 0-1.24.82Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'globe':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="10" cy="10" r="7.25" stroke="currentColor" stroke-width="1.4"/><path d="M2.75 10h14.5M10 2.75c1.86 2.02 2.9 4.62 2.9 7.25S11.86 15.23 10 17.25C8.14 15.23 7.1 12.63 7.1 10S8.14 4.77 10 2.75Z" stroke="currentColor" stroke-width="1.4"/></svg>';
			case 'templates':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2.75" y="3.25" width="14.5" height="13.5" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M2.75 7.75h14.5M6.25 11.25h7.5M6.25 13.75h4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
			case 'heart':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 17s-6.5-3.98-6.5-8.6C3.5 5.6 5.44 3.75 7.75 3.75c1.28 0 2.42.63 3.25 1.6a4.36 4.36 0 0 1 3.25-1.6c2.31 0 4.25 1.85 4.25 4.65C18.5 13.02 10 17 10 17Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>';
			case 'log':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.25" y="2.75" width="13.5" height="14.5" rx="2" stroke="currentColor" stroke-width="1.4"/><path d="M6.5 6.75h7M6.5 10h7M6.5 13.25h4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
			case 'inbox':
				return '<svg width="22" height="22" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.25 10.5 4.9 4.34A1.5 1.5 0 0 1 6.35 3.25h7.3a1.5 1.5 0 0 1 1.45 1.09L16.75 10.5" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M3.25 10.5h4.14a.5.5 0 0 1 .46.3l.55 1.28a.5.5 0 0 0 .46.3h2.28a.5.5 0 0 0 .46-.3l.55-1.28a.5.5 0 0 1 .46-.3h4.14v4.25a1.5 1.5 0 0 1-1.5 1.5h-10a1.5 1.5 0 0 1-1.5-1.5V10.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>';
			case 'sync':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 10a6 6 0 0 1-10.6 3.8M4 10a6 6 0 0 1 10.6-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M4 13.8V17h3.2M16 6.2V3h-3.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'tools':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 4.5a3 3 0 0 0-3.9 3.9l-6.1 6.1 2 2 6.1-6.1a3 3 0 0 0 3.9-3.9l-2.1 2.1-2-2 2.1-2.1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>';
			case 'check':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 10.5 8 14.5 16 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'logs':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3.5" y="3" width="13" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 7h7M6.5 10h7M6.5 13h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
			case 'audit':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 3.5h7l3 3V16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7 9h6M7 12h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
			case 'chat':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.5 5.5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-3.5 3v-3a2 2 0 0 1-2-2v-6Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>';
			case 'star':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 3.2l2.02 4.1 4.53.66-3.28 3.2.78 4.52L10 13.5l-4.05 2.13.78-4.52-3.28-3.2 4.53-.66L10 3.2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>';
			case 'copy':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="7" y="7" width="9.5" height="9.5" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M13 7V4.5A1.5 1.5 0 0 0 11.5 3h-7A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13H7" stroke="currentColor" stroke-width="1.5"/></svg>';
			case 'users':
				return '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="7" r="2.5" stroke="currentColor" stroke-width="1.5"/><path d="M2.5 16c.5-3 2.2-4.5 4.5-4.5s4 1.5 4.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="14" cy="7.5" r="2" stroke="currentColor" stroke-width="1.5"/><path d="M12.5 11.3c1.8.2 3 1.5 3.5 4.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
			case 'search':
				return '<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M17 17l-3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
			case 'shield':
				return '<svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2.5l6 2.2v4.4c0 4.2-2.6 7.4-6 8.4-3.4-1-6-4.2-6-8.4V4.7l6-2.2Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M7.3 10.2l1.9 1.9 3.5-3.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'paypal':
				return '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.7 15.5H4.9a.3.3 0 0 1-.3-.35L6.5 3.8a.5.5 0 0 1 .5-.4h4.4c2 0 3.4 1.2 3.1 3.2-.3 2.4-2.1 3.7-4.3 3.7H8.6a.5.5 0 0 0-.5.4l-.7 3.9a.5.5 0 0 1-.5.4Z" fill="currentColor" opacity=".55"/><path d="M9.2 15.5H7.4a.3.3 0 0 1-.3-.35L9 3.8a.5.5 0 0 1 .5-.4h4.4c2 0 3.4 1.2 3.1 3.2-.3 2.4-2.1 3.7-4.3 3.7h-1.6a.5.5 0 0 0-.5.4l-.7 3.9a.5.5 0 0 1-.5.4Z" fill="currentColor"/></svg>';
			default:
				return '';
		}
	}

	/**
	 * Cheap, query-free summary of a global tab's targeting rules for the
	 * Global Tabs table's "Scope" column – computed entirely from the row's
	 * already-loaded target data, never from a live product-count query
	 * (which would turn viewing this list into a full product-table scan).
	 *
	 * @param array<string,mixed>|null $target
	 */
	private static function describe_target_scope( ?array $target ): string {
		if ( null === $target ) {
			return __( 'All products', 'kitgenix-custom-tabs-for-woocommerce' );
		}

		$labels = [
			'products'   => __( 'products', 'kitgenix-custom-tabs-for-woocommerce' ),
			'categories' => __( 'categories', 'kitgenix-custom-tabs-for-woocommerce' ),
			'tags'       => __( 'tags', 'kitgenix-custom-tabs-for-woocommerce' ),
			'types'      => __( 'types', 'kitgenix-custom-tabs-for-woocommerce' ),
		];

		$parts = [];
		foreach ( $labels as $dimension => $label ) {
			$dim = isset( $target[ $dimension ] ) && is_array( $target[ $dimension ] ) ? $target[ $dimension ] : [];
			$include_count = isset( $dim['include'] ) && is_array( $dim['include'] ) ? count( $dim['include'] ) : 0;
			$exclude_count = isset( $dim['exclude'] ) && is_array( $dim['exclude'] ) ? count( $dim['exclude'] ) : 0;

			if ( $exclude_count > 0 ) {
				/* translators: 1: count of excluded items, 2: dimension label (e.g. "categories") */
				$parts[] = sprintf( __( 'excl. %1$d %2$s', 'kitgenix-custom-tabs-for-woocommerce' ), $exclude_count, $label );
			}
			if ( $include_count > 0 ) {
				/* translators: 1: count of included items, 2: dimension label (e.g. "categories") */
				$parts[] = sprintf( __( '%1$d %2$s', 'kitgenix-custom-tabs-for-woocommerce' ), $include_count, $label );
			}
		}

		if ( empty( $parts ) ) {
			return __( 'All products', 'kitgenix-custom-tabs-for-woocommerce' );
		}

		return __( 'Targeted', 'kitgenix-custom-tabs-for-woocommerce' ) . ': ' . implode( ', ', $parts );
	}

	private static function render_global_table_row( int $index, string $title, string $nickname, string $slug, int $priority, bool $enabled = true, ?string $scope_label = null ): void {
		$display_title = $nickname !== '' ? $nickname : ( $title !== '' ? $title : __( 'Untitled', 'kitgenix-custom-tabs-for-woocommerce' ) );
		$subtitle      = ( $nickname !== '' && $title !== '' && $nickname !== $title ) ? $title : '';
		$display_slug  = $slug !== '' ? $slug : '–';
		$row_class     = $enabled ? '' : ' kitgenix-custom-tabs-for-woocommerce-row-disabled';

		echo '<tr data-kitgenix-custom-tabs-for-woocommerce-row="1" data-index="' . esc_attr( (string) $index ) . '" class="' . esc_attr( trim( $row_class ) ) . '">'
			. '<td>'
			. '<strong data-kitgenix-custom-tabs-for-woocommerce-row-title="1">' . esc_html( $display_title ) . '</strong>'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-tabs-subtitle" data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1">' . esc_html( $subtitle ) . '</div>'
			. ( $enabled ? '' : '<span class="kitgenix-badge neutral" data-kitgenix-custom-tabs-for-woocommerce-row-disabled-badge="1">' . esc_html__( 'Disabled', 'kitgenix-custom-tabs-for-woocommerce' ) . '</span>' )
			. '</td>'
			. '<td><span class="kitgenix-custom-tabs-for-woocommerce-code" data-kitgenix-custom-tabs-for-woocommerce-row-slug="1">' . esc_html( $display_slug ) . '</span></td>'
			. '<td><span data-kitgenix-custom-tabs-for-woocommerce-row-position="1">' . esc_html( (string) $priority ) . '</span></td>'
			. ( null !== $scope_label ? '<td><span class="kitgenix-badge ' . esc_attr( 'All products' === $scope_label ? 'neutral' : 'info' ) . '">' . esc_html( $scope_label ) . '</span></td>' : '' )
			. '<td class="kitgenix-custom-tabs-for-woocommerce-actions">'
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-up="1" aria-label="' . esc_attr__( 'Move up', 'kitgenix-custom-tabs-for-woocommerce' ) . '">&#8593;</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-move-down="1" aria-label="' . esc_attr__( 'Move down', 'kitgenix-custom-tabs-for-woocommerce' ) . '">&#8595;</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-edit="1">' . esc_html__( 'Edit', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a> '
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-toggle-enabled="1">' . ( $enabled ? esc_html__( 'Disable', 'kitgenix-custom-tabs-for-woocommerce' ) : esc_html__( 'Enable', 'kitgenix-custom-tabs-for-woocommerce' ) ) . '</a> '
			. '<a href="#" class="button button-link-delete" data-kitgenix-custom-tabs-for-woocommerce-remove="1">' . esc_html__( 'Remove', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</td>'
			. '</tr>';
	}

	/**
	 * @param array<string,mixed>|null $visibility
	 * @param array<string,mixed>|null $target
	 */
	private static function render_global_hidden_fields( int $index, string $title, string $nickname, string $slug, int $priority, string $content, bool $enabled = true, ?array $visibility = null, ?array $target = null, bool $hide_title = false ): void {
		$prefix       = esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . ']';
		$target_value = $target ?? Tab_Matcher::default_target();

		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[title]" value="' . esc_attr( $title ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[hide_title]" value="' . ( $hide_title ? '1' : '' ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[nickname]" value="' . esc_attr( $nickname ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[slug]" value="' . esc_attr( $slug ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[priority]" value="' . esc_attr( (string) $priority ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[enabled]" value="' . ( $enabled ? '1' : '0' ) . '" data-kitgenix-custom-tabs-for-woocommerce-enabled-field="1" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[visibility]" value="' . esc_attr( (string) wp_json_encode( $visibility ?? Tab_Matcher::default_visibility() ) ) . '" data-kitgenix-custom-tabs-for-woocommerce-visibility-field="1" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[target]" value="' . esc_attr( (string) wp_json_encode( $target_value ) ) . '" data-kitgenix-custom-tabs-for-woocommerce-target-field="1" data-kitgenix-custom-tabs-for-woocommerce-target-product-labels="' . esc_attr( (string) wp_json_encode( self::target_product_labels( $target_value ) ) ) . '" />'
			. '<textarea name="' . esc_attr( $prefix ) . '[content]" data-kitgenix-custom-tabs-for-woocommerce-content="1">' . esc_textarea( $content ) . '</textarea>'
			. '</div>';
	}

	/**
	 * Resolve {id: title} for every product ID referenced by a global tab's
	 * target rules, so the modal's AJAX-backed product-search field can show
	 * already-selected products without a lookup round trip when the row loads.
	 *
	 * @param array<string,mixed> $target
	 * @return array<string,string>
	 */
	private static function target_product_labels( array $target ): array {
		$ids = array_merge(
			(array) ( $target['products']['include'] ?? [] ),
			(array) ( $target['products']['exclude'] ?? [] )
		);
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$labels = [];
		foreach ( $ids as $id ) {
			$title = get_the_title( $id );
			if ( '' !== $title ) {
				$labels[ (string) $id ] = $title;
			}
		}

		return $labels;
	}

	private static function render_template_hidden_fields( int $index, string $title, string $nickname, string $slug, int $priority, string $content, bool $enabled = true, bool $hide_title = false ): void {
		$prefix = esc_attr( Settings::TEMPLATES_OPTION_NAME ) . '[' . esc_attr( (string) $index ) . ']';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[title]" value="' . esc_attr( $title ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[hide_title]" value="' . ( $hide_title ? '1' : '' ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[nickname]" value="' . esc_attr( $nickname ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[slug]" value="' . esc_attr( $slug ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[priority]" value="' . esc_attr( (string) $priority ) . '" />'
			. '<input type="hidden" name="' . esc_attr( $prefix ) . '[enabled]" value="' . ( $enabled ? '1' : '0' ) . '" data-kitgenix-custom-tabs-for-woocommerce-enabled-field="1" />'
			. '<textarea name="' . esc_attr( $prefix ) . '[content]" data-kitgenix-custom-tabs-for-woocommerce-content="1">' . esc_textarea( $content ) . '</textarea>'
			. '</div>';
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function get_templates_dataset_attribute( array $settings ): string {
		$templates = [];
		if ( isset( $settings['tab_templates'] ) && is_array( $settings['tab_templates'] ) ) {
			foreach ( $settings['tab_templates'] as $template ) {
				if ( ! is_array( $template ) ) {
					continue;
				}

				$title = isset( $template['title'] ) ? trim( (string) $template['title'] ) : '';
				if ( '' === $title ) {
					continue;
				}

				$templates[] = [
					'title'      => $title,
					'nickname'   => isset( $template['nickname'] ) ? (string) $template['nickname'] : '',
					'slug'       => isset( $template['slug'] ) ? (string) $template['slug'] : '',
					'priority'   => isset( $template['priority'] ) ? (int) $template['priority'] : 0,
					'content'    => isset( $template['content'] ) ? (string) $template['content'] : '',
					'hide_title' => empty( $template['hide_title'] ) ? 0 : 1,
					'label'      => isset( $template['nickname'] ) && (string) $template['nickname'] !== ''
						? (string) $template['nickname']
						: $title,
				];
			}
		}

		if ( empty( $templates ) ) {
			return '';
		}

		return ' data-kitgenix-custom-tabs-for-woocommerce-templates="' . esc_attr( wp_json_encode( $templates ) ) . '"';
	}

	private static function render_backbone_modal_template(): void {
		$position_help_url = esc_url( 'https://woocommerce.com/document/woocommerce-product-tabs/' );

		// Render as a persistent Kitgenix modal (shared kitgenix-modal-* library markup).
		?>
		<div id="kitgenix-custom-tabs-for-woocommerce-modal" class="kitgenix-modal" hidden role="dialog" aria-modal="true">
			<div class="kitgenix-modal-backdrop" data-kitgenix-modal-close="1"></div>
			<div class="kitgenix-modal-dialog kitgenix-modal-dialog-lg" role="document">
				<div class="kitgenix-modal-header">
					<h2 class="kitgenix-modal-title" data-kitgenix-custom-tabs-for-woocommerce-modal-title="1"><?php esc_html_e( 'Edit Tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h2>
					<button type="button" class="kitgenix-modal-close" data-kitgenix-modal-close="1" aria-label="<?php echo esc_attr__( 'Close', 'kitgenix-custom-tabs-for-woocommerce' ); ?>">&times;</button>
				</div>

				<div class="kitgenix-modal-body">
					<input type="hidden" data-kitgenix-custom-tabs-for-woocommerce-modal-index="1" value="" />
					<div class="kitgenix-custom-tabs-for-woocommerce-modal-scope">
						<div class="kitgenix-custom-tabs-for-woocommerce-modal-grid">
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_title"><?php esc_html_e( 'Tab title', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_title" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="title" value="" maxlength="50" placeholder="<?php echo esc_attr__( 'Title for tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
								<div class="kitgenix-custom-tabs-for-woocommerce-field__error" data-kitgenix-custom-tabs-for-woocommerce-error="title" aria-live="polite"></div>
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label class="kitgenix-custom-tabs-for-woocommerce-field__checkbox">
									<input type="checkbox" id="kitgenix_custom_tabs_for_woocommerce_modal_hide_title" data-kitgenix-custom-tabs-for-woocommerce-modal-field="hide_title" value="1" />
									<?php esc_html_e( 'Hide title inside tab content', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
								</label>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'The tab still shows this title in the tab bar; this only hides the heading repeated inside the panel.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></div>
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_priority"><?php esc_html_e( 'Tab position', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__inline">
									<input type="number" min="0" step="1" id="kitgenix_custom_tabs_for_woocommerce_modal_priority" class="small-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="priority" value="" />
									<a class="kitgenix-custom-tabs-for-woocommerce-modal__help-link" href="<?php echo esc_url( $position_help_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about positioning', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
								</div>
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field kitgenix-custom-tabs-for-woocommerce-is-full">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_editor"><?php esc_html_e( 'Tab content', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<div class="kitgenix-custom-tabs-for-woocommerce-editor-slot" data-kitgenix-custom-tabs-for-woocommerce-editor-slot="1"></div>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__error" data-kitgenix-custom-tabs-for-woocommerce-error="content" aria-live="polite"></div>
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_nickname"><?php esc_html_e( 'Tab nickname (optional)', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_nickname" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="nickname" value="" placeholder="<?php echo esc_attr__( 'Nickname for tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
							</div>
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_slug"><?php esc_html_e( 'Tab slug', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__inline">
									<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_slug" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="slug" value="" placeholder="<?php echo esc_attr__( 'Tab slug', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
									<a href="#" class="kitgenix-custom-tabs-for-woocommerce-modal__help-link" data-kitgenix-custom-tabs-for-woocommerce-slug-generate="1"><?php esc_html_e( 'Generate from title', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
								</div>
								<div class="kitgenix-custom-tabs-for-woocommerce-field__hint"><?php esc_html_e( 'Allowed: letters, numbers, and hyphens.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></div>
							</div>
							<?php
							// Both sections are always rendered; admin.js shows/hides them per
							// data-kitgenix-custom-tabs-for-woocommerce-manager-type (Global Tabs
							// gets both, Templates gets neither – a template is a reusable snippet,
							// not a live placement rule).
							self::render_visibility_modal_fields( 'kitgenix_custom_tabs_for_woocommerce_modal' );
							self::render_target_modal_fields();
							?>
						</div>
					</div>
				</div>

				<div class="kitgenix-modal-footer">
					<button type="button" class="button" data-kitgenix-custom-tabs-for-woocommerce-cancel="1" data-kitgenix-modal-close="1"><?php esc_html_e( 'Cancel', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
					<button type="button" class="button button-primary" data-kitgenix-custom-tabs-for-woocommerce-save="1"><?php esc_html_e( 'Done', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_editor_dock(): void {
		// No-op with Quill. Editor markup is created lazily in the modal slot by admin.js.
	}

	private static function render_portability_tab(): void {
		$admin_post_url = admin_url( 'admin-post.php' );
		$nonce_action    = Portability::get_nonce_action();

		Portability::render_notice();

		echo '<div class="kitgenix-card">';
		echo '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'templates' ) . '</span><div class="kitgenix-card-head-text">'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<h2>' . esc_html__( 'Export', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'JSON keeps everything, including visibility conditions and product targeting rules. CSV is a simple flattened format (title, nickname, slug, priority, enabled, content) for bulk editing in a spreadsheet.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div></div></div>';
		echo '<div class="kitgenix-card-body">';

		echo '<form method="post" action="' . esc_url( $admin_post_url ) . '" class="kitgenix-button-group" style="margin-bottom:12px;">';
		echo '<input type="hidden" name="action" value="' . esc_attr( Portability::get_export_action() ) . '" />';
		wp_nonce_field( $nonce_action );
		submit_button( __( 'Download full JSON export', 'kitgenix-custom-tabs-for-woocommerce' ), 'primary', 'submit', false );
		echo '</form>';

		foreach ( [ 'global_tabs' => __( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ), 'templates' => __( 'Templates', 'kitgenix-custom-tabs-for-woocommerce' ) ] as $which => $label ) {
			echo '<form method="get" action="' . esc_url( $admin_post_url ) . '" class="kitgenix-button-group" style="margin-bottom:6px;">';
			echo '<input type="hidden" name="action" value="' . esc_attr( Portability::get_csv_export_action() ) . '" />';
			echo '<input type="hidden" name="which" value="' . esc_attr( $which ) . '" />';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( wp_create_nonce( $nonce_action ) ) . '" />';
			/* translators: %s: "Global Tabs" or "Templates" */
			submit_button( sprintf( __( 'Download %s CSV', 'kitgenix-custom-tabs-for-woocommerce' ), $label ), 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '</div></div>';

		echo '<div class="kitgenix-card">';
		echo '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'templates' ) . '</span><div class="kitgenix-card-head-text">'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<h2>' . esc_html__( 'Import JSON', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Import a JSON export from this plugin. Choose a file to preview what it contains before importing.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div></div></div>';
		echo '<div class="kitgenix-card-body">';
		echo '<form method="post" action="' . esc_url( $admin_post_url ) . '" enctype="multipart/form-data">';
		echo '<input type="hidden" name="action" value="' . esc_attr( Portability::get_import_action() ) . '" />';
		wp_nonce_field( $nonce_action );
		echo '<div class="kitgenix-settings-group">';
		echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked"><div class="kitgenix-setting-row-label"><label for="kitgenix_ctw_import_json_file">' . esc_html__( 'JSON file', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></div>'
			. '<div class="kitgenix-setting-row-control"><input type="file" id="kitgenix_ctw_import_json_file" name="kitgenix_custom_tabs_for_woocommerce_import_file" accept="application/json,.json" required />'
			. '<div id="kitgenix-ctw-import-json-preview" hidden></div></div></div>';
		echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked"><div class="kitgenix-setting-row-label">' . esc_html__( 'Import mode', 'kitgenix-custom-tabs-for-woocommerce' ) . '</div>'
			. '<div class="kitgenix-setting-row-control"><fieldset>'
			. '<label class="kitgenix-custom-tabs-for-woocommerce-radio-inline"><input type="radio" name="kitgenix_custom_tabs_for_woocommerce_import_mode" value="replace" checked /> ' . esc_html__( 'Replace current settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '<label><input type="radio" name="kitgenix_custom_tabs_for_woocommerce_import_mode" value="merge" /> ' . esc_html__( 'Merge into current settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '</fieldset></div></div>';
		echo '</div>';
		submit_button( __( 'Import JSON', 'kitgenix-custom-tabs-for-woocommerce' ), 'primary', 'submit', false, [ 'id' => 'kitgenix-ctw-import-json-submit' ] );
		echo '</form>';
		echo '</div></div>';

		echo '<div class="kitgenix-card">';
		echo '<div class="kitgenix-card-head"><div class="kitgenix-card-head-main"><span class="kitgenix-card-icon" aria-hidden="true">' . self::icon( 'templates' ) . '</span><div class="kitgenix-card-head-text">'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
			. '<h2>' . esc_html__( 'Import CSV', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Import a CSV file (matching the export column headers) into Global Tabs or Templates. Visibility and targeting rules aren\'t part of CSV and are left untouched on any matching existing rows.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div></div></div>';
		echo '<div class="kitgenix-card-body">';
		echo '<form method="post" action="' . esc_url( $admin_post_url ) . '" enctype="multipart/form-data">';
		echo '<input type="hidden" name="action" value="' . esc_attr( Portability::get_csv_import_action() ) . '" />';
		wp_nonce_field( $nonce_action );
		echo '<div class="kitgenix-settings-group">';
		echo '<div class="kitgenix-setting-row"><div class="kitgenix-setting-row-label"><label for="kitgenix_ctw_import_csv_which">' . esc_html__( 'Import into', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></div>'
			. '<div class="kitgenix-setting-row-control"><select id="kitgenix_ctw_import_csv_which" name="which"><option value="global_tabs">' . esc_html__( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option><option value="templates">' . esc_html__( 'Templates', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option></select></div></div>';
		echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked"><div class="kitgenix-setting-row-label"><label for="kitgenix_ctw_import_csv_file">' . esc_html__( 'CSV file', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></div>'
			. '<div class="kitgenix-setting-row-control"><input type="file" id="kitgenix_ctw_import_csv_file" name="kitgenix_custom_tabs_for_woocommerce_import_csv_file" accept="text/csv,.csv" required /></div></div>';
		echo '<div class="kitgenix-setting-row kitgenix-setting-row-stacked"><div class="kitgenix-setting-row-label">' . esc_html__( 'Import mode', 'kitgenix-custom-tabs-for-woocommerce' ) . '</div>'
			. '<div class="kitgenix-setting-row-control"><fieldset>'
			. '<label class="kitgenix-custom-tabs-for-woocommerce-radio-inline"><input type="radio" name="kitgenix_custom_tabs_for_woocommerce_import_mode" value="replace" checked /> ' . esc_html__( 'Replace', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '<label><input type="radio" name="kitgenix_custom_tabs_for_woocommerce_import_mode" value="merge" /> ' . esc_html__( 'Merge', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label>'
			. '</fieldset></div></div>';
		echo '</div>';
		submit_button( __( 'Import CSV', 'kitgenix-custom-tabs-for-woocommerce' ), 'secondary', 'submit', false );
		echo '</form>';
		echo '</div></div>';
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function render_support_tab( array $settings ): void {
		unset( $settings );

		$donate_url      = 'https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2';
		$plugin_page_url = 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/';
		$docs_url        = 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/documentation/';
		$review_url      = 'https://wordpress.org/support/plugin/kitgenix-custom-tabs-for-woocommerce/reviews/#new-post';
		$copy_onclick    = "if(window.navigator&&navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(" . wp_json_encode( $plugin_page_url ) . ");}else{window.prompt(" . wp_json_encode( __( 'Copy plugin link:', 'kitgenix-custom-tabs-for-woocommerce' ) ) . ", " . wp_json_encode( $plugin_page_url ) . ");}return false;";

		?>
		<div class="kitgenix-custom-tabs-for-woocommerce-support-page kitgenix-support-page">
			<div class="kitgenix-card kitgenix-support-hero">
				<div class="kitgenix-support-hero-inner">
					<span class="kitgenix-support-hero-icon" aria-hidden="true"><?php echo self::icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?></span>
					<p class="kitgenix-support-hero-eyebrow"><?php echo esc_html__( 'Support Kitgenix', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					<h2><?php echo esc_html__( 'Help us keep building', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h2>
					<p class="kitgenix-support-hero-body"><?php echo esc_html__( 'Custom Tabs For WooCommerce adds unlimited per-product and global tabs to your product pages, with drag-to-reorder ordering and no subscription or locked-away Pro version. It is built and maintained by one person in their own time, and donations are what keep it supported through every new WooCommerce release.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					<a class="kitgenix-support-hero-button" href="<?php echo esc_url( $donate_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo self::icon( 'paypal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?>
						<?php echo esc_html__( 'Donate with PayPal', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
					</a>
					<p class="kitgenix-support-hero-caption">
						<?php echo self::icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?>
						<?php echo esc_html__( 'Donations are entirely optional. Kitgenix plugins keep working whether you donate or not.', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
					</p>
				</div>

				<div class="kitgenix-support-links">
					<a class="kitgenix-support-link" href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo self::icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?>
						<?php echo esc_html__( 'Leave a review', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
					</a>
					<a class="kitgenix-support-link" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo self::icon( 'logs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?>
						<?php echo esc_html__( 'Read the docs', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
					</a>
					<button type="button" class="kitgenix-support-link" onclick="<?php echo esc_attr( $copy_onclick ); ?>">
						<?php echo self::icon( 'copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG. ?>
						<?php echo esc_html__( 'Copy plugin link', 'kitgenix-custom-tabs-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_log_tab(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['kitgenix_log_cleared'] ) ) {
			echo '<div class="kitgenix-notice kitgenix-notice-success" role="status">'
				. '<span class="kitgenix-notice-icon" aria-hidden="true">' . self::icon( 'inbox' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
				. '<div class="kitgenix-notice-body"><p class="kitgenix-notice-text">' . esc_html__( 'Event log cleared.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p></div>'
				. '<button type="button" class="kitgenix-notice-close" aria-label="' . esc_attr__( 'Dismiss', 'kitgenix-custom-tabs-for-woocommerce' ) . '">&times;</button>'
				. '</div>';
		}

		$clear_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=kitgenix_custom_tabs_for_woocommerce_clear_event_log' ),
			'kitgenix_custom_tabs_for_woocommerce_clear_event_log'
		);

		$log_entries = Event_Log::get_raw_log();

		echo '<div class="kitgenix-card">';

		echo '<div class="kitgenix-card-head">'
			. '<div>'
			. '<h2 class="kitgenix-support-subheading">' . esc_html__( 'Activity Log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p class="description">' . esc_html__( 'A record of recent plugin events. Entries show the timestamp, context, outcome, and a plain-English note. IP addresses and sensitive data are never stored here.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>'
			. '<div class="kitgenix-card-head-actions">'
			. '<a href="' . esc_url( $clear_url ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Clear all log entries?', 'kitgenix-custom-tabs-for-woocommerce' ) ) . '\')">' . esc_html__( 'Clear log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-card-body">';

		if ( empty( $log_entries ) ) {
			echo '<div class="kitgenix-empty-state">'
				. '<span class="kitgenix-empty-state-icon" aria-hidden="true">' . self::icon( 'inbox' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
				. '<h3 class="kitgenix-empty-state-title">' . esc_html__( 'No events logged yet', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h3>'
				. '<p class="kitgenix-empty-state-desc">' . esc_html__( 'Activity will appear here as it happens.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
				. '</div>';
		} else {
			echo '<div class="kitgenix-search-bar">'
				. '<div class="kitgenix-search-bar-input">'
				. '<span class="kitgenix-search-bar-icon" aria-hidden="true">' . self::icon( 'search' ) . '</span>'  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped, static trusted SVG markup from self::icon().
				. '<input type="search" placeholder="' . esc_attr__( 'Search log…', 'kitgenix-custom-tabs-for-woocommerce' ) . '" data-kitgenix-table-search data-kitgenix-table-search-target="#kctfw-event-log-table" />'
				. '</div>'
				. '</div>';

			echo '<div class="kitgenix-table-wrap" id="kctfw-event-log-table" data-kitgenix-table-paginate="25">';
			echo '<table class="kitgenix-table">';
			echo '<thead><tr>'
				. '<th>' . esc_html__( 'Time', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
				. '<th>' . esc_html__( 'Context', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
				. '<th>' . esc_html__( 'Outcome', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
				. '<th>' . esc_html__( 'Note', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
				. '</tr></thead>';
			echo '<tbody>';

			$format = (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' );

			foreach ( array_reverse( $log_entries ) as $entry ) {
				$time = isset( $entry['time'] ) ? (int) $entry['time'] : 0;
				if ( function_exists( 'wp_date' ) ) {
					$when = $time ? (string) wp_date( $format, $time ) : '';
				} else {
					$when = $time ? (string) date_i18n( $format, $time ) : '';
				}
				$when = $when !== '' ? $when : __( 'Unknown time', 'kitgenix-custom-tabs-for-woocommerce' );

				$context = (string) ( $entry['context'] ?? '' );
				$outcome = (string) ( $entry['outcome'] ?? '' );
				$note    = (string) ( $entry['note'] ?? '' );

				$outcome_l = strtolower( $outcome );
				if ( in_array( $outcome_l, [ 'success', 'ok' ], true ) ) {
					$badge_class = 'success';
				} elseif ( in_array( $outcome_l, [ 'error', 'fail', 'failed', 'failure' ], true ) ) {
					$badge_class = 'danger';
				} else {
					$badge_class = 'neutral';
				}

				echo '<tr data-kitgenix-table-row>'
					. '<td>' . esc_html( $when ) . '</td>'
					. '<td><code>' . esc_html( $context ) . '</code></td>'
					. '<td><span class="kitgenix-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $outcome ) . '</span></td>'
					. '<td>' . esc_html( $note ) . '</td>'
					. '</tr>';
			}

			echo '</tbody>';
			echo '</table>';
			echo '<div class="kitgenix-empty-state" data-kitgenix-table-empty style="display:none;">'
				. '<p class="kitgenix-empty-state-title">' . esc_html__( 'No matching log entries', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
				. '</div>';
			echo '</div>';
		}

		echo '<div class="kitgenix-table-wrap"><table class="kitgenix-table">';
		echo '<thead><tr>'
			. '<th>' . esc_html__( 'Category', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th>' . esc_html__( 'What it means', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th>' . esc_html__( 'Action needed?', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '</tr></thead>';
		echo '<tbody>';

		echo '<tr>'
			. '<td><code>saved</code></td>'
			. '<td>' . esc_html__( 'Plugin settings were saved successfully.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'None.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>disabled</code></td>'
			. '<td>' . esc_html__( 'Custom Tabs was turned off in Settings, so a product save was skipped and existing tabs were left untouched.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'None, unless you expected tabs to save – check that Custom Tabs is enabled in Settings.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>not-applicable</code></td>'
			. '<td>' . esc_html__( 'A saved post was not a product, so no tab data applied.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'None – this is expected on non-product screens.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>permission-denied</code></td>'
			. '<td>' . esc_html__( 'The user saving the product did not have permission to edit it, so the tab save was blocked.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'Genuine issue if it repeats for a user who should have access – check their role and capabilities.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>cached-or-expired-page</code></td>'
			. '<td>' . esc_html__( 'The security (nonce) token was missing or stale. Most common on a cached or long-open product edit page.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'Usually a false positive – NOT necessarily a problem. Reload the product edit page and save again. If frequent, exclude wp-admin from page caching.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>save-error</code></td>'
			. '<td>' . esc_html__( 'The submitted tab data was not in the expected format, so nothing was saved.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'If this repeats, check for a plugin or theme conflict on the product edit screen.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';
		echo '<tr>'
			. '<td><code>auto-corrected</code></td>'
			. '<td>' . esc_html__( 'A settings value was outside its allowed range and was automatically adjusted back into range.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '<td>' . esc_html__( 'None – this is a safeguard, not an error.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</td>'
			. '</tr>';

		echo '</tbody>';
		echo '</table></div>';

		echo '</div>';

		echo '</div>';
	}

	public static function handle_clear_event_log(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'kitgenix-custom-tabs-for-woocommerce' ), 403 );
		}
		check_admin_referer( 'kitgenix_custom_tabs_for_woocommerce_clear_event_log' );
		Event_Log::clear();
		wp_safe_redirect( admin_url( 'admin.php?page=kitgenix-custom-tabs-for-woocommerce&tab=log&kitgenix_log_cleared=1' ) );
		exit;
	}

	public static function on_settings_saved( mixed $old_value, mixed $new_value, string $option ): void {
		Event_Log::record( 'settings-saved', 'success', __( 'Plugin settings were saved via the admin settings page.', 'kitgenix-custom-tabs-for-woocommerce' ), 'settings-saved' );
	}

}

