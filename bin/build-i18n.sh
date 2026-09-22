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
PO_FILE="${LANG_DIR}/${DOMAIN}-de_DE.po"
MO_FILE="${LANG_DIR}/${DOMAIN}-de_DE.mo"
JED_FILE="${LANG_DIR}/${DOMAIN}-de_DE-t1schema-app.json"

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

if [[ ! -f "${PO_FILE}" ]]; then
	echo "ℹ German PO not present yet; POT generation complete."
	exit 0
fi

echo "→ Merging German translations…"
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
	echo "error: German catalog has ${untranslated_count} untranslated and ${fuzzy_count} fuzzy entries" >&2
	exit 1
fi

msgfmt --check --check-format -o "${MO_FILE}" "${PO_FILE}"

echo "→ Building JavaScript translations…"
MAP_FILE="$(mktemp)"
JSON_DIR="$(mktemp -d)"
cleanup() {
	rm -f "${MAP_FILE}"
	rm -rf "${JSON_DIR}"
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
	echo "error: expected one bundled JED file, found ${json_count}" >&2
	exit 1
fi

json_file="$(find "${JSON_DIR}" -type f -name '*.json' -print)"
cp "${json_file}" "${JED_FILE}"
echo "✓ Generated POT, German MO, and JED JSON artifacts."
