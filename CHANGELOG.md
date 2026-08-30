# Changelog

## 2.0.0 – 22 August 2026

- **New:** Added Conditional Global Tabs, allowing a global tab to target all products or be restricted by specific products, categories, tags, and/or product types, each with independent Include/Exclude rules. Exclude rules always take precedence, and matching uses the already-loaded product without additional database queries per tab.
- **New:** Added Tab Visibility Conditions so product-specific and global tabs can be restricted by visitor login state, user role, stock status, and/or purchasability using four independent conditions without introducing a complex rules engine.
- **New:** Added an Enabled/Disabled toggle to every product tab, global tab, and template row so tabs can be temporarily disabled without deleting or losing their content and configuration.
- **New:** Added keyboard-accessible Move Up and Move Down controls that swap the actual stored `priority` values of neighbouring tabs, ensuring the management table order always matches frontend rendering order.
- **New:** Global Tabs and Templates tables now support live search and click-to-sort columns for Tab/Template, Slug, and Position.
- **New:** Added a Scope column to the Global Tabs table showing whether each tab applies to all products or uses targeted rules, calculated entirely from the tab's saved configuration without live product-count queries.
- **New:** Added a Portability tab with full-fidelity JSON export/import for settings, Global Tabs, and Templates, including visibility and targeting rules.
- **New:** JSON imports are versioned and validated and include a client-side preview of the uploaded file before Replace or Merge is committed.
- **New:** Added CSV export/import for Global Tabs and Templates as a simpler spreadsheet-friendly portability format.
- **New:** Added WooCommerce Products bulk actions for applying a saved Kitgenix tab template to multiple selected products in one operation.
- **New:** Added the `[kitgenix_tab]` shortcode for rendering a saved template anywhere WordPress shortcodes are processed, including posts, pages, widgets, and other product tabs.
- **New:** Added unsaved-change protection to the product editor Custom Tabs panel and the Settings, Global Tabs, Templates, and Portability forms, warning before navigating away with unsaved edits.
- **New:** Redesigned the entire admin interface around the shared Kitgenix design system with a sticky topbar, cross-plugin navigation, consistent cards, tables, and controls.
- **New:** Added live in-page settings search with "/" and Cmd/Ctrl+K keyboard shortcuts.
- **New:** Added a light/dark theme toggle for the admin interface, with the selected preference remembered between visits.
- **New:** Added dismissible admin notices throughout the plugin's management screens.
- **New:** Added three developer filters for custom tab behaviour: `kitgenix_custom_tabs_for_woocommerce_tab_eligible`, `kitgenix_custom_tabs_for_woocommerce_tab_priority`, and `kitgenix_custom_tabs_for_woocommerce_tab_content`.
- **Improved:** Rebuilt the General Settings interface using shared Kitgenix cards, labelled setting rows, and toggle switches instead of the previous WordPress form-table layout.
- **Improved:** Updated the Kitgenix Hub page to match the shared topbar and admin design system.
- **Improved:** Added Image Optimizer to the Kitgenix Hub plugin catalogue.
- **Improved:** Renamed the MultiStore Hub listing to "MultiStore for WooCommerce" and updated its associated plugin slug.
- **Improved:** Rebuilt the Support, Log, Global Tabs, and Templates screens with the shared card layout, notices, table components, and empty states for a consistent experience across Kitgenix plugins.
- **Improved:** The Log tab now displays entries in a searchable, paginated table containing time, context, outcome, and a plain-English note instead of a plain text block.
- **Improved:** Tab-save requests rejected because of missing permissions, expired security tokens, or invalid requests are now recorded in the Log tab rather than failing without a diagnostic trail.
- **Improved:** JSON/CSV imports and bulk-applied templates are now recorded in the Log for easier auditing and troubleshooting.
- **Improved:** Added a reference table to the Log tab explaining what each recorded category means and whether administrator action is required, distinguishing routine issues such as stale security tokens from genuine problems.
- **Improved:** The Support tab is now three focused cards – a donate card with a collapsible monthly-amount picker, a "what your support funds" summary, and a "get involved" panel for reviews and plugin links – replacing the previous stack of donate, trust, and community cards.
- **Improved:** Moved the Log tab directly before Support in the admin navigation for a more logical workflow.
- **Improved:** Restyled the Edit Tab modal using the shared Kitgenix modal component and extended it with Visibility Conditions and, for Global Tabs, Product Targeting controls.
- **Improved:** Updated the WooCommerce product editor Custom Tabs panel to use the Kitgenix brand-blue palette, spacing scale, and border-radius system instead of the previous purple-toned styling.
- **Improved:** Saved Templates now use their dedicated library limit of 100 entries instead of incorrectly inheriting the per-product `max_tabs` setting, which defaults to 10.
- **Performance:** Templates are now stored in their own dedicated WordPress option with autoload explicitly disabled instead of being stored inside the main frontend settings option.
- **Performance:** Moving Templates out of the main settings option prevents large admin-only template libraries from being loaded into WordPress's `alloptions` cache on every frontend request.
- **Performance:** Template-option autoloading is explicitly disabled regardless of WordPress core version, maintaining the optimisation for versions before the `register_setting()` autoload argument was introduced.
- **Performance:** Global tab visibility and targeting checks perform no additional database queries per tab per page load, relying on the already-loaded WooCommerce product object and cached WordPress taxonomy lookups through `has_term()`.
- **Performance:** Existing Support-tab usage statistics continue to be cached for one hour and are only recalculated from admin views, never during frontend page loads.
- **Fix:** The "Max tabs per product" limit is now enforced once across the combined set of global and product-specific tabs. Previously each group enforced the limit independently, allowing up to twice the configured maximum to render.
- **Fix:** Modal open/close behaviour now uses the `hidden` attribute instead of a CSS class, preventing visible modal state from becoming out of sync with its underlying accessibility state.
- **Fix:** Empty-state messaging in Global Tabs and Templates tables now renders as a consistent empty-state component with an icon and message in both server-rendered and JavaScript-rendered states.
- **Fix:** Number inputs throughout the settings screens are no longer cramped to a fixed 50px width.
- **Fix:** The Support tab's "Copy plugin link" action now distinguishes successful clipboard copies from failures caused by denied clipboard permissions, unsupported APIs, or insecure contexts instead of always displaying "Copied!".
- **Fix:** Corrected the Templates screen regression that caused the template library to use the per-product `max_tabs` limit rather than its intended 100-template library limit.
- **Security:** The Quill rich-text editor is now bundled locally under `assets/vendor/quill/` instead of loading executable JavaScript from `cdn.jsdelivr.net` on product edit and plugin settings screens.
- **Security:** JSON and CSV imports now pass through the same `Settings::sanitize_tabs_rows()` sanitisation used for manually saved tab data, ensuring imported content is never trusted as pre-sanitised.
- **Security:** Templates applied through the WooCommerce Products bulk action now pass through the same tab-row sanitisation pipeline as manual saves and imports.
- **Security:** Every new admin-post handler for JSON/CSV import and export requires the appropriate `manage_woocommerce` or `manage_options` capability and a matching nonce before processing.
- **Compatibility:** Confirmed compatibility with WordPress 7.1 and corrected the readme header, which previously still reported WordPress 7.0 despite the plugin header already declaring 7.1.
- **Compatibility:** Verified compatibility with WooCommerce 11.0.1 across the classic product editor, product-tab rendering for simple, variable, grouped, and external products, and the existing HPOS declaration.
- **Compatibility:** Reviewed WooCommerce 11.0 and 11.0.1 developer advisories and confirmed that changes relating to phone validation hooks, ESLint configuration, the Product Image block, shop-page queried objects, shipping-class taxonomy visibility, reserve-stock duration, and order-item actions do not affect hooks used by this plugin.
- **Compatibility:** Confirmed the plugin continues to use WooCommerce's supported classic meta-box Product Data editor after WooCommerce 11.0 removed the experimental block-based Product Editor Beta.
- **Compatibility:** Re-verified the plugin's HPOS (`custom_order_tables`) compatibility declaration. The plugin does not read or modify WooCommerce order data.
- **Removed:** Removed reliance on the externally hosted Quill editor from `cdn.jsdelivr.net`; the editor library is now included locally with the plugin.
- **Dev:** Added `KitgenixCustomTabsForWooCommerce\Core\Tab_Matcher` as the shared targeting and visibility engine, including `is_eligible()`, `matches_target()`, and `is_visible()`.
- **Dev:** Added shared `sanitize_target()` and `sanitize_visibility()` sanitisation methods for the new targeting and visibility configuration structures.
- **Dev:** Extracted the shared Visibility Conditions and Product Targeting modal markup into a `Tab_Modal_Fields` trait used by both `Product_Tabs` and `Settings_UI`, preventing the product-editor and plugin-settings modal implementations from drifting apart.
- **Dev:** Refreshed and renamed the Kitgenix logo and icon asset library, including light, dark, and primary variants used throughout the admin interface, Kitgenix Hub, and WooCommerce product editor.
- **Documentation:** Rewrote the Developers section to document the dedicated Templates option, the complete tab-row structure including `enabled`, `visibility`, and `target`, Portability admin-post actions, the `[kitgenix_tab]` shortcode, WooCommerce bulk action, and new developer filters.
- **Documentation:** Documented the locally bundled Quill library and its licence under External Services.
- **Documentation:** Documented WooCommerce's product-search AJAX endpoint under External Services.
- **Documentation:** Documented that the plugin's Custom Tabs admin interface uses WooCommerce's classic meta-box product editor for merchants evaluating compatibility with alternative product editing interfaces.

