# Teil1 Schema Manager — Documentation

**Version:** 2.4.0
**Author:** teil1 development  
**Requires:** WordPress 6.0+, PHP 8.0+  
**License:** GPL v2 or later

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Core Concepts](#core-concepts)
4. [Admin Dashboard](#admin-dashboard)
   - [Schema Quality Score](#schema-quality-score)
   - [Recommended Rule Templates](#recommended-rule-templates)
   - [Admin Bar Indicator](#admin-bar-indicator)
5. [Global Schemas](#global-schemas)
6. [Schema Rules](#schema-rules)
7. [Site Map](#site-map)
8. [Per-Page Local Schemas](#per-page-local-schemas)
9. [Post Editor Meta Box](#post-editor-meta-box)
10. [Dynamic Variables](#dynamic-variables)
11. [Health Validation](#health-validation)
12. [WP-CLI Reference](#wp-cli-reference)
13. [Schema Types Reference](#schema-types-reference)
14. [Priority & Override Logic](#priority--override-logic)
15. [mu-Plugin Conflict Handling](#mu-plugin-conflict-handling)
16. [WooCommerce Compatibility](#woocommerce-compatibility)
17. [Hooks & Filters](#hooks--filters)
18. [Troubleshooting](#troubleshooting)

---

## Overview

Teil1 Schema Manager is a high-performance Schema.org JSON-LD markup plugin for WordPress. It provides three layers of structured data management:

- **Global Schemas** — site-wide markup that fires on every page (e.g. Organization, WebSite)
- **Schema Rules** — conditional templates that target specific page types, archives, taxonomies, and more
- **Local Overrides** — per-page schemas stored in post_meta for granular control

All schema data is output as a single `<script type="application/ld+json">` tag in `<head>`, using the `@graph` pattern when multiple schemas exist on the same page.

---

## Installation

1. Upload `teil1-schema-manager.zip` via **Plugins → Add New → Upload Plugin**
2. Activate the plugin
3. Navigate to **Teil1 Schema Manager** in the admin sidebar

On first activation, the plugin:
- Creates the `t1schema_globals` and `t1schema_rules` database tables
- Seeds a default **Organization** and **WebSite** schema using your site name and URL
- Leaves conflict suppression off — enable it under Help → Settings if another plugin emits duplicate JSON-LD

---

## Core Concepts

### The Three Layers

```
┌──────────────────────────────────────────────┐
│  Layer 3: LOCAL OVERRIDES (highest priority) │
│  Per-post/page via post_meta                 │
│  Managed in: Pages tab or Post Editor        │
├──────────────────────────────────────────────┤
│  Layer 2: SCHEMA RULES                       │
│  Conditional templates with targeting        │
│  Managed in: Rules tab                       │
├──────────────────────────────────────────────┤
│  Layer 1: SITE-WIDE GLOBALS (lowest)         │
│  Always fires on every page                  │
│  Managed in: Globals tab                     │
└──────────────────────────────────────────────┘
```

**Priority:** Local Override > Schema Rule > Global.

When multiple layers output the same `@type`, the higher-priority layer wins. For example, if you have a global `Article` and a local override `Article` on a specific post, only the local version renders.

### Dynamic Variables

Instead of hardcoding values, use `{{variable}}` tags in any schema property. These are resolved at render time based on the current page context.

Example: `{{post_title}}` becomes the actual post title on each page.

---

## Admin Dashboard

Access via **WordPress Admin → t1 Schema**. The dashboard has five tabs:

| Tab | Icon | Purpose |
|-----|------|---------|
| **Globals** | 📊 | Site-wide schemas (Organization, WebSite, etc.) |
| **Rules** | 🎯 | Conditional schemas targeting specific page types |
| **Site Map** | 🗺️ | Hierarchical overview of all URL contexts with coverage |
| **Pages** | 📄 | Browse posts/pages and manage per-page schemas |
| **Help** | 📖 | Variable reference, workflow guide, health status explainer |

### Schema Quality Score

At the top of the dashboard, an animated **Score Ring** (0–100) shows an objective quality metric for your site's schema markup. The score is calculated from four weighted factors:

| Factor | Weight | What it measures |
|--------|--------|-----------------|
| **Coverage** | 40% | What percentage of your site's URL contexts (post types, archives, taxonomies, search, 404) have schema rules or globals covering them |
| **Health** | 30% | What percentage of all active schemas pass validation (no missing required properties) |
| **Depth** | 20% | How many recommended/required properties are actually filled vs. available across all schemas |
| **Diversity** | 10% | How many distinct `@type`s you use (capped at 8 for 100%) |

**Grades:** A (90+), B (75+), C (60+), D (40+), F (below 40)

### Recommended Rule Templates

Below the score, t1 Schema suggests **opt-in rule templates** for common use cases. These are sensible defaults that are *not* activated automatically — you choose which to enable.

| Template | What it does |
|----------|-------------|
| **Article → All Blog Posts** | Adds Article schema with headline, dates, author, and image to every `post` |
| **WebPage → All Pages** | Adds WebPage schema with name, URL, and excerpt to every `page` |
| **BreadcrumbList → All Singulars** | Adds breadcrumb navigation schema for enhanced SERP display |
| **CollectionPage → Post Archives** | Adds CollectionPage to the blog archive |
| **SearchResultsPage → Search** | Adds SearchResultsPage schema to the search results page |

Click **Activate** on any template to create the rule instantly. Already-active templates are hidden.

### Admin Bar Indicator

When viewing the frontend as an admin, the **WordPress admin bar** shows a 🔮 icon with the count of active schemas on the current page.

**Hover** to see a dropdown listing each active schema type with its property count. Click to jump to the t1 Schema dashboard.

This works on all page types — singulars, archives, search, 404 — and shows the exact schemas that would appear in the JSON-LD output.

---

## Global Schemas

Global schemas fire on **every page** of your site. Use these for:
- Organization identity
- WebSite with search action
- Person (site owner)

### Creating a Global Schema

1. Click **+ New Global** in the Globals tab
2. Select a Schema.org type from the dropdown
3. Fill in properties — use the `{ }` button to insert dynamic variables
4. Click **Create Schema**

### Editing & Deleting

- Hover over any schema card → click **Edit** or the trash icon
- The sidebar shows a live **JSON-LD Preview** and **Rich Snippet Preview** as you edit

### Health Badges

Each schema card shows a colored badge:
- 🟢 **Valid** — all required properties are set
- 🟡 **N warnings** — recommended properties are missing (click to inspect)
- 🔴 **N errors** — required properties are missing (click to inspect)

Click any warning/error badge to expand the **Health Detail** panel with specific messages and fix suggestions.

---

## Schema Rules

Rules are the most powerful feature. They let you apply schema templates to specific WordPress contexts **without** editing each post individually.

### How Rules Work

Each rule has:
- **Conditions** — one or more targeting conditions (AND logic)
- **Schema Type** — the Schema.org type to output
- **Schema Data** — property values (can use dynamic variables)
- **Priority** — lower number = higher priority (default: 10)

### Available Conditions

#### Single Content
| Condition | Targets |
|-----------|---------|
| All Posts (single) | Every individual blog post |
| All Pages (single) | Every individual page |
| All [CPT] (single) | Every individual post of a custom post type |

#### Archives
| Condition | Targets |
|-----------|---------|
| Blog Index | The blog listing page |
| [CPT] Archive | Custom post type archive page (e.g. `/portfolio/`) |

#### Taxonomies
| Condition | Targets |
|-----------|---------|
| All Categories | Every category archive |
| All Tags | Every tag archive |
| Category: [name] | A specific category archive |
| Tag: [name] | A specific tag archive |
| All [Custom Tax] | Every custom taxonomy archive |
| [Custom Tax]: [term] | A specific custom taxonomy term archive |

#### Page Hierarchy
| Condition | Targets |
|-----------|---------|
| Children of: [Page] | All descendant pages of a parent (children, grandchildren, etc.) |

> Uses `get_post_ancestors()` — matches the entire subtree, not just direct children. Available for all hierarchical post types (Pages, etc.). The condition dropdown shows top-level pages and second-level pages that have children.

#### Special Pages
| Condition | Targets |
|-----------|---------|
| Front Page | The homepage (static or dynamic) |
| Search Results | The search results page |
| 404 Page | The 404 error page |
| Date Archives | All date-based archive pages |
| All Author Archives | Every author archive |
| Author: [name] | A specific author's archive |

### Creating a Rule

1. Go to the **Rules** tab → click **+ New Rule**
2. Add one or more conditions using the dropdown
3. Select a Schema.org type
4. Fill in properties with dynamic variables
5. Set priority (optional — default is 10)
6. Click **Create Rule**

### Example Rules

**Article schema for all blog posts:**
- Condition: `All Posts (single)`
- Type: `Article`
- Properties: `headline: {{post_title}}`, `datePublished: {{post_date}}`, `image: {{featured_image_url}}`

**CollectionPage for CPT archive:**
- Condition: `Portfolio Archive`
- Type: `CollectionPage`
- Properties: `name: {{archive_title}}`, `url: {{archive_url}}`

**BreadcrumbList for all category archives:**
- Condition: `All Categories`
- Type: `BreadcrumbList`
- Properties: use JSON import for complex nested structure

### Multiple Conditions (AND Logic)

When a rule has multiple conditions, **all must match** for the rule to fire. This is useful for very specific targeting, e.g.:
- Condition 1: `All Posts (single)` + Condition 2: `Category: Marketing`
- → Only fires on blog posts in the "Marketing" category

---

## Site Map

The **Site Map** tab shows a complete, hierarchical view of every URL context on your WordPress site:

```
🏠 Front Page               → Organization, WebSite ✓ Covered
📝 Blog Index                → [no rules]
📄 Pages (12 items)          → WebPage ✓ Covered
📰 Posts (34 items)          → Article ✓ Covered
📁 Portfolio (8 items)       → CreativeWork ✓ Covered
   📋 Portfolio Archive      → CollectionPage ✓ Covered
📁 Glossary (15 items)       → [no rules]
   📋 Glossary Archive       → [no rules]
🏷️ Categories
   ├── Marketing (5 items)   → [no rules]
   └── SEO (3 items)         → [no rules]
👤 Author Archives           → [no rules]
🔍 Search Results            → [no rules]
```

**Features:**
- **Coverage progress bar** — shows what % of your site contexts have schema rules
- **Expand/collapse** — click the arrow to see children (taxonomy terms, CPT archives)
- **Quick-create** — hover over any uncovered context → click **"+ Add Rule"** to jump directly to the Rule Builder with the condition pre-filled

---

## Per-Page Local Schemas

For individual posts and pages, you can add **local schemas** that override any global or rule-based schema of the same `@type`.

### Via the Pages Tab

1. Go to the **Pages** tab in the t1 Schema dashboard
2. Use the search bar, post type filter, or schema filter to find your page
3. Click **"+ Add Schema"** or **"Edit Schema"** on any row
4. The Local Schema Editor opens with:
   - Post context bar (title, URL, type)
   - Multi-schema tabs (if the page has multiple schemas)
   - Override toggle per schema
   - Property editor with variable picker
   - Health validation sidebar
5. Click **Save Changes**

### Filters

| Filter | Shows |
|--------|-------|
| All types | All public post types including CPTs |
| With schema | Only pages that have local schemas |
| Without schema | Only pages without local schemas |

The post type dropdown dynamically lists **all registered public post types** with counts — including any custom post types registered by your theme or other plugins.

### Storage Format

Local schemas are stored in the `_t1schema_local` post meta key as a JSON array. Each entry has the following structure:

```json
[
  {
    "@context": "https://schema.org",
    "@type": "Service",
    "name": "My Service",
    "url": "{{post_url}}",
    "_t1schema_meta": {
      "override_global": true,
      "status": "active"
    }
  }
]
```

The `_t1schema_meta` object is internal metadata stripped before rendering:
- `override_global` (bool) — if `true`, this schema replaces any global/rule schema of the same `@type`
- `status` — `"active"` or `"draft"` (draft schemas are skipped during rendering)

---

## Post Editor Meta Box

When editing any post, page, or custom post type in the WordPress editor, a **🔮 Teil1 Schema Manager — Local Schemas** panel appears in the sidebar.

The panel is read-only. It answers "what structured data does this page emit, and is it healthy?" — everything editable lives in the full editor, so schema data has a single save path.

**Features:**
- Shows all active local schemas on the current post
- Health badge per schema (Valid / Warnings / Errors)
- Inline error/warning messages (first 3 shown)
- Override indicator ("↑ Overrides global" or "∥ Coexists with global")
- **Edit in Teil1 Schema Manager →** opens the full editor on this post's local schemas, via `admin.php?page=teil1-schema-manager&t1_post={ID}`. On a post with no local schemas the link reads **Add a schema →**.

The panel registers no form fields and hooks nothing on `save_post`, so saving a post never writes schema data.

> **Removed in 2.0.1.** Earlier versions had a Quick Add dropdown and per-schema Remove buttons. Both only took effect on post save, gave no feedback, and left the panel showing stale state until reload. Quick Add also emitted a bare `{"@type": "X"}` for the 29 types with no auto-population map, which failed validation immediately. Use the full editor instead.

---

## Dynamic Variables

Use `{{variable_name}}` in any schema property value. Variables are resolved at render time on the frontend.

### Post Variables

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{post_title}}` | Post/page title | `My Blog Post` |
| `{{post_excerpt}}` | Post excerpt (auto-generated if empty) | `A short summary…` |
| `{{post_content}}` | Full content (plain text) | `The full body text…` |
| `{{post_date}}` | Publish date (ISO 8601) | `2026-05-04T12:00:00+02:00` |
| `{{post_modified}}` | Last modified date (ISO 8601) | `2026-05-04T15:30:00+02:00` |
| `{{post_url}}` | Full permalink | `https://example.com/my-post/` |
| `{{post_id}}` | Post ID | `42` |
| `{{post_slug}}` | URL slug | `my-post` |
| `{{post_type}}` | Post type slug | `post` |
| `{{featured_image_url}}` | Featured image URL (full size) | `https://example.com/wp-content/uploads/hero.jpg` |
| `{{featured_image_alt}}` | Featured image alt text | `Hero image` |

### Author Variables

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{author_name}}` | Author display name | `Max Mustermann` |
| `{{author_url}}` | Author archive URL | `https://example.com/author/max/` |
| `{{author_description}}` | Author bio | `Digital marketing specialist…` |
| `{{author_avatar_url}}` | Author avatar (96px) | `https://example.com/wp-content/uploads/author.jpg` |

> **Filterable:** `author_name`, `author_url`, and `author_avatar_url` can be overridden via the `t1schema_author_name`, `t1schema_author_url`, and `t1schema_author_avatar_url` filters. This allows themes or plugins with custom author systems to inject the correct author data without modifying the schema plugin. See [Hooks & Filters](#hooks--filters).

### Site Variables

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{site_name}}` | Site title (Settings → General) | `My Website` |
| `{{site_url}}` | Site home URL | `https://example.com/` |
| `{{site_description}}` | Site tagline | `Your site tagline` |
| `{{site_logo}}` | Custom logo URL | `https://example.com/…/logo.svg` |
| `{{site_language}}` | Site language | `en-US` |

### Taxonomy Variables

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{primary_category}}` | First category name | `Marketing` |
| `{{primary_category_url}}` | First category URL | `https://example.com/category/marketing/` |
| `{{categories}}` | Comma-separated category names | `Marketing, SEO` |
| `{{tags}}` | Comma-separated tag names | `schema, seo, structured-data` |

### Archive Variables

These resolve on non-singular pages (archives, taxonomy pages, search):

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{term_name}}` | Current taxonomy term name | `Marketing` |
| `{{term_description}}` | Term description | `All marketing articles` |
| `{{term_url}}` | Term archive URL | `https://example.com/category/marketing/` |
| `{{archive_title}}` | Archive page title | `Portfolio` |
| `{{archive_url}}` | Current archive URL | `https://example.com/portfolio/` |
| `{{search_query}}` | Current search query | `schema markup` |

### Custom Meta Variables

Access any `post_meta` value:

| Variable | Description |
|----------|-------------|
| `{{meta:custom_key}}` | Value of `get_post_meta($post_id, 'custom_key', true)` |
| `{{meta:_price}}` | Raw WooCommerce price meta — prefer `{{product_price}}` below, which normalizes it |
| `{{meta:project_client}}` | Example: custom client field |

### WooCommerce Variables

Only resolve — and only listed in the Help tab's Variable Reference — when WooCommerce is active. All return `''` on a post that is not a `WC_Product`.

| Variable | Description | Example Output |
|----------|-------------|----------------|
| `{{product_price}}` | Current price — sale price if on sale, else regular price. For a variable product with a real price range, using this inside an `Offer.price` auto-upgrades that node to an `AggregateOffer` — see [WooCommerce Compatibility](#woocommerce-compatibility) | `29.90` |
| `{{product_regular_price}}` | Regular (non-sale) price | `34.90` |
| `{{product_sale_price}}` | Sale price, or `''` when not on sale | `29.90` |
| `{{product_currency}}` | Store currency code | `EUR` |
| `{{product_sku}}` | Product SKU | `WP-PENNANT-01` |
| `{{product_availability}}` | Stock status as a schema.org URL | `https://schema.org/InStock` |
| `{{product_rating}}` | Average rating, or `''` with no reviews or ratings disabled | `4.5` |
| `{{product_review_count}}` | Approved review count, same gate as `product_rating` | `12` |
| `{{product_brand}}` | First term from the `product_brand` taxonomy, if the taxonomy is registered | `Acme` |

`product_availability` mirrors WooCommerce core's own mapping (`is_in_stock()` → `InStock`/`OutOfStock`, `onbackorder` stock status → `BackOrder`) so a t1 Schema `Offer.availability` value always agrees with what WooCommerce itself would report, even when WooCommerce's own structured data is suppressed on that page (see [WooCommerce Compatibility](#woocommerce-compatibility) below).

### Custom Variables (Site Constants)

Define your own reusable values in **Dashboard → Custom Variables**. These are site-wide constants — the same value everywhere, regardless of which page renders.

**Use case:** Your phone number, address, logo URL, or any value that appears in multiple schemas.

**How to use:**

1. Open the t1 Schema dashboard → scroll to the **Custom Variables** section
2. Add a key (e.g. `phone`) and a value (e.g. `+43 1 234 5678`)
3. Click **Save Changes**
4. Use `{{custom.phone}}` in any schema property — it resolves to `+43 1 234 5678` on every page

| Variable | Description |
|----------|-------------|
| `{{custom.phone}}` | Your phone number |
| `{{custom.address}}` | Business address |
| `{{custom.logo_url}}` | Logo image URL |
| `{{custom.legal_name}}` | Legal company name |
| `{{custom.social_twitter}}` | Twitter/X profile URL |

Custom variables are stored as a single WP option (`t1schema_custom_variables`) and are also accessible in the variable picker when editing schemas. They appear under the **Custom** category with a preview of their current value.

---

## Health Validation

t1 Schema validates every schema against its type definition from the registry.

### Error Levels

| Level | Meaning | Badge Color |
|-------|---------|-------------|
| **Error** | A required property is missing or empty | 🔴 Red |
| **Warning** | A recommended property is missing | 🟡 Yellow |
| **Info** | A recommended property is missing on a rule-level schema (may be set per-post) | 🔵 Blue |
| **Valid** | All required and recommended properties are set | 🟢 Green |
| **Valid (Custom)** | Valid, with info-level notes for custom types | 🔵 Blue |

### Where Validation Shows

1. **Dashboard** — health badge on each schema card (click to expand details)
2. **Global Editor sidebar** — live validation updates as you type
3. **Local Editor sidebar** — per-schema health with fix suggestions
4. **Post Editor Meta Box** — compact health badge + inline error messages
5. **WP-CLI** — `wp t1-schema health` outputs a full report

### Fix Suggestions

When you expand a health detail panel, each issue includes:
- The exact error/warning message (e.g. "Missing required property: 'headline' for type 'Article'")
- A suggested fix with the specific variable to use (e.g. "Use `{{post_title}}`")

---

## WP-CLI Reference

All commands are under the `wp t1-schema` namespace.

### Global Schemas

```bash
# List all global schemas
wp t1-schema globals
wp t1-schema globals --format=json

# Create a new global schema
wp t1-schema create Organization --name="My Company" --url="https://example.com"
wp t1-schema create WebSite --name="My Site" --url="https://example.com"
wp t1-schema create Product --name="My Product" --status=draft

# Create from a JSON file (recommended for complex schemas / {{variables}})
wp t1-schema create Article --json-file=article.json

# Update a schema
wp t1-schema update 1 --name="New Name"
wp t1-schema update 1 --json-file=updated.json
wp t1-schema update 1 --status=draft

# Delete a schema
wp t1-schema delete 3
wp t1-schema delete 3 --yes

# Inspect full JSON-LD
wp t1-schema inspect 1
wp t1-schema inspect 1 --resolved   # resolves all {{variables}}
```

### Local Schemas (Per-Post)

```bash
# List local schemas on a post
wp t1-schema local 42
wp t1-schema local 42 --format=json

# Add a local schema to a post
wp teil1-schema-manager set-local 42 Article --schema-json='{"headline":"{{post_title}}","datePublished":"{{post_date}}"}'

# Replace all existing locals (instead of appending)
wp teil1-schema-manager set-local 42 Article --schema-json='{"headline":"{{post_title}}"}' --replace

# Don't override global schema of same type
wp teil1-schema-manager set-local 42 Organization --schema-json='{"name":"Local Branch"}' --no-override

# Clear all local schemas from a post
wp t1-schema clear-local 42
wp t1-schema clear-local 42 --yes
```

### Schema Rules

```bash
# List all schema rules
wp t1-schema rules
wp t1-schema rules --format=json

# Create a rule — Article for all blog posts
wp t1-schema add-rule Article \
  --conditions='[{"type":"singular","value":"post"}]' \
  --schema-json='{"headline":"{{post_title}}","datePublished":"{{post_date}}"}'

# Create a rule — CollectionPage for a CPT archive
wp t1-schema add-rule CollectionPage \
  --conditions='[{"type":"archive","value":"portfolio"}]' \
  --name="Portfolio Archive"

# Delete a rule
wp t1-schema delete-rule 3
wp t1-schema delete-rule 3 --yes
```

### Health Check

```bash
wp t1-schema health
wp t1-schema health --format=json
```

### Render — Preview Merged Output

Shows the final JSON-LD that would render in `<head>` for a specific post — globals + matching rules + local overrides, with variable resolution.

```bash
wp t1-schema render 42
wp t1-schema render 42 --raw      # without variable resolution
wp t1-schema render 42 --layers   # show each layer before merging
```

### Export — Full Backup

Exports all globals, rules, and local schemas to JSON. Use for site migration.

```bash
wp teil1-schema-manager export > backup.json
wp teil1-schema-manager export --globals-only
wp teil1-schema-manager export --rules-only
```

### Coverage — Site-Wide Audit

CLI version of the Site Map tab — shows schema coverage across all contexts.

```bash
wp t1-schema coverage
wp t1-schema coverage --format=json
```

### Doctor — Diagnostics

Checks database tables, duplicate types, plugin conflicts (including WooCommerce's own structured data — see [WooCommerce Compatibility](#woocommerce-compatibility)), health across all layers, orphaned schemas.

```bash
wp t1-schema doctor
```

### Utility Commands

```bash
wp t1-schema types       # list all Schema.org types
wp t1-schema variables   # list all dynamic variables
```

### Custom Variables (Site Constants)

```bash
# List all custom variables
wp t1-schema vars
wp t1-schema vars --format=json

# Set a custom variable (creates or updates)
wp t1-schema set-var phone "+43 1 234 5678"
wp t1-schema set-var address "Musterstraße 1, 1010 Wien"
wp t1-schema set-var logo_url "https://example.com/logo.svg"

# Delete a custom variable
wp t1-schema delete-var phone
wp t1-schema delete-var phone --yes
```

Once set, use `{{custom.phone}}` etc. in any schema property.

### Bulk Import

Import accepts two formats:

1. **Export format** (round-trip with `wp teil1-schema-manager export`):
   ```json
   {"globals": [...], "rules": [...], "locals": [...]}
   ```

2. **Flat array** (local schemas only):
   ```json
   [{"post_id": 42, "type": "Article", "data": {"headline": "..."}}]
   ```

```bash
# Round-trip: export from staging, import to production
wp teil1-schema-manager export > backup.json  # on staging
wp teil1-schema-manager import backup.json    # on production

# Preview before importing
wp teil1-schema-manager import backup.json --dry-run
```

### Batch Operations

```bash
# Add Article schema to all blog posts
for id in $(wp post list --post_type=post --format=ids); do
  wp teil1-schema-manager set-local $id Article --schema-json='{"headline":"{{post_title}}","datePublished":"{{post_date}}"}'
done

# Create rules for all CPTs
for pt in $(wp post-type list --public --field=name); do
  wp t1-schema add-rule WebPage --conditions="[{\"type\":\"singular\",\"value\":\"${pt}\"}]"
done
```

---

## Schema Types Reference

t1 Schema ships with 34 built-in Schema.org types. `wp t1-schema types` lists every one with its full property breakdown; the most commonly used are:

| Type | Parent | Required Properties |
|------|--------|---------------------|
| **Organization** | Thing | name, url, logo |
| **LocalBusiness** | Organization | name, url, address, telephone, image |
| **WebSite** | CreativeWork | name, url |
| **WebPage** | CreativeWork | name, url |
| **Article** | CreativeWork | headline, image, datePublished, author |
| **BlogPosting** | Article | headline, image, datePublished, author |
| **Product** | Thing | name, image, offers |
| **Offer** | Thing | price, priceCurrency |
| **AggregateOffer** | Offer | lowPrice, highPrice, priceCurrency |
| **FAQPage** | WebPage | mainEntity |
| **Question** | CreativeWork | name, acceptedAnswer |
| **Answer** | CreativeWork | text |
| **HowTo** | CreativeWork | name, step |
| **HowToStep** | Thing | text |
| **Event** | Thing | name, startDate, location |
| **Person** | Thing | name |
| **BreadcrumbList** | Thing | itemListElement |
| **ListItem** | Thing | position, name, item |
| **Review** | CreativeWork | author, reviewRating |
| **AggregateRating** | Thing | ratingValue, reviewCount |
| **VideoObject** | CreativeWork | name, description, thumbnailUrl, uploadDate |
| **ImageObject** | CreativeWork | contentUrl |
| **Service** | Thing | name |
| **SoftwareApplication** | CreativeWork | name, offers |
| **Course** | CreativeWork | name, description |
| **Recipe** | HowTo | name, image, recipeIngredient, recipeInstructions |
| **JobPosting** | Thing | title, description, datePosted, hiringOrganization, jobLocation |
| **CreativeWork** | Thing | name |
| **Thing** | — | name |

---

## Priority & Override Logic

### Same-Type Override

When multiple layers output the same `@type`, only the highest-priority version renders:

```
Post ID 42 has:
  Global:  Article (headline: "Default")
  Rule:    Article (headline: "{{post_title}}")    → overrides Global
  Local:   Article (headline: "Custom Override")   → overrides Rule + Global

Result: only "Custom Override" Article renders.
```

### Different-Type Coexistence

Different `@type`s from different layers **all render together**:

```
Post ID 42 has:
  Global:  Organization, WebSite
  Rule:    Article (for all posts)
  Local:   FAQPage

Result: Organization + WebSite + Article + FAQPage all render in @graph.
```

### Local Override Toggle

Each local schema has an **"Override global"** toggle:
- **On** (default): replaces any global/rule schema of the same `@type`
- **Off**: coexists alongside globals/rules, even if the same `@type`

### `@id`-Based Node Merging

When multiple schemas in the graph share the same `@id` (after variable resolution), they are **merged into a single node** instead of being output as duplicates. Later nodes (locals) overwrite properties from earlier nodes (rules/globals).

```
Rule 3 produces:
  { "@type": "DefinedTerm", "@id": "https://example.com/glossary/seo/#term",
    "name": "SEO", "url": "https://example.com/glossary/seo/" }

Local adds:
  { "@type": "DefinedTerm", "@id": "https://example.com/glossary/seo/#term",
    "sameAs": ["https://en.wikipedia.org/wiki/SEO"] }

Merged output:
  { "@type": "DefinedTerm", "@id": "https://example.com/glossary/seo/#term",
    "name": "SEO", "url": "...", "sameAs": ["..."] }
```

This eliminates duplicate `@id` entries — which are semantically invalid in JSON-LD.

### `@id` References in the UI

When a schema property contains only an `@id` reference (e.g. `"provider": {"@id": "{{site_url}}#organization"}`), the editor displays a compact **Linked Entity** badge instead of an empty object form. This prevents false "Required" warnings on fields that are intentionally resolved via graph linking.

### Auto-Generated BreadcrumbList

On hierarchical post types (Pages) with at least one ancestor, the plugin automatically generates a `BreadcrumbList` schema. The trail follows `Home → Ancestors → Current Page`.

**Conditions for auto-generation:**
- The current page is a singular, hierarchical post type (e.g. `page`)
- The page has at least one parent (top-level pages are excluded)
- No `BreadcrumbList` already exists in the graph (from rules, globals, or locals)

**Disable via filter:**
```php
add_filter( 't1schema_auto_breadcrumbs', '__return_false' );
```

### Rule Priority

Rules are evaluated by `priority` number (ascending). Lower = fires first:
- Priority 1: fires before Priority 10
- When two rules match the same context with the same `@type`, the lower-priority rule wins

---

## mu-Plugin Conflict Handling

t1 Schema can remove known conflicting schema output functions to prevent duplicate JSON-LD in `<head>`. This is **opt-in and off by default**, because it changes the behaviour of software t1 Schema does not own. Enable it under **Help → Settings → Suppress conflicting schema output**.

```php
// From t1-schema.php — reads the 't1schema_suppress_conflicts' option (default false)
if ( apply_filters( 't1schema_suppress_conflicts', (bool) get_option( 't1schema_suppress_conflicts', false ) ) ) {
    if ( function_exists( 'teil1_schema_output' ) ) {
        remove_action( 'wp_head', 'teil1_schema_output' );
    }
}
```

The setting is also **filterable** — force it on or off in code regardless of the stored option:

```php
add_filter( 't1schema_suppress_conflicts', '__return_true' );
```

**Action hook:** `t1schema_loaded` fires after initialization, so other plugins can detect that t1 Schema is active.

---

## WooCommerce Compatibility

`product` is a public custom post type like any other, so it works with Global Schemas, Schema Rules (`singular: product`, `archive: product`), and Local Overrides without any WooCommerce-specific setup.

**Duplicate structured data.** WooCommerce ships its own JSON-LD via `WC_Structured_Data`, on by default with no setting to turn it off. It emits `Product`, `Review`, `BreadcrumbList`, and `WebSite` markup on WooCommerce-templated pages. If a t1 Schema rule or local override also covers one of those types on the same page, the page ends up with two separate `<script type="application/ld+json">` blocks describing the same thing.

Turning on **Help → Settings → Suppress conflicting schema output** also covers this case. `WooCommerceCompat` runs on the `wp` hook, checks which `@type`s t1 Schema is about to render on the current request (reusing `Frontend::assemble_schemas()` — no extra render pass), and adds a `__return_empty_array` filter for each matching WooCommerce type:

| t1 Schema is rendering… | …suppresses WooCommerce's | via filter |
|---|---|---|
| `Product` | Product, Offer, AggregateRating, Review (all bundled into one markup array by WooCommerce) | `woocommerce_structured_data_product` |
| `Review` | Standalone per-comment Review on the reviews tab | `woocommerce_structured_data_review` |
| `BreadcrumbList` | WooCommerce's breadcrumb markup — t1 Schema auto-generates its own `BreadcrumbList` on every singular page by default, so this is the most common overlap even without an explicit rule | `woocommerce_structured_data_breadcrumblist` |
| `WebSite` | WooCommerce's WebSite markup on shop/archive/single-product templates | `woocommerce_structured_data_website` |

Only the overlapping type is suppressed. `Order` has no t1 Schema equivalent and is never touched. `WC_Structured_Data::generate_order_data()` is unaffected regardless of this setting.

The check runs independently of the mu-plugin conflict handling above and is filterable on its own:

```php
// Force WooCommerce suppression on or off, ignoring the shared t1schema_suppress_conflicts option
add_filter( 't1schema_suppress_woocommerce_conflicts', '__return_true' );
```

`wp t1-schema doctor` reports whether WooCommerce is detected and whether suppression is currently on.

**Variable products.** `{{product_price}}` alone can only resolve to one number, which understates a variable product (size/color options, etc.) as its cheapest variation. `WooCommerceOffers::expand()` runs on every render before variable resolution: if the current product is variable and its variations don't all share one active price, it rewrites the `Offer` node into a proper `AggregateOffer`:

```json
// You write, in the Rule/Local editor:
{ "@type": "Offer", "price": "{{product_price}}", "priceCurrency": "{{product_currency}}" }

// A variable product with a real price range renders as:
{ "@type": "AggregateOffer", "lowPrice": "19.99", "highPrice": "34.99", "offerCount": 4, "priceCurrency": "EUR" }
```

If every variation happens to share one active price, nothing is rewritten — the plain `Offer` with `{{product_price}}` is already correct and simpler. This only touches `Offer` nodes that actually use `{{product_price}}`; a hand-written static `Offer` is never altered. The price range itself is computed the same way WooCommerce's own `WC_Structured_Data::generate_product_data()` does (`get_variation_price( 'min'|'max', true )`, which already applies your site's tax display setting), so it agrees with what WooCommerce itself would report. This expansion is independent of the suppression setting above — it happens whether or not WooCommerce's own output is being suppressed.

---

## Hooks & Filters

### PHP Filters

```php
// Change the required capability (default: manage_options)
add_filter( 't1schema_required_capability', function() {
    return 'edit_posts'; // Allow editors
});

// Modify the final JSON-LD output
add_filter( 't1schema_jsonld_output', function( $html, $schemas ) {
    // $html = the full <script> tag
    // $schemas = array of schema data
    return $html;
}, 10, 2 );

// Custom variable resolution
add_filter( 't1schema_resolve_variable', function( $value, $tag, $post_id ) {
    if ( $tag === 'my_custom_var' ) {
        return 'Custom Value';
    }
    return $value;
}, 10, 3 );

// Custom condition matching for rules
add_filter( 't1schema_condition_match', function( $match, $condition, $context ) {
    if ( $condition['type'] === 'my_custom_condition' ) {
        return some_custom_check();
    }
    return $match;
}, 10, 3 );

// Override author data for custom author systems
add_filter( 't1schema_author_name', function( $name, $post ) {
    // Example: resolve from custom author profile
    $custom_id = get_post_meta( $post->ID, '_custom_author', true );
    if ( $custom_id ) {
        return get_custom_author_name( $custom_id );
    }
    return $name;
}, 10, 2 );

add_filter( 't1schema_author_avatar_url', function( $url, $post ) {
    // Same pattern for avatar
    return $url;
}, 10, 2 );

add_filter( 't1schema_author_url', function( $url, $post ) {
    // Same pattern for author URL
    return $url;
}, 10, 2 );

// Disable auto-generated BreadcrumbList
add_filter( 't1schema_auto_breadcrumbs', '__return_false' );

// Force suppression of conflicting schema plugins on, ignoring the stored setting
add_filter( 't1schema_suppress_conflicts', '__return_true' );

// Same, but scoped to WooCommerce's own structured data only
add_filter( 't1schema_suppress_woocommerce_conflicts', '__return_true' );
```

### Action Hooks

```php
// Fires after t1 Schema is loaded and mu-plugin conflicts are suppressed
do_action( 't1schema_loaded' );
```

---

## Troubleshooting

### Schema not appearing on the page

1. Check that the schema status is **active** (not draft)
2. For rules: verify the condition matches the current page. Use `wp t1-schema rules` to list conditions.
3. For local schemas: verify the post has the `_t1schema_local` meta. Use `wp t1-schema local <post_id>`.
4. Check for a higher-priority override: a local schema of the same `@type` will suppress a rule.
5. View source → search for `t1schema-jsonld`. If absent, the plugin may not be active.

### Variables showing as `{{post_title}}` instead of actual values

- Variables only resolve on the **frontend**. In the admin editor, you see the raw tags.
- Use `wp t1-schema inspect <id> --resolved` to preview resolved values.

### Health shows warnings for properties I don't need

- Warnings are for **recommended** (not required) properties. They won't break anything.
- Only errors (red) indicate actually missing required properties.

### Database tables not created

- Deactivate and reactivate the plugin, or visit any admin page (auto-migration runs on `plugins_loaded`).
- Verify PHP 8.0+ and WordPress 6.0+.

### Plugin folder is 70 MB

- This is normal during development. The `admin/node_modules/` folder (69 MB) contains build tools.
- The deployable zip is ~151 KB. A `.distignore` file defines what to exclude when building the distribution zip.

### WP-CLI commands not found

- WP-CLI commands only register when `WP_CLI` is defined. Run commands via `wp t1-schema` (not `php`).
- Verify the plugin is activated: `wp plugin list | grep t1-schema`.

### Duplicate Product/Review/BreadcrumbList JSON-LD on a WooCommerce store

- WooCommerce outputs its own structured data by default. If a t1 Schema rule or local override also covers `Product`, `Review`, `BreadcrumbList`, or `WebSite`, you'll see two `<script type="application/ld+json">` blocks for the same page.
- Turn on **Help → Settings → Suppress conflicting schema output**. t1 Schema will then remove only the overlapping WooCommerce output — see [WooCommerce Compatibility](#woocommerce-compatibility).
- Confirm it's working with `wp t1-schema doctor`, which reports WooCommerce detection and whether suppression is on.

---

## File Structure

```
teil1-schema-manager/
├── t1-schema.php                # Plugin bootstrap, autoloader, hooks
├── uninstall.php                # Cleanup on uninstall
├── readme.txt                   # WP.org standard readme
├── license.txt                  # GPL v2 license text
├── .distignore                  # Files excluded from distribution zip
├── includes/
│   ├── Activator.php            # DB table creation, default seeding
│   ├── Deactivator.php          # Deactivation hooks
│   ├── Admin.php                # Admin menu, asset enqueuing
│   ├── AdminBar.php             # Frontend admin bar schema indicator
│   ├── RestApi.php              # All REST API endpoints
│   ├── Frontend.php             # JSON-LD output in wp_head
│   ├── SchemaRegistry.php       # Schema.org type definitions
│   ├── SchemaValidator.php      # Health validation engine
│   ├── VariableResolver.php     # {{variable}} tag resolution
│   ├── ContextDetector.php      # WordPress context detection
│   ├── ConditionMatcher.php     # Rule condition evaluation
│   ├── MetaBox.php              # Post editor sidebar panel
│   ├── WooCommerceCompat.php    # Suppresses WooCommerce's own JSON-LD on overlapping types
│   ├── WooCommerceOffers.php    # Expands {{product_price}} into AggregateOffer for variable products
│   └── CLI.php                  # WP-CLI command class
├── data/
│   ├── schema-types.json        # Schema.org type registry (34 types)
│   └── valid-types.json         # Full Schema.org type list for validation
├── languages/
│   └── teil1-schema-manager.pot # Translation template
├── css/
│   └── admin-bar.css            # Frontend admin-bar styles (enqueued)
├── assets/                      # Built production JS + CSS (Vite output)
│   ├── app-*.js                 # React SPA bundle (~287 KB)
│   └── main-*.css               # Stylesheet (~33 KB)
├── .wordpress-org/              # WP.org directory assets (not in plugin zip)
│   ├── banner-1544x500.png      # Plugin banner
│   └── icon-256x256.png         # Plugin icon
└── admin/                       # Development source (not deployed)
    ├── src/                     # React source code
    ├── node_modules/            # NPM dependencies (dev only)
    ├── package.json             # NPM config
    └── vite.config.js           # Vite bundler config
```

---

## REST API Endpoints

All endpoints are under `/wp-json/teil1-schema-manager/v1/` and require `manage_options` capability.

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/globals` | GET | List all global schemas |
| `/globals` | POST | Create a global schema |
| `/globals/{id}` | GET | Get a single global schema |
| `/globals/{id}` | PUT | Update a global schema |
| `/globals/{id}` | DELETE | Delete a global schema |
| `/rules` | GET | List all schema rules |
| `/rules` | POST | Create a schema rule |
| `/rules/{id}` | GET | Get a single rule |
| `/rules/{id}` | PUT | Update a rule |
| `/rules/{id}` | DELETE | Delete a rule |
| `/local/{post_id}` | GET | Get local schemas for a post |
| `/local/{post_id}` | PUT | Save local schemas for a post |
| `/health` | GET | Global health report |
| `/health/{post_id}` | GET | Per-post health report |
| `/posts` | GET | Browse posts with schema status |
| `/post-types` | GET | List all public post types |
| `/site-structure` | GET | Full site context tree with coverage |
| `/contexts` | GET | Available condition types for builder |
| `/types` | GET | Schema.org type registry |
| `/variables` | GET | Available dynamic variables |
| `/custom-variables` | GET/PUT | Custom site-wide variables |
| `/settings` | GET/PUT | Plugin settings |
| `/parse-jsonld` | POST | Parse raw JSON-LD for import |
| `/score` | GET | Schema quality score (0–100) |
| `/recommended-rules` | GET | Suggested rule templates |
