=== Kitgenix Custom Tabs for WooCommerce ===
Contributors: kitgenix
Donate link: https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2
Tags: woocommerce, product tabs, custom tabs, global tabs, tab manager, product page tabs, additional information tab, product description, sizing guide, reusable content, shortcode, product editor
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
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

Add custom product tabs to WooCommerce with global tabs, targeting rules, visibility conditions, and reusable templates.

== Description ==

**Kitgenix Custom Tabs for WooCommerce** adds extra tabs to the WooCommerce product page – sizing guides, warranty terms, ingredient lists, care instructions, or anything else the built-in Description, Additional Information, and Reviews tabs don't cover. Tabs are created and managed from the WooCommerce product editor itself, so there's no separate interface to learn for everyday work, while a dedicated Kitgenix admin screen handles store-wide tabs, reusable content, and bulk actions.

This plugin is built for merchants who need tab content to differ by product, by category, or by audience – not just one fixed set of tabs pinned to every product regardless of context.

= Key Features =

* **Per-product tabs** – add title, optional nickname, optional slug, position, and rich content to any product from Product data → Custom Tabs, without leaving the product editor.
* **Global tabs with targeting** – create a tab once and apply it to every product, or restrict it by specific products, categories, tags, and/or product types, with independent Include/Exclude rules per dimension. Leave everything empty and it applies store-wide, exactly like a simple global tab.
* **Visibility conditions** – show or hide any product or global tab based on visitor login state, user role, stock status, or purchasability, without needing a separate rules-engine plugin.
* **Reusable templates** – save common tab content once (sizing guide, warranty, ingredients) and insert it into any product or global tab in one click, or output it anywhere with the `[kitgenix_tab]` shortcode.
* **Bulk-apply from the Products list** – select multiple products and apply a saved template to all of them in one WooCommerce bulk action, skipping any product already at the tab limit.
* **Rich-text editing, self-hosted** – tab content is written in a Quill-powered modal editor bundled with the plugin, so wp-admin never depends on a third-party CDN to edit a product.
* **Reorder, enable/disable, duplicate** – keyboard-accessible Move up/down controls swap actual stored priority values (not just visual order); every tab, global tab, and template can be toggled off without deleting it, or duplicated as a starting point for a variation.
* **Import/export** – full-fidelity JSON export/import for settings, global tabs, and templates (including visibility and targeting rules, with a preview before you commit), plus a simpler spreadsheet-friendly CSV format for Global Tabs and Templates.
* **Usage-aware, cache-friendly** – templates live in their own non-autoloaded option so a large template library never adds weight to ordinary page loads, and tab matching runs against the already-loaded product object with no extra database queries per tab.

= Built for WooCommerce =

Tabs render through WooCommerce's own `woocommerce_product_tabs` filter, alongside the Description, Additional Information, and Reviews tabs – there's no custom markup or separate template system to fight with your theme.

Targeting and visibility rules can match by product type (simple, variable, grouped, external), so a tab can be scoped to, for example, only variable products or only simple products. Stock-status and purchasability conditions read the product's own state directly, so a "back in stock" or "in stock only" tab stays accurate without any extra configuration.

= How It Works =

1. Add a tab to an individual product from Product data → Custom Tabs in the product editor.
2. Optionally, create store-wide tabs under Kitgenix → Custom Tabs → Global Tabs, and target them to specific products, categories, tags, or product types.
3. Optionally, save reusable snippets under Kitgenix → Custom Tabs → Templates, then insert them into products or global tabs, or embed one anywhere with `[kitgenix_tab slug="..."]`.
4. Optionally, restrict any tab with visitor login state, role, stock status, or purchasability using the tab editor's Visibility conditions section.
5. Use Kitgenix → Custom Tabs → Settings to control the maximum tabs per product, default positioning, shortcode support, and heading visibility, and use the Portability tab to export or import everything as JSON or CSV.

= Compatibility =

* Works with WooCommerce's classic (meta-box) Product Data panel, which is the supported product-editing screen for WooCommerce 11.0+ following the removal of the experimental block-based Product Editor Beta.
* Declares compatibility with WooCommerce's High-Performance Order Storage (HPOS / custom order tables) via `FeaturesUtil::declare_compatibility()`. The plugin never reads or writes order data.
* Tab targeting supports simple, variable, grouped, and external product types.
* The bundled rich-text editor (Quill) is loaded entirely from your own site – nothing is fetched from a CDN.

