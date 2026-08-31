=== Kitgenix Custom Tabs for WooCommerce ===
Contributors: kitgenix
Donate link: https://www.paypal.com/donate/?hosted_button_id=KALF36K6JJ9B2
Tags: woocommerce, product tabs, custom product tabs, global tabs, product content
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 2.0.0
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

* WordPress 6.0 or later; tested up to WordPress 7.1.
* PHP 8.1 or later.
* WooCommerce 10.0 or later; tested with WooCommerce 11.0.1.
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

= 2.0.0 =
Adds conditional global tabs, visibility rules, reusable-template improvements, JSON/CSV portability, bulk template application, shortcode output, and the redesigned Kitgenix admin interface.

== Changelog ==

= 2.0.0 (31 August 2026) =

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
