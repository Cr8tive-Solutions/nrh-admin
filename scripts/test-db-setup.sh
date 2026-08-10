#!/usr/bin/env bash
#
# Build the local Postgres test database used by BOTH nrh-admin and
# nrh-intelligence test suites.
#
# Why this exists: neither repo's migrations can rebuild the production schema.
# nrh-admin ships delta migrations only (they assume the shared base tables
# already exist), and nrh-intelligence is missing several tables that were added
# later via direct Schema:: calls. On top of that the code uses Postgres-only
# `ilike`, so the default sqlite :memory: driver cannot run the query paths we
# most need to test.
#
# So: we snapshot the live Supabase schema (STRUCTURE ONLY — never data) into a
# local Postgres database. Tests then run inside transactions that roll back, so
# the schema is loaded once and never mutated.
#
# Usage:
#   ./scripts/test-db-setup.sh            # reuse existing dump if present
#   ./scripts/test-db-setup.sh --refresh  # re-dump the schema from Supabase
#
# Requires: a running local Postgres, and pg_dump >= the Supabase server version
# (Supabase is on 17.x; Homebrew `libpq` ships an 18.x pg_dump).

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DUMP="${NRH_TEST_SCHEMA_DUMP:-${TMPDIR:-/tmp}/nrh_schema.sql}"

TEST_DB="${NRH_TEST_DB:-nrh_test}"
TEST_USER="${NRH_TEST_USER:-nrh_test}"
TEST_PASS="${NRH_TEST_PASS:-nrh_test}"
TEST_HOST="${NRH_TEST_HOST:-127.0.0.1}"
TEST_PORT="${NRH_TEST_PORT:-5432}"

# Local server binaries (psql/createdb). Override if your Postgres lives elsewhere.
PG_LOCAL_BIN="${NRH_PG_LOCAL_BIN:-/usr/local/opt/postgresql@15/bin}"
# Client binaries used to talk to Supabase — must be >= the remote server version.
PG_CLIENT_BIN="${NRH_PG_CLIENT_BIN:-/usr/local/opt/libpq/bin}"

psql_local() { "$PG_LOCAL_BIN/psql" -h "$TEST_HOST" -p "$TEST_PORT" "$@"; }

if ! "$PG_LOCAL_BIN/pg_isready" -h "$TEST_HOST" -p "$TEST_PORT" >/dev/null 2>&1; then
  echo "ERROR: no Postgres listening on $TEST_HOST:$TEST_PORT" >&2
  echo "Start it with:  $PG_LOCAL_BIN/pg_ctl -D /usr/local/var/postgresql@15 -l /tmp/pg15.log start" >&2
  exit 1
fi

# ── 1. Snapshot the production schema (structure only, never data) ───────────
if [[ "${1:-}" == "--refresh" || ! -f "$DUMP" ]]; then
  echo "==> Dumping schema from Supabase (structure only, no data)"
  # shellcheck disable=SC1091
  set -a; . "$REPO_ROOT/.env"; set +a
  PGPASSWORD="$DB_PASSWORD" "$PG_CLIENT_BIN/pg_dump" \
    -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
    --schema-only --schema=public --no-owner --no-privileges --no-comments \
    -f "$DUMP"

  # Refuse to continue if the dump somehow contains row data.
  if grep -qE '^(COPY|INSERT)' "$DUMP"; then
    echo "ERROR: dump contains data rows — aborting so production data is never copied." >&2
    exit 1
  fi
  echo "    $(grep -c '^CREATE TABLE' "$DUMP") tables -> $DUMP"
else
  echo "==> Reusing existing dump at $DUMP (pass --refresh to re-dump)"
fi

# ── 2. Recreate the local test role + database ───────────────────────────────
echo "==> Recreating role '$TEST_USER' and database '$TEST_DB'"
psql_local -d postgres -q -c "DROP DATABASE IF EXISTS $TEST_DB;"
psql_local -d postgres -q -c "DROP ROLE IF EXISTS $TEST_USER;"
psql_local -d postgres -q -c "CREATE ROLE $TEST_USER LOGIN PASSWORD '$TEST_PASS' SUPERUSER;"
psql_local -d postgres -q -c "CREATE DATABASE $TEST_DB OWNER $TEST_USER;"
# The dump carries its own `CREATE SCHEMA public`, so drop the one a fresh
# database is born with — otherwise the load fails with "already exists".
psql_local -d "$TEST_DB" -q -c "DROP SCHEMA IF EXISTS public CASCADE;"

# ── 3. Load the schema ───────────────────────────────────────────────────────
# The dump is produced by a newer pg_dump than the local server, so it may emit
# GUCs the local server doesn't recognise (e.g. `transaction_timeout`, added in
# PG17). Those are cosmetic session settings — strip them so we can still load
# with ON_ERROR_STOP=1 and catch genuine schema errors.
echo "==> Loading schema into $TEST_DB"
sed -E '/^SET (transaction_timeout|idle_session_timeout) /d' "$DUMP" \
  | PGPASSWORD="$TEST_PASS" psql_local -U "$TEST_USER" -d "$TEST_DB" -q -v ON_ERROR_STOP=1

COUNT=$(PGPASSWORD="$TEST_PASS" psql_local -U "$TEST_USER" -d "$TEST_DB" -tAc \
  "select count(*) from information_schema.tables where table_schema='public';")
echo "==> Done. $TEST_DB ready with $COUNT tables."
