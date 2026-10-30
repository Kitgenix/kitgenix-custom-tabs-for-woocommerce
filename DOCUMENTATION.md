# Kitgenix Custom Tabs for WooCommerce

Technical reference for developers maintaining this plugin. All facts below were verified against the actual source in this repository (no assumptions) as of plugin version **2.2.0**.

## Overview

- **Plugin Name:** Kitgenix Custom Tabs for WooCommerce
- **Main file:** `kitgenix-custom-tabs-for-woocommerce.php`
- **Purpose (from the plugin header docblock):** "Add custom WooCommerce product tabs with per-product content, global tabs, and lightweight controls."
- **Version:** `2.2.0` (defined both in the plugin header `Version:` field and the `KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_VERSION` constant), matching `readme.txt`'s `Stable tag:`.
- **Requires at least (WordPress):** 6.0
- **Tested up to (WordPress):** 7.4
- **Requires PHP:** 8.2
- **WooCommerce required at least:** 10.0
- **WooCommerce tested up to:** 11.2.1
- **Requires Plugins header:** `woocommerce` (WordPress will refuse activation via the native "Requires Plugins" mechanism, and the plugin also self-enforces this in its own activation hook 2.2.0 – 30 October 2026 see Security/Uninstall section)
- **License:** GPLv3 or later
- **Text Domain:** `kitgenix-custom-tabs-for-woocommerce`
- **Domain Path:** `/languages`
- **Author:** Kitgenix (https://kitgenix.com/)

The plugin is a WooCommerce product-tab manager. It lets store admins add per-product custom tabs (via a "Custom Tabs" panel inside the WooCommerce Product Data metabox) and/or catalogue-wide "Global Tabs" with optional per-product/category/tag/product-type targeting and visitor-based visibility rules. It also offers a reusable Templates library, JSON/CSV import-export ("Portability"), a WooCommerce Products bulk action to apply a template to many products at once, a `[kitgenix_tab]` shortcode, and a capped local activity log. All frontend output goes through WooCommerce's native `woocommerce_product_tabs` filter, so the plugin does not replace or fork the WooCommerce single-product template.

## Architecture

### Folder / file layout

```
kitgenix-custom-tabs-for-woocommerce.php   Bootstrap file: constants, autoloader, Kitgenix Hub admin page, activation/deactivation hooks
uninstall.php                              WP_UNINSTALL_PLUGIN cleanup handler (options, transients, product postmeta)
includes/
  Plugin.php                               KitgenixCustomTabsForWooCommerce\Plugin – central init() bootstrapper
  Core/
    Settings.php                           Options schema, defaults, sanitizers, get/set helpers (KitgenixCustomTabsForWooCommerce\Core\Settings)
    Tab_Matcher.php                        Targeting ("target") + visibility matching engine for tab rows (Core\Tab_Matcher)
    Event_Log.php                          Capped activity log with age-based pruning and a daily WP-Cron hook (Core\Event_Log)
  Admin/
    Product_Tabs.php                       Product Data "Custom Tabs" panel: render, save, enqueue (Admin\Product_Tabs)
    Settings_UI.php                        Kitgenix → Custom Tabs admin screen: Settings/Templates/Global Tabs/Portability/Support/Log tabs (Admin\Settings_UI)
    Tab_Modal_Fields.php                   Trait: shared Visibility Conditions + Product Targeting modal markup, reused by Product_Tabs and Settings_UI (Admin\Tab_Modal_Fields)
    Bulk_Tools.php                         WooCommerce Products list bulk action "Add Kitgenix tab: <template>" (Admin\Bulk_Tools)
    Portability.php                        JSON/CSV export & import admin-post handlers (Admin\Portability)
  Frontend/
    Tabs.php                               woocommerce_product_tabs filter: injects global + product tabs, priority/eligibility resolution (Frontend\Tabs)
    Shortcode.php                          [kitgenix_tab] shortcode renderer, sourced from the Templates library (Frontend\Shortcode)
assets/
  css/admin.css                            Plugin-specific admin screen CSS (layered on top of the shared design system)
  css/kitgenix-admin-ui.css                Shared cross-plugin Kitgenix admin design system (topbar, cards, tables, modal, badges, etc.)
  css/kitgenix-hub.css                     Styling for the "Kitgenix Hub" top-level plugin-discovery page
  css/product-edit.css                     Styling scoped to the WooCommerce product-editor "Custom Tabs" panel and its modal
  js/admin.js                              Tab manager logic: modal, Quill editor wiring, add/edit/remove/duplicate/reorder/templates, import preview
  js/kitgenix-admin-components.js          Shared component controller: toasts, modal open/close, collapsibles, copy-to-clipboard, table search/pagination
  js/kitgenix-admin-tabs.js                Shared topbar/tab-panel controller: tab switching, search, theme toggle, mobile menu, sticky offset
  vendor/quill/quill.min.js                Bundled Quill 1.3.7 rich-text editor (BSD-3-Clause), no longer loaded from a CDN
  vendor/quill/quill.snow.css              Quill's "Snow" theme stylesheet
  images/...                               Logos, social icons, WordPress.org plugin banners/icons/screenshots (static assets only, no code)
languages/
  kitgenix-custom-tabs-for-woocommerce.pot Translation template; textdomain `kitgenix-custom-tabs-for-woocommerce`. No .po/.mo files are bundled.
readme.txt                                 WordPress.org plugin readme (description, FAQ, external-services disclosure, changelog for the current release only)
CHANGELOG.md                               Full release history (all versions)
```

### Namespace / autoloading

Root namespace: `KitgenixCustomTabsForWooCommerce`. The main plugin file registers a simple hand-rolled PSR-4-style autoloader via `spl_autoload_register()`:

```php
$prefix = 'KitgenixCustomTabsForWooCommerce\\';
// KitgenixCustomTabsForWooCommerce\Admin\Product_Tabs
// → includes/Admin/Product_Tabs.php  (relative to KITGENIX_CUSTOM_TABS_FOR_WOOCOMMERCE_DIR)
```

There is no Composer `vendor/autoload.php` 2.2.0 – 30 October 2026 this is the plugin's only autoload mechanism. The namespace split mirrors the folder layout exactly:

| Namespace | Folder | Responsibility |
|---|---|---|
| `KitgenixCustomTabsForWooCommerce\Core` | `includes/Core/` | Framework-agnostic data model: settings schema, sanitization, matching logic, logging. No direct HTML output. |
| `KitgenixCustomTabsForWooCommerce\Admin` | `includes/Admin/` | wp-admin screens, metaboxes, admin-post handlers, bulk actions. Depends on `Core`. |
| `KitgenixCustomTabsForWooCommerce\Frontend` | `includes/Frontend/` | Public-facing output: the `woocommerce_product_tabs` filter callback and the `[kitgenix_tab]` shortcode. Depends on `Core`. |
| `KitgenixCustomTabsForWooCommerce` (root) | `includes/Plugin.php` | Central bootstrapper that wires `Core`/`Admin`/`Frontend` together. |

### Bootstrap sequence

1. `kitgenix-custom-tabs-for-woocommerce.php` loads unconditionally on every request (it is the plugin's entry point).
   - Registers `admin_enqueue_scripts` (priority 5) to register the shared `kitgenix-custom-tabs-for-woocommerce-admin-ui` style.
   - Registers `admin_menu` (priority 5) to create the shared top-level **Kitgenix** admin menu page (`kitgenix_custom_tabs_for_woocommerce_ensure_admin_menu()`), guarded so it is only added once even if several Kitgenix plugins are active (checks `$admin_page_hooks['kitgenix']`).
   - Registers `admin_enqueue_scripts` (priority 20) to enqueue Hub-specific CSS (`kitgenix-hub.css`) plus WordPress's Thickbox/`plugin-install` assets, only on the `kitgenix` admin page.
   - Defines `kitgenix_render_admin_page()` 2.2.0 – 30 October 2026 renders the **Kitgenix Hub**, a grid of all known Kitgenix plugins (Turnstile CAPTCHA, this plugin, Document Manager, Order Tracking, PDF Invoicing, Stock Sync, MultiStore, Image Optimizer, Affiliate Link Manager) with Install/Activate/Open/Details/Review/Support-Forum actions per card, using `plugins_api()` to fetch WordPress.org active-install counts, ratings, and banner/icon artwork (cached ~1 day via transients).
   - `register_activation_hook()`: if `WooCommerce` class doesn't exist, deactivates itself and `wp_die()`s with an explanatory message; otherwise sets a 30-second `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect` transient.
   - `admin_init`: if that transient is set (and it wasn't a bulk activation), redirects once to the plugin's own settings page, then deletes the transient.
   - `register_deactivation_hook()`: deletes the activation-redirect transient and clears the scheduled `kitgenix_custom_tabs_for_woocommerce_prune_event_log` cron hook via `wp_clear_scheduled_hook()`.
   - `plugin_action_links_{plugin_basename}` filter adds a "Settings" link on the Plugins list screen.
   - Registers the class autoloader.
   - `before_woocommerce_init`: declares HPOS (`custom_order_tables`) compatibility via `FeaturesUtil::declare_compatibility()`.
   - `plugins_loaded`: if `WooCommerce` is active, calls `Plugin::init()`.
2. `Plugin::init()` (`includes/Plugin.php`):
   - `Settings::ensure_defaults()` 2.2.0 – 30 October 2026 seeds the options and runs the one-time templates-option migration.
   - `admin_init` → `Settings::register_settings()`.
   - `admin_init` → `Event_Log::maybe_schedule_pruning()`.
   - Registers `Event_Log::get_cron_hook()` (`kitgenix_custom_tabs_for_woocommerce_prune_event_log`) → `Event_Log::prune()`.
   - If `is_admin()`: `Settings_UI::init()`, `Product_Tabs::init()`, `Portability::init()`, `Bulk_Tools::init()`.
   - Always: `Shortcode::init()` and `Tabs::init()` (frontend tab injection and the shortcode work regardless of admin context, since `is_admin()` is false on AJAX-from-frontend edge cases are not relevant here but both are safe to register unconditionally).
   - A code comment notes that explicit `load_plugin_textdomain()` is intentionally **not** called 2.2.0 – 30 October 2026 see Internationalization.

### Key classes and responsibilities

| Class | Responsibility |
|---|---|
| `Plugin` | Central bootstrapper; the only class instantiating/init'ing the others. |
| `Core\Settings` | Owns option names/schema, `defaults()`, per-row sanitization (`sanitize_tabs_rows()`), settings registration via the Settings API, template-option migration/autoload management, and small typed getters (`enabled()`, `max_tabs()`, `priority_base()`, etc.). |
| `Core\Tab_Matcher` | Pure logic: sanitizes and evaluates a tab row's `target` (global-tab-only product/category/tag/type include-exclude rules) and `visibility` (auth/role/stock/purchasable) sub-structures against a `WC_Product` and the current visitor, with no additional DB queries per check. |
| `Core\Event_Log` | Append-only capped (100 entries) activity log stored in a single option, with age-based retention (7/14/30 days, default 30) pruned both on write and via a daily cron hook; also maps structured "codes" to human-readable category/note pairs for the Log admin tab. |
| `Admin\Product_Tabs` | Adds the "Custom Tabs" tab+panel to the WooCommerce Product Data metabox, renders per-product tab rows and their hidden fields, enqueues Quill + admin JS/CSS on the product editor, and saves submitted tab rows on `woocommerce_process_product_meta`. |
| `Admin\Settings_UI` | Renders the full `Kitgenix → Custom Tabs` admin page (Settings / Templates / Global Tabs / Portability / Support / Log tabs), including the shared modal markup, admin notices, and admin-post handlers for clearing the log / saving log retention. |
| `Admin\Tab_Modal_Fields` (trait) | Shared HTML for the "Visibility conditions" block (used by both product tabs and global tabs) and the "Product targeting" block (global tabs only), so `Product_Tabs` and `Settings_UI` can't drift apart. |
| `Admin\Bulk_Tools` | Hooks WooCommerce's Products list bulk-actions UI to add one "Add Kitgenix tab: <template title>" action per enabled template, and applies that template's row to every selected product on submit. |
| `Admin\Portability` | `admin-post.php` handlers for full-fidelity JSON export/import and flattened CSV export/import of Global Tabs / Templates. |
| `Frontend\Tabs` | Hooks `woocommerce_product_tabs` (priority 20); merges global tabs + product-specific postmeta tabs into WooCommerce's tabs array, applying the combined max-tabs cap, eligibility checks, priority resolution, and three developer filters. |
| `Frontend\Shortcode` | Registers `[kitgenix_tab]`; looks up a saved Template by slug or title and renders its content (with shortcode-recursion depth guard). |

## Features

### Per-product ("Custom") tabs

Each WooCommerce product can carry its own list of tab rows, edited from **Product data → Custom Tabs** in the classic product editor (`Admin\Product_Tabs`). Each row has: `title`, `nickname` (optional internal label shown in the admin table in place of the title), `content` (rich HTML), `slug` (used to build a stable, de-duplicated tab array key), `priority` (an integer controlling WooCommerce tab ordering), `enabled` (1/0 2.2.0 – 30 October 2026 disabled tabs are kept in storage but never rendered), `hide_title` (per-row override to suppress the `<h2>` heading WooCommerce normally prints inside the tab panel), and `visibility` (see below). Product tabs do **not** carry a `target` field 2.2.0 – 30 October 2026 targeting rules only apply to Global Tabs, since a product tab is already scoped to one product.

The admin table for a product's tabs supports: Add new tab (opens a shared modal), Edit, Duplicate (clones the row, appends "(Copy)" to nickname/title and "-copy" to the slug), Enable/Disable toggle, Remove (with a confirm dialog), and keyboard-accessible Move Up/Move Down buttons. Moving a row **swaps the stored `priority` value** with its immediate neighbor (sorted by priority, not DOM order) rather than reindexing the whole list 2.2.0 – 30 October 2026 this keeps the visible admin table order always consistent with the actual WooCommerce frontend render order, since render order is driven purely by `priority`.

Rows can also be reordered by **drag and drop** using the handle (`data-kitgenix-custom-tabs-for-woocommerce-drag-handle`) beside each tab name. It uses pointer events (mouse, pen and touch) and is `aria-hidden`, since the Move Up/Move Down buttons are the accessible path. Starting a drag first clears any click-to-sort column order and restores priority order. On drop, the rows' existing `priority` values are sorted and reassigned in the new visual order (forced strictly increasing to break ties), so tabs keep the same slots relative to WooCommerce's default tabs. The same handle is used in the Global Tabs and Templates tables.

### Global Tabs

Configured under **Kitgenix → Custom Tabs → Global Tabs**, stored inside the main settings option (`global_tabs` key). A global tab has the same base row shape as a product tab, plus a `target` structure (see **WooCommerce Integration → Conditional targeting** below) that can restrict it to specific products, categories, tags, and/or product types. Global tabs render on **every product** by default (empty/no-restriction target) and are added *before* the product's own tabs on each product page (see `Frontend\Tabs::inject_tabs()` 2.2.0 – 30 October 2026 global tabs are looped first, then product-specific tabs).

The Global Tabs admin table adds a **Scope** column (`Admin\Settings_UI::describe_target_scope()`) summarizing each tab's targeting as "All products" or e.g. "Targeted: excl. 2 categories, 3 tags" 2.2.0 – 30 October 2026 computed entirely from the row's already-saved `target` data with zero live product-count queries. The table also supports live client-side search (`data-kitgenix-table-search`) and click-to-sort columns for Tab/Slug/Position (`Admin\admin.js` → `bindSortableTable()`), both of which only reorder/filter the *visible* `<tr>` rows 2.2.0 – 30 October 2026 they never touch the underlying stored data or its order.

### Templates (reusable content library)

A separate, dedicated library (**Kitgenix → Custom Tabs → Templates**) of up to 100 reusable tab snippets, stored in their own WordPress option (`kitgenix_custom_tabs_for_woocommerce_templates`) rather than inside the main settings option. Templates carry the base row fields (title/nickname/content/slug/priority/enabled/hide_title) but **no** `visibility` or `target` 2.2.0 – 30 October 2026 a template is an inert, reusable piece of content, not a live placement rule. Templates can be:

1. **Inserted** into a product tab from the admin modal's "Template library" toolbar (creates an editable *copy* 2.2.0 – 30 October 2026 editing the inserted tab never changes the saved template). The equivalent toolbar is also present on the Global Tabs screen, but it is currently non-functional there 2.2.0 – 30 October 2026 see **Known Limitations**.
2. **Applied in bulk** to many products at once via the WooCommerce Products list bulk-actions dropdown (see `Admin\Bulk_Tools`).
3. **Rendered directly** anywhere WordPress shortcodes run, via `[kitgenix_tab slug="…"]` (see `Frontend\Shortcode`).

### Rich content editor

The admin tab-edit modal uses the **Quill 1.3.7** rich-text editor (`assets/vendor/quill/quill.min.js` + `quill.snow.css`), bundled locally rather than pulled from a CDN (explicitly called out as a 2.0.0 security change in the changelog 2.2.0 – 30 October 2026 previously loaded from `cdn.jsdelivr.net`). `assets/js/admin.js` lazily builds the Quill toolbar/editor DOM inside the modal's editor slot (`ensureModalEditorMarkup()`), instantiates `new Quill(editorEl, {theme:'snow', modules:{toolbar: toolbar}})`, and mirrors the editor's `text-change` event into a hidden `<textarea>` that is the actual form field submitted to the server (`normalizeHtml()` collapses an empty `<p><br></p>` back to `''`). Toolbar options exposed: header (H2/H3), bold/italic/underline/strike, ordered/bullet lists, link, and a "clean formatting" button.

Server-side, all tab content passes through `wp_kses_post()` during sanitization (`Core\Settings::sanitize_tabs_rows()`) before it is ever stored, and again through `wp_kses_post( wpautop( $content ) )` at render time (`Frontend\Tabs::render_tab()` and `Frontend\Shortcode::render()`).

### Conditional targeting and visitor visibility (2.0.0+)

Two independent, composable rule systems, both implemented in `Core\Tab_Matcher`:

- **`target`** (Global Tabs only): four dimensions 2.2.0 – 30 October 2026 `products`, `categories`, `tags`, `types` (`simple`/`variable`/`grouped`/`external`) 2.2.0 – 30 October 2026 each with an `include` and `exclude` ID/key list. **Exclude always wins** and is checked first, independent of any include rule. A populated `include` list on a dimension restricts to that list (OR within the dimension); dimensions left empty impose no restriction; when several dimensions are populated the product must satisfy *all* of them (AND across dimensions). Category/tag matching uses `has_term()` (cached taxonomy lookup, no extra query); product-type matching uses `$product->get_type()`; product-ID matching is a simple `in_array()`. No database queries beyond what's already needed to load the product.
- **`visibility`** (product tabs and global tabs): four independent conditions 2.2.0 – 30 October 2026 `auth` (`logged_in`/`logged_out`/any), `roles` (multi-select, only applied when logged in and not restricted to logged-out), `stock` (`in_stock`/`out_of_stock`/any), `purchasable` (`yes`/`no`/any). All unset conditions pass automatically.

`Tab_Matcher::is_eligible()` is the single entry point `Frontend\Tabs` calls per row 2.2.0 – 30 October 2026 it combines `matches_target()` + `is_visible()` (which also checks the row's own `enabled` flag).

### Tab ordering / priority

Each tab row stores an explicit integer `priority`. If a row has no priority (or `0`), `Core\Settings::compute_priority_for_index( $base, $step, $index )` computes one as `base + (index * step)`, where `$base` = the "Default Tab Position" setting (`priority_base`, default `50`) and `$step` = "Default priority step" (`priority_step`, default `10`). A special clamp keeps auto-generated priorities inside the gap between WooCommerce's built-in tabs if the admin chose one of the "between" position presets in Settings (Description=10, Additional Information=20, Reviews=30 are WooCommerce's own defaults): a base strictly between 10–20 clamps generated priorities to ≤19; a base strictly between 20–30 clamps to ≤29. The JS mirrors this exact clamp logic client-side (`computePriorityForIndex()` in `admin.js`) so the modal's auto-filled position field matches what PHP would compute.

The Settings screen exposes a "Default Tab Position" `<select>` with presets (Before description / Between description & additional info / Between additional info & reviews / After all default tabs / Custom) that is synced two-way with the raw `priority_base` number input via `initPositionPresetSync()` in `admin.js`.

### Enable/Disable toggle

Every tab row (product, global, or template) carries an `enabled` flag (default `1`). Disabling a row keeps its content/config intact but removes it from frontend rendering (`Tab_Matcher::is_visible()` short-circuits on `enabled === 0`) and, for templates, from the WooCommerce bulk-actions dropdown (`Bulk_Tools::register_bulk_actions()` skips disabled templates) and from `[kitgenix_tab]` shortcode lookups (`Shortcode::find_template()` skips disabled templates).

### Shortcode processing inside tab content

Controlled by the **Allow shortcodes** setting (`allow_shortcodes`, default on). When enabled, `Frontend\Tabs::render_tab()` and `Frontend\Shortcode::render()` both run `do_shortcode()` on the tab's content before `wpautop()`/`wp_kses_post()`. When disabled, shortcode text is rendered literally.

### Unsaved-changes protection

`assets/js/admin.js`'s `initUnsavedChangesGuard()` marks any form dirty (`data-kitgenix-custom-tabs-for-woocommerce-dirty="1"`) on native `input`/`change` events (plain settings forms) or explicitly via `markManagerDirty()` whenever the JS-driven tab manager mutates state (add/edit/remove/duplicate/reorder/toggle). A `beforeunload` listener blocks navigation while any tracked form is dirty and the page isn't mid-submit.

## Admin Interface

All admin UI is rendered under a single top-level **Kitgenix** menu (shared across all Kitgenix plugins, `slug = 'kitgenix'`, capability `manage_woocommerce` if WooCommerce is active else `manage_options`, icon from `assets/images/logos/kitgenix-*-favicon.svg`, menu position 58), with this plugin's settings living at the submenu **Kitgenix → Custom Tabs** (`page = kitgenix-custom-tabs-for-woocommerce`, registered by `Settings_UI::register_menu()` on `admin_menu` priority 50, capability `manage_woocommerce`/`manage_options`).

The settings page (`Settings_UI::render_page()`) is a single-page app shell with a sticky topbar (brand, tab nav, search, theme toggle, Kitgenix Hub link, mobile hamburger) and six client-side-switched tab panels, driven entirely by `?tab=` in the URL / `#kitgenix-tab-*` hash (`assets/js/kitgenix-admin-tabs.js`):

### 1. Settings tab (`tab=settings`)

Form posts to `options.php` under settings group `kitgenix_custom_tabs_for_woocommerce_settings_group`, option `kitgenix_custom_tabs_for_woocommerce_settings`. Also carries its own separate nonce field (`kitgenix_custom_tabs_for_woocommerce_settings_save` / `kitgenix_custom_tabs_for_woocommerce_settings_nonce`) in addition to the Settings API's own `settings_fields()` nonce 2.2.0 – 30 October 2026 this extra nonce is not read anywhere in the codebase found by this review (Settings API's own nonce/referer check governs the actual save); it appears to be defensive/legacy.

| Field | Option key | Type | Default | Behavior |
|---|---|---|---|---|
| Enable custom tabs | `enabled` | toggle | `1` | Master switch. When off: `Product_Tabs` skips rendering/saving the product panel, `Frontend\Tabs` skips tab injection entirely, `Shortcode::render()` returns `''`, `Bulk_Tools` doesn't register its bulk actions. Existing saved data is preserved, not deleted. |
| Max tabs per product | `max_tabs` | number (1–50, clamped) | `10` | Caps rows kept by `sanitize_tabs_rows()` for product/global contexts, and caps the **combined** global+product tab count actually rendered on one product page (`Frontend\Tabs::add_rows_to_tabs()` 2.2.0 – 30 October 2026 a single running `$rendered` counter shared across both loops, fixed in 2.0.0 to stop it being enforced independently per group). |
| Allow shortcodes | `allow_shortcodes` | toggle | `1` | Gates `do_shortcode()` on tab content, frontend only. |
| Default Tab Position | `priority_base` | number (preset-synced select) | `50` | Base value for auto-computed priorities; see Tab ordering above. |
| Default priority step | `priority_step` | number (min 1) | `10` | Step value for auto-computed priorities. |
| Hide Tab Heading Inside the Tab | `hide_tab_heading` | toggle | `0` | Global override: when on, no tab ever prints its `<h2 class="woocommerce-Tabs-panel__title">` heading, regardless of each row's own `hide_title` flag. |

`global_tabs` is also a key of this same option (an array of tab rows) but is edited from the separate Global Tabs tab, not this form.

### 2. Templates tab (`tab=templates`)

Its own form, posting to `options.php` under group `kitgenix_custom_tabs_for_woocommerce_templates_group`, option `kitgenix_custom_tabs_for_woocommerce_templates` (registered separately from the main settings group). Own nonce `kitgenix_custom_tabs_for_woocommerce_templates_save`/`..._templates_nonce`. Uses the same shared tab-manager JS/modal as product/global tabs but with `data-kitgenix-custom-tabs-for-woocommerce-manager-type="template"`, which hides both the Visibility Conditions and Product Targeting sections in the modal. Max rows: 100 (`Settings::template_library_max()`), a limit that is independent of `max_tabs` (this was a bug fixed in 2.0.0 2.2.0 – 30 October 2026 templates previously incorrectly inherited the per-product `max_tabs` default of 10).

### 3. Global Tabs tab (`tab=global-tabs`)

Same underlying option/group as the Settings tab (`kitgenix_custom_tabs_for_woocommerce_settings_group` / `...settings`), just a different form rendering only the `global_tabs[]` fields 2.2.0 – 30 October 2026 so saving this form re-sanitizes and re-saves the *whole* settings array (the `Settings::sanitize()` merge-with-existing logic is what stops one form's submit from wiping fields owned by another form/tab). Table columns: Tab, Slug, Position, **Scope**, Actions. Modal shows both Visibility Conditions and Product Targeting sections (`managerType === 'global'`).

### 4. Portability tab (`tab=portability`)

Three cards:

- **Export** 2.2.0 – 30 October 2026 one button for a full JSON export (all settings + global tabs + templates), plus two buttons for flattened CSV export of Global Tabs or Templates individually. CSV exports neutralise spreadsheet formula injection (`Portability::neutralize_csv_formula()` prefixes any value starting with `=`, `+`, `-`, `@`, a tab or a carriage return with an apostrophe), and CSV import reverses this (`Portability::restore_csv_formula()`) so exported files round-trip unchanged.
- **Import JSON** 2.2.0 – 30 October 2026 file upload (`accept="application/json,.json"`), Replace/Merge mode radio, client-side preview (reads the file with `FileReader`, shows counts of global tabs / templates / setting keys and warns if the `plugin` field in the JSON doesn't match this plugin's slug) before enabling the Import button.
- **Import CSV** 2.2.0 – 30 October 2026 file upload, a `<select>` for "Import into" (Global Tabs or Templates), Replace/Merge mode radio.

See **AJAX / REST Endpoints** below for the concrete `admin-post` actions.

### 5. Support tab (`tab=support`)

Static content: a donate hero card (PayPal donate link), and a links row (WordPress.org review link, documentation link, "Copy plugin link" button using the Clipboard API with a `window.prompt()` fallback). This button is built with a bespoke inline `onclick` handler (`Settings_UI::render_support_tab()`) rather than the shared `data-kitgenix-copy` component, so it does **not** get the distinct success/failure feedback (class toggling/label change) that the shared copy-to-clipboard component provides elsewhere 2.2.0 – 30 October 2026 it silently succeeds or falls back to `window.prompt()` with no visual confirmation either way. No settings/options here.

### 6. Log tab (`tab=log`)

Displays `Core\Event_Log`'s stored entries in a searchable (`data-kitgenix-table-search`), paginated (25/page, `data-kitgenix-table-paginate="25"`) table (Time / Context / Outcome / Note), newest first. Also has:
- A retention `<select>` (7/14/30 days, default 30) posted to `admin-post.php?action=kitgenix_custom_tabs_for_woocommerce_save_log_retention`.
- A "Clear log" link (nonce-protected GET to `admin-post.php?action=kitgenix_custom_tabs_for_woocommerce_clear_event_log`) with a JS `confirm()` prompt.
- A static reference table explaining each log "category" (saved, disabled, not-applicable, permission-denied, cached-or-expired-page, save-error, auto-corrected) and whether it needs admin action.

### Product editor: "Custom Tabs" panel

Added to WooCommerce's classic Product Data metabox as a new tab (`woocommerce_product_data_tabs` filter, priority label "Custom Tabs", internal key `kitgenix_custom_tabs_for_woocommerce_tabs`, `priority: 120`), with a matching panel rendered via `woocommerce_product_data_panels`. Contains: a header bar with quick links to Tab settings / Templates / Help & FAQ / Global tabs (all open in a new tab) and an "Add new tab" button; two lines of help copy; the tab table (Tab / Slug / Position / Actions) with the same shared modal/manager JS (`managerType="product"`, so the modal shows Visibility Conditions but not Product Targeting).

## WooCommerce Integration

### Injection into `woocommerce_product_tabs`

`Frontend\Tabs::init()` registers `add_filter( 'woocommerce_product_tabs', [ self::class, 'inject_tabs' ], 20 )`. `inject_tabs()`:

1. Bails immediately (returning `$tabs` unchanged) if the feature is disabled, or if the global `$product` is missing / not a `WC_Product` instance, or has no valid ID.
2. Loads Global Tabs (`Settings::global_tabs()`) and the product's own stored tabs (`get_post_meta( $product_id, 'kitgenix_custom_tabs_for_woocommerce_tabs', true )`).
3. Calls a shared private helper `add_rows_to_tabs()` **first for global tabs, then for product tabs**, sharing one `$idx` (array-key uniqueness counter) and one `$rendered` counter (the combined max-tabs cap) across both calls 2.2.0 – 30 October 2026 so global tabs are always evaluated/added before product-specific ones, and the max-tabs limit is enforced across the *combined* set, not per group.
4. For each row: skips if `title`/`content` is empty, skips if `Tab_Matcher::is_eligible()` returns false, and skips if the `kitgenix_custom_tabs_for_woocommerce_tab_eligible` filter (see Hooks Reference) returns false.
5. Builds a de-duplicated array key: `kitgenix_custom_tabs_for_woocommerce_tab_{slug}` (or `_{index}` if no slug), appending `_2`, `_3`, … on collision.
6. Resolves priority via the row's stored value, or `Settings::compute_priority_for_index()` if unset/`0`, then passes it through the `kitgenix_custom_tabs_for_woocommerce_tab_priority` filter.
7. Passes content through the `kitgenix_custom_tabs_for_woocommerce_tab_content` filter.
8. Writes the resulting tab definition into `$tabs[$key]` with WooCommerce's expected shape: `title`, `priority`, `callback` (`[Tabs::class, 'render_tab']`), plus plugin-private keys `kitgenix_content`, `kitgenix_title`, `kitgenix_hide_title` that the callback reads back out.

`render_tab( $key, $tab )` is the WooCommerce tab callback: prints an `<h2 class="woocommerce-Tabs-panel__title">` heading (unless the global `hide_tab_heading` setting or the row's own `hide_title` is set), runs `do_shortcode()` if shortcodes are allowed, and echoes `wp_kses_post( wpautop( $content ) )`.

### Effect on WooCommerce's default tabs

None of WooCommerce's built-in tabs (Description=priority 10, Additional Information=priority 20, Reviews=priority 30) are removed, modified, or unhooked by this plugin 2.2.0 – 30 October 2026 they are simply additional array entries in the same `$tabs` filter, and their relative order versus custom tabs is entirely a function of the numeric `priority` each tab (built-in or custom) carries. The "Default Tab Position" setting and its presets exist specifically to let admins choose whether their auto-numbered custom tabs land before, between, or after these three defaults (see priority clamp logic above).

### Product Data metabox integration

Separately from the frontend filter, `Admin\Product_Tabs` hooks `woocommerce_product_data_tabs` / `woocommerce_product_data_panels` (metabox UI) and `woocommerce_process_product_meta` (save, priority 10, receives `$post_id, $post`) to persist the per-product tab rows to postmeta on every product save.

### Global Tabs targeting 2.2.0 – 30 October 2026 WooCommerce data touched

Category/tag matching calls WordPress/WooCommerce's own `has_term( $term_ids, 'product_cat'|'product_tag', $product_id )`. Product-type matching calls `$product->get_type()`. Neither issues new SQL beyond WordPress's own term-relationship caching. The "Products" targeting field in the modal is powered by WooCommerce's own `woocommerce_json_search_products` AJAX action via `wc-enhanced-select`/Select2 (the same mechanism the core "Linked Products" tab uses) 2.2.0 – 30 October 2026 the plugin defines no custom product-search endpoint.

## Hooks Reference

### Hooks this plugin registers into (WordPress/WooCommerce core hooks it listens on)

| Hook | Type | Priority | Callback | Purpose |
|---|---|---|---|---|
| `admin_enqueue_scripts` | action | 5 | `kitgenix_custom_tabs_for_woocommerce_register_admin_ui_style` (main file) | Registers the shared admin-ui stylesheet early. |
| `admin_menu` | action | 5 | `kitgenix_custom_tabs_for_woocommerce_ensure_admin_menu` (main file) | Creates the shared top-level `kitgenix` menu page once. |
| `admin_enqueue_scripts` | action | 20 | `kitgenix_custom_tabs_for_woocommerce_enqueue_hub_styles` (main file) | Enqueues Hub CSS + Thickbox only on the Hub page. |
| `admin_init` | action | default | anonymous fn (main file) | One-time activation redirect. |
| `plugin_action_links_{basename}` | filter | default | anonymous fn (main file) | Adds "Settings" plugin-list link. |
| `before_woocommerce_init` | action | default | anonymous fn (main file) | Declares HPOS compatibility. |
| `plugins_loaded` | action | default | anonymous fn (main file) | Bootstraps `Plugin::init()` once WooCommerce is confirmed active. |
| `admin_init` | action | default | `Settings::register_settings` | Registers both Settings API option groups. |
| `admin_init` | action | default | `Event_Log::maybe_schedule_pruning` | Ensures the daily prune cron event exists. |
| `kitgenix_custom_tabs_for_woocommerce_prune_event_log` (custom cron hook) | action | default | `Event_Log::prune` | Daily log pruning. |
| `add_option_kitgenix_custom_tabs_for_woocommerce_templates` | action | default | `Settings::force_templates_autoload_off` | Forces the templates option's `autoload` to `no`. |
| `update_option_kitgenix_custom_tabs_for_woocommerce_templates` | action | default | `Settings::force_templates_autoload_off` | Same, on every update. |
| `woocommerce_product_data_tabs` | filter | default | `Product_Tabs::product_data_tabs` | Adds the "Custom Tabs" metabox tab. |
| `woocommerce_product_data_panels` | action | default | `Product_Tabs::product_data_panels` | Renders the metabox panel. |
| `woocommerce_process_product_meta` | action | 10 | `Product_Tabs::save_product_tabs` | Saves per-product tab rows. |
| `admin_enqueue_scripts` | action | 20 | `Product_Tabs::enqueue_assets` | Enqueues Quill + admin CSS/JS on the product editor screen only. |
| `before_delete_post` | action | default | `Product_Tabs::flush_usage_stats_cache_for_product` | Invalidates the usage-stats transient when a product is deleted. |
| `admin_menu` | action | 50 | `Settings_UI::register_menu` | Registers the `Kitgenix → Custom Tabs` submenu. |
| `admin_enqueue_scripts` | action | 20 | `Settings_UI::enqueue_assets` | Enqueues admin CSS/JS on the settings screen only. |
| `admin_notices` | action | default | `Settings_UI::render_admin_notices` | Prints `settings_errors()` on the settings screen. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_clear_event_log` | action | default | `Settings_UI::handle_clear_event_log` | Clears the log. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_save_log_retention` | action | default | `Settings_UI::handle_save_log_retention` | Saves log retention days. |
| `update_option_kitgenix_custom_tabs_for_woocommerce_settings` | action | default | `Settings_UI::on_settings_saved` | Logs a "settings-saved" event. |
| `bulk_actions-edit-product` | filter | default | `Bulk_Tools::register_bulk_actions` | Adds one bulk action per enabled template. |
| `handle_bulk_actions-edit-product` | filter | default | `Bulk_Tools::handle_bulk_action` | Applies the chosen template to selected products. |
| `admin_notices` | action | default | `Bulk_Tools::render_result_notice` | Shows the bulk-apply result notice. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_export` | action | default | `Portability::handle_export_json` | JSON export. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_import` | action | default | `Portability::handle_import_json` | JSON import. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_export_csv` | action | default | `Portability::handle_export_csv` | CSV export. |
| `admin_post_kitgenix_custom_tabs_for_woocommerce_import_csv` | action | default | `Portability::handle_import_csv` | CSV import. |
| `woocommerce_product_tabs` | filter | 20 | `Tabs::inject_tabs` | Main frontend tab injection. |
| (shortcode) `kitgenix_tab` | 2.2.0 – 30 October 2026 | 2.2.0 – 30 October 2026 | `Shortcode::render` | Registered via `add_shortcode()`. |

### Custom hooks this plugin fires (for other developers to use)

| Hook | Type | Fired in | Signature | Purpose |
|---|---|---|---|---|
| `kitgenix_custom_tabs_for_woocommerce_tab_eligible` | filter | `Frontend\Tabs::add_rows_to_tabs()` | `bool $eligible, array $row, WC_Product $product` | Final override after the built-in enabled/target/visibility checks already passed; return `false` to suppress a specific tab for a specific product/request. |
| `kitgenix_custom_tabs_for_woocommerce_tab_priority` | filter | `Frontend\Tabs::add_rows_to_tabs()` | `int $priority, array $row, WC_Product $product` | Override the resolved ordering priority for one tab. |
| `kitgenix_custom_tabs_for_woocommerce_tab_content` | filter | `Frontend\Tabs::add_rows_to_tabs()` | `string $content, array $row, WC_Product $product` | Override/transform a tab's content before it is handed to the render callback (runs before shortcode processing / `wpautop()`). |
| `kitgenix_custom_tabs_for_woocommerce_shortcode_html` | filter | `Frontend\Shortcode::render()` | `string $html, array $template` | Override the final rendered HTML of a `[kitgenix_tab]` shortcode call. |

All four are documented explicitly in the 2.0.0 changelog entry (`CHANGELOG.md`) as new developer-facing filters.

## Shortcodes / Blocks / Widgets

Only one shortcode is registered; no blocks or widgets.

### `[kitgenix_tab]`

Registered by `Frontend\Shortcode::init()` (`add_shortcode( 'kitgenix_tab', ... )`). Renders a saved **Template** (not a live per-product tab or Global Tab 2.2.0 – 30 October 2026 those are meaningless outside a product-page context) anywhere WordPress shortcodes are processed (post/page content, widgets, other tabs, etc.).

| Attribute | Default | Purpose |
|---|---|---|
| `slug` | `''` | Matches a template's `slug` (sanitized via `sanitize_title()`). Checked first if non-empty. |
| `title` | `''` | Case-insensitive exact match against a template's `title`, used only if `slug` is empty. |
| `heading` | `'0'` | If truthy, prints an `<h2 class="kitgenix-custom-tabs-for-woocommerce-shortcode-title">` before the content, using the template's own `title`. |

Behavior notes: returns `''` if the feature is disabled (`Settings::enabled()`), if neither `slug` nor `title` is supplied, if no enabled template matches, or if the matched template's content is empty. Recursion guard: a static `$depth` counter capped at `MAX_DEPTH = 3` prevents infinite loops if a template's own content (processed via `do_shortcode()` when shortcodes are allowed) references `[kitgenix_tab]` again. Output passes through `wp_kses_post( wpautop( $content ) )` and finally the `kitgenix_custom_tabs_for_woocommerce_shortcode_html` filter.

Example: `[kitgenix_tab slug="warranty-info"]`

## Data Storage

No custom database tables are created. All persistence uses WordPress options and one dedicated product-postmeta key.

### Options

| Option name | Autoload | Registered via | Purpose |
|---|---|---|---|
| `kitgenix_custom_tabs_for_woocommerce_settings` | default (yes) | `register_setting()`, group `kitgenix_custom_tabs_for_woocommerce_settings_group` | Main settings array: `enabled`, `max_tabs`, `allow_shortcodes`, `priority_base`, `priority_step`, `hide_tab_heading`, `global_tabs` (array of tab rows, each optionally with `visibility`/`target`). Sanitized by `Settings::sanitize()`. |
| `kitgenix_custom_tabs_for_woocommerce_templates` | explicitly forced to `no` (see below) | `register_setting()`, group `kitgenix_custom_tabs_for_woocommerce_templates_group` | Array of up to 100 saved template rows (base fields only, no `visibility`/`target`). Sanitized by `Settings::sanitize_templates_option()`. |
| `kitgenix_custom_tabs_for_woocommerce_event_log` | `false` (passed explicitly to `update_option()`) | n/a (not part of the Settings API) | Array of up to 100 log entries: `{time, context, outcome, note, code}`. Managed by `Core\Event_Log`. |
| `kitgenix_custom_tabs_for_woocommerce_log_retention_days` | default | plain `get_option`/`update_option` | Integer, one of `7`/`14`/`30` (default `30`) 2.2.0 – 30 October 2026 how long log entries are kept before pruning. |
| `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect` | n/a (transient) | `set_transient()`/`get_transient()` | 30-second one-shot flag used to trigger the post-activation redirect. |

**Templates autoload handling:** because `register_setting()`'s `autoload` arg was only added in WP 6.6 and this plugin supports WP 6.0+, `Settings::force_templates_autoload_off()` is hooked to both `add_option_{name}` and `update_option_{name}` for the templates option and forces `autoload = no` directly 2.2.0 – 30 October 2026 via `wp_set_option_autoload()` if available, else a direct `$wpdb->update()` on `wp_options` followed by `wp_cache_delete( 'alloptions', 'options' )`. This keeps a potentially large, admin-only template library out of the `alloptions` cache loaded on every frontend request.

**Legacy migration:** `Settings::migrate_templates_option()` (called from `ensure_defaults()` on every `admin_init`) is a one-time, idempotent migration: if the main settings option still has a legacy `tab_templates` key (pre-2.0.0 layout), its contents are moved into the new dedicated templates option (only if that option is currently empty) and the key is stripped from the settings option. `Settings::sanitize()` also unconditionally strips any `tab_templates` key from what it saves, so this legacy key cannot reappear via `array_merge()` with defaults.

### Post meta

| Meta key | Post type | Value | Purpose |
|---|---|---|---|
| `kitgenix_custom_tabs_for_woocommerce_tabs` | `product` | Array of tab rows (base fields + `visibility`, no `target`) | Per-product custom tabs. Written by `Product_Tabs::save_product_tabs()` and by `Bulk_Tools::handle_bulk_action()` (when appending a template); read by `Frontend\Tabs::inject_tabs()` and `Product_Tabs::get_product_tabs()`. |

### Transients

| Transient | Purpose |
|---|---|
| `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect` | One-shot activation-redirect flag (30s TTL). |
| `kitgenix_custom_tabs_for_woocommerce_usage_stats` | Referenced by `Product_Tabs::get_usage_stats_cache_key()`/`flush_usage_stats_cache()` (invalidated on save and on product deletion) 2.2.0 – 30 October 2026 its *producer* (whatever calculates and sets this transient) was not found in the files read for this review; only the cache-key accessor and invalidation calls exist in `Product_Tabs`. Likely used by a Support-tab usage-statistics display referenced in the 2.0.0 changelog ("Existing Support-tab usage statistics continue to be cached for one hour…") but the code path that populates it was not located among the plugin's own PHP files. |
| `kitgenix_hub_wporg_active_installs_v1` | Cached WordPress.org active-install counts for the Kitgenix Hub page (main plugin file), ~1 day TTL. |
| `kitgenix_hub_wporg_ratings_v1` | Cached WordPress.org ratings, ~1 day TTL. |
| `kitgenix_hub_wporg_media_v1` | Cached WordPress.org banner/icon artwork URLs, ~1 day TTL. |

## AJAX / REST Endpoints

This plugin registers **no** custom `wp_ajax_*` handlers and **no** custom REST API routes/namespaces. Two categories of "endpoint-like" behavior exist instead:

1. **`admin-post.php` actions** (form posts, not AJAX/XHR):

| Action | Method | Handler | Permission check | Nonce | Purpose |
|---|---|---|---|---|---|
| `kitgenix_custom_tabs_for_woocommerce_export` | GET/POST (form `method="post"`) | `Portability::handle_export_json` | `manage_woocommerce` or `manage_options` | `check_admin_referer( 'kitgenix_custom_tabs_for_woocommerce_portability' )` | Streams a `.json` file download of settings + global tabs + templates. |
| `kitgenix_custom_tabs_for_woocommerce_import` | POST, multipart | `Portability::handle_import_json` | same | same | Parses an uploaded JSON file, validates its `plugin` field, sanitizes via `Settings::sanitize_tabs_rows()`, writes both options, records an `import_success` log entry, redirects with a result flag. |
| `kitgenix_custom_tabs_for_woocommerce_export_csv` | GET (form `method="get"`) | `Portability::handle_export_csv` | same | same | Streams a `.csv` download of Global Tabs or Templates (`?which=global_tabs\|templates`). |
| `kitgenix_custom_tabs_for_woocommerce_import_csv` | POST, multipart | `Portability::handle_import_csv` | same | same | Parses an uploaded CSV, sanitizes, writes the target option, logs, redirects. |
| `kitgenix_custom_tabs_for_woocommerce_clear_event_log` | GET (nonce URL) | `Settings_UI::handle_clear_event_log` | `manage_options` | `check_admin_referer( 'kitgenix_custom_tabs_for_woocommerce_clear_event_log' )` | Deletes the log option, redirects with `kitgenix_log_cleared=1`. |
| `kitgenix_custom_tabs_for_woocommerce_save_log_retention` | POST | `Settings_UI::handle_save_log_retention` | `manage_options` | `check_admin_referer( 'kitgenix_custom_tabs_for_woocommerce_save_log_retention' )` | Saves the retention-days option (clamped to 7/14/30), redirects with `kitgenix_log_retention_saved=1`. |

Upload size for both JSON and CSV imports is capped at 5 MB (`Portability::require_uploaded_file()`); CSV parsing additionally streams through an in-memory `php://temp/maxmemory:5242880` buffer so it never spills to a disk temp file within that cap.

2. **Third-party AJAX reused, not defined by this plugin:** the Global Tabs modal's "Products" targeting field uses WooCommerce core's own `woocommerce_json_search_products` AJAX action (via the `wc-enhanced-select` script and Select2), exactly as WooCommerce's native "Linked Products" tab does. This plugin defines no handler for it and no custom search endpoint of its own.

3. **`options.php`** (WordPress Settings API): the Settings, Templates, and Global Tabs admin tabs all post standard `<form action="options.php">` submissions guarded by `settings_fields()` (which emits the Settings API's own nonce/referer/option-page fields) plus, on the Settings/Global Tabs forms, an additional custom nonce field (`kitgenix_custom_tabs_for_woocommerce_settings_save`) that does not appear to be separately verified anywhere in the reviewed code.

## Cron / Scheduled Events

One scheduled event:

| Hook | Recurrence | Scheduled by | Callback | Purpose |
|---|---|---|---|---|
| `kitgenix_custom_tabs_for_woocommerce_prune_event_log` | `daily` | `Event_Log::maybe_schedule_pruning()` (called on every `admin_init`, only schedules if `wp_next_scheduled()` returns falsy) | `Event_Log::prune()` | Removes log entries older than the configured retention period (7/14/30 days) even on a quiet site with no new events to trigger the on-write pruning path in `record()`. |

The event is explicitly cleared via `wp_clear_scheduled_hook()` in the plugin's `register_deactivation_hook()` callback, so it does not linger after deactivation.

## Frontend Behavior

On any WooCommerce single-product page render, `Frontend\Tabs::inject_tabs()` (hooked to `woocommerce_product_tabs`, priority 20) merges eligible Global Tabs and the current product's own stored tabs into WooCommerce's tabs array, subject to: the master `enabled` setting, the combined `max_tabs` cap, each row's own `enabled` flag, `target` (global tabs) and `visibility` matching, and the three developer filters described above. WooCommerce's own theme template (`content-single-product.php` / `single-product/tabs/tabs.php` or block-theme equivalent) then renders the merged tab list exactly as it would any other tab 2.2.0 – 30 October 2026 this plugin supplies no template overrides of its own.

Additionally, `[kitgenix_tab]` can render template content anywhere on the frontend (or admin previews) that processes shortcodes 2.2.0 – 30 October 2026 not limited to product pages.

No plugin-specific frontend CSS/JS is enqueued on the storefront: the tab *content* is just HTML/shortcode output styled entirely by the active theme's own WooCommerce tab styling. All of this plugin's own CSS/JS assets are admin-only (product editor and Kitgenix settings screens).

## Assets

### JavaScript (`assets/js/`)

| File | Enqueued on | Purpose |
|---|---|---|
| `admin.js` | Product editor (`post.php`/`post-new.php` for `product` post type) and the Kitgenix Custom Tabs settings screen | Core tab-manager: builds/binds the Add/Edit modal, wires the Quill editor into the modal, add/edit/remove/duplicate/enable-disable/move-up/move-down/drag-and-drop row logic (priority-based reordering), template-picker toolbar, position-preset ↔ priority_base sync, sortable-table headers, notice dismissal, and the client-side JSON import preview. No external dependencies beyond the globally-available `window.Quill` and, for the Products targeting field, `window.jQuery`/Select2 (loaded by WooCommerce's `wc-enhanced-select`). |
| `kitgenix-admin-tabs.js` | Kitgenix Custom Tabs settings screen (and shared across other Kitgenix plugins using the same design system) | Shared topbar/tab-panel controller: tab switching driven by URL query/hash, in-page settings search ("/" and Cmd/Ctrl+K shortcuts), mobile hamburger menu, dropdown menus, collapsible search panel, light/dark theme toggle persisted to `localStorage` (`kitgenixAdminTheme`), moving WP admin notices out of the sticky header, and computing a `--kitgenix-sticky-offset` CSS variable from the topbar's real rendered height. File header states it is intentionally kept identical across all Kitgenix plugins. |
| `kitgenix-admin-components.js` | Any Kitgenix admin screen | Shared, independent component controller: toast notifications (`window.KitgenixAdminUI.toast()`), modal open/close with focus management and an open-modal stack, collapsible/accordion panels, copy-to-clipboard with distinct success/failure feedback, live table search (`data-kitgenix-table-search`), and lightweight client-side table pagination (`data-kitgenix-table-paginate`). Exposes `window.KitgenixAdminUI.initComponents()`/`openModal()`/`closeModal()` for re-scanning dynamically injected markup. Also explicitly documented as kept identical across plugins. |

### Vendor

| File | Version | License | Notes |
|---|---|---|---|
| `assets/vendor/quill/quill.min.js` | 1.3.7 | BSD-3-Clause (Copyright Jason Chen 2014, salesforce.com 2013) | Bundled locally since 2.0.0, replacing a prior CDN (`cdn.jsdelivr.net`) load 2.2.0 – 30 October 2026 called out explicitly as a security improvement in the changelog and `readme.txt`'s "Bundled Library & Source Code" section. |
| `assets/vendor/quill/quill.snow.css` | 1.3.7 | BSD-3-Clause | Quill's "Snow" theme stylesheet. |

### CSS (`assets/css/`)

| File | Enqueued on | Purpose |
|---|---|---|
| `admin.css` | Kitgenix Custom Tabs settings screen (handle `kitgenix-custom-tabs-for-woocommerce-admin`) | Plugin-specific structural/functional styling layered on top of the shared design system: manager toolbar, tab/template list table extras, modal field grid, "Advanced settings" `<details>` disclosure, disabled-row styling, visibility/target section layout, Quill editor slot sizing, Support tab styling, reduced-motion handling. |
| `kitgenix-admin-ui.css` | Every Kitgenix admin screen (handle `kitgenix-custom-tabs-for-woocommerce-admin-ui`), plus the Hub page | The full shared cross-plugin Kitgenix design system: CSS custom-property design tokens (brand colors, radius scale, shadows, spacing scale, Manrope/Inter typography) with a dark-theme override block toggled via `[data-kitgenix-theme="dark"]`; full-bleed admin canvas; sticky topbar; buttons; layout/grid; cards & sections; utility classes; extended badge variants; icon-header/collapsible cards; settings rows (replacement for `.form-table`); field grid; toggle switch; data table; empty state; notice/alert banners; stat/metric tile; modal/dialog; search/filter bar; pagination; progress bar; file dropzone; button group; CSS-only tooltip; toast; shared grid/tile components; Support-page hero card; plain section subheading. Imports Google Fonts (Manrope, Inter) via `@import url('https://fonts.googleapis.com/...')`. File header states it is intentionally kept identical across all Kitgenix plugins. |
| `kitgenix-hub.css` | Only the `kitgenix` top-level Hub page (handle `kitgenix-hub`) | Styles the plugin-discovery grid/cards on the Kitgenix Hub screen; consumes the same `--kitgenix-*` tokens as `kitgenix-admin-ui.css`. |
| `product-edit.css` | WooCommerce product editor screen only (handle `kitgenix-custom-tabs-for-woocommerce-product-edit`) | Self-contained panel styling scoped to `#kitgenix_custom_tabs_for_woocommerce_tabs` and `#kitgenix-custom-tabs-for-woocommerce-modal`, deliberately re-declaring its own local `--kitgenix-custom-tabs-for-woocommerce-*` design tokens (not reusing the shared admin-ui tokens) so this panel is unaffected by / doesn't affect the rest of the WooCommerce product edit screen; includes a WooCommerce product-data tab icon mask using `assets/images/logos/kitgenix-wordpress-admin-menu-favicon.svg`, and explicit overrides to counteract WooCommerce's own float/label CSS interfering with the modal. |

Cache-busting: every plugin CSS/JS enqueue uses `filemtime()` of the actual file as its version string when the file is readable, falling back to the plugin version constant otherwise 2.2.0 – 30 October 2026 so edits to any asset file bust browser cache automatically without a manual version bump.

### Images (`assets/images/`)

Static only 2.2.0 – 30 October 2026 logos (multiple color/format variants: black/white/dark/primary favicons and full logos, some with taglines), social-media icons (Facebook, Instagram, YouTube, Reddit, LinkedIn, X, TikTok, GitHub, globe), and WordPress.org listing assets (banners, icons, screenshots). No code.

## Internationalization

- **Text domain:** `kitgenix-custom-tabs-for-woocommerce` (declared in the plugin header, used consistently in every `__()`/`_e()`/`esc_html__()` call across the codebase).
- **Domain Path:** `/languages`.
- **Translation loading:** the plugin deliberately does **not** call `load_plugin_textdomain()`. Both `includes/Plugin.php` and the plugin header area carry explicit comments stating that for plugins hosted on WordPress.org, translations are loaded automatically by WordPress.org's translation system and an explicit call is "no longer required and is discouraged."
- **Files present:** only `languages/kitgenix-custom-tabs-for-woocommerce.pot` (the translation template, `Project-Id-Version: ... 2.2.0`, `POT-Creation-Date: 2026-10-22`, generated by `WP-CLI 2.12.0`). **No `.po` or `.mo` files are bundled** 2.2.0 – 30 October 2026 no translations currently ship with the plugin package itself; any localization relies entirely on WordPress.org-hosted translation packs for public releases, or a site admin adding their own `.mo` files.
- Every user-facing string reviewed uses proper `__()`/`_e()`/`esc_html__()`/`esc_attr__()`/`_n()` wrapping with the correct text domain; `sprintf()`-based strings carry `/* translators: */` comments for placeholder context (verified in `Event_Log`, `Settings`, `Bulk_Tools`, `Settings_UI`, main plugin file's Hub renderer).

## Security

### Nonce usage

| Context | Nonce action | Verified via |
|---|---|---|
| Product tab save (`woocommerce_process_product_meta`) | `kitgenix_custom_tabs_for_woocommerce_tabs_save` (field name `kitgenix_custom_tabs_for_woocommerce_tabs_nonce`) | `wp_verify_nonce()` 2.2.0 – 30 October 2026 manual check with explicit "missing" vs "invalid" branches, each logged to `Event_Log` with distinct codes (`nonce_missing`, `nonce_invalid`) rather than a hard failure, since a stale/cached edit-product page is a common, largely harmless cause. |
| Settings / Global Tabs form | `kitgenix_custom_tabs_for_woocommerce_settings_save` (plus the Settings API's own `settings_fields()` nonce, which is what WordPress core actually enforces on `options.php`) | `wp_nonce_field()` emitted; not separately re-verified in the reviewed handler code (Settings API governs the real save). |
| Templates form | `kitgenix_custom_tabs_for_woocommerce_templates_save` (plus Settings API nonce) | Same pattern as above. |
| Portability export/import (all four admin-post actions) | `kitgenix_custom_tabs_for_woocommerce_portability` | `check_admin_referer( self::NONCE )` at the top of every handler. |
| Clear event log | `kitgenix_custom_tabs_for_woocommerce_clear_event_log` | `check_admin_referer()`, plus the link itself is built with `wp_nonce_url()`. |
| Save log retention | `kitgenix_custom_tabs_for_woocommerce_save_log_retention` | `check_admin_referer()`. |
| Kitgenix Hub plugin Install/Activate actions | WordPress core's own `install-plugin_{slug}` / `activate-plugin_{file}` nonces | `wp_nonce_url()` (delegates entirely to WordPress core's own plugin-management nonce/capability handling). |

### Capability checks

- Product tab save: `current_user_can( 'edit_post', $post_id )`.
- Settings/Templates/Global Tabs pages and their `options.php` submissions: `manage_woocommerce` (if WooCommerce active) else `manage_options`, checked both when rendering the page (`Settings_UI::render_page()`) and implicitly by the Settings API's own capability gate on `options.php`.
- Portability handlers: `manage_woocommerce`/`manage_options` via `Portability::require_capability()` (calls `wp_die()` on failure).
- Log clear/retention admin-post handlers: `manage_options` explicitly (calls `wp_die( ..., 403 )` on failure).
- Bulk template application: `current_user_can( 'edit_products' )` at the action level, plus a per-product `current_user_can( 'edit_post', $post_id )` check before touching each selected product.
- Kitgenix Hub page: `manage_options` OR (`WooCommerce` active AND `manage_woocommerce`).
- Kitgenix Hub Install/Activate buttons: `install_plugins` / `activate_plugins` respectively, checked before generating the nonce URL (falls back to a plain search-in-repo link when the user lacks `install_plugins`).

### Sanitization / escaping patterns

- All tab-row input funnels through `Core\Settings::sanitize_tabs_rows()`: `sanitize_text_field()` for title/nickname, `wp_kses_post()` for content (allows a safe HTML subset, strips scripts/unsafe attributes), `sanitize_title()` for slug, `absint()` for priority, explicit boolean coercion for `enabled`/`hide_title`. This single sanitizer is reused for manual product-tab saves, manual Settings-page saves, JSON imports, CSV imports, and bulk-applied templates 2.2.0 – 30 October 2026 the 2.0.0 changelog explicitly calls out that imports/bulk-apply were changed to go through this same path rather than being trusted as pre-sanitized.
- `Tab_Matcher::sanitize_target()` / `sanitize_visibility()` validate the nested targeting/visibility JSON structures: IDs via `absint()` + `array_unique`/`array_filter`, product types and visibility enum values via `sanitize_key()` intersected against an explicit allow-list (`allowed_product_types()`, `{logged_in,logged_out}`, `{in_stock,out_of_stock}`, `{yes,no}`), roles intersected against `wp_roles()->roles` keys.
- Output escaping is consistent throughout the admin renderers: `esc_html()`/`esc_html__()`/`esc_html_e()` for text, `esc_attr()`/`esc_attr__()` for attributes, `esc_url()` for links, `esc_textarea()` for textarea content, `wp_kses_post()` for any stored rich content echoed back into HTML, and `wp_kses()` with an explicit allowed-tags array for small trusted HTML fragments (e.g. the Hub page's badge/action markup). Several `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` comments mark spots emitting static, hand-authored SVG icon markup (`Settings_UI::icon()`) that is deliberately not re-escaped since it's fully trusted, hard-coded output.
- Superglobal access consistently uses `wp_unslash()` before sanitizing, and `filter_input( INPUT_POST, ..., FILTER_REQUIRE_ARRAY )` is used for the tab-rows POST payload as an extra static-analysis-friendly guard in `Product_Tabs::save_product_tabs()`.
- Frontend rendering re-applies `wp_kses_post()` at output time (on top of the sanitize-on-save pass) both in `Frontend\Tabs::render_tab()` and `Frontend\Shortcode::render()`, i.e. defense-in-depth rather than trusting stored data unconditionally.
- File uploads (JSON/CSV import): validated for `$_FILES[...]['error']`, size (≤5 MB), and `is_uploaded_file()` before any read. CSV import (`Portability::handle_import_csv()`) reads file contents via `WP_Filesystem` (`$wp_filesystem->get_contents()`) rather than raw `fopen()`; JSON import (`Portability::handle_import_json()`) reads the uploaded file with plain PHP `file_get_contents()` instead.

## Uninstall / Cleanup

### Deactivation (`register_deactivation_hook`, main plugin file)

- Deletes the `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect` transient.
- Clears the scheduled `kitgenix_custom_tabs_for_woocommerce_prune_event_log` cron event via `wp_clear_scheduled_hook()`.
- **Does not** delete any options, postmeta, or the templates library 2.2.0 – 30 October 2026 deactivation is reversible/non-destructive by design.

### Uninstall (`uninstall.php`, runs only when the plugin is deleted through WordPress, guarded by `defined('WP_UNINSTALL_PLUGIN')`)

`kitgenix_custom_tabs_for_woocommerce_remove_site_data()` performs, per site:
- `delete_option()` for `kitgenix_custom_tabs_for_woocommerce_settings`, `kitgenix_custom_tabs_for_woocommerce_templates`, `kitgenix_custom_tabs_for_woocommerce_event_log`, `kitgenix_custom_tabs_for_woocommerce_do_activation_redirect`.
- `delete_transient()` for `kitgenix_custom_tabs_for_woocommerce_usage_stats`.
- A single direct `$wpdb->query()` `DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'kitgenix_custom_tabs_for_woocommerce_tabs'` 2.2.0 – 30 October 2026 removes all per-product tab data across every product in one query rather than looping per-post.

Note: `kitgenix_custom_tabs_for_woocommerce_log_retention_days` and the three Kitgenix-Hub WordPress.org cache transients (`kitgenix_hub_wporg_*_v1`) are **not** explicitly deleted by `uninstall.php` 2.2.0 – 30 October 2026 see Known Limitations.

**Multisite:** if `is_multisite()`, the uninstall script iterates every site (`get_sites(['fields'=>'ids'])`), calling `switch_to_blog()` / the cleanup function / `restore_current_blog()` per site, so tab data and settings are removed network-wide, not just on the site where deletion was triggered.

### Activation

`register_activation_hook()` in the main plugin file will deactivate the plugin and `wp_die()` with an explanatory message if `WooCommerce` is not active at activation time (defense in depth alongside the `Requires Plugins: woocommerce` header, which WordPress core also enforces on its own since 6.5+). On successful activation, sets a short-lived transient that triggers a one-time redirect to the plugin's settings page on the next `admin_init` (skipped if this was a bulk activation, detected via `$_GET['activate-multi']`).

## Known Limitations / TODOs

No `TODO`/`FIXME` comments were found anywhere in the PHP or JS source reviewed. The following are gaps/inconsistencies observed while reading the code, not statements from the code's own comments (except where noted):

- **The Global Tabs screen's "Template library" picker is empty/non-functional.** `Settings_UI::render_global_tabs_tab()` populates the modal's template-insert dropdown via `get_templates_dataset_attribute()` (a separate method from the one `Product_Tabs` uses), which reads `$settings['tab_templates']` 2.2.0 – 30 October 2026 a key `Settings::sanitize()`/`migrate_templates_option()` unconditionally strip from the main settings option as of the 2.0.0 migration. Templates now live exclusively in the dedicated `kitgenix_custom_tabs_for_woocommerce_templates` option, which this method never reads. Net effect: on any site that has gone through the 2.0.0 migration, the Global Tabs admin screen's "Template library" toolbar receives zero templates, even though saved templates exist and the equivalent feature works correctly on the product-editor Custom Tabs panel. This looks like a genuine regression, not just a doc gap.
- **The `kitgenix_custom_tabs_for_woocommerce_usage_stats` transient has no discoverable producer.** `Product_Tabs` exposes `get_usage_stats_cache_key()` and calls `flush_usage_stats_cache()` on every product-tab save and on product deletion, and `uninstall.php` explicitly deletes it 2.2.0 – 30 October 2026 but no code path that actually calls `set_transient()` for this key was found among the PHP files under `includes/`. The `readme.txt`/`CHANGELOG.md` 2.0.0 notes reference "Support-tab usage statistics" cached for one hour, but `Settings_UI::render_support_tab()` (as read) renders only static donate/links content with no statistics. This may indicate removed-but-not-fully-cleaned-up functionality, or code that exists elsewhere and was not encountered in this review's file enumeration (the enumeration covered every `.php` file under `includes/`).
- **A second, apparently-unverified nonce field on Settings API forms.** The Settings, Global Tabs, and Templates admin forms each emit both the Settings API's own `settings_fields()` nonce (which WordPress core actually checks on `options.php`) and an additional custom `wp_nonce_field( 'kitgenix_custom_tabs_for_woocommerce_settings_save', ... )` / `'..._templates_save'` field. No `check_admin_referer()`/`wp_verify_nonce()` call against these specific actions was found in `Settings_UI.php`. This is not a security gap (the Settings API's own nonce still protects the save), but the extra field appears to be dead/defensive code.
- **CSV import/export does not round-trip `visibility`/`target`.** This is by explicit design (documented in-code and in `readme.txt`/UI copy: "Visibility and targeting rules aren't part of CSV and are left untouched on any matching existing rows"), not a bug 2.2.0 – 30 October 2026 but it means CSV is a lossy format for anything beyond the six flat fields (title, nickname, slug, priority, enabled, content), and merge-mode CSV import cannot itself set or clear visibility/targeting on rows it creates.
- **`max_tabs` governs product/global tabs but not the Templates library**, which has its own fixed, non-configurable cap of 100 (`Settings::template_library_max()`). This is intentional (the 2.0.0 changelog explicitly frames the old shared-limit behavior as a bug that was fixed), documented here so it isn't mistaken for an oversight when reading `max_tabs`'s use elsewhere.
- **No REST API surface at all.** Every server interaction is either a classic HTML form POST (`options.php`, `admin-post.php`) or a WooCommerce metabox save (`woocommerce_process_product_meta`) 2.2.0 – 30 October 2026 there is no `register_rest_route()` anywhere in the codebase, and no custom `wp_ajax_*` action either; the only AJAX involved (`woocommerce_json_search_products`) belongs to WooCommerce core, not this plugin.
- **Log retention transient/option is not deleted on uninstall.** `kitgenix_custom_tabs_for_woocommerce_log_retention_days` is a plain (non-transient) option but is not included in `uninstall.php`'s `delete_option()` list, so a user's chosen retention period would persist (orphaned) if the plugin were later reinstalled. Similarly, the three `kitgenix_hub_wporg_*_v1` transients (shared Kitgenix Hub cache, defined in the main plugin file rather than plugin-specific code) are not cleaned up on uninstall 2.2.0 – 30 October 2026 likely intentional since the Hub is shared infrastructure other Kitgenix plugins also populate/consume, but worth noting as it means uninstalling this specific plugin does not fully clear its Hub-related cache footprint.
