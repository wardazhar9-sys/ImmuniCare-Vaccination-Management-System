#!/bin/sh
set -eu

ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
XAMPP_ROOT="${XAMPP_ROOT:-/Applications/XAMPP}"
XAMPP_CONTROL="${XAMPP_ROOT}/xamppfiles/xampp"
PHP_BIN="${PHP_BIN:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-vaccination_management_system}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
APP_PORT="${APP_PORT:-8080}"

if [ -z "$PHP_BIN" ]; then
    if [ -x "${XAMPP_ROOT}/bin/php" ]; then
        PHP_BIN="${XAMPP_ROOT}/bin/php"
    else
        PHP_BIN="$(command -v php || true)"
    fi
fi

if [ -z "$PHP_BIN" ]; then
    echo "PHP was not found. Set PHP_BIN or install XAMPP." >&2
    exit 1
fi

if [ -x "$XAMPP_CONTROL" ]; then
    "$XAMPP_CONTROL" startmysql >/dev/null 2>&1 || true
else
    echo "XAMPP controller not found at $XAMPP_CONTROL. Set XAMPP_ROOT or start MySQL manually." >&2
fi

attempt=0
while ! DB_HOST="$DB_HOST" DB_USER="$DB_USER" DB_PASSWORD="$DB_PASSWORD" DB_PORT="$DB_PORT" "$PHP_BIN" -r '
    mysqli_report(MYSQLI_REPORT_OFF);
    $c = @new mysqli(getenv("DB_HOST"), getenv("DB_USER"), getenv("DB_PASSWORD"), "", (int)getenv("DB_PORT"));
    exit($c->connect_errno ? 1 : 0);
'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 30 ]; then
        echo "MySQL did not become available on ${DB_HOST}:${DB_PORT}." >&2
        exit 1
    fi
    sleep 1
done

cd "$ROOT"
DB_HOST="$DB_HOST" \
DB_PORT="$DB_PORT" \
DB_NAME="$DB_NAME" \
DB_USER="$DB_USER" \
DB_PASSWORD="$DB_PASSWORD" \
APP_TIMEZONE="${APP_TIMEZONE:-Asia/Karachi}" \
"$PHP_BIN" setup/scripts/deploy.php

echo "ImmuniCare is available at http://127.0.0.1:${APP_PORT}"
exec "$PHP_BIN" -S "127.0.0.1:${APP_PORT}" -t "$ROOT"
