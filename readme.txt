=== Teil1 Schema Manager ===
Contributors: pvj7000
Tags: schema, json-ld, structured-data, rich-snippets, woocommerce
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Visual Schema.org JSON-LD manager for rich snippets, with conditional rules, quality scoring, @graph output, and WooCommerce support.

== Description ==

Teil1 Schema Manager helps you create and control Schema.org JSON-LD without editing theme code. Build site-wide schemas, conditional rules, and per-page overrides from a visual WordPress dashboard.

Structured data can help search engines and AI-powered discovery systems interpret page entities and relationships. It does not guarantee rich results, search rankings, AI citations, or inclusion in AI Overviews.

**Global Schemas** – Site-wide markup that fires on every page (Organization, WebSite, etc.)

**Schema Rules** – Conditional templates that target specific page types, archives, taxonomies, and custom post types. Rules support AND logic for precise targeting.

**Local Overrides** – Per-page schemas stored in post meta for granular control, with an override toggle for same-type resolution.

= Schema Quality Score =

The dashboard's internal 0–100 Schema Quality Score summarizes configured coverage, required and recommended properties, schema depth, and type diversity. It is a diagnostic for improving your configuration, not a Google ranking or Rich Results score.

= Key Features =

* **Visual Schema Editor** — Build schemas with a property-by-property editor, live JSON-LD preview, and Rich Snippet preview.
* **Dynamic Variables** — Use `{{post_title}}`, `{{post_date}}`, `{{featured_image_url}}`, `{{meta:custom_key}}`, and 30+ variables that resolve at render time.
* **Custom Variables** — Define reusable site-wide constants (phone, address, logo) accessible as `{{custom.key}}` in any schema.
* **Schema Quality Score** — Review an internal 0–100 diagnostic based on configured coverage, required and recommended properties, schema depth, and type diversity.
* **Health Validation** — Every schema is validated against its type definition. Errors, warnings, and fix suggestions are shown inline.
* **Site Map** — Hierarchical view of every URL context on your site with schema coverage indicators and one-click rule creation.
* **Recommended Templates** — Sensible default rules (Article for posts, WebPage for pages, etc.) that you opt into — never auto-activated.
* **34 Built-In Schema Types** — Organization, Article, Product, FAQPage, HowTo, Event, VideoObject, Service, and more.
* **WooCommerce Compatible** — `product` works as a condition like any post type, plus dynamic `{{product_price}}`, `{{product_availability}}`, and `{{product_rating}}` variables. Variable products receive `AggregateOffer` price ranges, and optional conflict suppression can remove overlapping WooCommerce Product, Review, BreadcrumbList, and WebSite markup.
* **WP-CLI** — Full command suite: create, inspect, render, health-check, export, import, coverage audit, and diagnostics.
* **Admin Bar Indicator** — Shows active schema count on frontend pages with a dropdown listing each type.
* **Post Editor Meta Box** — Compact sidebar panel showing the local schemas on the current post with health badges, and a link straight into the full editor for that post.
* **`@graph` Pattern** — Multiple schemas on the same page are output in a single `<script>` tag using the `@graph` array.
* **`@id`-Based Merging** — Schemas sharing the same `@id` are merged into a single node, eliminating duplicates.
* **Auto BreadcrumbList** — Hierarchical pages with ancestors automatically get a BreadcrumbList schema.
* **Developer Hooks** — Filters for capability, JSON-LD output, variable resolution, condition matching, and author data.

= Compatibility and Duplicate Schema =

Teil1 Schema Manager can run alongside Yoast SEO, Rank Math, SEOPress, and AIOSEO because it does not change their titles, descriptions, sitemaps, or other SEO features. If two plugins output the same Schema.org type on a page, however, duplicate JSON-LD can result. Disable the overlapping schema output in the other SEO plugin when you configure the same type here.

For WooCommerce, the optional **Suppress conflicting schema output** setting removes only WooCommerce's Product, Review, BreadcrumbList, or WebSite markup when Teil1 Schema Manager renders the same type on that request. It does not merge arbitrary third-party schema graphs or disable unrelated WooCommerce structured data.

= Who Is This For? =

* **SEO professionals** who need precise control over structured data across complex WordPress sites.
* **Developers** managing multi-CPT architectures with conditional schema requirements.
* **Agencies** that need a scalable, rule-based approach to Schema.org markup.

= What This Plugin Does NOT Do =

* It does not auto-generate schemas from your content — you control what gets output.
* It does not add any visible output to your frontend — only a `<script type="application/ld+json">` tag in `<head>`.
* It does not phone home, track users, or load external assets. Everything runs locally.

= Source Code =

