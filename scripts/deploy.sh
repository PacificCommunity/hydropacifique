#!/usr/bin/env bash
#
# Deploy HydroPacifique in production: build the image, start MySQL, load the
# database, then start the web container and smoke-test it.
#
#   ./scripts/deploy.sh                  # full deploy: build, DB, web
#   ./scripts/deploy.sh --sql <file>     # load this dump instead of the default
#   ./scripts/deploy.sh --reload-db      # restore over an existing database
#   ./scripts/deploy.sh --keep-db        # code only, leave the database alone
#   ./scripts/deploy.sh --schema-only    # empty 63-table schema, no rows
#
# The database step always runs through scripts/restore-db.sh, so a deploy gets
# the same snapshot-drop-import-verify procedure as a manual restore. What
# differs is only whether it is invoked:
#
#   empty database    -> restored automatically (nothing to lose)
#   populated + -y    -> kept; an unattended run never drops live data
#   populated         -> the operator is asked; default is to keep
#   --reload-db       -> restored without asking
#   --keep-db         -> never restored
#
set -euo pipefail

# Always operate from the repo root, whatever directory the script is called from.
REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

readonly BACKUP_DIR="db_backup"
readonly SCHEMA_SQL="20260814-HP-DB-Structure.sql"

ASSUME_YES=0
DO_BUILD=1
PULL_BASE=0
RELOAD_DB=0
KEEP_DB=0
SCHEMA_ONLY=0
SQL_FILE=""

usage() {
    cat <<'EOF'
Usage: ./scripts/deploy.sh [options]

  --sql <file>    SQL dump to load (.sql or .sql.gz). Default: the newest
                  db_backup/<database>_*.sql[.gz]; if there is none, the
                  structure-only schema at the repo root.
  --schema-only   Load the structure-only schema even when dumps exist.
                  63 tables, zero rows — nobody can log in afterwards.
  --reload-db     Restore without asking, even if the database already has
                  tables. DESTRUCTIVE — the existing tables are dropped, after
                  a pre-restore snapshot is written to db_backup/.
  --keep-db       Never touch the database; deploy the code only.
  --no-build      Skip the image build and use the existing image.
  --pull          Refresh base images during the build (docker build --pull).
  -y, --yes       Skip confirmations (for unattended runs).
  -h, --help      Show this help.

Reads MYSQL_*, WEB_PORT and WEB_BIND from .env — copy .env.example first.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --sql)
            [[ $# -ge 2 ]] || { echo "Error: --sql needs a file path." >&2; exit 2; }
            SQL_FILE="$2"; shift 2 ;;
        --schema-only) SCHEMA_ONLY=1; shift ;;
        --reload-db)   RELOAD_DB=1; shift ;;
        --keep-db)     KEEP_DB=1; shift ;;
        --no-build)    DO_BUILD=0; shift ;;
        --pull)        PULL_BASE=1; shift ;;
        -y|--yes)      ASSUME_YES=1; shift ;;
        -h|--help)     usage; exit 0 ;;
        *)             echo "Unknown argument: $1" >&2; usage >&2; exit 2 ;;
    esac
done

[[ "$RELOAD_DB" -eq 1 && "$KEEP_DB" -eq 1 ]] \
    && { echo "Error: --reload-db and --keep-db contradict each other." >&2; exit 2; }

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Preflight — fail before touching anything
# ---------------------------------------------------------------------------

log "Preflight"

# docker compose (v2) vs docker-compose (v1)
if docker compose version >/dev/null 2>&1; then
    DC=(docker compose)
elif command -v docker-compose >/dev/null 2>&1; then
    DC=(docker-compose)
else
    die "Neither 'docker compose' nor 'docker-compose' is available. See README section 2."
fi

# A stopped daemon otherwise surfaces as a confusing failure three steps later.
docker info >/dev/null 2>&1 \
    || die "Cannot talk to the Docker daemon. Is it running, and is your user in the 'docker' group?"

