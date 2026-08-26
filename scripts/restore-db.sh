#!/usr/bin/env bash
#
# Restore a HydroPacifique database dump into the running Docker stack.
#
#   ./scripts/restore-db.sh db_backup/hp-data-fj_20260823_184954.sql.gz
#
# Sequence: stop web -> safety-dump current DB -> drop & recreate -> import ->
# verify -> start web.
#
# The web container is stopped for the whole restore so nothing writes to a
# half-populated database. On success it is started again automatically. On
# FAILURE it is deliberately left down: serving a partially restored database is
# worse than serving nothing. The rollback command is printed if that happens.
#
set -euo pipefail

# ---------------------------------------------------------------------------
# Setup
# ---------------------------------------------------------------------------

# Always operate from the repo root, whatever directory the script is called from.
REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

readonly BACKUP_DIR="db_backup"
ASSUME_YES=0
DUMP=""

usage() {
    cat <<'EOF'
Usage: ./scripts/restore-db.sh [-y] [dump.sql|dump.sql.gz]

  -y, --yes    Skip the interactive confirmation (for unattended runs).
  -h, --help   Show this help.

With no dump given, the newest "<database>_<timestamp>.sql[.gz]" file in
db_backup/ is used. pre-restore_* safety snapshots are never auto-selected —
rolling back to one is always an explicit choice.

The dump is imported into the database named by MYSQL_DATABASE in .env.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        -y|--yes)  ASSUME_YES=1; shift ;;
        -h|--help) usage; exit 0 ;;
        -*)        echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
        *)
            if [[ -n "$DUMP" ]]; then
                echo "Error: more than one dump file given." >&2
                exit 2
            fi
            DUMP="$1"; shift ;;
    esac
done

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Preflight — fail before touching anything
# ---------------------------------------------------------------------------

# docker compose (v2) vs docker-compose (v1)
if docker compose version >/dev/null 2>&1; then
    DC=(docker compose)
elif command -v docker-compose >/dev/null 2>&1; then
    DC=(docker-compose)
else
    die "Neither 'docker compose' nor 'docker-compose' is available."
fi

[[ -f .env ]] || die ".env not found. Copy .env.example to .env and fill it in."

# Read credentials without exporting the whole file into this shell.
env_get() {
    local key="$1"
    sed -n "s/^[[:space:]]*${key}=//p" .env | tail -n1 | sed 's/^"\(.*\)"$/\1/; s/^'\''\(.*\)'\''$/\1/'
}

DB_NAME="$(env_get MYSQL_DATABASE)"; DB_NAME="${DB_NAME:-hp-data-fj}"
DB_ROOT_PW="$(env_get MYSQL_ROOT_PASSWORD)"
[[ -n "$DB_ROOT_PW" ]] || die "MYSQL_ROOT_PASSWORD is not set in .env"

# ---------------------------------------------------------------------------
# Resolve which dump to restore
# ---------------------------------------------------------------------------
# No argument means "the latest dump for this database".
#
# Sorted by FILENAME, not mtime: mysqldump names carry a YYYYMMDD_HHMMSS stamp,
# so lexicographic order is chronological order, and it survives file copies
# (scp/rsync rewrite mtimes, which would otherwise make an older dump look
# newest right after a transfer to the server). Descending sort also puts
# ".sql.gz" ahead of a same-stamp ".sql", so the compressed copy wins.
#
# pre-restore_* is excluded by construction — the glob requires the database
# prefix. That matters: those snapshots are written into this same directory by
# the restore below, so a bare second run would otherwise "restore the latest"
# and silently roll back the restore that just succeeded.
if [[ -z "$DUMP" ]]; then
    [[ -d "$BACKUP_DIR" ]] || die "No dump given and $BACKUP_DIR/ does not exist."

    shopt -s nullglob
    CANDIDATES=("$BACKUP_DIR/${DB_NAME}"_*.sql "$BACKUP_DIR/${DB_NAME}"_*.sql.gz)
    shopt -u nullglob

    [[ "${#CANDIDATES[@]}" -gt 0 ]] \
        || die "No dump given and no ${DB_NAME}_*.sql[.gz] file found in $BACKUP_DIR/.
     Create one with:
       mysqldump --lock-all-tables --set-gtid-purged=OFF --routines --triggers \\
                 --events --hex-blob --default-character-set=utf8mb4 '${DB_NAME}' \\
         | gzip > ${BACKUP_DIR}/${DB_NAME}_\$(date +%Y%m%d_%H%M%S).sql.gz"

    # Sort descending and take the first entry.
    IFS=$'\n' CANDIDATES=($(printf '%s\n' "${CANDIDATES[@]}" | sort -r)); unset IFS
    DUMP="${CANDIDATES[0]}"

    log "No dump given — selected the latest in ${BACKUP_DIR}/"
    ok "$DUMP"