= Developer Features =

* Filters to adjust tab eligibility, priority, and content per product/visitor: `kitgenix_custom_tabs_for_woocommerce_tab_eligible`, `kitgenix_custom_tabs_for_woocommerce_tab_priority`, `kitgenix_custom_tabs_for_woocommerce_tab_content`, and `kitgenix_custom_tabs_for_woocommerce_shortcode_html`.
* A single matching engine, `Tab_Matcher::is_eligible()`, used by both the frontend renderer and any custom integration, so targeting/visibility logic never has to be reimplemented.
* JSON export/import schema versioning, and a shared sanitizer used by manual saves, JSON import, CSV import, and the bulk-apply action, so imported content is never trusted as pre-sanitised.

See the "Developers" section below for the full list of hooks, data structures, and admin-post actions.

== Installation ==

1. Make sure WooCommerce is installed and active – this plugin requires it and will deactivate itself with a notice if it isn't.
2. Upload the plugin folder to `/wp-content/plugins/` (or install it via Plugins → Add New → search for "Kitgenix Custom Tabs").
3. Activate the plugin. You'll be redirected to Kitgenix → Custom Tabs to configure it.
4. Edit any product: Products → Edit → Product data → Custom Tabs, and add your first tab.

== External Services ==

This plugin can connect to the WordPress.org Plugins API when you view the shared Kitgenix admin hub page (Kitgenix → the main hub screen listing all Kitgenix plugins). It uses WordPress core's `plugins_api()` function to look up, for each installed or listed Kitgenix plugin slug, its active install count, star rating, and banner/icon artwork, purely so the hub page can display that information. This happens only when an administrator views that specific admin screen – it is never triggered on the frontend or on any other wp-admin page. Results are cached in WordPress transients (`kitgenix_hub_wporg_active_installs_v1`, `kitgenix_hub_wporg_ratings_v1`, `kitgenix_hub_wporg_media_v1`) for one day (`DAY_IN_SECONDS`) to minimise repeated requests. No customer, order, or product data is sent — only the plugin slugs being looked up. This is the same `plugins_api()` call WordPress core itself uses on the Plugins → Add New screen, made to wordpress.org, whose terms are available at https://wordpress.org/about/privacy/.

