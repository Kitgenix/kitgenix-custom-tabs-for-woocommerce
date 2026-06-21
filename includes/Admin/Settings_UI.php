<?php

namespace KitgenixCustomTabsForWooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Core\Event_Log;
use KitgenixCustomTabsForWooCommerce\Core\Settings;

final class Settings_UI {
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

		wp_enqueue_style( 'kitgenix-admin-ui' );

		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-admin',
			plugins_url( 'assets/css/admin.css', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[ 'kitgenix-admin-ui' ],
			$admin_css_ver
		);

		wp_enqueue_style(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css',
			[],
			'1.3.7'
		);

		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-quill',
			'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js',
			[],
			'1.3.7',
			true
		);

		if ( class_exists( 'WooCommerce' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}

		wp_enqueue_script(
			'kitgenix-custom-tabs-for-woocommerce-admin',
			plugins_url( 'assets/js/admin.js', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ),
			[ 'kitgenix-custom-tabs-for-woocommerce-quill' ],
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
			if ( in_array( $maybe_tab, [ 'settings', 'templates', 'global-tabs', 'support', 'log' ], true ) ) {
				$default_tab = $maybe_tab;
			}
		}

		$logo = plugins_url( 'assets/images/logos/kitgenix-favicon-purple.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );
		$social_base = plugins_url( 'assets/images/social-media/', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );
		$base_tab_url = admin_url( 'admin.php?page=' . rawurlencode( 'kitgenix-custom-tabs-for-woocommerce' ) );

		echo '<div class="wrap kitgenix-admin-app kitgenix-custom-tabs-for-woocommerce-use-top-tabs kitgenix-custom-tabs-for-woocommerce-scope" data-kitgenix-tabs data-kitgenix-default-tab="' . esc_attr( $default_tab ) . '" id="kitgenix-custom-tabs-for-woocommerce-admin-app">';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-settings-intro kitgenix-settings-header">'
			. '<div class="kitgenix-settings-header-row">'
			. '<div class="kitgenix-settings-header-main">'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-settings-brand kitgenix-settings-brand">'
			. '<img class="kitgenix-custom-tabs-for-woocommerce-settings-logo kitgenix-settings-logo" src="' . esc_url( $logo ) . '" alt="' . esc_attr__( 'Kitgenix', 'kitgenix-custom-tabs-for-woocommerce' ) . '" />'
			. '<h1 class="kitgenix-custom-tabs-for-woocommerce-admin-title">' . esc_html__( 'Kitgenix Custom Tabs for WooCommerce', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h1>'
			. '</div>'
			. '<p>' . esc_html__( 'Add product-specific custom tabs from the WooCommerce product editor. Built to stay fast and modular as we add more features.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-settings-meta kitgenix-settings-meta">'
			. '<span class="kitgenix-custom-tabs-for-woocommerce-settings-version kitgenix-settings-version" aria-label="Plugin version">v' . esc_html( $ver ) . '</span>'
			. '</div>'
			. '</div>'
			. '<div class="kitgenix-settings-header-actions">'
			. '<div class="kitgenix-intro-links kitgenix-custom-tabs-for-woocommerce-intro-links">'
			. '<a href="' . esc_url( 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a href="' . esc_url( 'https://wordpress.org/support/plugin/kitgenix-custom-tabs-for-woocommerce/reviews/#new-post' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Review Plugin', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a href="' . esc_url( 'https://wordpress.org/support/plugin/kitgenix-custom-tabs-for-woocommerce/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support Request', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a href="' . esc_url( 'https://buymeacoffee.com/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support Kitgenix', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. ( $social_base ? '<div class="kitgenix-social-links kitgenix-social-links--icons">'
				. '<a href="https://kitgenix.com" target="_blank" rel="noopener noreferrer" aria-label="Website" title="Website"><img src="' . esc_url( plugins_url( 'assets/images/social-media/globe-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Website</span></a>'
				. '<a href="https://www.facebook.com/groups/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook Community" title="Facebook Community"><img src="' . esc_url( plugins_url( 'assets/images/social-media/facebook-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Facebook Community</span></a>'
				. '<a href="https://www.facebook.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><img src="' . esc_url( plugins_url( 'assets/images/social-media/facebook-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Facebook</span></a>'
				. '<a href="https://www.instagram.com/kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Instagram" title="Instagram"><img src="' . esc_url( plugins_url( 'assets/images/social-media/instagram-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Instagram</span></a>'
				. '<a href="https://www.youtube.com/@Kitgenix" target="_blank" rel="noopener noreferrer" aria-label="YouTube" title="YouTube"><img src="' . esc_url( plugins_url( 'assets/images/social-media/youtube-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">YouTube</span></a>'
				. '<a href="https://www.reddit.com/r/Kitgenix/" target="_blank" rel="noopener noreferrer" aria-label="Reddit" title="Reddit"><img src="' . esc_url( plugins_url( 'assets/images/social-media/reddit-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">Reddit</span></a>'
				. '<a href="https://www.linkedin.com/company/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" title="LinkedIn"><img src="' . esc_url( plugins_url( 'assets/images/social-media/linkedin-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">LinkedIn</span></a>'
				. '<a href="https://x.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><img src="' . esc_url( plugins_url( 'assets/images/social-media/x-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">X</span></a>'
				. '<a href="https://www.tiktok.com/@kitgenix" target="_blank" rel="noopener noreferrer" aria-label="TikTok" title="TikTok"><img src="' . esc_url( plugins_url( 'assets/images/social-media/tiktok-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">TikTok</span></a>'
				. '<a href="https://github.com/kitgenix" target="_blank" rel="noopener noreferrer" aria-label="GitHub" title="GitHub"><img src="' . esc_url( plugins_url( 'assets/images/social-media/github-solid.svg', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE ) ) . '" alt="" width="13" height="13" aria-hidden="true" /><span class="screen-reader-text">GitHub</span></a>'
				. '</div>' : '' )
			. '</div>'
			. '</div>'
			. '</div>';

		echo '<h2 class="nav-tab-wrapper kitgenix-nav-tabs" aria-label="Settings navigation">'
			. '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'settings' ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=settings#kitgenix-tab-settings' ) . '" data-kitgenix-tab="settings">' . esc_html__( 'Settings', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'templates' ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=templates#kitgenix-tab-templates' ) . '" data-kitgenix-tab="templates">' . esc_html__( 'Templates', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'global-tabs' ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=global-tabs#kitgenix-tab-global-tabs' ) . '" data-kitgenix-tab="global-tabs">' . esc_html__( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'support' ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=support#kitgenix-tab-support' ) . '" data-kitgenix-tab="support">' . esc_html__( 'Support', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="nav-tab kitgenix-tab-trigger ' . ( $default_tab === 'log' ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $base_tab_url . '&tab=log#kitgenix-tab-log' ) . '" data-kitgenix-tab="log">' . esc_html__( 'Log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</h2>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-settings-layout kitgenix-settings-layout"><div class="kitgenix-custom-tabs-for-woocommerce-settings-content kitgenix-settings-content" id="kitgenix-custom-tabs-for-woocommerce-settings-content" tabindex="-1">';

		echo '<div id="kitgenix-tab-settings" data-kitgenix-tab-panel="settings"' . ( $default_tab !== 'settings' ? ' hidden="hidden"' : '' ) . '>';
		self::render_settings_tab( $settings );
		echo '</div>';

		echo '<div id="kitgenix-tab-templates" data-kitgenix-tab-panel="templates"' . ( $default_tab !== 'templates' ? ' hidden="hidden"' : '' ) . '>';
		self::render_templates_tab( $settings );
		echo '</div>';

		echo '<div id="kitgenix-tab-global-tabs" data-kitgenix-tab-panel="global-tabs"' . ( $default_tab !== 'global-tabs' ? ' hidden="hidden"' : '' ) . '>';
		self::render_global_tabs_tab( $settings );
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
	}

	private static function render_sidebar(): void {
		$social_base = plugins_url( 'assets/images/social-media/', KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_FILE );

		echo '<aside class="kitgenix-settings-sidebar" aria-label="' . esc_attr__( 'Help and links', 'kitgenix-custom-tabs-for-woocommerce' ) . '">';

		echo '<div class="kitgenix-sidebar-card">'
			. '<h2>' . esc_html__( 'Need Help?', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Open the documentation for setup guidance or send us a support request if you need help configuring the plugin.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-sidebar-actions">'
			. '<a class="button button-secondary" href="' . esc_url( 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/documentation/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '<a class="button button-primary" href="' . esc_url( 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/support' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Request Support', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-sidebar-card">'
			. '<h2>' . esc_html__( 'Visit Our Official Facebook Group', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'Join the Kitgenix community to ask questions, share feedback, and keep up with product updates.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<div class="kitgenix-sidebar-actions">'
			. '<a class="button button-secondary" href="' . esc_url( 'https://www.facebook.com/groups/kitgenix' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Join Group', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</div>'
			. '</div>';

		echo '<div class="kitgenix-sidebar-card">'
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

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-card">'
			. '<h3>' . esc_html__( 'General', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h3>'
			. '<table class="form-table">'
			. '<tr>'
			. '<th scope="row">' . esc_html__( 'Enable custom tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<td><label><input type="checkbox" name="' . esc_attr( Settings::OPTION_NAME ) . '[enabled]" value="1" ' . checked( ! empty( $settings['enabled'] ), true, false ) . ' /> '
			. esc_html__( 'Enable the custom tabs feature', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></td>'
			. '</tr>'
			. '<tr>'
			. '<th scope="row"><label for="kitgenix_custom_tabs_for_woocommerce_max_tabs">' . esc_html__( 'Max tabs per product', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></th>'
			. '<td><input type="number" min="1" max="50" id="kitgenix_custom_tabs_for_woocommerce_max_tabs" name="' . esc_attr( Settings::OPTION_NAME ) . '[max_tabs]" value="' . esc_attr( (string) ( $settings['max_tabs'] ?? 10 ) ) . '" class="small-text" />'
			. '<p class="description">' . esc_html__( 'Keeps the product editor fast and prevents accidental huge tab lists.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p></td>'
			. '</tr>'
			. '<tr>'
			. '<th scope="row">' . esc_html__( 'Allow shortcodes', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<td><label><input type="checkbox" name="' . esc_attr( Settings::OPTION_NAME ) . '[allow_shortcodes]" value="1" ' . checked( ! empty( $settings['allow_shortcodes'] ), true, false ) . ' /> '
			. esc_html__( 'Process shortcodes in tab content on the frontend', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></td>'
			. '</tr>'
			. '<tr>'
			. '<th scope="row"><label for="kitgenix_custom_tabs_for_woocommerce_default_position_preset">' . esc_html__( 'Default Tab Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></th>'
			. '<td>'
			. '<p class="description">' . esc_html__( 'This value will be auto-filled into the tab position field when creating a new tab.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<p><a href="' . esc_url( 'https://woocommerce.com/document/woocommerce-product-tabs/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more about arranging tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a></p>'
			. '<p><select id="kitgenix_custom_tabs_for_woocommerce_default_position_preset" data-kitgenix-custom-tabs-for-woocommerce-position-preset="1">'
			. '<option value="before_description">' . esc_html__( 'Before description tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="between_description_additional">' . esc_html__( 'In between Description and Additional Information', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="between_additional_reviews">' . esc_html__( 'In between Additional Information and Reviews', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="after_all">' . esc_html__( 'After all default tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '<option value="custom">' . esc_html__( 'Custom', 'kitgenix-custom-tabs-for-woocommerce' ) . '</option>'
			. '</select></p>'
			. '<p class="description">' . esc_html__( 'Enter a custom position value or choose one of the options above.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '<p><input type="number" min="0" id="kitgenix_custom_tabs_for_woocommerce_priority_base" name="' . esc_attr( Settings::OPTION_NAME ) . '[priority_base]" value="' . esc_attr( (string) ( $settings['priority_base'] ?? 50 ) ) . '" class="small-text" /></p>'
			. '</td>'
			. '</tr>'
			. '<tr>'
			. '<th scope="row"><label for="kitgenix_custom_tabs_for_woocommerce_priority_step">' . esc_html__( 'Default priority step', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></th>'
			. '<td><input type="number" min="1" id="kitgenix_custom_tabs_for_woocommerce_priority_step" name="' . esc_attr( Settings::OPTION_NAME ) . '[priority_step]" value="' . esc_attr( (string) ( $settings['priority_step'] ?? 10 ) ) . '" class="small-text" />'
			. '<p class="description">' . esc_html__( 'Each new tab will use base + (index × step) if no priority is set.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p></td>'
			. '</tr>'
			. '<tr>'
			. '<th scope="row">' . esc_html__( 'Hide Tab Heading Inside the Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<td><label><input type="checkbox" name="' . esc_attr( Settings::OPTION_NAME ) . '[hide_tab_heading]" value="1" ' . checked( ! empty( $settings['hide_tab_heading'] ), true, false ) . ' /> '
			. esc_html__( 'Yes', 'kitgenix-custom-tabs-for-woocommerce' ) . '</label></td>'
			. '</tr>'
			. '</table>'
			. '</div>';

		submit_button( __( 'Save Settings', 'kitgenix-custom-tabs-for-woocommerce' ) );
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

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-card">'
			. '<h3>' . esc_html__( 'Global Tabs', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h3>'
			. '<p class="description">' . esc_html__( 'These tabs are added to every product. Product-specific tabs (set in the product editor) are added in addition to these.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-manager kitgenix-custom-tabs-for-woocommerce-scope"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager="1"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-base="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs]"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-max="' . esc_attr( (string) $max_tabs ) . '"'
			. self::get_templates_dataset_attribute( $settings ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped data-* attributes only.
			. ' data-kitgenix-custom-tabs-for-woocommerce-empty-message="' . esc_attr( $empty_message ) . '"'
			. '>';

		echo '<p class="kitgenix-custom-tabs-for-woocommerce-manager-actions"><button type="button" class="button" data-kitgenix-custom-tabs-for-woocommerce-add="1">' . esc_html__( 'Add Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</button></p>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-table-wrap kitgenix-custom-tabs-for-woocommerce-manager-table">'
			. '<table class="widefat striped">'
			. '<thead><tr>'
			. '<th scope="col">' . esc_html__( 'Tab', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Slug', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
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

			self::render_global_table_row( $index, $title, $nickname, $slug, $priority );
			ob_start();
			self::render_global_hidden_fields( $index, $title, $nickname, $slug, $priority, $content );
			$fields_html .= (string) ob_get_clean();
			$index++;
		}
		if ( $index === 0 ) {
			echo '<tr class="kitgenix-custom-tabs-for-woocommerce-empty-row" data-kitgenix-custom-tabs-for-woocommerce-empty="1">'
				. '<td class="kitgenix-custom-tabs-for-woocommerce-empty-cell" colspan="4">'
				. '<span class="description">' . esc_html( $empty_message ) . '</span>'
				. '</td>'
				. '</tr>';
		}

		echo '</tbody></table></div>';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1" class="kitgenix-custom-tabs-for-woocommerce-tabs-fields-wrap">'
			. $fields_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. '</div>';

		echo '</div>';
		echo '</div>';

		submit_button( __( 'Save Settings', 'kitgenix-custom-tabs-for-woocommerce' ) );
		echo '</form>';
	}

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function render_templates_tab( array $settings ): void {
		$max_tabs = isset( $settings['max_tabs'] ) ? (int) $settings['max_tabs'] : 10;
		$base     = isset( $settings['priority_base'] ) ? (int) $settings['priority_base'] : 50;
		$step     = isset( $settings['priority_step'] ) ? (int) $settings['priority_step'] : 10;
		$tabs     = ( isset( $settings['tab_templates'] ) && is_array( $settings['tab_templates'] ) ) ? $settings['tab_templates'] : [];
		$empty_message = __( 'No saved templates yet. Click Add template to create a reusable tab snippet.', 'kitgenix-custom-tabs-for-woocommerce' );

		echo '<form method="post" action="options.php" autocomplete="off" novalidate>';
		settings_fields( 'kitgenix_custom_tabs_for_woocommerce_settings_group' );
		wp_nonce_field( 'kitgenix_custom_tabs_for_woocommerce_settings_save', 'kitgenix_custom_tabs_for_woocommerce_settings_nonce' );

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-card">'
			. '<h3>' . esc_html__( 'Tab Templates', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h3>'
			. '<p class="description">' . esc_html__( 'Save reusable warranty text, sizing guides, ingredient lists, care instructions, and other snippets here. Product editors and global tabs can then insert these templates in one click and customize them per use.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-manager kitgenix-custom-tabs-for-woocommerce-scope"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-manager="1"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-base="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates]"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-max="' . esc_attr( (string) $max_tabs ) . '"'
			. ' data-kitgenix-custom-tabs-for-woocommerce-empty-message="' . esc_attr( $empty_message ) . '"'
			. '>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-toolbar">'
			. '<p class="kitgenix-custom-tabs-for-woocommerce-manager-actions"><button type="button" class="button" data-kitgenix-custom-tabs-for-woocommerce-add="1">' . esc_html__( 'Add template', 'kitgenix-custom-tabs-for-woocommerce' ) . '</button></p>'
			. '<p class="description kitgenix-custom-tabs-for-woocommerce-toolbar__hint">' . esc_html__( 'Templates are editable copies. Inserting one into a product or global tab does not change the saved template.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>'
			. '</div>';

		echo '<div class="kitgenix-custom-tabs-for-woocommerce-table-wrap kitgenix-custom-tabs-for-woocommerce-manager-table">'
			. '<table class="widefat striped">'
			. '<thead><tr>'
			. '<th scope="col">' . esc_html__( 'Template', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Slug', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'Position', 'kitgenix-custom-tabs-for-woocommerce' ) . '</th>'
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

			self::render_global_table_row( $index, $title, $nickname, $slug, $priority );
			ob_start();
			self::render_template_hidden_fields( $index, $title, $nickname, $slug, $priority, $content );
			$fields_html .= (string) ob_get_clean();
			$index++;
		}
		if ( $index === 0 ) {
			echo '<tr class="kitgenix-custom-tabs-for-woocommerce-empty-row" data-kitgenix-custom-tabs-for-woocommerce-empty="1">'
				. '<td class="kitgenix-custom-tabs-for-woocommerce-empty-cell" colspan="4">'
				. '<span class="description">' . esc_html( $empty_message ) . '</span>'
				. '</td>'
				. '</tr>';
		}

		echo '</tbody></table></div>';
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields-wrap="1" class="kitgenix-custom-tabs-for-woocommerce-tabs-fields-wrap">'
			. $fields_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from render_template_hidden_fields() which escapes field values.
			. '</div>';

		echo '</div>';
		echo '</div>';

		submit_button( __( 'Save Settings', 'kitgenix-custom-tabs-for-woocommerce' ) );
		echo '</form>';
	}

	private static function render_global_table_row( int $index, string $title, string $nickname, string $slug, int $priority ): void {
		$display_title = $nickname !== '' ? $nickname : ( $title !== '' ? $title : __( 'Untitled', 'kitgenix-custom-tabs-for-woocommerce' ) );
		$subtitle      = ( $nickname !== '' && $title !== '' && $nickname !== $title ) ? $title : '';
		$display_slug  = $slug !== '' ? $slug : '—';

		echo '<tr data-kitgenix-custom-tabs-for-woocommerce-row="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<td>'
			. '<strong data-kitgenix-custom-tabs-for-woocommerce-row-title="1">' . esc_html( $display_title ) . '</strong>'
			. '<div class="kitgenix-custom-tabs-for-woocommerce-tabs-subtitle" data-kitgenix-custom-tabs-for-woocommerce-row-subtitle="1">' . esc_html( $subtitle ) . '</div>'
			. '</td>'
			. '<td><span class="kitgenix-custom-tabs-for-woocommerce-code" data-kitgenix-custom-tabs-for-woocommerce-row-slug="1">' . esc_html( $display_slug ) . '</span></td>'
			. '<td><span data-kitgenix-custom-tabs-for-woocommerce-row-position="1">' . esc_html( (string) $priority ) . '</span></td>'
			. '<td class="kitgenix-custom-tabs-for-woocommerce-actions">'
			. '<a href="#" class="button button-small" data-kitgenix-custom-tabs-for-woocommerce-edit="1">' . esc_html__( 'Edit', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a> '
			. '<a href="#" class="button button-link-delete" data-kitgenix-custom-tabs-for-woocommerce-remove="1">' . esc_html__( 'Remove', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>'
			. '</td>'
			. '</tr>';
	}

	private static function render_global_hidden_fields( int $index, string $title, string $nickname, string $slug, int $priority, string $content ): void {
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . '][title]" value="' . esc_attr( $title ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . '][nickname]" value="' . esc_attr( $nickname ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . '][slug]" value="' . esc_attr( $slug ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . '][priority]" value="' . esc_attr( (string) $priority ) . '" />'
			. '<textarea name="' . esc_attr( Settings::OPTION_NAME ) . '[global_tabs][' . esc_attr( (string) $index ) . '][content]" data-kitgenix-custom-tabs-for-woocommerce-content="1">' . esc_textarea( $content ) . '</textarea>'
			. '</div>';
	}

	private static function render_template_hidden_fields( int $index, string $title, string $nickname, string $slug, int $priority, string $content ): void {
		echo '<div data-kitgenix-custom-tabs-for-woocommerce-fields="1" data-index="' . esc_attr( (string) $index ) . '">'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates][' . esc_attr( (string) $index ) . '][title]" value="' . esc_attr( $title ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates][' . esc_attr( (string) $index ) . '][nickname]" value="' . esc_attr( $nickname ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates][' . esc_attr( (string) $index ) . '][slug]" value="' . esc_attr( $slug ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates][' . esc_attr( (string) $index ) . '][priority]" value="' . esc_attr( (string) $priority ) . '" />'
			. '<textarea name="' . esc_attr( Settings::OPTION_NAME ) . '[tab_templates][' . esc_attr( (string) $index ) . '][content]" data-kitgenix-custom-tabs-for-woocommerce-content="1">' . esc_textarea( $content ) . '</textarea>'
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
					'title'    => $title,
					'nickname' => isset( $template['nickname'] ) ? (string) $template['nickname'] : '',
					'slug'     => isset( $template['slug'] ) ? (string) $template['slug'] : '',
					'priority' => isset( $template['priority'] ) ? (int) $template['priority'] : 0,
					'content'  => isset( $template['content'] ) ? (string) $template['content'] : '',
					'label'    => isset( $template['nickname'] ) && (string) $template['nickname'] !== ''
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

		// Render as a persistent Kitgenix modal (matches Order Tracking modal styling/behavior).
		?>
		<div id="kitgenix-custom-tabs-for-woocommerce-modal" class="kitgenix-modal kitgenix-modal--wide" aria-hidden="true" role="dialog" aria-modal="true">
			<div class="kitgenix-modal__backdrop" data-kitgenix-modal-close="1"></div>
			<div class="kitgenix-modal__dialog" role="document">
				<div class="kitgenix-modal__header">
					<h2 class="kitgenix-modal__title" data-kitgenix-custom-tabs-for-woocommerce-modal-title="1"><?php esc_html_e( 'Edit Tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h2>
					<button type="button" class="button kitgenix-modal__close" data-kitgenix-modal-close="1"><?php esc_html_e( 'Close', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
				</div>

				<div class="kitgenix-modal__body">
					<input type="hidden" data-kitgenix-custom-tabs-for-woocommerce-modal-index="1" value="" />
					<div class="kitgenix-custom-tabs-for-woocommerce-modal-scope">
						<div class="kitgenix-custom-tabs-for-woocommerce-modal-grid">
							<div class="kitgenix-custom-tabs-for-woocommerce-field">
								<label for="kitgenix_custom_tabs_for_woocommerce_modal_title"><?php esc_html_e( 'Tab title', 'kitgenix-custom-tabs-for-woocommerce' ); ?></label>
								<input type="text" id="kitgenix_custom_tabs_for_woocommerce_modal_title" class="regular-text" data-kitgenix-custom-tabs-for-woocommerce-modal-field="title" value="" maxlength="50" placeholder="<?php echo esc_attr__( 'Title for tab', 'kitgenix-custom-tabs-for-woocommerce' ); ?>" />
								<div class="kitgenix-custom-tabs-for-woocommerce-field__error" data-kitgenix-custom-tabs-for-woocommerce-error="title" aria-live="polite"></div>
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
						</div>
					</div>
				</div>

				<div class="kitgenix-modal__actions">
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

	/**
	 * @param array<string,mixed> $settings
	 */
	private static function render_support_tab( array $settings ): void {
		$max_tabs            = isset( $settings['max_tabs'] ) ? (int) $settings['max_tabs'] : 10;
		$usage_stats         = self::get_tab_usage_stats();
		$products_with_tabs  = (int) $usage_stats['products_with_tabs'];
		$total_tabs          = (int) $usage_stats['total_tabs'];

		$donate_once_url     = 'https://buymeacoffee.com/kitgenix';
		$monthly_support_url = 'https://buymeacoffee.com/kitgenix/membership';
		$plugin_page_url     = 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/';
		$review_url          = 'https://wordpress.org/support/plugin/kitgenix-custom-tabs-for-woocommerce/reviews/#new-post';
		$support_request_url = 'https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/support';
		$copy_onclick        = "if(window.navigator&&navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(" . wp_json_encode( $plugin_page_url ) . ");}else{window.prompt(" . wp_json_encode( __( 'Copy plugin link:', 'kitgenix-custom-tabs-for-woocommerce' ) ) . ", " . wp_json_encode( $plugin_page_url ) . ");}return false;";
		$monthly_options     = [
			[ 'label' => __( 'Kitgenix Supporter (£4/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
			[ 'label' => __( 'Kitgenix Plus (£8/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
			[ 'label' => __( 'Kitgenix Pro Supporter (£19/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
			[ 'label' => __( 'Kitgenix Agency (£37/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
			[ 'label' => __( 'Kitgenix Partner (£75/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
			[ 'label' => __( 'Kitgenix YouTube Sponsor (£730/month)', 'kitgenix-custom-tabs-for-woocommerce' ), 'url' => 'https://buymeacoffee.com/kitgenix/membership' ],
		];

		$impact_cards = [
			[
				'label' => __( 'Products with custom tabs', 'kitgenix-custom-tabs-for-woocommerce' ),
				'value' => number_format_i18n( $products_with_tabs ),
				'meta'  => __( 'Products currently enhanced with extra content tabs.', 'kitgenix-custom-tabs-for-woocommerce' ),
			],
			[
				'label' => __( 'Total custom tabs', 'kitgenix-custom-tabs-for-woocommerce' ),
				'value' => number_format_i18n( $total_tabs ),
				'meta'  => __( 'Extra product content blocks already configured across the catalog.', 'kitgenix-custom-tabs-for-woocommerce' ),
			],
			[
				'label' => __( 'Max tabs per product', 'kitgenix-custom-tabs-for-woocommerce' ),
				'value' => (string) $max_tabs,
				'meta'  => __( 'The current editorial limit available to each product editor.', 'kitgenix-custom-tabs-for-woocommerce' ),
			],
		];
		$meaning_points = [
			__( 'You already have products using richer custom content beyond the default WooCommerce tabs.', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'The total tab count shows how much extra product information your catalog is serving to shoppers.', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Your max-tab limit controls how much editorial flexibility each product editor gets.', 'kitgenix-custom-tabs-for-woocommerce' ),
		];
		$support_points = [
			__( 'Compatibility updates for new WordPress / WooCommerce releases', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Bug fixes, edge-case testing, and better product-tab coverage', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Security hardening and ongoing performance improvements', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Documentation upgrades and faster, clearer support responses', 'kitgenix-custom-tabs-for-woocommerce' ),
		];
		$trust_points = [
			__( 'No paid features locked behind donations', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'No tracking or invasive upsells', 'kitgenix-custom-tabs-for-woocommerce' ),
			__( 'Support is always optional, and genuinely appreciated.', 'kitgenix-custom-tabs-for-woocommerce' ),
		];
		?>
		<div class="kitgenix-custom-tabs-for-woocommerce-card kitgenix-support-page">
			<div class="kitgenix-support-shell">
				<section class="kitgenix-support-hero">
					<div class="kitgenix-support-hero__copy">
						<span class="kitgenix-support-eyebrow"><?php echo esc_html__( 'Help keep Kitgenix independent', 'kitgenix-custom-tabs-for-woocommerce' ); ?></span>
						<h2 class="kitgenix-support-heading"><?php echo esc_html__( 'Support Kitgenix', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h2>
						<p class="description kitgenix-support-intro"><?php echo esc_html__( 'We try to keep Kitgenix plugins lightweight, privacy-friendly, and free for everyone. If Custom Tabs for WooCommerce saves you admin time or helps customers find product information faster, please consider supporting Kitgenix. Your support directly funds ongoing development, testing, and maintenance so we can keep features open and updates frequent.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					</div>
					<div class="kitgenix-support-hero__aside">
						<p class="kitgenix-support-kicker"><?php echo esc_html__( 'Support this plugin', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
						<div class="kitgenix-support-actions">
							<a class="button button-primary" href="<?php echo esc_url( $donate_once_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Buy Me a Coffee', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
							<a class="button button-secondary" href="<?php echo esc_url( $monthly_support_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Become a member', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
						</div>
						<p class="kitgenix-support-note"><?php echo esc_html__( 'Via Buy Me a Coffee. Cancel anytime.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					</div>
				</section>

				<section class="kitgenix-support-section kitgenix-support-section--feature">
					<div class="kitgenix-support-section__header">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'Your site impact', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<p class="description"><?php echo esc_html__( 'These stats show how Custom Tabs for WooCommerce is currently working on your site:', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					</div>
					<div class="kitgenix-support-metric-grid">
						<?php foreach ( $impact_cards as $impact_card ) : ?>
							<div class="kitgenix-support-stat">
								<span class="kitgenix-support-stat__label"><?php echo esc_html( $impact_card['label'] ); ?></span>
								<strong class="kitgenix-support-stat__value"><?php echo esc_html( $impact_card['value'] ); ?></strong>
								<span class="kitgenix-support-stat__meta"><?php echo esc_html( $impact_card['meta'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<div class="kitgenix-support-grid">
					<section class="kitgenix-support-section">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'Support options', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<p class="description"><?php echo esc_html__( 'Buy Me a Coffee: A quick way to say thanks and help fund the next round of improvements.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
						<p class="description"><?php echo esc_html__( 'A membership helps keep development consistent if this plugin is part of your day-to-day merchandising workflow.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
						<div class="kitgenix-support-chip-list">
							<?php foreach ( $monthly_options as $monthly_option ) : ?>
								<?php
								$monthly_label = (string) $monthly_option['label'];
								$monthly_name  = $monthly_label;
								$monthly_price = '';
								if ( preg_match( '/^(.*)\(([^)]+)\)$/u', $monthly_label, $monthly_parts ) ) {
									$monthly_name  = trim( $monthly_parts[1] );
									$monthly_price = trim( $monthly_parts[2] );
								}
								?>
								<a class="kitgenix-support-chip" href="<?php echo esc_url( $monthly_option['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<span class="kitgenix-support-chip__name"><?php echo esc_html( $monthly_name ); ?></span>
									<?php if ( '' !== $monthly_price ) : ?>
										<span class="kitgenix-support-chip__price"><?php echo esc_html( $monthly_price ); ?></span>
									<?php endif; ?>
								</a>
							<?php endforeach; ?>
						</div>
					</section>

					<section class="kitgenix-support-section">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'What this means', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<ul class="kitgenix-support-list">
							<?php foreach ( $meaning_points as $meaning_point ) : ?>
								<li><?php echo esc_html( $meaning_point ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>

					<section class="kitgenix-support-section kitgenix-support-section--soft">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'What your support helps with', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<ul class="kitgenix-support-list">
							<?php foreach ( $support_points as $support_point ) : ?>
								<li><?php echo esc_html( $support_point ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>

					<section class="kitgenix-support-section">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'Not in a position to donate?', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<p class="description"><?php echo esc_html__( 'No worries - you can still massively help:', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
						<p class="description"><?php echo esc_html__( 'Reviews help others discover the plugin and keep the project sustainable. Sharing the plugin with store owners who want cleaner product pages and sending strong feature reports both help move the roadmap forward.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
						<div class="kitgenix-support-actions">
							<a class="button button-secondary" href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Leave a WordPress.org review', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
							<button type="button" class="button button-secondary" onclick="<?php echo esc_attr( $copy_onclick ); ?>"><?php echo esc_html__( 'Copy plugin link', 'kitgenix-custom-tabs-for-woocommerce' ); ?></button>
							<a class="button button-secondary" href="<?php echo esc_url( $support_request_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Open support / feature request', 'kitgenix-custom-tabs-for-woocommerce' ); ?></a>
						</div>
					</section>

					<section class="kitgenix-support-section kitgenix-support-section--full">
						<h3 class="kitgenix-support-subheading"><?php echo esc_html__( 'A small note on trust & privacy', 'kitgenix-custom-tabs-for-woocommerce' ); ?></h3>
						<ul class="kitgenix-support-list">
							<?php foreach ( $trust_points as $trust_point ) : ?>
								<li><?php echo esc_html( $trust_point ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="kitgenix-support-footer-note"><?php echo esc_html__( 'Thank you for supporting Kitgenix.', 'kitgenix-custom-tabs-for-woocommerce' ); ?></p>
					</section>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @return array{products_with_tabs:int,total_tabs:int}
	 */
	private static function get_tab_usage_stats(): array {
		$cache_key = Product_Tabs::get_usage_stats_cache_key();
		$cached = get_transient( $cache_key );

		if ( is_array( $cached ) && isset( $cached['products_with_tabs'], $cached['total_tabs'] ) ) {
			return [
				'products_with_tabs' => max( 0, (int) $cached['products_with_tabs'] ),
				'total_tabs'         => max( 0, (int) $cached['total_tabs'] ),
			];
		}

		$stats = [
			'products_with_tabs' => 0,
			'total_tabs'         => 0,
		];
		$paged = 1;
		$max_pages = 1;
		$meta_key = 'kitgenix_custom_tabs_for_woocommerce_tabs';

		do {
			$query = new \WP_Query(
				[
					'post_type'              => 'product',
					'post_status'            => 'any',
					'fields'                 => 'ids',
					'posts_per_page'         => 250,
					'paged'                  => $paged,
					'no_found_rows'          => false,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'suppress_filters'       => false,
				]
			);

			$product_ids = is_array( $query->posts ) ? $query->posts : [];
			$max_pages = max( 1, (int) $query->max_num_pages );

			foreach ( $product_ids as $product_id ) {
				$tabs = get_post_meta( (int) $product_id, $meta_key, true );
				if ( ! is_array( $tabs ) || empty( $tabs ) ) {
					continue;
				}

				$stats['products_with_tabs']++;
				$stats['total_tabs'] += count( $tabs );
			}

			$paged++;
		} while ( ! empty( $product_ids ) && $paged <= $max_pages );

		set_transient( $cache_key, $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	private static function render_log_tab(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['kitgenix_log_cleared'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Event log cleared.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p></div>';
		}

		$clear_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=kitgenix_custom_tabs_for_woocommerce_clear_event_log' ),
			'kitgenix_custom_tabs_for_woocommerce_clear_event_log'
		);

		echo '<div class="kitgenix-settings-section">';
		echo '<h2>' . esc_html__( 'Activity Log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'A record of recent plugin events. Entries show the timestamp, context, outcome, and a plain-English note. IP addresses and sensitive data are never stored here.', 'kitgenix-custom-tabs-for-woocommerce' ) . '</p>';
		echo '<textarea class="large-text code" rows="20" readonly>' . esc_textarea( Event_Log::get_log_text() ) . '</textarea>';
		echo '<p>';
		echo '<a href="' . esc_url( $clear_url ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Clear all log entries?', 'kitgenix-custom-tabs-for-woocommerce' ) ) . '\')">' . esc_html__( 'Clear log', 'kitgenix-custom-tabs-for-woocommerce' ) . '</a>';
		echo '</p>';
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
		Event_Log::record( 'settings-saved', 'success', __( 'Plugin settings were saved via the admin settings page.', 'kitgenix-custom-tabs-for-woocommerce' ) );
	}

}