fi

[[ -f "$DUMP" ]] || die "Dump file not found: $DUMP"
[[ -s "$DUMP" ]] || die "Dump file is empty: $DUMP"

# Decompress on the fly if needed.
if [[ "$DUMP" == *.gz ]]; then
    command -v gzip >/dev/null || die "gzip is required to read $DUMP"
    READ_DUMP=(gzip -dc -- "$DUMP")
else
    READ_DUMP=(cat -- "$DUMP")
fi

# Inspect the dump in ONE pass with awk.
#
# Deliberately not `grep -q` / `head -c` per check: those exit as soon as they
# have their answer, the producer (cat/gzip) then dies of SIGPIPE, and with
# `set -o pipefail` the whole pipeline reports failure. Which check appears to
# fail becomes a race against the pipe buffer. awk consumes the entire stream, so
# the producer always exits cleanly — and a multi-GB dump is read once, not four
# times. A non-zero status here is a genuine read error (e.g. a corrupt .gz).
PROBE="$("${READ_DUMP[@]}" | awk '
    /GTID_PURGED/                          { gtid++ }
    /^CREATE TABLE/                        { tables++ }
    /Dump completed/                       { marker++ }
    /MySQL dump|CREATE TABLE|INSERT INTO/  { sqlish++ }
    END { printf "%d %d %d %d\n", gtid+0, tables+0, marker+0, sqlish+0 }
')" || die "Could not read $DUMP (corrupt archive?)"

read -r GTID_HITS TABLES_IN_DUMP MARKER_HITS SQLISH_HITS <<<"$PROBE"

# Guard against pointing this at something that is not a MySQL dump.
[[ "$SQLISH_HITS" -gt 0 ]] || die "$DUMP does not look like a MySQL dump."

# A dump made by a mismatched client can carry GTID statements that abort the
# import with "@@GLOBAL.GTID_PURGED can only be set when GTID_EXECUTED is empty".
[[ "$GTID_HITS" -eq 0 ]] || die "$DUMP contains GTID_PURGED statements and will fail to import.
     Re-dump with:  mysqldump --set-gtid-purged=OFF ...
     (check you are not using an old mysqldump from another install:
      run 'which -a mysqldump' and compare its version against the server)"

# A truncated dump is the classic way to silently lose data. Every mysqldump ends
# with this marker, so its absence means the file is incomplete.
[[ "$MARKER_HITS" -gt 0 ]] \
    || die "$DUMP has no 'Dump completed' trailer — it is truncated or still being written."

[[ "$TABLES_IN_DUMP" -gt 0 ]] || die "$DUMP contains no CREATE TABLE statements."

# ---------------------------------------------------------------------------
# Bring the database up (web stays down until the very end)
# ---------------------------------------------------------------------------

mysql_root() {
    # MYSQL_PWD keeps the password out of the container's process list.
    "${DC[@]}" exec -T -e MYSQL_PWD="$DB_ROOT_PW" db \
        mysql -u root --default-character-set=utf8mb4 "$@"
}

log "Starting database service"
"${DC[@]}" up -d db

log "Waiting for the database to accept connections"
for i in $(seq 1 60); do
    if mysql_root -e 'SELECT 1' >/dev/null 2>&1; then
        ok "database is ready"
        break
    fi
    [[ "$i" -eq 60 ]] && die "Database did not become ready within 120s. Check: ${DC[*]} logs db"
    sleep 2
done

CURRENT_TABLES="$(mysql_root -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';" 2>/dev/null || echo 0)"

# ---------------------------------------------------------------------------
# Confirm — this is destructive
# ---------------------------------------------------------------------------

cat <<EOF

  ────────────────────────────────────────────────────────────────
   DESTRUCTIVE RESTORE
  ────────────────────────────────────────────────────────────────
   Target database : ${DB_NAME}
   Current tables  : ${CURRENT_TABLES}  (will be DROPPED)
   Restoring from  : ${DUMP}
   Tables in dump  : ${TABLES_IN_DUMP}
   Web downtime    : from now until the restore completes
  ────────────────────────────────────────────────────────────────

EOF

if [[ "$ASSUME_YES" -ne 1 ]]; then
    read -r -p "Type the database name to confirm: " reply
    [[ "$reply" == "$DB_NAME" ]] || die "Confirmation did not match. Nothing was changed."
fi

# ---------------------------------------------------------------------------
# Restore
# ---------------------------------------------------------------------------

RESTORE_OK=0
SAFETY_DUMP=""