The "Products" field in a global tab's targeting rules uses WooCommerce's own built-in AJAX product search (the same `woocommerce_json_search_products` endpoint WooCommerce's core "Linked Products" fields use) to search your own store's products as you type. This is a request to your own site, not a third party – no data leaves your server.

The rich-text tab editor is powered by Quill (https://quilljs.com/), bundled locally in `assets/vendor/quill/` (BSD-3-Clause licensed) and loaded only from your own site – it is not fetched from a CDN or any external host.

== Developers ==

Text domain:
- `kitgenix-custom-tabs-for-woocommerce`

Settings option:
- `kitgenix_custom_tabs_for_woocommerce_settings`

Settings group (Settings API):
- `kitgenix_custom_tabs_for_woocommerce_settings_group`

Settings keys (stored inside the main option array):
- `enabled` (bool/int)
- `max_tabs` (int) – per-product-page cap across global + product-specific tabs combined
- `allow_shortcodes` (bool/int)
- `priority_base` (int)
- `priority_step` (int)
- `hide_tab_heading` (bool/int)
- `global_tabs` (array of tab rows – see "Tab row shape" below)

Templates option (separate, non-autoloaded – see "Why templates moved to their own option" below):
- `kitgenix_custom_tabs_for_woocommerce_templates` (array of tab rows, without `visibility`/`target`)

Tab row shape (product-specific and global tabs):
- `title`, `nickname`, `content`, `slug`, `priority` – unchanged since 1.0
- `enabled` (0/1, default 1 – a row saved before 2.0.0 has no `enabled` key at all, which is treated as enabled, so no existing tab is silently hidden by upgrading)
- `visibility` (object): `auth` ('' | logged_in | logged_out), `roles` (array of role slugs, any-match), `stock` ('' | in_stock | out_of_stock), `purchasable` ('' | yes | no)
- `target` (global tabs only – object): `products`, `categories`, `tags`, `types`, each `{include: [...], exclude: [...]}`. Exclude always wins; an empty `include` list on a dimension means "no restriction" for that dimension. See `KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher`.

Why templates moved to their own option:
- Templates are admin-only (never read on the frontend) and can grow into a sizeable library of rich-HTML snippets. Keeping them in the main autoloaded settings option meant that whole library was loaded into memory on *every* request, including pages with no products at all. `Settings::ensure_defaults()` runs a one-time, idempotent migration (`migrate_templates_option()`) that moves an existing `tab_templates` value out of the old option into the new one and removes the old key – nothing is lost on upgrade.

Sanitization behavior:
- The sanitize callback merges submitted values into the existing option, so saving one settings tab/form does not wipe values from other tabs.
- `Settings::sanitize_tabs_rows( $raw, $max, $context )` is the single sanitizer used by product tabs, global tabs, templates, and both import paths (JSON/CSV) – `$context` is `'product'`, `'global'`, or `'template'` and controls which of `visibility`/`target` are collected, so all four save/import paths can never drift out of sync with each other.

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
- Settings form nonce field/action: `kitgenix_custom_tabs_for_woocommerce_settings_nonce` / `kitgenix_custom_tabs_for_woocommerce_settings_save`
- Templates form nonce/group: uses the dedicated `kitgenix_custom_tabs_for_woocommerce_templates_group` Settings API group (own `register_setting()` call, own sanitize callback) now that templates are a separate option
- Portability (import/export) nonce action: `kitgenix_custom_tabs_for_woocommerce_portability` (field name `_wpnonce`)
- Event log clear nonce action: `kitgenix_custom_tabs_for_woocommerce_clear_event_log`

Admin-post actions (Portability tab):
- `kitgenix_custom_tabs_for_woocommerce_export` – full JSON export (settings + global tabs + templates)
- `kitgenix_custom_tabs_for_woocommerce_import` – full JSON import (Replace/Merge, with a client-side preview before submit)
- `kitgenix_custom_tabs_for_woocommerce_export_csv` – flattened CSV export of Global Tabs or Templates (`?which=global_tabs|templates`)
- `kitgenix_custom_tabs_for_woocommerce_import_csv` – CSV import into Global Tabs or Templates (Replace/Merge). CSV has no `visibility`/`target` columns; those are only round-tripped via the JSON format.

Shortcode:
- `[kitgenix_tab slug="warranty-info"]` or `[kitgenix_tab title="Warranty Info"]` – renders a saved template's content anywhere shortcodes are processed (post/page content, widgets, other tabs), not only inside the WooCommerce product tabs area. Optional `heading="1"` attribute prints the template's title as a heading above the content. Templates only – a live per-product or global tab is meaningless outside its product-page context.

Bulk action (Products list):
- Selecting one or more products on Products → All Products and choosing "Add Kitgenix tab: {template name}" from the Bulk actions dropdown appends that template as a product-specific tab to every selected product (skipping any product already at the max-tabs cap). Uses WordPress core's own `bulk_actions-edit-product` / `handle_bulk_actions-edit-product` hooks.

Hooks used (consuming other systems):
- `plugins_loaded` (action): bootstrap plugin when WooCommerce is available
- `admin_init` (action): register settings; perform activation redirect once
- `admin_menu` (action): ensure shared Kitgenix hub menu; register plugin submenu page
- `admin_enqueue_scripts` (action): enqueue hub/admin/product editor assets
- `plugin_action_links_{plugin_basename}` (filter): add "Settings" link on Plugins screen
- `before_woocommerce_init` (action): declare WooCommerce HPOS compatibility
- `woocommerce_product_tabs` (filter): inject frontend tabs
- `woocommerce_product_data_tabs` (filter): add Product data tab
- `woocommerce_product_data_panels` (action): render Product data panel
- `woocommerce_process_product_meta` (action): save product tab meta
- `bulk_actions-edit-product` / `handle_bulk_actions-edit-product` (filters): the Products list bulk action above
- `init` (action): register the `[kitgenix_tab]` shortcode

Developer hooks (provided by this plugin):
- `kitgenix_custom_tabs_for_woocommerce_tab_eligible` (filter: `bool $eligible, array $row, WC_Product $product`) – runs after the built-in enabled/target/visibility checks already passed; return `false` to suppress a specific resolved tab for a specific product/visitor.
- `kitgenix_custom_tabs_for_woocommerce_tab_priority` (filter: `int $priority, array $row, WC_Product $product`) – adjust a tab's final ordering priority.
- `kitgenix_custom_tabs_for_woocommerce_tab_content` (filter: `string $content, array $row, WC_Product $product`) – adjust a tab's content before shortcode processing/`wpautop()`.
- `kitgenix_custom_tabs_for_woocommerce_shortcode_html` (filter: `string $html, array $template`) – adjust the final `[kitgenix_tab]` shortcode output.

Matching engine:
- `KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher::is_eligible( array $row, WC_Product $product )` is the single entry point both the frontend renderer and any custom integration should use to decide whether a saved row applies – it combines `matches_target()` (global-tab product/category/tag/type rules) and `is_visible()` (enabled flag + visitor/stock/purchasable conditions). No additional database queries are issued per match: it reads only from the already-loaded `$product` object and cached taxonomy lookups (`has_term()`).

== Security & Privacy ==

- Admin actions are protected with capability checks (`edit_products` for product tabs, `manage_woocommerce`/`manage_options` for settings, portability, and bulk actions).
- Product tab saving, settings saves, the event log clear action, and JSON/CSV import/export are all nonce-protected.
- Settings and templates are saved via WordPress' Settings API (options.php), which is nonce-protected by WordPress core.
- Tab content is output with WordPress formatting and safe HTML handling (`wp_kses_post()`), so rich content is preserved rather than stripped down to plain text.
- Visibility conditions read only standard WordPress data already available to the current request (login state, the current user's own role list, product stock/purchasable status) – nothing external is contacted and no additional personal data is stored or transmitted.
- Settings export excludes nothing sensitive by default since this plugin stores no API keys, tokens, or credentials – only tab content, structure, and configuration.

== Uninstall ==

On uninstall, the plugin removes, for every site on a multisite network:
- The settings option and the separate templates option
- The activity log option
- All saved product tab meta entries
- The cached usage-stats transient and the activation-redirect transient

Note: the Kitgenix Hub's shared WordPress.org cache transients (active-install counts, ratings, media) are intentionally left in place, since other installed Kitgenix plugins may still be using them; they expire on their own after about a day.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes. Kitgenix Custom Tabs for WooCommerce requires WooCommerce to be installed and active – tabs are added and managed through the WooCommerce product editor and rendered on the WooCommerce product page. The plugin deactivates itself with a notice if WooCommerce isn't active.

= How do I add a custom tab to a WooCommerce product? =
Edit a product and open Product data → Custom Tabs. Click "Add new tab" and fill in the title, content, and any optional fields in the modal editor.

= Does it support variable products? =
Yes. Global tab targeting can include or exclude by product type – simple, variable, grouped, and external – and product-specific tabs work on any product type the same way.

= Can I add store-wide tabs to every product? =
Yes. Use Kitgenix → Custom Tabs → Global Tabs to create a tab that applies to every product by default, or restrict it to specific products, categories, tags, or product types using Include/Exclude rules.

= Can I target a global tab to only some products? =
Yes. Open a global tab's editor and use the "Product targeting" section. Leaving a dimension empty means "no restriction" for that dimension. Exclude rules always win over Include rules. Leave every dimension empty and the tab applies to every product.

= Can I show or hide a tab based on who's viewing it? =
Yes. Any product tab or global tab can be restricted by visitor login state (logged-in/logged-out), user role, stock status, and/or purchasability, using the "Visibility conditions" section in the tab editor.

= Can I control the order of tabs? =
Yes. Each tab has a priority; lower numbers appear earlier. Table rows also have keyboard-accessible Move up/down controls that swap the actual stored priority values between neighbouring tabs, so the order shown in the admin always matches what renders on the product page.

= Can I save a tab as a reusable template? =
Yes. The Templates screen lets you save reusable tab snippets, and both the product editor and the Global Tabs screen can insert a saved template directly into a new tab.

= Can I apply a template to many products at once? =
Yes. Select products on the WooCommerce Products list and choose "Add Kitgenix tab: {template name}" from the Bulk actions dropdown.

= Can I duplicate an existing tab? =
Yes. Product-specific tabs and global tabs include a Duplicate action so you can clone an existing row and only change the parts that differ.

= Can I temporarily turn a tab off without deleting it? =
Yes. Every tab row – product-specific, global, and template – has an Enable/Disable action.

= Do tabs support rich content? =
Yes. The tab editor is a modal powered by a locally-bundled Quill rich-text editor, so you get proper formatting without loading anything from an external CDN.

= Do tabs support shortcodes? =
Yes, if "Allow shortcodes in tab content" is enabled in Settings. When enabled, shortcodes inside tab content are processed on the frontend.

= Can I show a template's content outside the product tabs area? =
Yes, with the `[kitgenix_tab]` shortcode – for example `[kitgenix_tab slug="warranty-info"]` – anywhere shortcodes are processed (post/page content, widgets, other tabs).

= Can I hide the heading inside the tab content? =
Yes. There's a setting to keep the title on the tab label only and hide the repeated heading inside the tab panel, plus a per-tab override.

= Can I export or import my tabs and settings? =
Yes, from the Portability tab. JSON export/import carries everything, including visibility conditions and targeting rules, and shows a preview of an uploaded file before you commit to Replace or Merge. CSV export/import is available separately for Global Tabs and Templates, covering the basic fields (title, nickname, slug, priority, enabled, content) – visibility/targeting rules aren't part of CSV.

= Does it support WooCommerce's High-Performance Order Storage (HPOS)? =
Yes. The plugin declares compatibility with WooCommerce's custom order tables feature. It doesn't read or write order data at all.

= Does this work with WooCommerce's block-based product editor? =
The plugin's Custom Tabs panel is built on WooCommerce's classic (meta-box) Product Data screen, which is the supported product-editing interface following WooCommerce 11.0's removal of the experimental block-based Product Editor Beta.

= Will adding lots of tabs slow down my store? =
Templates are stored in their own non-autoloaded option so a large template library doesn't add weight to ordinary page loads. Global tab matching runs against the already-loaded product object with no extra database queries per tab, and a configurable maximum-tabs-per-product setting keeps the product editor and the frontend tab list from growing unbounded.

= Does the rich-text editor load anything from outside my site? =
No. The Quill editor is bundled with the plugin and served from your own site, not a CDN.

= Does this plugin phone home or send any data externally? =
Only when you view the Kitgenix admin hub screen, where it looks up active-install counts, ratings, and artwork for Kitgenix plugins from the WordPress.org Plugins API (cached for a day). No customer, order, or product data is ever sent. See "External Services" above for details.

= What happens to my tabs if I deactivate or uninstall the plugin? =
Deactivating the plugin keeps all of your data in place; tabs simply stop rendering until you reactivate. Uninstalling (deleting the plugin from the Plugins screen) permanently removes the plugin's settings, templates, activity log, and all saved product tab meta – see "Uninstall" above.

== Screenshots ==

1. The Custom Tabs panel inside the WooCommerce product editor, listing a product's tabs with position, slug, and reorder/enable/duplicate actions.
2. The tab editor modal, showing the Quill rich-text content editor alongside the title, slug, position, and Visibility conditions fields.
3. The Global Tabs screen, with a store-wide tab's Product targeting rules set to include specific categories and exclude specific products.
4. The Templates screen, listing saved reusable tab snippets such as a sizing guide and a warranty notice ready to insert into any product.
5. The Portability tab, with JSON export/import and CSV export/import options for settings, Global Tabs, and Templates.
6. The WooCommerce Products list Bulk actions dropdown, applying a saved Kitgenix tab template to several selected products at once.
7. A custom tab rendered on the live WooCommerce product page, alongside the built-in Description and Reviews tabs.
8. The Kitgenix admin interface with the light/dark theme toggle switched to dark mode.

== Credits ==
Built with ❤︎ by @kitgenix - https://kitgenix.com

Includes Quill (https://quilljs.com/), Copyright (c) 2014 Jason Chen, Copyright (c) 2013 salesforce.com, used under the BSD-3-Clause license. Bundled locally in `assets/vendor/quill/`; unmodified from upstream.

== Support Development ==

If this plugin saves you time, please consider making a donation to support its continued development and maintenance:
https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2

== Upgrade Notice ==

= 2.0.0 =
Adds conditional global tabs, per-tab visibility conditions, JSON/CSV import-export, a bulk "apply template" action, and a shortcode. Existing tabs, templates, and settings migrate automatically. The rich-text editor no longer loads from an external CDN.

== Changelog ==

= 2.0.0 (25 August 2026) =

* New: Added Conditional Global Tabs, allowing a global tab to target all products or be restricted by specific products, categories, tags, and/or product types, each with independent Include/Exclude rules. Exclude rules always take precedence, and matching uses the already-loaded product without additional database queries per tab.
* New: Added Tab Visibility Conditions so product-specific and global tabs can be restricted by visitor login state, user role, stock status, and/or purchasability using four independent conditions without introducing a complex rules engine.
* New: Added an Enabled/Disabled toggle to every product tab, global tab, and template row so tabs can be temporarily disabled without deleting or losing their content and configuration.
* New: Added keyboard-accessible Move Up and Move Down controls that swap the actual stored `priority` values of neighbouring tabs, ensuring the management table order always matches frontend rendering order.
* New: Global Tabs and Templates tables now support live search and click-to-sort columns for Tab/Template, Slug, and Position.
* New: Added a Scope column to the Global Tabs table showing whether each tab applies to all products or uses targeted rules, calculated entirely from the tab's saved configuration without live product-count queries.
* New: Added a Portability tab with full-fidelity JSON export/import for settings, Global Tabs, and Templates, including visibility and targeting rules.
* New: JSON imports are versioned and validated and include a client-side preview of the uploaded file before Replace or Merge is committed.
* New: Added CSV export/import for Global Tabs and Templates as a simpler spreadsheet-friendly portability format.
* New: Added WooCommerce Products bulk actions for applying a saved Kitgenix tab template to multiple selected products in one operation.
* New: Added the `[kitgenix_tab]` shortcode for rendering a saved template anywhere WordPress shortcodes are processed, including posts, pages, widgets, and other product tabs.
* New: Added unsaved-change protection to the product editor Custom Tabs panel and the Settings, Global Tabs, Templates, and Portability forms, warning before navigating away with unsaved edits.
* New: Redesigned the entire admin interface around the shared Kitgenix design system with a sticky topbar, cross-plugin navigation, consistent cards, tables, and controls.
* New: Added live in-page settings search with "/" and Cmd/Ctrl+K keyboard shortcuts.
* New: Added a light/dark theme toggle for the admin interface, with the selected preference remembered between visits.
* New: Added dismissible admin notices throughout the plugin's management screens.
* New: Added three developer filters for custom tab behaviour: `kitgenix_custom_tabs_for_woocommerce_tab_eligible`, `kitgenix_custom_tabs_for_woocommerce_tab_priority`, and `kitgenix_custom_tabs_for_woocommerce_tab_content`.
* Improved: Rebuilt the General Settings interface using shared Kitgenix cards, labelled setting rows, and toggle switches instead of the previous WordPress form-table layout.
* Improved: Updated the Kitgenix Hub page to match the shared topbar and admin design system.
* Improved: Added Image Optimizer to the Kitgenix Hub plugin catalogue.
* Improved: Renamed the MultiStore Hub listing to "MultiStore for WooCommerce" and updated its associated plugin slug.
* Improved: Rebuilt the Support, Log, Global Tabs, and Templates screens with the shared card layout, notices, table components, and empty states for a consistent experience across Kitgenix plugins.
* Improved: The Log tab now displays entries in a searchable, paginated table containing time, context, outcome, and a plain-English note instead of a plain text block.
* Improved: Tab-save requests rejected because of missing permissions, expired security tokens, or invalid requests are now recorded in the Log tab rather than failing without a diagnostic trail.
* Improved: JSON/CSV imports and bulk-applied templates are now recorded in the Log for easier auditing and troubleshooting.
* Improved: Added a reference table to the Log tab explaining what each recorded category means and whether administrator action is required, distinguishing routine issues such as stale security tokens from genuine problems.
* Improved: The Support tab is now three focused cards – a donate card with a collapsible monthly-amount picker, a "what your support funds" summary, and a "get involved" panel for reviews and plugin links – replacing the previous stack of donate, trust, and community cards.
* Improved: Moved the Log tab directly before Support in the admin navigation for a more logical workflow.
* Improved: Restyled the Edit Tab modal using the shared Kitgenix modal component and extended it with Visibility Conditions and, for Global Tabs, Product Targeting controls.
* Improved: Updated the WooCommerce product editor Custom Tabs panel to use the Kitgenix brand-blue palette, spacing scale, and border-radius system instead of the previous purple-toned styling.
* Improved: Saved Templates now use their dedicated library limit of 100 entries instead of incorrectly inheriting the per-product `max_tabs` setting, which defaults to 10.
* Performance: Templates are now stored in their own dedicated WordPress option with autoload explicitly disabled instead of being stored inside the main frontend settings option.
* Performance: Moving Templates out of the main settings option prevents large admin-only template libraries from being loaded into WordPress's `alloptions` cache on every frontend request.
* Performance: Template-option autoloading is explicitly disabled regardless of WordPress core version, maintaining the optimisation for versions before the `register_setting()` autoload argument was introduced.
* Performance: Global tab visibility and targeting checks perform no additional database queries per tab per page load, relying on the already-loaded WooCommerce product object and cached WordPress taxonomy lookups through `has_term()`.
* Performance: Existing Support-tab usage statistics continue to be cached for one hour and are only recalculated from admin views, never during frontend page loads.
* Fix: The "Max tabs per product" limit is now enforced once across the combined set of global and product-specific tabs. Previously each group enforced the limit independently, allowing up to twice the configured maximum to render.
* Fix: Modal open/close behaviour now uses the `hidden` attribute instead of a CSS class, preventing visible modal state from becoming out of sync with its underlying accessibility state.
* Fix: Empty-state messaging in Global Tabs and Templates tables now renders as a consistent empty-state component with an icon and message in both server-rendered and JavaScript-rendered states.
* Fix: Number inputs throughout the settings screens are no longer cramped to a fixed 50px width.
* Fix: The Support tab's "Copy plugin link" action now distinguishes successful clipboard copies from failures caused by denied clipboard permissions, unsupported APIs, or insecure contexts instead of always displaying "Copied!".
* Fix: Corrected the Templates screen regression that caused the template library to use the per-product `max_tabs` limit rather than its intended 100-template library limit.
* Security: The Quill rich-text editor is now bundled locally under `assets/vendor/quill/` instead of loading executable JavaScript from `cdn.jsdelivr.net` on product edit and plugin settings screens.
* Security: JSON and CSV imports now pass through the same `Settings::sanitize_tabs_rows()` sanitisation used for manually saved tab data, ensuring imported content is never trusted as pre-sanitised.
* Security: Templates applied through the WooCommerce Products bulk action now pass through the same tab-row sanitisation pipeline as manual saves and imports.
* Security: Every new admin-post handler for JSON/CSV import and export requires the appropriate `manage_woocommerce` or `manage_options` capability and a matching nonce before processing.
* Compatibility: Confirmed compatibility with WordPress 7.1 and corrected the readme header, which previously still reported WordPress 7.0 despite the plugin header already declaring 7.1.
* Compatibility: Verified compatibility with WooCommerce 11.0.1 across the classic product editor, product-tab rendering for simple, variable, grouped, and external products, and the existing HPOS declaration.
* Compatibility: Reviewed WooCommerce 11.0 and 11.0.1 developer advisories and confirmed that changes relating to phone validation hooks, ESLint configuration, the Product Image block, shop-page queried objects, shipping-class taxonomy visibility, reserve-stock duration, and order-item actions do not affect hooks used by this plugin.
* Compatibility: Confirmed the plugin continues to use WooCommerce's supported classic meta-box Product Data editor after WooCommerce 11.0 removed the experimental block-based Product Editor Beta.
* Compatibility: Re-verified the plugin's HPOS (`custom_order_tables`) compatibility declaration. The plugin does not read or modify WooCommerce order data.
* Removed: Removed reliance on the externally hosted Quill editor from `cdn.jsdelivr.net`; the editor library is now included locally with the plugin.
* Documentation: Rewrote the Developers section to document the dedicated Templates option, the complete tab-row structure including `enabled`, `visibility`, and `target`, Portability admin-post actions, the `[kitgenix_tab]` shortcode, WooCommerce bulk action, and new developer filters.
* Documentation: Documented the locally bundled Quill library and its licence under External Services.
* Documentation: Documented WooCommerce's product-search AJAX endpoint under External Services.
* Documentation: Documented that the plugin's Custom Tabs admin interface uses WooCommerce's classic meta-box product editor for merchants evaluating compatibility with alternative product editing interfaces.
* Dev: Added `KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher` as the shared targeting and visibility engine, including `is_eligible()`, `matches_target()`, and `is_visible()`.
* Dev: Added shared `sanitize_target()` and `sanitize_visibility()` sanitisation methods for the new targeting and visibility configuration structures.
* Dev: Extracted the shared Visibility Conditions and Product Targeting modal markup into a `Tab_Modal_Fields` trait used by both `Product_Tabs` and `Settings_UI`, preventing the product-editor and plugin-settings modal implementations from drifting apart.
* Dev: Refreshed and renamed the Kitgenix logo and icon asset library, including light, dark, and primary variants used throughout the admin interface, Kitgenix Hub, and WooCommerce product editor.
