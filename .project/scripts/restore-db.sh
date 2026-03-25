#!/bin/bash
#
# Database restore using mysql-loader, with layer support.
#
# Usage:
#   restore-db.sh <database> <layer> [<layer>...]
#
# Databases:
#   local    — the local (staging) TYPO3 database
#   foreign  — the foreign (production) TYPO3 database
#
# Layers (applied in order):
#   base        — base TYPO3 + in2publish_core tables
#                 (/packages/in2publish_core/.project/data/dumps/)
#   in2publish  — enterprise overlay tables (workflow, wfpn)
#                 (/packages/in2publish/.project/data/dumps/)
#
# Examples:
#   restore-db.sh local base                  # in2publish_core tests
#   restore-db.sh foreign base                # in2publish_core tests
#   restore-db.sh local base in2publish       # in2publish tests
#   restore-db.sh foreign base in2publish     # in2publish tests

set -euo pipefail

MYSQL_LOADER="${MYSQL_LOADER:-/app/vendor/bin/mysql-loader}"
DB_HOST="${DB_HOST:-mysql}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root}"

LAYER_BASE=/packages/in2publish_core/.project/data/dumps
LAYER_IN2PUBLISH=/packages/in2publish/.project/data/dumps

if [ $# -lt 2 ]; then
    echo "Usage: $0 <database> <layer> [<layer>...]"
    echo "  database: local or foreign"
    echo "  layer:    base | in2publish (one or more, applied in order)"
    exit 1
fi

DATABASE="$1"
shift

for LAYER in "$@"; do
    case "$LAYER" in
        base)
            DUMP_PATH="${LAYER_BASE}/${DATABASE}"
            ;;
        in2publish)
            DUMP_PATH="${LAYER_IN2PUBLISH}/${DATABASE}"
            ;;
        *)
            echo "ERROR: Unknown layer '${LAYER}'. Valid layers: base, in2publish"
            exit 1
            ;;
    esac

    if [ ! -d "$DUMP_PATH" ]; then
        echo "ERROR: Dump directory not found: ${DUMP_PATH}"
        exit 1
    fi

    echo "Restoring ${DATABASE} [${LAYER}] from: ${DUMP_PATH}"
    "$MYSQL_LOADER" import \
        -H"$DB_HOST" \
        -u"$DB_USER" \
        -p"$DB_PASS" \
        -D"$DATABASE" \
        -f"$DUMP_PATH/"
done

echo "Database ${DATABASE} restored successfully."