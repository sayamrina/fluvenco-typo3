#!/usr/bin/env bash
# Rebuild the demo from scratch: fresh SQLite database, admin user, page tree and content.
# Admin credentials come from TYPO3_ADMIN_USER / TYPO3_ADMIN_PASSWORD.
set -euo pipefail
cd "$(dirname "$0")/.."
: "${TYPO3_ADMIN_USER:?set TYPO3_ADMIN_USER}" "${TYPO3_ADMIN_PASSWORD:?set TYPO3_ADMIN_PASSWORD}"
rm -rf var/sqlite var/cache config/system/settings.php config/sites/fluvenco/settings.yaml
cp config/sites/fluvenco/config.yaml /tmp/fluvenco-site-config.yaml
TYPO3_DB_DRIVER=sqlite \
TYPO3_SETUP_ADMIN_USERNAME="$TYPO3_ADMIN_USER" \
TYPO3_SETUP_ADMIN_PASSWORD="$TYPO3_ADMIN_PASSWORD" \
TYPO3_SETUP_ADMIN_EMAIL="${TYPO3_ADMIN_EMAIL:-admin@example.org}" \
TYPO3_PROJECT_NAME="Fluvenco" \
TYPO3_SERVER_TYPE=other \
TYPO3_SETUP_CREATE_SITE="http://localhost/" \
vendor/bin/typo3 setup --no-interaction --force >/dev/null
cp /tmp/fluvenco-site-config.yaml config/sites/fluvenco/config.yaml
rm -rf config/sites/main
vendor/bin/typo3 extension:setup >/dev/null
vendor/bin/typo3 fluvenco:seed
vendor/bin/typo3 cache:flush