## 1.1.3 – 26 May 2026

- **New:** Added a Log tab to the admin settings page. Records recent plugin activity (settings saves and key operations) with timestamps, context labels, and plain-English notes to aid troubleshooting.
- **Fix:** Activity log data is now fully cleaned up when the plugin is uninstalled.
- **Compatibility:** Confirmed compatibility with WordPress 7.0 and WooCommerce 10.x.

## 1.1.2 – 26 May 2026

- **Dev:** Skipped to be in line with other Kitgenix Plugins.

## 1.1.1 – 26 May 2026

- **Dev:** Skipped to be in line with other Kitgenix Plugins.

## 1.1.0 – 7 May 2026

- **New:** Added a dedicated Templates screen for saving reusable tab snippets such as sizing guides, warranty text, ingredient lists, and care instructions.
- **New:** Added one-click template insertion inside both the WooCommerce product editor and the Global Tabs screen.
- **New:** Added Duplicate actions for product tabs and global tabs so merchants can clone an existing tab and edit the copy.
- **Improvement:** Product editors now link directly to the Templates screen from the Custom Tabs panel for faster merchandising workflows.
- **Improvement:** Saved templates now use their own library limit instead of being capped by the per-product tab limit.

## 1.0.0 – 7 April 2026

- **New:** Initial release.
- **New:** Product-specific custom tabs added via Products → Edit → Product Data → Custom Tabs.
- **New:** Global tabs that can be applied to every product via Kitgenix → Custom Tabs → Global Tabs.
- **New:** Added tab fields for title, optional nickname, optional slug, position/priority, and rich content.
- **New:** Added optional shortcode processing for tab content.
- **New:** Added settings to control the maximum number of tabs per product, default positioning (base + step), and whether to hide the tab heading inside the tab.
- **New:** Added an admin interface with a fast modal rich-text editor workflow.
- **UI:** Improved the Kitgenix admin header layout and social icon links with compact icon buttons across settings and the Kitgenix Hub.
- **UI:** Admin tables inside Kitgenix pages now use Kitgenix styling for a more consistent branded look.
- **Fix:** Admin notices now display above the Kitgenix header using the standard WordPress notice area.
- **Fix:** Added defensive notice normalization to prevent notices being relocated into the header by other scripts.
- **Fix:** Added spacing between adjacent action links/buttons such as Edit/Delete.
- **Fix:** Replaced the expensive Support-tab usage query with cached usage statistics and cache invalidation on save/delete.
- **Fix:** Escaped shared Kitgenix Hub card media output for WordPress coding standards compliance.
- **Security:** Admin actions are protected with capability checks and nonces.
- **Cleanup:** Removes plugin settings and all saved product tab metadata on uninstall.
- **Maintenance:** Updated the plugin Author URI to the public Kitgenix WordPress.org profile and replaced the old custom admin-menu icon CSS with the native Dashicons icon.