# Translation guide

Teil1 Schema Manager uses English source strings and the WordPress text domain
`teil1-schema-manager`. WordPress chooses the runtime translation from the
current user/site locale; the plugin does not provide a separate language
switcher.

## German (`de_DE`)

- Use clear, neutral WordPress German and avoid unnecessary direct address.
- Keep `Teil1 Schema Manager`, `WordPress`, `WooCommerce`, `Schema.org`,
  `JSON-LD`, `Rich Results`, and product/plugin names unchanged.
- Never translate Schema.org identifiers such as `Organization`, `@type`,
  `@id`, property names, REST/JSON keys, command names, CLI options, URLs, or
  `{{variable}}` tokens.
- Preserve positional placeholders (`%1$s`, `%2$d`, and similar) exactly.
- Translate human-facing labels and explanations around technical identifiers.
- German uses two plural forms: `nplurals=2; plural=(n != 1);`.

The `de_DE` catalog is distinct from `de_DE_formal`, `de_AT`, and `de_CH`.
Those locales need their own reviewed catalogs.

## Build

Run from the repository root:

```bash
bash bin/build-i18n.sh
```

This regenerates the schema metadata catalog and POT file, merges the German
PO, rejects fuzzy or untranslated entries, compiles the MO, and creates the
JED JSON file used by the React admin application.

## Review and WordPress.org

Before release, a fluent German reviewer must inspect the full PO in context on
a German WordPress installation. Unreviewed machine translations must not be
submitted to translate.wordpress.org.

After release, import the reviewed translation into the German Stable and
Stable Readme projects. A de_DE PTE or GTE must approve entries as Current
before WordPress.org generates the community language pack.