[[ -f docker-compose.yml ]] || die "docker-compose.yml not found in $REPO_ROOT"
[[ -f .env ]] || die ".env not found. Run: cp .env.example .env && chmod 600 .env"

# Mounted over include/config_plateform.php; without it the app has no DB config.
[[ -f include/config_plateform.docker.php ]] \
    || die "include/config_plateform.docker.php is missing — the web container has no platform config."

# Read credentials without exporting the whole file into this shell.
env_get() {
    local key="$1"
    sed -n "s/^[[:space:]]*${key}=//p" .env | tail -n1 | sed 's/^"\(.*\)"$/\1/; s/^'\''\(.*\)'\''$/\1/'
}

DB_NAME="$(env_get MYSQL_DATABASE)"; DB_NAME="${DB_NAME:-hp-data-fj}"
DB_ROOT_PW="$(env_get MYSQL_ROOT_PASSWORD)"
# MYSQL_USER/MYSQL_PASSWORD are not read here: compose passes them to both
# containers, and restore-db.sh re-applies the app user's grants after an import.
HTTP_SERVER="$(env_get HP_HTTP_SERVER)"
WEB_PORT="$(env_get WEB_PORT)"; WEB_PORT="${WEB_PORT:-8080}"
WEB_BIND="$(env_get WEB_BIND)"; WEB_BIND="${WEB_BIND:-0.0.0.0}"

# compose interpolates these with ${VAR:?...}, so an empty value aborts the run
# midway. Catch it here instead, with a message that names the file.
for var in MYSQL_ROOT_PASSWORD MYSQL_USER MYSQL_PASSWORD HP_HTTP_SERVER HP_HTTPS_SERVER; do
    [[ -n "$(env_get "$var")" ]] || die "$var is not set in .env"
done
ok "compose: ${DC[*]} · database: ${DB_NAME}"

# --- Production sanity checks (warnings, never fatal) -----------------------

# The app builds absolute links and redirects from HTTP_SERVER; a localhost
# value sends every user back to their own machine after login.
case "$HTTP_SERVER" in
    *localhost*|*127.0.0.1*)
        warn "HP_HTTP_SERVER is ${HTTP_SERVER} — fine locally, but on a real"
        warn "  deployment logins will redirect users to their own machine." ;;
esac

if [[ -f .env.example ]]; then
    # Shipped credentials in a production .env are a real finding, not a nit.
    for var in MYSQL_ROOT_PASSWORD MYSQL_PASSWORD; do
        example_val="$(sed -n "s/^[[:space:]]*${var}=//p" .env.example | tail -n1)"
        if [[ -n "$example_val" && "$(env_get "$var")" == "$example_val" ]]; then
            warn "$var still has the .env.example value — rotate it before going live."
        fi
    done
fi

# .env holds the root password; it must not be world-readable.
PERMS="$(stat -f '%Lp' .env 2>/dev/null || stat -c '%a' .env 2>/dev/null || echo '')"
if [[ -n "$PERMS" && "$PERMS" != "600" ]]; then
    warn ".env is mode ${PERMS}; tighten it with: chmod 600 .env"
fi

# ---------------------------------------------------------------------------
# Decide which SQL to load
# ---------------------------------------------------------------------------
# Data dump by default, not the schema: the structure-only file has no rows in
# ad_user, so a deployment seeded from it has nobody who can log in.

if [[ "$SCHEMA_ONLY" -eq 1 ]]; then
    [[ -z "$SQL_FILE" ]] || die "--sql and --schema-only are mutually exclusive."
    SQL_FILE="$SCHEMA_SQL"
fi

