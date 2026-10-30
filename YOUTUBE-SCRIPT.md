# YouTube Script: Kitgenix Custom Tabs for WooCommerce

**Suggested video title:** Add Custom Product Tabs to WooCommerce (Size Guides, Shipping Info, FAQs) – No Code | Kitgenix Custom Tabs

**Alternative titles:**
- WooCommerce Custom Product Tabs: Per-Product AND Category-Wide (Free Plugin)
- Stop Editing Theme Files: Custom WooCommerce Tabs the Easy Way

**Estimated runtime:** 10–11 minutes (approx. 1,900 spoken words at a typical tutorial pace of ~175–180 wpm; allow extra time for screen-demo pauses in sections 5, 6 and 10)

**Plugin version covered:** 2.2.0 (requires WordPress 6.0+, PHP 8.2+, WooCommerce 10.0+)

**Recording notes:**
- Record the product-editor sections in WooCommerce's classic Product Data editor (that is the editor the Custom Tabs panel lives in).
- Have a demo store ready with a "Clothing" category, a few variable T-shirt products, and one "Gift Card" product in that category to use for the exclude demo.
- Before recording Section 8, create one saved template on the Templates screen (e.g. "Warranty Info", slug `warranty-info`).
- Do NOT demo the "Template library" picker inside the Global Tabs modal. It is currently a known issue (it shows no templates). Only demo template insertion from the product editor.

---

## 1. Hook

[ON SCREEN: A standard WooCommerce product page. Zoom in on the three default tabs: Description, Additional Information, Reviews.]

Here's a WooCommerce product page. Description. Additional information. Reviews. That's it. That's all you get.

[ON SCREEN: Quick cuts of a cluttered product description: a size chart, delivery info, and a warranty paragraph all crammed into one long Description tab.]

So where does your size guide go? Your delivery information? Your warranty, your care instructions, your FAQs? Usually it all gets crammed into the description, or you end up editing theme files, or installing a page builder just to add one extra tab.

[ON SCREEN: The same product page, now with clean tabs: "Size Guide", "Delivery & Returns", "Warranty".]

In this video, we'll show you how to add as many tabs as you need, on one product or across a whole category, without touching a line of code.

## 2. Intro

