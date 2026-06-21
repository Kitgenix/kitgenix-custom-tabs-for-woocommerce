<?php
/**
 * Main plugin bootstrap.
 */

declare(strict_types=1);

namespace KitgenixCustomTabsForWooCommerce;

defined( 'ABSPATH' ) || exit;

use KitgenixCustomTabsForWooCommerce\Admin\Product_Tabs;
use KitgenixCustomTabsForWooCommerce\Admin\Settings_UI;
use KitgenixCustomTabsForWooCommerce\Core\Settings;
use KitgenixCustomTabsForWooCommerce\Frontend\Tabs;

final class Plugin {
	public static function init(): void {
		// Ensure defaults exist.
		Settings::ensure_defaults();

		// Register settings.
		\add_action( 'admin_init', [ Settings::class, 'register_settings' ] );

		// Translations are loaded by WordPress.org for plugins hosted on wp.org;
		// explicit load_plugin_textdomain() is no longer required and is discouraged.

		// Admin.
		if ( \is_admin() ) {
			Settings_UI::init();
			Product_Tabs::init();
		}

		// Frontend.
		Tabs::init();
	}

	// Textdomain loading is handled automatically by WordPress.org for plugins
	// hosted on the plugin repository. No explicit load_plugin_textdomain()
	// call is necessary.
}