if [[ -z "$SQL_FILE" ]]; then
    shopt -s nullglob
    # Named <database>_<YYYYMMDD_HHMMSS>.sql[.gz], so a descending filename sort
    # is chronological and survives scp/rsync rewriting mtimes. The database
    # prefix also excludes pre-restore_* snapshots written by restore-db.sh.
    CANDIDATES=("$BACKUP_DIR/${DB_NAME}"_*.sql "$BACKUP_DIR/${DB_NAME}"_*.sql.gz)
    shopt -u nullglob

    if [[ "${#CANDIDATES[@]}" -gt 0 ]]; then
        IFS=$'\n' CANDIDATES=($(printf '%s\n' "${CANDIDATES[@]}" | sort -r)); unset IFS
        SQL_FILE="${CANDIDATES[0]}"
    elif [[ -f "$SCHEMA_SQL" ]]; then
        SQL_FILE="$SCHEMA_SQL"
        warn "No dump in ${BACKUP_DIR}/ — falling back to the structure-only schema."
    else
        die "No SQL to load: ${BACKUP_DIR}/ has no ${DB_NAME}_*.sql[.gz] and ${SCHEMA_SQL} is missing."
    fi
fi

[[ -f "$SQL_FILE" ]] || die "SQL file not found: $SQL_FILE"
[[ -s "$SQL_FILE" ]] || die "SQL file is empty: $SQL_FILE"

if [[ "$SQL_FILE" == *.gz ]]; then
    command -v gzip >/dev/null || die "gzip is required to read $SQL_FILE"
    READ_SQL=(gzip -dc -- "$SQL_FILE")
else
    READ_SQL=(cat -- "$SQL_FILE")
fi

# Inspect the dump in ONE pass with awk. Not `grep -q` per check: those exit
# early, the producer dies of SIGPIPE, and with `set -o pipefail` the pipeline
# reports a failure that is really a race against the pipe buffer.
PROBE="$("${READ_SQL[@]}" | awk '
    /GTID_PURGED/                          { gtid++ }
    /^CREATE TABLE/                        { tables++ }
    /Dump completed/                       { marker++ }
    /^INSERT INTO/                         { inserts++ }
    /MySQL dump|CREATE TABLE|INSERT INTO/  { sqlish++ }
    END { printf "%d %d %d %d %d\n", gtid+0, tables+0, marker+0, sqlish+0, inserts+0 }
')" || die "Could not read $SQL_FILE (corrupt archive?)"

read -r GTID_HITS TABLES_IN_SQL MARKER_HITS SQLISH_HITS INSERTS_IN_SQL <<<"$PROBE"

[[ "$SQLISH_HITS" -gt 0 ]] || die "$SQL_FILE does not look like a MySQL dump."
[[ "$TABLES_IN_SQL" -gt 0 ]] || die "$SQL_FILE contains no CREATE TABLE statements."

# A dump made by a mismatched client carries GTID statements that abort the
# import with "@@GLOBAL.GTID_PURGED can only be set when GTID_EXECUTED is empty".
[[ "$GTID_HITS" -eq 0 ]] || die "$SQL_FILE contains GTID_PURGED statements and will fail to import.
     Re-dump with:  mysqldump --set-gtid-purged=OFF ..."

# Every mysqldump ends with this marker; its absence means a truncated file.
[[ "$MARKER_HITS" -gt 0 ]] \
    || die "$SQL_FILE has no 'Dump completed' trailer — it is truncated or still being written."

# Say plainly whether this file carries rows: a structure-only import leaves
# ad_user empty, and that is only ever intentional.
if [[ "$INSERTS_IN_SQL" -gt 0 ]]; then
    ok "SQL to load: ${SQL_FILE}"
    ok "  ${TABLES_IN_SQL} tables, ${INSERTS_IN_SQL} INSERT statements ($(du -h "$SQL_FILE" | cut -f1))"
else
    ok "SQL to load: ${SQL_FILE}"
    warn "  ${TABLES_IN_SQL} tables, STRUCTURE ONLY — no rows, so nobody will be able to log in."
fi

