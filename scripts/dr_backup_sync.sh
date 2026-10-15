#!/usr/bin/env bash
# ============================================================
# Neilos Disaster Recovery & Remote Sync Launcher
# ============================================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON_BIN="/usr/bin/python3"

if [ ! -x "$PYTHON_BIN" ]; then
    PYTHON_BIN="$(which python3)"
fi

exec "$PYTHON_BIN" "$SCRIPT_DIR/dr_sync.py" "$@"
