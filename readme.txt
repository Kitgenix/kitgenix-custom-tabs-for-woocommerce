=== Kitgenix Custom Tabs for WooCommerce ===
Contributors: kitgenix
Donate link: https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2
Tags: woocommerce, product tabs, custom product tabs, global tabs, product content
Requires at least: 6.0
Tested up to: 7.4
Requires PHP: 8.2
Stable tag: 2.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create WooCommerce product tabs with per-product content, global targeting, visibility rules, templates and bulk tools.

== Description ==

**Kitgenix Custom Tabs for WooCommerce** is a WooCommerce product tab manager for adding structured information to product pages without editing theme templates. Create product-specific tabs, reusable global tabs and saved content templates for size guides, technical specifications, ingredients, care instructions, warranties, delivery information, FAQs and other product content.

The plugin uses WooCommerce's native product-tab filter, so frontend output remains part of the normal WooCommerce tab system rather than replacing the entire product page with a proprietary layout.

It is suitable for stores that need different content on individual products as well as stores that need the same tab across a product category, tag, product type or carefully targeted group of products.

Developed by [Kitgenix](https://kitgenix.com/).

= WooCommerce Custom Tab Features =

* Add multiple custom tabs to an individual WooCommerce product.
* Create global tabs that can be reused across the catalogue.
* Include or exclude selected products.
* Include or exclude product categories.
* Include or exclude product tags.
* Target simple, variable, grouped or external product types.
* Restrict tab visibility by logged-in/logged-out state.
* Restrict tab visibility by WordPress user role.
* Restrict tabs by stock status and product purchasability.
* Reorder tabs with drag and drop, or with keyboard-accessible Move Up and Move Down controls.
* Set explicit tab priorities and use configurable default priority/step values.
* Enable, disable and duplicate tabs without discarding the original content.
* Choose whether the heading inside the tab panel is displayed.
* Optionally process WordPress shortcodes inside tab content.
* Edit rich content with the locally bundled Quill editor.
* Save reusable content templates.
* Apply a saved template to selected products using WooCommerce bulk actions.
* Render an enabled saved template with the `[kitgenix_tab]` shortcode.
* Export/import full configuration with JSON.
* Export/import core Global Tab or Template fields with CSV.
* Keep a local capped activity log for operational events and diagnostics.
* Declare WooCommerce High-Performance Order Storage compatibility.

= Per-Product Tabs =

Product-specific tabs are managed from the WooCommerce product editing experience. Each product can have its own collection of tab records containing the title, content, slug, priority, enabled state and presentation/visibility settings supported by the plugin.

This is useful for information that is unique to a single item: a model-specific specification, one-off installation notes, an individual warranty statement or product-specific downloadable guidance.

= Global Product Tabs =

Global Tabs let one piece of content appear across many products without copying and maintaining it on every product separately.

A global tab can be left broadly applicable or narrowed with Include and Exclude rules. Targeting dimensions include products, categories, tags and product types. Exclusions take precedence. Where several Include dimensions are populated, the product must satisfy each populated dimension, while selected values within an individual dimension act as alternatives.

That allows combinations such as "show this size guide on variable products in the clothing category except these three products" or "show this compliance note only on products carrying a specific tag."

= Customer and Product Visibility Rules =

Product-specific and global tabs can also be restricted by context. The code supports rules based on login state, WordPress role, stock state and whether the product is purchasable.

These rules can be used for trade-only information, logged-in customer resources, stock-dependent messaging or product instructions that only make sense while an item can be purchased.

= Rich Content and Shortcodes =

The admin editor uses a locally bundled Quill editor, allowing formatted content without loading the editor from a third-party JavaScript CDN. Stored tab content is sanitised through WordPress before output.

Shortcode processing inside tab content is optional. Leaving shortcode processing disabled is useful where stores want plain controlled content; enabling it allows existing WordPress shortcodes to be embedded in tab content.

= Reusable Templates =

Templates provide a separate library for frequently repeated tab content. A template can be inserted while editing tabs, applied to selected products through WooCommerce bulk actions or rendered elsewhere with the template shortcode.

Example:

`[kitgenix_tab slug="warranty-info"]`

The shortcode renders enabled saved templates. It is not a dynamic lookup for a product's private per-product tab or a conditional Global Tab.

= JSON and CSV Import/Export =

JSON is the full-fidelity portability format. It can carry plugin settings, Global Tabs, Templates, targeting conditions and visibility rules and can be used for controlled migration between stores.

CSV is intended for spreadsheet-friendly editing of the core Global Tab or Template fields such as title, nickname, slug, priority, enabled state and content. Targeting and visibility rules are not represented in the CSV format, so JSON should be used when those rules must be preserved.

Upload size is bounded by the plugin to reduce the risk of oversized import requests.

= WooCommerce-Native Output =

Frontend tabs are inserted with WooCommerce's `woocommerce_product_tabs` filter. That means the active WooCommerce-compatible theme continues to control the surrounding tab layout and normal product-page behaviour.

The plugin does not need to read or write WooCommerce orders in order to display product tabs. Product-specific tab data is stored against products, while global configuration and templates use plugin options.

= Typical Store Uses =

* Product size guides shared across a category.
* Warranty or guarantee information.
* Technical datasheets and product specifications.
* Ingredients, allergens or material information.
* Care and maintenance instructions.
* Delivery and collection information.
* Trade-only technical notes for logged-in roles.
* Product FAQs or installation instructions.
* Reusable compliance or safety information.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin to `/wp-content/plugins/`, or install it through Plugins → Add Plugin.
3. Activate **Kitgenix Custom Tabs for WooCommerce**. The plugin prevents activation if WooCommerce is not active.
4. Go to Kitgenix → Custom Tabs to configure settings, Global Tabs, Templates, Portability, Support, and the activity log.
5. To create a product-specific tab, edit a WooCommerce product and open Product data → Custom Tabs.

== Compatibility ==

* WordPress 6.0 or later; tested up to WordPress 7.4.
* PHP 8.2 or later.
* WooCommerce 10.0 or later; tested with WooCommerce 11.2.1.
* Declares WooCommerce HPOS compatibility.
* Uses the native `woocommerce_product_tabs` filter for frontend output.
* Product-specific tabs are stored as product meta; the plugin does not read or write WooCommerce order data.

== External Services ==

The product-tab functionality is self-contained, but two external services can be contacted on specific plugin admin screens.

= WordPress.org Plugin Directory =

When an authorised administrator opens the top-level **Kitgenix Hub**, the plugin uses WordPress's `plugins_api()` function to request public metadata for a fixed list of Kitgenix plugins, including active-install counts, ratings, and available banner/icon artwork. Results are cached for approximately one day. Remote artwork returned by WordPress.org may also be requested by the administrator's browser when displayed.

The plugin does not intentionally send customer, order, product, or tab-content data in these requests. Normal network information such as IP addresses and HTTP request metadata may be visible to WordPress.org.

Service: https://wordpress.org/plugins/
Privacy policy: https://wordpress.org/about/privacy/

= Google Fonts =

The shared Kitgenix wp-admin stylesheet imports the **Inter** and **Manrope** font families from Google Fonts on plugin admin screens where that stylesheet is loaded. The administrator's browser may therefore contact Google's font infrastructure, which can receive normal request metadata such as IP address and browser information.

Service: https://fonts.google.com/
Privacy information: https://developers.google.com/fonts/faq/privacy
Terms: https://policies.google.com/terms

No external service is required to render custom tabs on public WooCommerce product pages.

== Privacy & Security ==

The plugin does not include Kitgenix telemetry or send customer, order, product, or tab content to Kitgenix servers.

Its local activity log stores a timestamp, event context, outcome, short note, and event code. It does not intentionally record IP addresses, passwords, payment data, or customer order information. The log can be cleared from the plugin's Log screen.

Tab HTML is sanitised with WordPress APIs before storage/output. Administrative saves, imports, exports, and other sensitive actions use capability and nonce checks appropriate to their context.

Per-product tabs are stored in `kitgenix_custom_tabs_for_woocommerce_tabs`. Settings/global tabs use `kitgenix_custom_tabs_for_woocommerce_settings`; templates use `kitgenix_custom_tabs_for_woocommerce_templates`; the activity log uses `kitgenix_custom_tabs_for_woocommerce_event_log`.

When the plugin is uninstalled through WordPress, its settings, templates, event log, related transients, and product-tab meta are removed. Multisite uninstall performs the cleanup for each site in the network.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes. WooCommerce is a required dependency, and activation is prevented if WooCommerce is inactive.

= How do I add a custom WooCommerce product tab? =
Edit a product, open Product data → Custom Tabs, add the tab title and content, configure any visibility or priority options, and update the product.

= Can I add one tab to every product? =
Yes. Create a Global Tab and leave its targeting rules empty. You can also narrow a global tab to selected products, categories, tags, or product types.

= Can tabs be shown only to certain customers? =
Yes. Visibility can depend on logged-in/logged-out status, WordPress user roles, stock status, and product purchasability.

= Can I reuse the same content on many products? =
Yes. Save it as a Template, insert it into tabs, apply it to selected products using WooCommerce bulk actions, or display the saved template with `[kitgenix_tab]`.

= Does it support rich text and shortcodes? =
Yes. The admin editor uses a locally bundled Quill editor. WordPress shortcodes in tab content can be processed when Allow Shortcodes is enabled.

= Can I export tabs to another store? =
Yes. JSON preserves settings, global tabs, templates, targeting, and visibility rules. CSV is available for the basic Global Tabs or Templates fields.

= Is it HPOS compatible? =
Yes. The plugin declares compatibility with WooCommerce custom order tables and does not depend on WooCommerce order storage for its product tabs.

== Screenshots ==

1. Product Data → Custom Tabs for product-specific tab management.
2. Rich tab editor with title, content, slug, priority, heading, and visibility controls.
3. Global Tabs with Include/Exclude targeting rules.
4. Reusable Templates for common WooCommerce product information.
5. Portability tools for JSON and CSV import/export.
6. WooCommerce Products bulk actions for applying saved templates.
7. A custom tab displayed in WooCommerce's frontend product-tab area.
8. Kitgenix admin interface and activity log.

== Bundled Library & Source Code ==

The plugin bundles **Quill Editor 1.3.7** locally for rich-text editing in wp-admin (`assets/vendor/quill/quill.min.js` and `quill.snow.css`). It is not loaded from a third-party JavaScript CDN.

Quill 1.3.7 source code: https://github.com/slab/quill/tree/1.3.7
Quill project: https://quilljs.com/
License: BSD-3-Clause
Copyright (c) 2014 Jason Chen; Copyright (c) 2013 salesforce.com.

== Support ==

Plugin information and documentation:
https://kitgenix.com/plugins/kitgenix-custom-tabs-for-woocommerce/

For WordPress.org installations, support is also available through the plugin's WordPress.org support forum.

== Support Development ==

Kitgenix Custom Tabs for WooCommerce is free software. If it helps your store, you can support continued development with a voluntary donation:
https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2

== Upgrade Notice ==

= 2.2.0 =
Adds drag-and-drop tab ordering and security hardening for CSV exports. Recommended for all sites.

== Changelog ==

= 2.2.0 (30 October 2026) =

* New: Drag-and-drop tab ordering. Rearrange product tabs, Global Tabs and Templates by dragging the handle beside each tab name; the keyboard-accessible Move Up and Move Down controls remain available. Dropping a tab redistributes the existing position values in the new order, so the admin table always matches the frontend tab order.
* Fix: Global tab content no longer loads on every request on the whole site. Global tabs previously lived inside the main settings option, which WordPress autoloads on every page load, so every global tab's full HTML content was pulled into memory on every single request regardless of whether that page needed it. Global tabs now live in their own, non-autoloaded option, the same way reusable templates already do. Existing global tabs are migrated across automatically the next time the plugin runs; no setup is needed.
* New: Added an "Also match products in sub-categories" option to each category rule in a global tab's product targeting (Settings → Global Tabs → Product targeting). Previously, a rule that included or excluded a parent category never matched products filed only in a child category. Turning this on makes the rule follow the category tree. It defaults on for a newly added category rule and off for every category rule saved before this release, so no existing global tab's matching changes on its own.
* Security: CSV exports of global tabs and templates now neutralise spreadsheet formula injection by prefixing any value that starts with `=`, `+`, `-`, `@`, a tab or a carriage return with an apostrophe, so tab content cannot run as a formula when an export is opened in Excel, Google Sheets or similar software.
* Improved: CSV imports automatically remove that protective apostrophe again, so files exported by 2.2.0 import back unchanged.
* Improved: Raised the minimum PHP version to 8.2 (`Requires PHP: 8.2`).
* Improved: Tested up to WordPress 7.4 and WooCommerce 11.2.1, and reviewed for compatibility with PHP 8.2 to 8.5. The full codebase passes syntax checks on PHP 8.5 and a PHPCompatibility scan for PHP 8.2–8.5, and uses no dynamic properties or functions deprecated in PHP 8.2–8.5.
* Improved: Declared compatibility with the WooCommerce Cart and Checkout blocks (`cart_checkout_blocks`), so WooCommerce no longer lists the plugin as possibly incompatible in the Checkout block editor.
* Fix: The CSV `$escape` argument is now passed explicitly in the global tab CSV export and import, preventing "the $escape parameter must be provided" deprecation notices on PHP 8.4 and later. The CSV format is unchanged.
* Dev: Corrected a WordPress coding-standards (PHPCS) escaping annotation on the Activity Log's inline log-retention form; `wp_nonce_field()` output is already escaped by WordPress core, so this is a no-op code-quality fix.
* Dev: Removed the PluginScore verification code from the readme, as Kitgenix Plugin Score has been discontinued.
* Dev: Bumped the version to 2.2.0 across the plugin.
* Documentation: Regenerated the `.pot` translation template for 2.2.0.
* Documentation: The readme changelog now lists only the current release. The full release history is in `CHANGELOG.md`.

Full release history is available in the plugin's `CHANGELOG.md` file.
