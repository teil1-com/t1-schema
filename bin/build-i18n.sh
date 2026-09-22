#!/usr/bin/env bash
#
# Generate deterministic WordPress translation artifacts.
#
# Source strings stay in PHP and admin/src. The German PO is the reviewed
# translation source; MO and JED JSON files are generated from it.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOMAIN="teil1-schema-manager"
LANG_DIR="${ROOT}/languages"
POT_FILE="${LANG_DIR}/${DOMAIN}.pot"
LOCALES=( "de_DE" "de_DE_formal" )

for command in php wp msgattrib msgfmt msgmerge python3; do
	if ! command -v "${command}" >/dev/null 2>&1; then
		echo "error: ${command} is required to build translations" >&2
		exit 1
	fi
done

WP_BIN="$(command -v wp)"
run_wp() {
	php -d error_reporting=24575 "${WP_BIN}" "$@"
}

echo "→ Generating Schema.org translation catalog…"
php "${ROOT}/bin/generate-schema-translations.php"

echo "→ Extracting PHP and JavaScript strings…"
run_wp i18n make-pot \
	"${ROOT}" \
	"${POT_FILE}" \
	--slug="${DOMAIN}" \
	--domain="${DOMAIN}" \
	--exclude="admin/node_modules,assets,dist,.github,.wordpress-org" \
	--package-name="Teil1 Schema Manager" \
	--headers='{"Report-Msgid-Bugs-To":"https://github.com/teil1-com/t1-schema/issues","POT-Creation-Date":""}'

MAP_FILE="$(mktemp)"
TEMP_DIR="$(mktemp -d)"
cleanup() {
	rm -f "${MAP_FILE}"
	rm -rf "${TEMP_DIR}"
}
trap cleanup EXIT

python3 - "${ROOT}" "${MAP_FILE}" <<'PY'
import json
import pathlib
import sys

root = pathlib.Path(sys.argv[1])
target = pathlib.Path(sys.argv[2])
mapping = {
    path.relative_to(root).as_posix(): "assets/app.js"
    for path in (root / "admin" / "src").rglob("*")
    if path.suffix in {".js", ".jsx"}
}
target.write_text(json.dumps(mapping, sort_keys=True), encoding="utf-8")
PY

for locale in "${LOCALES[@]}"; do
	PO_FILE="${LANG_DIR}/${DOMAIN}-${locale}.po"
	MO_FILE="${LANG_DIR}/${DOMAIN}-${locale}.mo"
	JED_FILE="${LANG_DIR}/${DOMAIN}-${locale}-t1schema-app.json"
	JSON_DIR="${TEMP_DIR}/${locale}"

	if [[ ! -f "${PO_FILE}" ]]; then
		echo "error: required ${locale} PO file is missing" >&2
		exit 1
	fi

	echo "→ Merging ${locale} translations…"
	msgmerge --update --backup=none "${PO_FILE}" "${POT_FILE}"

	untranslated_count="$(
		msgattrib --untranslated --no-obsolete --no-wrap "${PO_FILE}" |
			awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)"
	fuzzy_count="$(
		msgattrib --only-fuzzy --no-obsolete --no-wrap "${PO_FILE}" |
			awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)"
	if [[ "${untranslated_count}" -ne 0 || "${fuzzy_count}" -ne 0 ]]; then
		echo "error: ${locale} catalog has ${untranslated_count} untranslated and ${fuzzy_count} fuzzy entries" >&2
		exit 1
	fi

	msgfmt --check --check-format -o "${MO_FILE}" "${PO_FILE}"

	echo "→ Building ${locale} JavaScript translations…"
	mkdir -p "${JSON_DIR}"
	run_wp i18n make-json \
		"${PO_FILE}" \
		"${JSON_DIR}" \
		--domain="${DOMAIN}" \
		--extensions="js,jsx" \
		--no-purge \
		--pretty-print \
		--use-map="${MAP_FILE}"

	json_count="$(find "${JSON_DIR}" -type f -name '*.json' | wc -l | tr -d ' ')"
	if [[ "${json_count}" -ne 1 ]]; then
		echo "error: expected one ${locale} JED file, found ${json_count}" >&2
		exit 1
	fi

	json_file="$(find "${JSON_DIR}" -type f -name '*.json' -print)"
	cp "${json_file}" "${JED_FILE}"
done

echo "✓ Generated POT, German, and German Formal runtime artifacts."