on_exit() {
    local code=$?
    if [[ "$RESTORE_OK" -eq 1 ]]; then
        log "Starting web service"
        "${DC[@]}" up -d web
        ok "web is back up"
        echo
        ok "Restore complete: ${DB_NAME} restored from ${DUMP}"
        [[ -n "$SAFETY_DUMP" ]] && echo "     Pre-restore snapshot kept at: ${SAFETY_DUMP}"
    else
        echo
        warn "RESTORE DID NOT COMPLETE — web has been left STOPPED on purpose."
        warn "Serving a half-restored database would be worse than serving nothing."
        if [[ -n "$SAFETY_DUMP" && -s "$SAFETY_DUMP" ]]; then
            echo
            echo "  Roll back to the pre-restore state with:"
            echo "      ./scripts/restore-db.sh ${SAFETY_DUMP}"
        fi
        echo
        echo "  Inspect the failure:   ${DC[*]} logs db"
        echo "  Force web back up:     ${DC[*]} up -d web"
    fi
    exit "$code"
}
trap on_exit EXIT

log "Stopping web service (downtime starts now)"
"${DC[@]}" stop web
ok "web stopped"

# Snapshot whatever is there now, so a bad dump is always recoverable.
if [[ "$CURRENT_TABLES" -gt 0 ]]; then
    mkdir -p "$BACKUP_DIR"
    SAFETY_DUMP="${BACKUP_DIR}/pre-restore_$(date +%Y%m%d_%H%M%S).sql.gz"
    log "Snapshotting current database to ${SAFETY_DUMP}"
    # --lock-all-tables, not --single-transaction: the schema mixes MyISAM and
    # InnoDB, and MyISAM is not covered by a transaction snapshot.
    "${DC[@]}" exec -T -e MYSQL_PWD="$DB_ROOT_PW" db \
        mysqldump -u root \
            --lock-all-tables \
            --set-gtid-purged=OFF \
            --routines --triggers --events \
            --hex-blob \
            --default-character-set=utf8mb4 \
            "$DB_NAME" | gzip > "$SAFETY_DUMP"

    # grep -c (not `tail | grep -q`) for the same SIGPIPE/pipefail reason as the
    # preflight probe above: an early-exiting consumer would make this flaky.
    SNAP_MARKER="$(gzip -dc "$SAFETY_DUMP" | grep -c 'Dump completed' || true)"
    [[ "$SNAP_MARKER" -gt 0 ]] \
        || die "Safety snapshot is truncated — refusing to continue without a good rollback point."
    ok "snapshot verified ($(du -h "$SAFETY_DUMP" | cut -f1))"
else
    warn "target database is empty or absent; no safety snapshot needed"
fi

# Recreate the schema from scratch. DROP DATABASE (rather than relying on the
# dump's DROP TABLE statements) also clears tables that no longer exist in the
# dump, which would otherwise linger as stale data.
log "Dropping and recreating '${DB_NAME}'"
mysql_root -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;
               CREATE DATABASE \`${DB_NAME}\`
                 CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"
ok "empty database created"

log "Importing ${DUMP} (${TABLES_IN_DUMP} tables)"
"${READ_DUMP[@]}" | mysql_root "$DB_NAME"
ok "import finished"

# The app user's grants are created by the entrypoint only on first boot, so
# they survive a DROP DATABASE — but re-assert them in case the name changed.
APP_USER="$(env_get MYSQL_USER)"
if [[ -n "$APP_USER" ]]; then
    mysql_root -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${APP_USER}'@'%'; FLUSH PRIVILEGES;" \
        && ok "grants re-applied for '${APP_USER}'" \
        || warn "could not re-apply grants for '${APP_USER}' — check the app can still connect"
fi

# ---------------------------------------------------------------------------
# Verify
# ---------------------------------------------------------------------------

log "Verifying"
RESTORED_TABLES="$(mysql_root -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';")"

echo "     tables in dump: ${TABLES_IN_DUMP}"
echo "     tables restored: ${RESTORED_TABLES}"

[[ "$RESTORED_TABLES" -eq "$TABLES_IN_DUMP" ]] \
    || die "Table count mismatch (${RESTORED_TABLES} restored vs ${TABLES_IN_DUMP} expected)."

# The station table is central to the app; an empty one means a bad restore.
STATIONS="$(mysql_root -N -B -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.station;" 2>/dev/null || echo "n/a")"
echo "     station rows: ${STATIONS}"
[[ "$STATIONS" == "0" ]] && warn "station table is empty — is this the dump you meant?"

USERS="$(mysql_root -N -B -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.ad_user;" 2>/dev/null || echo "n/a")"
echo "     ad_user rows: ${USERS}"
[[ "$USERS" == "0" ]] && warn "ad_user is empty — nobody will be able to log in."

ok "verification passed"

RESTORE_OK=1
