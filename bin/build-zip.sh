#!/usr/bin/env bash
#
# Build a WordPress-ready distribution zip for Teil1 Schema Manager.
# Exclusions are read from .distignore at the repo root.
#
# Usage (from repo root):
#   ./bin/build-zip.sh
#
# Output:
#   dist/teil1-schema-manager-{version}.zip
# The folder inside the zip matches the assigned WordPress.org slug.

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG="teil1-schema-manager"
ZIP_NAME="teil1-schema-manager"
DIST_DIR="${ROOT}/dist"
STAGING="$(mktemp -d)"
EXCLUDES="$(mktemp)"

cleanup() {
	rm -rf "${STAGING}" "${EXCLUDES}"
}
trap cleanup EXIT

cd "${ROOT}"

VERSION="$(grep -m1 'Version:' t1-schema.php | awk '{print $3}')"
if [[ -z "${VERSION}" ]]; then
	echo "error: could not read plugin version from t1-schema.php" >&2
	exit 1
fi

echo "→ Building admin assets (v${VERSION})…"
cd "${ROOT}/admin"
# CI always starts from a clean checkout; skip the reinstall when deps are
# already present so local builds don't wipe node_modules.
if [[ ! -d node_modules ]]; then
	if [[ -f package-lock.json ]]; then
		npm ci
	else
		npm install
	fi
fi
npm run build

echo "→ Staging plugin files…"
cd "${ROOT}"
grep -v '^[[:space:]]*#' .distignore | grep -v '^[[:space:]]*$' > "${EXCLUDES}"
rsync -a --exclude-from="${EXCLUDES}" ./ "${STAGING}/${SLUG}/"

mkdir -p "${DIST_DIR}"
ZIP_PATH="${DIST_DIR}/${ZIP_NAME}-${VERSION}.zip"
rm -f "${ZIP_PATH}"

cd "${STAGING}"
zip -rq "${ZIP_PATH}" "${SLUG}"

echo "✓ Created ${ZIP_PATH} ($(du -h "${ZIP_PATH}" | awk '{print $1}'))"