[ON SCREEN: Kitgenix logo, then the plugin's WordPress.org banner.]

Hi, we're Kitgenix, and this is Kitgenix Custom Tabs for WooCommerce.

It's a free plugin for store owners, agencies and anyone managing a WooCommerce catalogue who wants to put structured information on product pages: size guides, specs, ingredients, delivery details, warranties, FAQs, the lot.

[ON SCREEN: Text overlay: "Per-product tabs. Global tabs. Targeting. Templates. Import/export."]

The promise is simple: the right tabs, on the right products, managed from your WordPress dashboard.

## 3. Why We Built This

[ON SCREEN: Side-by-side: WooCommerce's three default tabs vs. a product page with five custom tabs.]

Out of the box, WooCommerce gives you three fixed tabs. If you want a fourth, the usual options aren't great.

You can write code, which is fine if you're a developer. You can use a page builder, which often means replacing your whole product layout just to add some text. Or you can use a tabs plugin that gives you one extra tab, or makes you paste the same content into hundreds of products by hand.

[ON SCREEN: Diagram: "Your tabs → WooCommerce's native product tabs → Your theme".]

We wanted something lighter. Kitgenix Custom Tabs plugs into WooCommerce's own native product-tab system. It doesn't replace your product template, so your theme keeps styling the tabs exactly as it does now. And it loads no extra scripts or styles on your storefront. Everything you're about to see happens in the admin.

## 4. Installing & Activating

[ON SCREEN: WordPress dashboard → Plugins → Add Plugin. Search "Kitgenix Custom Tabs".]

Installing it is the standard WordPress routine. Make sure WooCommerce is installed and active first. The plugin needs it and won't activate without it.

Go to Plugins, then Add Plugin, search for "Kitgenix Custom Tabs", click Install, then Activate.

[ON SCREEN: After activation, the browser lands on Kitgenix → Custom Tabs automatically.]

Once it's activated, you'll be taken straight to the plugin's settings screen. You'll find it any time under the Kitgenix menu, then Custom Tabs.

[ON SCREEN: Slowly pan across the topbar tabs: Settings, Templates, Global Tabs, Portability, Log, Support.]

Up top, you've got Settings, Templates, Global Tabs, Portability, a Log and Support. There's also a search box, which you can open with the forward-slash key or Command/Ctrl-K, and a light and dark mode toggle.

[ON SCREEN: Settings tab. Highlight each setting as it's mentioned.]

In Settings you'll find a master on/off switch, which keeps your saved tabs when it's off. There's also a maximum number of tabs per product, which is ten by default, a toggle for shortcodes in tab content, and a Default Tab Position that controls where new tabs land compared with WooCommerce's own tabs.

## 5. Creating Your First Custom Tab

[ON SCREEN: Products → edit a T-shirt product. Scroll to the Product Data box and click the "Custom Tabs" tab in the left column.]

Let's add our first tab. Open any product and scroll down to the Product Data box. You'll see a new "Custom Tabs" section in the list on the left.

[ON SCREEN: The Custom Tabs panel: table with Tab / Slug / Position / Actions columns, and an "Add new tab" button.]

This is where the tabs for this one product live. Click "Add new tab".

[ON SCREEN: The modal opens. Type the title "Care Instructions".]

Give it a title. This is what shoppers see on the tab itself. Let's call it "Care Instructions". There's also an optional nickname, which is just an internal label to help you tell similar tabs apart in the admin.

[ON SCREEN: Type into the rich editor. Use a heading, bold text, a bulleted list and a link. Hover over the toolbar buttons as they're mentioned.]

Now the content. This is a proper rich text editor. You can add headings, bold, italic, underline and strikethrough, numbered or bulleted lists, and links, and there's a button to clear formatting if you paste something messy in.

[ON SCREEN: Show the slug and position fields, then the heading option.]

Each tab also has a slug, which is a short identifier, and a position number that controls the order. WooCommerce's own tabs are 10 for Description, 20 for Additional Information and 30 for Reviews. So 15 puts your tab between the first two, and 50 puts it after Reviews. If you leave it empty, the plugin uses your default. You can also hide the heading inside this tab.

[ON SCREEN: Expand the Visibility Conditions section.]

Visibility Conditions let you show a tab only to logged-in or logged-out visitors, to certain user roles, when the product is in or out of stock, or when it can be purchased. That's great for trade-only notes. We'll leave this tab open to everyone.

[ON SCREEN: Save the modal. Add a second tab, "Delivery & Returns". Then click Move Up / Move Down, Duplicate, and the Enable/Disable toggle.]

Save that, and it appears in the table. Add a few more and you can reorder them with the Move Up and Move Down buttons. The order you see here is exactly the order shoppers will see. You can also duplicate a tab, or disable one temporarily without losing its content.

[ON SCREEN: Click "Update" on the product. Open the product on the frontend and click through the new tabs.]

Click Update on the product, view it on your store, and there they are, sitting right alongside WooCommerce's own tabs and styled by your theme.

## 6. Global Tabs

[ON SCREEN: Kitgenix → Custom Tabs → Global Tabs. Show the table with columns Tab / Slug / Position / Scope / Actions.]

Per-product tabs are great for information that's unique to one item. But what about content that's the same across lots of products, like delivery information or a size guide? That's what Global Tabs are for.

You create a global tab once, here under Global Tabs, and it can appear on many products at the same time. Edit it once, and every product updates.

[ON SCREEN: Click "Add new tab". Scroll down the modal to the Product Targeting section.]

The modal is the same one you've already seen, with one extra section: Product Targeting. This controls which products the tab appears on, and you can target four things: specific products, categories, tags and product types, meaning simple, variable, grouped or external.

[ON SCREEN: Text overlay explaining the rules as each one is said: "Empty = all products", "Exclude always wins", "Within a field = any of", "Across fields = all of".]

Each of those has an Include list and an Exclude list, and the rules are easy to remember.

If you leave everything empty, the tab shows on every product.

Exclude always wins. If a product matches an exclude rule, the tab won't show, whatever your include rules say.

Within one field, it's "any of". If you include the Clothing and Accessories categories, a product only needs to be in one of them.

Across fields, it's "all of". If you include the Clothing category and also the Variable product type, a product has to match both.

[ON SCREEN: Back on the Global Tabs table, point to the Scope column showing "All products" vs. "Targeted: ...".]

The Scope column gives you a quick summary of each tab, either "All products" or a short description of its targeting rules.

[ON SCREEN: Diagram: a product page's tabs sorted by position number, mixing a global tab and a product tab.]

How do global and per-product tabs work together? On the product page they're combined and sorted by position number. One thing to know: the max-tabs limit applies to the combined total, and global tabs are counted first. If the limit is five and three global tabs apply, that product has room for two of its own.

## 7. The [kitgenix_tab] Shortcode

[ON SCREEN: Kitgenix → Custom Tabs → Templates. Show the "Warranty Info" template with slug "warranty-info".]

Sometimes you want tab content to appear somewhere other than the product tabs. For that, there's a shortcode.

The shortcode shows a saved Template, which we'll cover properly in a moment. It doesn't pull a specific product's tab or a global tab. You point it at a template using its slug, like this:

[ON SCREEN: Text overlay: `[kitgenix_tab slug="warranty-info"]`]

`[kitgenix_tab slug="warranty-info"]`

[ON SCREEN: Paste the shortcode into a "Warranty & Guarantees" page in the block editor. Preview it.]

Here's a real example. You've written your warranty wording once as a template. Drop this shortcode on your "Warranty & Guarantees" page, in a widget, or even inside another tab, and it shows the same content. Update the template and every shortcode updates with it. Add `heading="1"` if you also want the template's title shown above it.

[ON SCREEN: Settings tab, highlight "Allow shortcodes".]

Quick note: if you want shortcodes to work inside your tab content, keep "Allow shortcodes" switched on in Settings. It's on by default.

## 8. Templates

[ON SCREEN: Templates tab. Click "Add new template" and create "Warranty Info".]

Templates are your library of reusable content. You can store up to a hundred: things like your standard warranty wording, care instructions or an ingredients notice. A template is just content. It doesn't decide where it shows up. You choose where to use it.

[ON SCREEN: Back in a product's Custom Tabs panel. Click "Add new tab", use the "Template library" picker in the modal toolbar, choose "Warranty Info".]

The best way to use one is inside the product editor. When you add a tab to a product, use the Template library picker at the top of the modal and choose a template. Its content is copied into the new tab.

[ON SCREEN: Edit a sentence in the inserted content. Text overlay: "Edits here don't change the saved template."]

And it's a copy. You can change it for this product, maybe adding a model number or an extra warranty term, and your original template stays exactly as it was.

[ON SCREEN: Text overlay: "Template picker: available in the product editor."]

To be clear: right now, inserting templates this way works in the product editor's Custom Tabs panel. We're not showing it on the Global Tabs screen because that picker isn't working properly in this version. For a global tab, just write or paste your content straight into the editor.

## 9. Import/Export & Bulk Tools

[ON SCREEN: Products → All Products. Tick several products. Open the Bulk actions dropdown and show "Add Kitgenix tab: Warranty Info".]

Templates also power one of the biggest time-savers in the plugin: bulk actions. On your main Products list, tick the products you want, open the Bulk actions dropdown, and you'll see an "Add Kitgenix tab" option for each of your enabled templates. Choose one, click Apply, and that template is added as a tab on every product you selected.

[ON SCREEN: The result notice, then open one of the products to show the new tab in its Custom Tabs panel.]

Each product gets its own copy of the tab, so you can still tweak any of them afterwards.

[ON SCREEN: Kitgenix → Custom Tabs → Portability. Show the Export, Import JSON and Import CSV cards.]

Next is the Portability tab, for moving your setup around.

JSON is the full backup. It exports your settings, global tabs and templates, including targeting and visibility rules. It's ideal for moving your setup from staging to live, or between client stores.

[ON SCREEN: Upload a JSON file. Show the preview counts and the Replace / Merge options.]

When you import, you'll see a preview of what's in the file before anything changes, and you choose whether to replace what's already there or merge it with your current setup.

[ON SCREEN: Click "Export Global Tabs (CSV)", open the file in a spreadsheet app.]

CSV is spreadsheet-friendly. You can export your global tabs or templates, edit titles, positions or content in bulk in Excel or Google Sheets, and import them back. Just remember that CSV only covers the basic fields. Targeting and visibility rules aren't included, so use JSON whenever you need those kept.

## 10. Real-World Example: A Size Guide for Your Whole Clothing Category

[ON SCREEN: Frontend: a T-shirt product page with only the default tabs.]

Let's put it all together. Say you sell clothing and you want one Size Guide tab on every clothing product, except gift cards, which also sit in that category.

[ON SCREEN: Kitgenix → Custom Tabs → Global Tabs → "Add new tab".]

Step one: go to Global Tabs and click "Add new tab". Give it the title "Size Guide".

[ON SCREEN: Build a size chart in the editor: an H2 "How to measure", a bulleted list of measurements, and a link to a full sizing page.]

Step two: write the content. We'll add a "How to measure" heading, a bulleted list of measurements and a link to our full sizing page.

[ON SCREEN: Set the position to 15. Text overlay: "15 = between Description (10) and Additional Information (20)".]

Step three: set the position. We want it right after Description, so we'll use 15.

[ON SCREEN: Product Targeting → Categories → Include: "Clothing". Products → Exclude: search and select "Gift Card".]

Step four: targeting. Under Categories, include "Clothing". Then under Products, exclude the gift card. Remember, exclude always wins, so even though the gift card is in Clothing, it won't get the tab.

[ON SCREEN: Save. Show the Scope column now reading something like "Targeted: ...".]

Save it, and the Scope column shows the tab is targeted.

[ON SCREEN: Frontend: T-shirt product shows "Size Guide" between Description and Additional Information. Then the Gift Card product with no Size Guide tab. Then a non-clothing product with no Size Guide tab.]

Now on the store: the T-shirt has the Size Guide tab, right after Description. The gift card doesn't. And a product outside Clothing doesn't either.

[ON SCREEN: Text overlay: "One tab. Whole category. Zero copy-and-paste."]

One tab, a whole category, and when your size chart changes, you edit it in one place.

## 11. Tips & Best Practices

[ON SCREEN: Each tip as a numbered text card over relevant footage.]

A few quick tips.

One: use global tabs for shared content and product tabs for anything unique. If you're pasting the same text into a second product, it probably belongs in a global tab or a template.

Two: leave gaps in your position numbers, like 40, 50 and 60, so you can slot new tabs in later.

Three: if a tab isn't showing, check the max-tabs limit first. It counts global and product tabs together.

Four: disable tabs rather than deleting them. Seasonal notices can be switched off and back on with their content intact.

And five: export a JSON backup before big changes, and if something doesn't save the way you expected, the Log tab explains what happened in plain English.

## 12. Recap & Call to Action

[ON SCREEN: Fast montage: product Custom Tabs panel → Global Tabs targeting → shortcode on a page → bulk action → Portability.]

So that's Kitgenix Custom Tabs for WooCommerce: per-product tabs, global tabs with targeting, visibility rules, templates and bulk actions, a shortcode, and JSON and CSV import and export. And it all runs through WooCommerce's own tab system, so your theme stays in charge of the look.

[ON SCREEN: WordPress.org plugin page with the install count and the "Download" button. Link in description.]

It's free on WordPress.org. The link is in the description. If it saves you some time, a review on WordPress.org really helps other store owners find it, and if you'd like to support development, there's a donate link on the plugin's Support tab.

[ON SCREEN: Subscribe button animation, end card with two suggested videos.]

If you have questions, leave them in the comments or post in the WordPress.org support forum. Subscribe for more WooCommerce tutorials from Kitgenix. Thanks for watching, and we'll see you in the next one.

[ON SCREEN: Kitgenix logo, kitgenix.com.]