# ---------------------------------------------------------------------------
# Host-side volumes
# ---------------------------------------------------------------------------
# data/ is bind-mounted and must exist before the container starts; the
# entrypoint creates the subtree and chowns it to www-data from inside.

[[ -d map ]] || warn "map/ is missing — it is a read-only mount; map tiles will 404."
mkdir -p data
ok "data/ ready"

# ---------------------------------------------------------------------------
# Build
# ---------------------------------------------------------------------------

if [[ "$DO_BUILD" -eq 1 ]]; then
    log "Building the web image (first build takes a few minutes)"
    if [[ "$PULL_BASE" -eq 1 ]]; then
        "${DC[@]}" build --pull web
    else
        "${DC[@]}" build web
    fi
    ok "image built"
else
    log "Skipping build (--no-build)"
fi

# ---------------------------------------------------------------------------
# Database
# ---------------------------------------------------------------------------
# web stays down until the database is loaded, so nothing serves a half-seeded
# database. compose's depends_on only waits for healthy, not for seeded.

log "Starting the database"
"${DC[@]}" up -d db

mysql_root() {
    # MYSQL_PWD keeps the password out of the container's process list.
    "${DC[@]}" exec -T -e MYSQL_PWD="$DB_ROOT_PW" db \
        mysql -u root --default-character-set=utf8mb4 "$@"
}

log "Waiting for the database to accept connections"
for i in $(seq 1 60); do
    if mysql_root -e 'SELECT 1' >/dev/null 2>&1; then
        ok "database is ready"
        break
    fi
    [[ "$i" -eq 60 ]] && die "Database did not become ready within 120s. Check: ${DC[*]} logs db"
    sleep 2
done

count_tables() {
    mysql_root -N -B -e \
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';" 2>/dev/null || echo 0
}

CURRENT_TABLES="$(count_tables)"

# Every import goes through restore-db.sh — the empty-database case included.
# It is the procedure that is already proven in production: safety snapshot,
# DROP + CREATE, import, grants, table/station/ad_user verification, and web
# left deliberately stopped if anything fails. Re-implementing a "simpler"
# import here would be a second, less careful path to the same data.
[[ -x scripts/restore-db.sh ]] || die "scripts/restore-db.sh is missing or not executable."

restore_db() {
    log "Loading the database via scripts/restore-db.sh"
    echo

    # restore-db.sh asks for the database name to be typed out; -y only when
    # this deploy was already told not to ask. Spelled out as two calls rather
    # than a built-up argument array: expanding a possibly-empty array under
    # `set -u` is an "unbound variable" error on bash 3.2 (macOS ships 3.2).
    if [[ "$ASSUME_YES" -eq 1 ]]; then
        ./scripts/restore-db.sh -y "$SQL_FILE"
    else
        ./scripts/restore-db.sh "$SQL_FILE"
    fi

    echo
    # restore-db.sh dies on any failure, so reaching here means it verified.
    DB_LOADED=1
}

DB_LOADED=0

if [[ "$KEEP_DB" -eq 1 ]]; then
    log "Leaving the database untouched (--keep-db): ${CURRENT_TABLES} tables"

elif [[ "$CURRENT_TABLES" -eq 0 ]]; then
    # Nothing to lose — a deploy onto an empty volume always loads the data.
    log "Database is empty — loading ${SQL_FILE}"
    restore_db

elif [[ "$RELOAD_DB" -eq 1 ]]; then
    log "Replacing the populated database (--reload-db): ${CURRENT_TABLES} tables will be dropped"
    restore_db

elif [[ "$ASSUME_YES" -eq 1 ]]; then
    # An unattended run must never decide by itself to drop live data.
    log "Database already holds ${CURRENT_TABLES} tables — keeping it (unattended run)"
    warn "To replace it with ${SQL_FILE}, re-run with --reload-db."

