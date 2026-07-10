#!/usr/bin/env bash
# Package Fenton Digital Badges as a WordPress plugin ZIP for upload/install.
#
# Usage:
#   ./package.sh                 # bump patch (0.1.5 → 0.1.6), sync, zip
#   ./package.sh --bump minor    # bump minor (0.1.5 → 0.2.0)
#   ./package.sh --bump major    # bump major (0.1.5 → 1.0.0)
#   ./package.sh --set 1.0.0     # set an explicit version, sync, zip
#   ./package.sh --no-bump       # package current version without changing it
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="fenton-digital-badges"
DIST_DIR="${ROOT}/dist"
PLUGIN_FILE="${ROOT}/fenton-digital-badges.php"
README_FILE="${ROOT}/readme.txt"

BUMP="patch"
SET_VERSION=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-bump)
      BUMP="none"
      shift
      ;;
    --bump)
      BUMP="${2:-}"
      if [[ ! "${BUMP}" =~ ^(major|minor|patch)$ ]]; then
        echo "Usage: $0 --bump major|minor|patch" >&2
        exit 1
      fi
      shift 2
      ;;
    --set)
      SET_VERSION="${2:-}"
      if [[ ! "${SET_VERSION}" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.-]+)?$ ]]; then
        echo "Usage: $0 --set X.Y.Z" >&2
        exit 1
      fi
      BUMP="set"
      shift 2
      ;;
    -h|--help)
      sed -n '2,10p' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      echo "Try: $0 --help" >&2
      exit 1
      ;;
  esac
done

read_version() {
  grep -E '^\s*\*\s*Version:' "${PLUGIN_FILE}" \
    | head -1 \
    | sed -E 's/.*Version:[[:space:]]*//' \
    | tr -d '[:space:]'
}

bump_version() {
  local current="$1"
  local part="$2"
  local major minor patch
  IFS='.' read -r major minor patch <<<"${current}"
  major="${major:-0}"
  minor="${minor:-0}"
  patch="${patch:-0}"
  # Strip any pre-release / build suffix from patch (e.g. 5-beta → 5).
  patch="${patch%%[^0-9]*}"

  case "${part}" in
    major) echo "$((major + 1)).0.0" ;;
    minor) echo "${major}.$((minor + 1)).0" ;;
    patch) echo "${major}.${minor}.$((patch + 1))" ;;
    *)
      echo "Invalid bump part: ${part}" >&2
      exit 1
      ;;
  esac
}

sync_version() {
  local version="$1"
  local tmp

  # Plugin header: * Version: X.Y.Z
  tmp="$(mktemp)"
  sed -E "s/^( \* Version:[[:space:]]*).+$/\1${version}/" "${PLUGIN_FILE}" >"${tmp}"
  mv "${tmp}" "${PLUGIN_FILE}"

  # Runtime constant: define( 'FENTON_DIGITAL_BADGES_VERSION', 'X.Y.Z' );
  tmp="$(mktemp)"
  sed -E "s/^(define\( 'FENTON_DIGITAL_BADGES_VERSION', ')[^']+('[[:space:]]*\);)$/\1${version}\2/" "${PLUGIN_FILE}" >"${tmp}"
  mv "${tmp}" "${PLUGIN_FILE}"

  # WordPress.org readme stable tag.
  if [[ -f "${README_FILE}" ]]; then
    tmp="$(mktemp)"
    sed -E "s/^(Stable tag:[[:space:]]*).+$/\1${version}/" "${README_FILE}" >"${tmp}"
    mv "${tmp}" "${README_FILE}"
  fi
}

CURRENT_VERSION="$(read_version)"
CURRENT_VERSION="${CURRENT_VERSION:-0.0.0}"

case "${BUMP}" in
  none)
    VERSION="${CURRENT_VERSION}"
    ;;
  set)
    VERSION="${SET_VERSION}"
    ;;
  *)
    VERSION="$(bump_version "${CURRENT_VERSION}" "${BUMP}")"
    ;;
esac

if [[ "${VERSION}" != "${CURRENT_VERSION}" ]]; then
  sync_version "${VERSION}"
  echo "Version ${CURRENT_VERSION} → ${VERSION}"
else
  echo "Version ${VERSION}"
fi

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
  --exclude '.gitkeep' \
  "${ROOT}/" "${STAGE_PLUGIN}/"

(
  cd "${STAGE}"
  zip -r "${ZIP_PATH}" "${PLUGIN_SLUG}" -x '*.DS_Store' '*/.git/*'
)

echo "Created ${ZIP_PATH}"
echo "Upload this ZIP via wp-admin → Plugins → Add New → Upload Plugin,"
echo "or submit it at https://wordpress.org/plugins/developers/add/"
