=== Kitgenix Custom Tabs for WooCommerce ===
Contributors: kitgenix
Donate link: https://buymeacoffee.com/kitgenix
Tags: woocommerce, product, tabs, custom tabs, product tabs
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.1.3
Requires Plugins: woocommerce
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://wordpress.org/plugins/kitgenix-custom-tabs-for-woocommerce/
Author Plugin URI: https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce
Author: Kitgenix
Author URI: https://kitgenix.com/
Documentation URI: https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/documentation
Support URI: https://wordpress.org/support/plugin/kitgenix-custom-tabs-for-woocommerce/
Author Support URI: https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/support
Feature Request URI: https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/feature-request

Add custom WooCommerce product tabs with per-product content, reusable templates, global tabs, and lightweight controls.

== Description ==

**Kitgenix Custom Tabs for WooCommerce** lets you add product-specific custom tabs from the WooCommerce product editor, reuse saved tab templates, and duplicate existing tabs when products only need small content variations.

Designed to be lightweight and modular:
- Add tabs per product (title, optional nickname, optional slug, optional priority/position, rich content)
- Add global tabs that apply to every product (optional)
- Optional shortcode processing for tab content
- Safety limits to keep the product editor fast (max tabs per product)
- Optional setting to hide the tab heading inside the tab content panel

= Features =

- Modal-based tab editor powered by Quill rich-text editing with inline validation.
- Saved tab templates for reusable snippets like sizing guides, warranties, ingredient lists, and care instructions.
- Fast duplication for product tabs and global tabs so merchants can clone and tweak existing content instead of rewriting it.
- Automatic slug generation from the tab title, with optional manual slug control when you need stable identifiers.
- Product-specific tabs plus store-wide global tabs managed from a dedicated Global Tabs screen.
- Templates can be inserted directly into the product editor and the Global Tabs screen without leaving the current workflow.
- Flexible placement controls using a default position preset plus priority base/step settings for tab ordering.
- Optional shortcode rendering inside tab content.
- Optional hidden tab headings when you want the title to appear only on the tab label itself.
- Cached usage stats in the Support tab so large stores can see how many products and tabs are in use without expensive queries.
- HPOS compatibility declaration for modern WooCommerce installs.

== Quick Start ==

1. Install and activate the plugin (WooCommerce required).
2. Go to Kitgenix → Custom Tabs to configure settings.
3. Edit a product: Products → Edit → Product data → Custom Tabs.
4. Add one or more tabs, then Update the product.