The React admin UI is built with Vite. Source code and build tools are available on [GitHub](https://github.com/teil1-com/t1-schema).

= Official Website =

Learn more, view documentation, and find examples on the official [Teil1 Schema Manager website](https://teil1.com/t1-schema/).

== Installation ==

1. Upload the `teil1-schema-manager` directory to `/wp-content/plugins/`, or install directly through the WordPress plugin screen.
2. Activate the plugin through the **Plugins** screen.
3. Navigate to **Teil1 Schema Manager** in the admin sidebar.

On first activation, the plugin:

* Creates two database tables (`t1schema_globals` and `t1schema_rules`).
* Seeds a default **Organization** and **WebSite** schema using your site name and URL.

== Frequently Asked Questions ==

= Does this plugin work with any theme? =

Yes. Teil1 Schema Manager outputs a `<script type="application/ld+json">` tag in `<head>` and has no dependency on any theme.

= Does it conflict with other SEO plugins? =

Teil1 Schema Manager only outputs JSON-LD structured data. It does not modify meta tags, sitemaps, or other SEO elements. It can run alongside Yoast SEO, Rank Math, SEOPress, and AIOSEO, but overlapping schema types should be disabled in the other plugin to prevent duplicate JSON-LD.

= Does it work with WooCommerce? =

Yes. `product` is treated as a regular post type, so it works with Global Schemas, Rules, and Local Overrides without extra setup, and dynamic variables like `{{product_price}}` and `{{product_availability}}` are available once WooCommerce is active. Variable products (size/color options) using `{{product_price}}` automatically get an accurate `AggregateOffer` price range instead of just their cheapest variation. WooCommerce also outputs its own Product/Review/BreadcrumbList/WebSite JSON-LD by default — turn on "Suppress conflicting schema output" under Help → Settings and Teil1 Schema Manager will automatically remove only the WooCommerce output that would otherwise duplicate a type you've configured.

= What happens when I deactivate the plugin? =

Your data (schemas, rules, custom variables) stays in the database. If you delete the plugin and have enabled the "Delete data on uninstall" setting, all data is removed.

= Can I use this with custom post types? =

Yes. All registered public post types appear automatically in the condition dropdown, the post type filter, and the Site Map.

= What are dynamic variables? =

Variables like `{{post_title}}` are placeholders that resolve to actual values at render time. This lets you create one rule that works across hundreds of pages. See the Help tab in the dashboard for the full variable reference.

= Can I export and import schemas between sites? =

Yes. Use `wp teil1-schema-manager export > backup.json` and `wp teil1-schema-manager import backup.json` via WP-CLI, or export the JSON from the dashboard.

== Screenshots ==

1. Globals dashboard — review the internal 0–100 Schema Quality Score, resolve action items, and manage reusable site variables.
2. Visual Rule Builder — target WordPress contexts, configure schema properties, and inspect the live JSON-LD output.
3. Schema Rules — review conditional coverage and priorities for posts, pages, archives, and taxonomies.
4. Site Map Coverage Audit — identify URL contexts with configured or missing structured data.
5. Pages & Posts — inspect and edit per-page local schemas and global override behavior.
6. Help & Settings — browse dynamic variables and configure conflict suppression and uninstall behavior.

== Changelog ==

= 2.4.1 =
* Feature: Map explicit blog editorial formats to Article, NewsArticle, or BlogPosting before canonical graph nodes are merged.
* Improvement: Added separate German and German Formal runtime catalogs so each locale follows WordPress.org language conventions.
* Improvement: Default German now uses neutral WordPress-style instructions, while German Formal preserves the approved "Sie" wording.

= 2.4.0 =
* Feature: Added complete WordPress-native internationalization for the React dashboard, PHP admin surfaces, REST messages, WP-CLI output, and Schema.org presentation metadata.
* Feature: Added a bundled German (`de_DE`) translation for immediate use while the WordPress.org language pack is reviewed.
* Improvement: Translation catalogs are generated reproducibly during release builds, including JavaScript JED files for the admin application.

= 2.3.3 =
* Documentation: Added the official Teil1 Schema Manager website and set the plugin homepage to its dedicated teil1 landing page.
* Directory: Improved the short description, feature tags, screenshot captions, Quality Score explanation, and compatibility guidance.
* Documentation: Added conservative guidance about structured data for AI-powered discovery without promising rankings, rich results, AI citations, or AI Overview inclusion.

= 2.3.2 =
* Compliance: Updated every gettext call and the plugin header to the confirmed WordPress.org slug and text domain, `teil1-schema-manager`.
* Security: WP-CLI exports now write JSON only to stdout. Use shell redirection to choose the destination file, preventing a command argument from overwriting arbitrary writable paths.
* Security: Escaped all custom table names before interpolating them into REST API queries.
* Fix: REST requests with query parameters now work on clean WordPress installations using plain permalinks.
* Fix: WP-CLI schema JSON input now uses `--schema-json`, avoiding WP-CLI's reserved `--json` format shortcut.
* Fix: `wp teil1-schema-manager doctor` now counts schema validation errors in its final result.

= 2.3.1 =
* Compliance: Set Tested up to 7.1.
* Interim submission build; superseded after WordPress.org confirmed the plugin slug is `teil1-schema-manager`.

= 2.3.0 =
* Compliance: Admin bar styles are now registered with `wp_enqueue_style()` instead of an inline `<style>` tag.
* Compliance: Removed the remote Gravatar example URL and the Google Fonts stylesheet from the admin UI. Fonts use the system stack; all plugin assets are local.
* Compliance: Plugin display name is now **Teil1 Schema Manager**. Existing options, tables, hooks, and the `wp t1-schema` CLI command are unchanged.

= 2.2.2 =
* Fix: Escape custom table names with `esc_sql()` before interpolating them into SQL, which clears Plugin Check's UnescapedDBParameter warnings.

= 2.2.1 =
* Fix: Move the Vite asset manifest out of the hidden `assets/.vite/` folder so the distribution zip passes WordPress.org Plugin Check (hidden files are not permitted).
* Fix: Drop the sixth readme tag. WordPress.org allows a maximum of five.

= 2.2.0 =
* Feature: Variable WooCommerce products (size/color options, etc.) now get an accurate `AggregateOffer` — `lowPrice`, `highPrice`, and `offerCount` — instead of `{{product_price}}` understating them at their cheapest variation. Computed the same way WooCommerce's own structured data does, so the numbers always agree. Products with one effective price across all variations are unaffected.
* Feature: New `AggregateOffer` schema type in the type registry (34 built-in types total).

= 2.1.0 =
* Feature: Automatic WooCommerce compatibility. When "Suppress conflicting schema output" is on, t1 Schema detects which schema types it is about to render on the current page and removes only the matching WooCommerce structured data (Product, Review, BreadcrumbList, or WebSite) — no more duplicate JSON-LD on stores running both.
* Feature: Nine WooCommerce dynamic variables — `{{product_price}}`, `{{product_regular_price}}`, `{{product_sale_price}}`, `{{product_currency}}`, `{{product_sku}}`, `{{product_availability}}`, `{{product_rating}}`, `{{product_review_count}}`, `{{product_brand}}`. Only listed in the Help tab when WooCommerce is active.
* Improvement: `wp t1-schema doctor` reports WooCommerce detection and suppression status alongside the existing conflict checks.

= 2.0.2 =
* Fix: Corrected the plugin and author URLs. The previous domain did not resolve, so the plugin homepage and author links were dead.

= 2.0.1 =
* **Breaking:** The post editor meta box is now read-only. Quick Add and the per-schema remove buttons have been removed — both only applied on post save, gave no feedback, and Quick Add produced an empty schema that failed validation for most types. Editing happens in the full editor.
* Feature: The meta box link opens the full editor on the schemas for that specific post instead of the dashboard.
* Improvement: The local schema endpoint returns the post title, so a linked editor shows the right heading before anything else loads.

= 2.0.0 =
* First release on the WordPress.org Plugin Directory.
* **Breaking:** Conflict suppression is now opt-in and disabled by default. Previously t1 Schema removed another plugin's schema output automatically. Enable it under Help → Settings if you see duplicate JSON-LD.
* Feature: Settings panel in the Help tab, covering conflict suppression and data removal on uninstall.
* Fix: Uninstall now drops the schema rules table alongside the globals table.
* Security: Direct-access guards added to every PHP file.
* Security: Meta box form input is unslashed before sanitizing.
* Improvement: Removed the `?t1debug` diagnostic panel from the admin screen. Use `wp t1-schema doctor` instead.
* Improvement: Tested against WordPress 7.0.

= 1.5.0 =
* Security hardening, i18n, readme, and directory-compliance groundwork.
* Fix: Health validator false positives for CollectionPage types.
* Fix: Context-aware validation — recommended property warnings downgraded to infos for rules.
* Fix: Custom variables (`{{custom.*}}`) now resolve in admin preview panels.
* Improvement: All SQL queries use prepared statements.
* Improvement: Output escaping hardened across all PHP files.
* Improvement: All user-facing strings wrapped for translation.

= 1.4.9 =
* Feature: `@id` reference badge in property editor.
* Feature: `child_of_page` condition type — target all descendants of a parent page.
* Feature: Copy button for JSON-LD preview panel.
* Feature: Custom author resolution hooks (`t1schema_author_name`, etc.).
* Fix: Variable regex allows digits for `{{meta:_custom_key_123}}`.
* Fix: CollectionPage added to schema registry.
* Fix: Dashboard health resolves variables before `@id` matching.

= 1.4.6 =
* Initial release. Recursive schema builder with visual editor.

== Upgrade Notice ==

= 2.0.1 =
The post editor meta box is now read-only. Quick Add and the remove buttons are gone; use the "Edit in t1 Schema" link, which now opens the editor for that post. Existing local schemas are untouched.

= 2.0.0 =
Conflict suppression is now opt-in. If you relied on t1 Schema removing another plugin's JSON-LD, enable it under Help → Settings after updating, or you may see duplicate structured data.

= 1.5.0 =
Security hardening and WordPress.org compliance. Recommended update for all users.
