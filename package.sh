#!/usr/bin/env bash
# Package Digital Badges as a WordPress plugin ZIP for upload/install.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="digital-badges"
DIST_DIR="${ROOT}/dist"
VERSION="$(
  grep -E '^\s*\*\s*Version:' "${ROOT}/digital-badges.php" \
    | head -1 \
    | sed -E 's/.*Version:[[:space:]]*//' \
    | tr -d '[:space:]'
)"
VERSION="${VERSION:-0.0.0}"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
ZIP_PATH="${DIST_DIR}/${ZIP_NAME}"

mkdir -p "${DIST_DIR}"
rm -f "${DIST_DIR}/${PLUGIN_SLUG}-"*.zip

# Stage into a temp dir so the ZIP root is the plugin folder (required by WP).
STAGE="$(mktemp -d)"
cleanup() {
  rm -rf "${STAGE}"
}
trap cleanup EXIT

STAGE_PLUGIN="${STAGE}/${PLUGIN_SLUG}"
mkdir -p "${STAGE_PLUGIN}"

rsync -a \
  --exclude '.git/' \
  --exclude '.gitignore' \
  --exclude '.DS_Store' \
  --exclude '.idea/' \
  --exclude '.vscode/' \
  --exclude '.cursor/' \
  --exclude 'node_modules/' \
  --exclude 'vendor/' \
  --exclude 'dist/' \
  --exclude 'coverage/' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude '*.log' \
  --exclude '.phpunit.result.cache' \
  --exclude 'package.sh' \
  --exclude '*.zip' \
  "${ROOT}/" "${STAGE_PLUGIN}/"

(
  cd "${STAGE}"
  zip -r "${ZIP_PATH}" "${PLUGIN_SLUG}" -x '*.DS_Store' '*/.git/*'
)

echo "Created ${ZIP_PATH}"
echo "Upload this ZIP via wp-admin → Plugins → Add New → Upload Plugin."