else
    # Populated database, interactive run: make it the operator's call rather
    # than a flag they have to remember, but default to keeping the data.
    echo
    echo "   Database '${DB_NAME}' already holds ${CURRENT_TABLES} tables."
    echo "   Restoring would DROP them and import ${SQL_FILE}"
    echo "   (a pre-restore snapshot is written to ${BACKUP_DIR}/ first)."
    echo
    printf '   Restore the database from this dump? [y/N] '
    read -r reply
    if [[ "$reply" == [yY]* ]]; then
        restore_db
    else
        ok "keeping the existing database — deploying code only"
    fi
fi

# ---------------------------------------------------------------------------
# Web
# ---------------------------------------------------------------------------

log "Starting the web service"
"${DC[@]}" up -d web

# The image's HEALTHCHECK curls the app itself, so container health is a real
# end-to-end signal (Apache up, PHP parsing, index.php rendering).
log "Waiting for the web container to report healthy"
WEB_CID="$("${DC[@]}" ps -q web)"
[[ -n "$WEB_CID" ]] || die "web container did not start. Check: ${DC[*]} logs web"

WEB_HEALTH="unknown"
for i in $(seq 1 45); do
    WEB_HEALTH="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$WEB_CID" 2>/dev/null || echo unknown)"
    case "$WEB_HEALTH" in
        healthy) ok "web is healthy"; break ;;
        # An image built before the HEALTHCHECK was added reports "none";
        # the HTTP check below is then the only verdict.
        none)    warn "image has no healthcheck; relying on the HTTP check"; break ;;
    esac
    # Compared as a string rather than piped into `grep -q`: an early-exiting
    # grep can SIGPIPE the producer, which `set -o pipefail` then reports as a
    # failure of the check itself.
    WEB_RUNNING="$(docker inspect -f '{{.State.Running}}' "$WEB_CID" 2>/dev/null || echo unknown)"
    if [[ "$WEB_RUNNING" != "true" ]]; then
        "${DC[@]}" logs --tail=40 web >&2 || true
        die "web container exited. Full logs: ${DC[*]} logs web"
    fi
    [[ "$i" -eq 45 ]] && warn "web still '${WEB_HEALTH}' after 90s — see ${DC[*]} logs web"
    sleep 2
done

# HTTP smoke test from the host, which also proves the port publish works.
# 0.0.0.0 is not an address to connect to; a specific WEB_BIND is.
PROBE_HOST="$WEB_BIND"
[[ "$PROBE_HOST" == "0.0.0.0" || -z "$PROBE_HOST" ]] && PROBE_HOST="127.0.0.1"
PROBE_URL="http://${PROBE_HOST}:${WEB_PORT}/"

if command -v curl >/dev/null 2>&1; then
    log "Smoke-testing ${PROBE_URL}"
    # Redirect to the login page is a normal, healthy response here.
    CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "$PROBE_URL" || echo 000)"
    case "$CODE" in
        2*|3*) ok "HTTP ${CODE}" ;;
        000)   warn "no HTTP response from ${PROBE_URL} — check ${DC[*]} logs web" ;;
        *)     warn "HTTP ${CODE} from ${PROBE_URL} — check ${DC[*]} logs web" ;;
    esac
else
    warn "curl not found on the host; skipped the HTTP smoke test."
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------

if [[ "$DB_LOADED" -eq 1 ]]; then
    DB_STATUS="restored from ${SQL_FILE}"
else
    DB_STATUS="left as it was (not restored)"
fi

echo
"${DC[@]}" ps
cat <<EOF

  ────────────────────────────────────────────────────────────────
   DEPLOYED
  ────────────────────────────────────────────────────────────────
   Local URL       : ${PROBE_URL}
   Public URL      : ${HTTP_SERVER}
   Database        : ${DB_NAME} — $(count_tables) tables, ${DB_STATUS}
   Logs            : ${DC[*]} logs -f web
   Stop (keep data): ${DC[*]} down
  ────────────────────────────────────────────────────────────────

EOF
