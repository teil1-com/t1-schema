# Teil1 Schema Manager

Schema.org JSON-LD markup for WordPress with granular control, built around three layers: site-wide Global Schemas, conditional Schema Rules, and per-page Local Overrides.

This repository contains the complete, unminified source for the plugin, including the React admin interface. The distributed plugin ships a compiled bundle; everything needed to reproduce that bundle from source is here.

- **Requires:** WordPress 6.0+, PHP 8.0+
- **License:** GPLv2 or later

## Repository layout

| Path | Contents |
| --- | --- |
| `t1-schema.php` | Plugin bootstrap, constants, and hook registration |
| `includes/` | PHP classes (admin, REST API, frontend rendering, WP-CLI, schema registry) |
| `admin/` | React admin interface source, built with Vite |
| `assets/` | Compiled JS/CSS output — generated, do not edit by hand |
| `data/` | Schema.org type definitions as JSON |
| `bin/build-zip.sh` | Produces the WordPress-ready distribution zip |

## Building from source

The admin interface is a React application compiled by [Vite](https://vitejs.dev/). Node.js 18 or newer is required.

```bash
cd admin
npm ci
npm run build
```

This reads `admin/vite.config.js` and writes the compiled bundle plus a manifest into `assets/` at the repository root. The PHP side resolves hashed filenames through `assets/manifest.json`, so the admin screen will not load until a build has been run at least once.

### Development

```bash
cd admin
npm run dev
```

Vite serves the app on `http://localhost:5173` with hot module replacement. Define `SCRIPT_DEBUG` as `true` in your `wp-config.php` and the plugin will load from the dev server instead of the compiled bundle.

### Tests

```bash
cd admin
npm test
```

## Building the distribution zip

```bash
./bin/build-zip.sh
```

The script builds the admin assets, stages the plugin using the exclusion list in `.distignore`, and writes `dist/teil1-schema-manager-{version}.zip`. The version is read from the `Version:` header in `t1-schema.php`.

The resulting zip deliberately omits `admin/`, `bin/`, and other development files. That is why the published plugin contains only compiled JavaScript — the corresponding source is the `admin/` directory in this repository, at the tag matching the released version.

## Releases

Pushing a `v*` tag triggers `.github/workflows/release.yml`, which runs the build script and attaches the zip to a GitHub Release.

```bash
git tag v2.0.0
git push origin v2.0.0
```

## WP-CLI

```bash
wp teil1-schema-manager doctor   # Environment and conflict diagnostics
wp teil1-schema-manager render <post>  # Print the JSON-LD graph for a post
wp teil1-schema-manager coverage       # Audit schema coverage across the site
wp teil1-schema-manager export > backup.json
wp teil1-schema-manager import backup.json
```

Run `wp help teil1-schema-manager` for the full command list.