Optional: add store-wide tabs under Kitgenix → Custom Tabs → Global Tabs.
Optional: save reusable snippets under Kitgenix → Custom Tabs → Templates, then insert them into products or global tabs in one click.
Optional: use the settings screen to control maximum tabs, default tab positions, shortcode support, and heading visibility.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` (or install via Plugins → Add New).
2. Activate the plugin.
3. Configure: Kitgenix → Custom Tabs.

== Frequently Asked Questions ==

= Where do I edit tabs? =
Edit a product and open Product data → Custom Tabs.

= Do tabs support shortcodes? =
Yes, if enabled in the plugin settings.

= Can I control the order of tabs? =
Yes. You can set a priority per tab. Lower numbers appear earlier.

= Can I save a tab as a reusable template? =
Yes. The Templates tab lets you save reusable tab snippets, and both the product editor and Global Tabs screen can insert those saved templates directly into a new tab row.

= Can I duplicate an existing tab? =
Yes. Product-specific tabs and global tabs now include a Duplicate action so you can clone an existing row, keep the content, and only change the parts that differ.

= Can I add store-wide tabs to every product? =
Yes. Use the Global Tabs tab in the plugin settings to create tabs that are added to every product automatically.

= Do tabs support rich content? =
Yes. The editor supports rich-text formatting in a fast modal workflow powered by Quill.

= Can I hide the heading inside the tab content? =
Yes. There is a setting to keep the heading on the tab label only and hide the repeated heading inside the tab panel.

= Does it support HPOS? =
Yes. The plugin declares WooCommerce HPOS compatibility.

== Screenshots ==

1. Product data panel with the Custom Tabs tab.
2. Modal editor with title, nickname, slug, priority, and rich content.
3. Templates screen for reusable tab snippets.
4. Global Tabs screen for store-wide tabs.
5. Settings screen for limits, positioning, shortcode support, and heading visibility.
6. Tab on product page.
7. Support tab with cached usage totals.

== Developers ==

Text domain:
- `kitgenix-custom-tabs-for-woocommerce`

Settings option:
- `kitgenix_custom_tabs_for_woocommerce_settings`

Settings group (Settings API):
- `kitgenix_custom_tabs_for_woocommerce_settings_group`

Settings keys (stored inside the option array):
- `enabled` (bool/int)
- `max_tabs` (int)
- `allow_shortcodes` (bool/int)
- `priority_base` (int)
- `priority_step` (int)
- `hide_tab_heading` (bool/int)
- `tab_templates` (array of reusable tab rows)
- `global_tabs` (array of tab rows)

Sanitization behavior:
- The sanitize callback merges submitted values into the existing option, so saving one settings tab/form does not wipe values from other tabs.

Post meta:
- `kitgenix_custom_tabs_for_woocommerce_tabs` (array)

Transients:
- `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect` (short-lived activation redirect flag)
- `kitgenix_custom_tabs_for_woocommerce_usage_stats` (cached support-tab usage totals)
- `kitgenix_hub_wporg_active_installs_v1` (Kitgenix hub cache; active install counts)
- `kitgenix_hub_wporg_ratings_v1` (Kitgenix hub cache; ratings percentage)
- `kitgenix_hub_wporg_media_v1` (Kitgenix hub cache; banners/icons)

Nonces (exact identifiers):
- Product save nonce field: `kitgenix_custom_tabs_for_woocommerce_tabs_nonce`
- Product save nonce action: `kitgenix_custom_tabs_for_woocommerce_tabs_save`
- Settings form nonce field: `kitgenix_custom_tabs_for_woocommerce_settings_nonce`
- Settings form nonce action: `kitgenix_custom_tabs_for_woocommerce_settings_save`

Hooks used:
- `plugins_loaded` (action): bootstrap plugin when WooCommerce is available
- `init` (action): load translations
- `admin_init` (action): register settings; perform activation redirect once
- `admin_menu` (action): ensure shared Kitgenix hub menu; register plugin submenu page
- `admin_head` (action): inject shared Kitgenix hub admin menu icon CSS
- `admin_enqueue_scripts` (action): enqueue hub/admin/product editor assets
- `plugin_action_links_{plugin_basename}` (filter): add “Settings” link on Plugins screen
- `before_woocommerce_init` (action): declare WooCommerce HPOS compatibility
- `woocommerce_product_tabs` (filter): inject frontend tabs
- `woocommerce_product_data_tabs` (filter): add Product data tab
- `woocommerce_product_data_panels` (action): render Product data panel
- `woocommerce_process_product_meta` (action): save product tab meta

== External Services ==

This plugin can connect to the WordPress.org Plugins API when you view the shared Kitgenix admin hub page.
It uses WordPress core's `plugins_api()` to fetch plugin information (such as active install counts and ratings) for Kitgenix plugins, and caches results in transients.
No customer/order/product data is sent.

== Security & Privacy ==

- Admin actions are protected with capability checks.
- Product tab saving is protected with a plugin nonce.
- Settings are saved via WordPress' Settings API (options.php), which is nonce-protected by WordPress core.
- Tab content is output with WordPress formatting and safe HTML handling.

== Uninstall ==

On uninstall, the plugin removes:
- The settings option
- All saved product tab meta entries

Note: the uninstall handler does not explicitly delete short-lived transients or hub cache transients.

== Support Development ==

If this plugin saves you time, you can support development here:
https://buymeacoffee.com/kitgenix

== Credits ==
Built with ❤︎ by @kitgenix - https://kitgenix.com

== Upgrade Notice ==

= 1.1.3 =
Recommended for all websites.

== Changelog ==
= 1.1.3 (26 May 2026) =
* Compatibility: Confirmed compatibility with WordPress 7.0 and WooCommerce 10.x.
* New: Added a Log tab to the admin settings page. Records recent plugin activity (settings saves and key operations) with timestamps, context labels, and plain-English notes to aid troubleshooting.
* Fix: Activity log data is now fully cleaned up when the plugin is uninstalled.

= 1.1.2 (26 May 2026) =
* Dev: Skipped to be in line with other Kitgenix Plugins

= 1.1.1 (26 May 2026) =
* Dev: Skipped to be in line with other Kitgenix Plugins

= 1.1.0 (7 May 2026) =
* New: Added a dedicated Templates screen for saving reusable tab snippets such as sizing guides, warranty text, ingredient lists, and care instructions.
* New: Added one-click template insertion inside both the WooCommerce product editor and the Global Tabs screen.
* New: Added Duplicate actions for product tabs and global tabs so merchants can clone an existing tab and edit the copy.
* Improvement: Product editors now link directly to the Templates screen from the Custom Tabs panel for faster merchandising workflows.
* Improvement: Saved templates now use their own library limit instead of being capped by the per-product tab limit.

= 1.0.0 (07 April 2026) =
* New: Initial release.
* New: Product-specific custom tabs added via Products -> Edit -> Product data -> Custom Tabs.
* New: Global tabs that can be applied to every product (Kitgenix -> Custom Tabs -> Global Tabs).
* New: Tab fields: title, optional nickname, optional slug, position/priority, and rich content.
* New: Optional shortcode processing for tab content.
* New: Settings to control max tabs per product, default positioning (base + step), and whether to hide the tab heading inside the tab.
* New: Admin UI with a fast modal rich-text editor workflow.
* Security: Admin actions protected with capability checks and nonces.
* Cleanup: Removes plugin settings and all saved product tab meta.
* UI: Improved the Kitgenix admin header layout and social icon links (compact icon buttons) across settings and the Kitgenix hub.
* Fix: Admin notices now display above the Kitgenix header using the WordPress standard notice area.
* Fix: Added defensive notice normalization to prevent notices being relocated into the header by other scripts.
* UI: Admin tables inside Kitgenix pages now use Kitgenix styling for a more consistent branded look.
* Fix: Added spacing between adjacent action links/buttons (e.g., Edit/Delete).
* Fix: Replaced the expensive support-tab usage query with cached usage stats and cache invalidation on save/delete.
* Fix: Escaped shared Kitgenix hub card media output for WordPress coding standards compliance.
* Maintenance: Updated the plugin Author URI to the public Kitgenix WordPress.org profile and replaced the old custom admin-menu icon CSS with the native Dashicons icon.
