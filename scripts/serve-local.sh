#!/usr/bin/env bash
#
# Run HydroPacifique in the terminal with PHP's built-in server, against the
# MySQL already running on this machine. No Docker involved.
#
#   ./scripts/serve-local.sh
#   ./scripts/serve-local.sh --port 8000
#   ./scripts/serve-local.sh --db-user root --db-password 'secret'
#   ./scripts/serve-local.sh --import                 # load the newest dump first
#
# include/config_plateform.php is the single source of truth for the database
# credentials and the public URL: this script reads them from there, and the
# --db-*/--port flags edit that file (after backing it up) rather than passing
# values the app would never see.
#
set -euo pipefail

REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

readonly CONFIG="include/config_plateform.php"
readonly BACKUP_DIR="db_backup"

HOST="127.0.0.1"
PORT=""
NEW_DB_NAME=""
NEW_DB_USER=""
NEW_DB_PASS=""
NEW_DB_HOST=""
PASS_GIVEN=0
DO_IMPORT=0
IMPORT_FILE=""
DEBUG=0
ASSUME_YES=0

usage() {
    cat <<'EOF'
Usage: ./scripts/serve-local.sh [options]

  --port <n>          Port for php -S (default: the port in HTTP_SERVER, else 8080).
  --host <addr>       Address to bind (default: 127.0.0.1).
  --db-host <addr>    Set DB_SERVER in include/config_plateform.php.
  --db-name <name>    Set DB_DATABASE.
  --db-user <user>    Set DB_SERVER_USERNAME.
  --db-password <pw>  Set DB_SERVER_PASSWORD (use '' for none).
  --import [file]     Import a dump before serving. With no file, the newest
                      db_backup/<database>_*.sql[.gz] is used. DESTRUCTIVE:
                      the target database is dropped and recreated.
  --debug             Show PHP errors in the browser and include deprecations.
  -y, --yes           Skip confirmations.
  -h, --help          Show this help.

The database credentials come from include/config_plateform.php — the same file
the application reads. Stop with Ctrl-C.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --port)        [[ $# -ge 2 ]] || { echo "Error: --port needs a value." >&2; exit 2; }
                       PORT="$2"; shift 2 ;;
        --host)        [[ $# -ge 2 ]] || { echo "Error: --host needs a value." >&2; exit 2; }
                       HOST="$2"; shift 2 ;;
        --db-host)     [[ $# -ge 2 ]] || { echo "Error: --db-host needs a value." >&2; exit 2; }
                       NEW_DB_HOST="$2"; shift 2 ;;
        --db-name)     [[ $# -ge 2 ]] || { echo "Error: --db-name needs a value." >&2; exit 2; }
                       NEW_DB_NAME="$2"; shift 2 ;;
        --db-user)     [[ $# -ge 2 ]] || { echo "Error: --db-user needs a value." >&2; exit 2; }
                       NEW_DB_USER="$2"; shift 2 ;;
        --db-password) [[ $# -ge 2 ]] || { echo "Error: --db-password needs a value." >&2; exit 2; }
                       NEW_DB_PASS="$2"; PASS_GIVEN=1; shift 2 ;;
        --import)      DO_IMPORT=1
                       # Optional value: only consume the next argument if it is
                       # not itself a flag.
                       if [[ $# -ge 2 && "$2" != -* ]]; then IMPORT_FILE="$2"; shift 2; else shift; fi ;;
        --debug)       DEBUG=1; shift ;;
        -y|--yes)      ASSUME_YES=1; shift ;;
        -h|--help)     usage; exit 0 ;;
        *)             echo "Unknown argument: $1" >&2; usage >&2; exit 2 ;;
    esac
done

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

confirm() {
    [[ "$ASSUME_YES" -eq 1 ]] && return 0
    local reply
    printf '   %s [y/N] ' "$1"
    read -r reply
    [[ "$reply" == [yY]* ]]
}

# ---------------------------------------------------------------------------
# PHP
# ---------------------------------------------------------------------------

log "Checking PHP"

command -v php >/dev/null 2>&1 || die "php is not on PATH. Install PHP 8.2+ (brew install php)."

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"

# composer.json requires >=8.0.30.
php -r 'exit(version_compare(PHP_VERSION, "8.0.30", ">=") ? 0 : 1);' \
    || die "PHP ${PHP_VERSION} is too old; composer.json requires >= 8.0.30."

# The Docker image runs 8.2 and that is what the dependency set is tested
# against; newer is allowed but worth saying out loud when something misbehaves.
if php -r 'exit(version_compare(PHP_VERSION, "8.4", ">=") ? 0 : 1);'; then
    warn "PHP ${PHP_VERSION} is newer than the 8.2 the app is deployed on —"
    warn "  expect deprecation noise from this 8.0-era codebase."
else
    ok "PHP ${PHP_VERSION}"
fi

# Extensions the app and its dependencies need. Missing intl/gd/zip surface late
# and confusingly (a blank PDF, a 500 on export), so check them up front.
#
# The module list is read ONCE into a string and matched with `case`, rather
# than `php -m | grep -q` per extension: `grep -q` exits at its first match,
# PHP then dies of SIGPIPE, and under `set -o pipefail` that reports as a failed
# check — so an extension that IS installed gets reported missing, and only the
# ones near the end of the alphabet (zip, zlib) appear to pass.
PHP_MODULES="$(php -m | tr 'A-Z' 'a-z' | tr '\n' ' ')"

MISSING=""
for ext in mysqli pdo_mysql gd zip intl calendar mbstring curl iconv dom simplexml xmlwriter fileinfo zlib; do
    case " ${PHP_MODULES} " in
        *" ${ext} "*) ;;
        *)            MISSING="${MISSING} ${ext}" ;;
    esac
done
[[ -z "$MISSING" ]] || die "PHP is missing required extensions:${MISSING}
     On macOS these ship with 'brew install php'; on Debian/Ubuntu install
     php-mysql php-gd php-zip php-intl php-mbstring php-curl php-xml."
ok "all required extensions present"

# ---------------------------------------------------------------------------
# Dependencies
# ---------------------------------------------------------------------------

if [[ ! -f vendor/autoload.php ]]; then
    log "vendor/ is missing — installing Composer dependencies"
    command -v composer >/dev/null 2>&1 \
        || die "composer is not on PATH and vendor/ is absent. Install Composer, then: composer install"
    composer install --no-interaction --no-progress
    ok "dependencies installed"
fi

# ---------------------------------------------------------------------------
# Platform config
# ---------------------------------------------------------------------------

log "Reading ${CONFIG}"

if [[ ! -f "$CONFIG" ]]; then
    warn "${CONFIG} does not exist — creating it with local defaults."
    cat > "$CONFIG" <<'PHP'
<?php

define('DB_SERVER', '127.0.0.1');
define('DB_SERVER_USERNAME', 'root');
define('DB_SERVER_PASSWORD', '');
define('DB_DATABASE', 'hp-data-fj');
define('INIT_T', 'Pacific');
define('HP_VERSION', 'Serveur');
define('HP_ACCES', 'Open');
define('HP_SERVEUR', 'Hydro Pacifique');
define('TITRE_SMALL', 'Hydro Pacifique');
define('HTTP_SERVER', 'http://127.0.0.1:8080/');
define('HTTPS_SERVER', 'http://127.0.0.1:8080/');
define('BACKGROUND_LOG', 'image/fond_index_fj.jpg');
define('BACKGROUND_LOG_NOMAD', 'image/fond_index_fj.jpg');
define('BACKGROUND_LOG_FOOTER', 'image/bkgd_footer.jpg');
define('LOGO_IMG', '');
PHP
    ok "created ${CONFIG}"
fi

# config_plateform.docker.php reads getenv() and throws at boot when the
# variables are absent — which is exactly the case under php -S.
if grep -q 'hp_env\|getenv(' "$CONFIG"; then
    die "${CONFIG} looks like the Docker config (it reads getenv()).
     Under php -S those variables are unset and it throws at boot.
     Move it aside and re-run to generate a literal local config:
       mv ${CONFIG} ${CONFIG}.docker-copy"
fi

# Read constants with PHP itself rather than parsing quotes with sed: the file
# is plain define() calls with no side effects, so this is exact. Values travel
# through the environment, never through the shell command line, so a password
# containing quotes or $ cannot break either helper.
cfg() {
    HP_FILE="$CONFIG" HP_KEY="$1" php <<'PHP' 2>/dev/null
<?php
require getenv('HP_FILE');
$key = getenv('HP_KEY');
echo defined($key) ? constant($key) : '';
PHP
}

# Rewrite one define() in place.
set_define() {
    local key="$1" value="$2"
    HP_FILE="$CONFIG" HP_KEY="$key" HP_VALUE="$value" php <<'PHP' || die "Failed to update ${key} in ${CONFIG}"
<?php
$file = getenv('HP_FILE');
$key = getenv('HP_KEY');
$value = getenv('HP_VALUE');

$src = file_get_contents($file);

// Single-quoted PHP string literal: only \ and ' need escaping.
$literal = "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";

$pattern = '/define\(\s*\'' . preg_quote($key, '/') . '\'\s*,.*?\);/s';
$out = preg_replace($pattern, "define('" . $key . "', " . $literal . ");", $src, 1, $count);

if ($count !== 1) {
    fwrite(STDERR, "could not rewrite {$key} in {$file}\n");
    exit(1);
}

file_put_contents($file, $out);
PHP
}

CONFIG_BACKED_UP=0
backup_config_once() {
    [[ "$CONFIG_BACKED_UP" -eq 1 ]] && return 0
    cp "$CONFIG" "${CONFIG}.bak.$(date +%s)"
    CONFIG_BACKED_UP=1
}

# --- Apply --db-* overrides --------------------------------------------------

apply_override() {
    local key="$1" new="$2" label="$3"
    local current
    current="$(cfg "$key")"
    [[ "$new" == "$current" ]] && return 0
    backup_config_once
    set_define "$key" "$new"
    ok "${label} -> ${4:-$new}"
}

[[ -n "$NEW_DB_HOST" ]] && apply_override DB_SERVER          "$NEW_DB_HOST" "DB_SERVER"
[[ -n "$NEW_DB_NAME" ]] && apply_override DB_DATABASE        "$NEW_DB_NAME" "DB_DATABASE"
[[ -n "$NEW_DB_USER" ]] && apply_override DB_SERVER_USERNAME "$NEW_DB_USER" "DB_SERVER_USERNAME"
[[ "$PASS_GIVEN" -eq 1 ]] && apply_override DB_SERVER_PASSWORD "$NEW_DB_PASS" "DB_SERVER_PASSWORD" "(set)"

DB_HOST="$(cfg DB_SERVER)"
DB_NAME="$(cfg DB_DATABASE)"
DB_USER="$(cfg DB_SERVER_USERNAME)"
DB_PASS="$(cfg DB_SERVER_PASSWORD)"
HTTP_SERVER="$(cfg HTTP_SERVER)"

[[ -n "$DB_NAME" ]] || die "DB_DATABASE is not defined in ${CONFIG}"
[[ -n "$DB_USER" ]] || die "DB_SERVER_USERNAME is not defined in ${CONFIG}"

ok "database: ${DB_USER}@${DB_HOST:-127.0.0.1}/${DB_NAME}"

# --- Keep the port and HTTP_SERVER in sync -----------------------------------
# The app builds absolute redirects from HTTP_SERVER. If it names a different
# port than php -S listens on, every login bounces straight back to the login
# page — the single most common way this setup "silently doesn't work".

CONFIG_PORT="$(printf '%s' "$HTTP_SERVER" | sed -n 's#^https\{0,1\}://[^/:]*:\([0-9]\{1,\}\).*#\1#p')"
[[ -z "$CONFIG_PORT" ]] && CONFIG_PORT=80

if [[ -z "$PORT" ]]; then
    PORT="$CONFIG_PORT"
    [[ "$PORT" == "80" ]] && PORT=8080
fi

if [[ "$PORT" != "$CONFIG_PORT" ]]; then
    warn "HTTP_SERVER names port ${CONFIG_PORT}, but the server will listen on ${PORT}."
    warn "  Left as-is, logins would redirect to the wrong port."
    if confirm "Update HTTP_SERVER/HTTPS_SERVER in ${CONFIG} to port ${PORT}?"; then
        backup_config_once
        set_define HTTP_SERVER  "http://${HOST}:${PORT}/"
        set_define HTTPS_SERVER "http://${HOST}:${PORT}/"
        HTTP_SERVER="http://${HOST}:${PORT}/"
        ok "HTTP_SERVER -> ${HTTP_SERVER}"
    else
        warn "continuing with mismatched ports — expect redirect loops at login"
    fi
fi

[[ "$CONFIG_BACKED_UP" -eq 1 ]] && ok "previous config saved as ${CONFIG}.bak.*"

# ---------------------------------------------------------------------------
# MySQL
# ---------------------------------------------------------------------------

log "Checking the local MySQL"

# Pick the newest client on the box, not the first on PATH. Anaconda ships a
# 5.7 mysql/mysqldump that shadows Homebrew's; against a MySQL 8/9 server it
# produces confusing auth failures and unusable dumps.
MYSQL_BIN=""
MYSQL_BIN_VER=""
for cand in $(which -a mysql 2>/dev/null) /opt/homebrew/bin/mysql /opt/homebrew/opt/mysql-client/bin/mysql /usr/local/bin/mysql /usr/bin/mysql; do
    [[ -x "$cand" ]] || continue
    # `|| true` on every one of these: under `set -e` a failing command
    # substitution aborts the script, and a broken binary on PATH must only
    # disqualify that candidate.
    ver="$("$cand" --version 2>/dev/null | sed -n 's/.*Distrib \([0-9][0-9.]*\).*/\1/p' || true)"
    [[ -n "$ver" ]] || ver="$("$cand" --version 2>/dev/null | sed -n 's/.*Ver \([0-9][0-9.]*\).*/\1/p' || true)"
    [[ -n "$ver" ]] || continue
    if [[ -z "$MYSQL_BIN" ]] || php -r 'exit(version_compare($argv[1], $argv[2], ">") ? 0 : 1);' "$ver" "$MYSQL_BIN_VER"; then
        MYSQL_BIN="$cand"; MYSQL_BIN_VER="$ver"
    fi
done

[[ -n "$MYSQL_BIN" ]] || die "No mysql client found. Install one: brew install mysql-client"

FIRST_ON_PATH="$(command -v mysql 2>/dev/null || true)"
if [[ -n "$FIRST_ON_PATH" && "$FIRST_ON_PATH" != "$MYSQL_BIN" ]]; then
    warn "PATH's mysql is ${FIRST_ON_PATH} — using the newer ${MYSQL_BIN} (${MYSQL_BIN_VER}) instead."
else
    ok "mysql client ${MYSQL_BIN_VER} (${MYSQL_BIN})"
fi

# MYSQL_PWD keeps the password out of this machine's process list. An empty
# password must stay unset rather than exported as "".
mysql_run() {
    if [[ -n "$DB_PASS" ]]; then
        MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -u "$DB_USER" --default-character-set=utf8mb4 "$@"
    else
        "$MYSQL_BIN" -h "${DB_HOST:-127.0.0.1}" -u "$DB_USER" --default-character-set=utf8mb4 "$@"
    fi
}

mysql_run -e 'SELECT 1' >/dev/null 2>&1 || die "Cannot connect to MySQL as '${DB_USER}'@'${DB_HOST:-127.0.0.1}'.
     Is the server running?   brew services list | grep mysql
     Wrong password?          ./scripts/serve-local.sh --db-password '<pw>'
     No password at all?      ./scripts/serve-local.sh --db-password ''"

SERVER_VERSION="$(mysql_run -N -B -e 'SELECT VERSION();' 2>/dev/null || echo unknown)"
ok "connected to MySQL ${SERVER_VERSION}"

TABLES="$(mysql_run -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';" 2>/dev/null || echo 0)"

# --- Import ------------------------------------------------------------------

if [[ "$DO_IMPORT" -eq 0 && "$TABLES" -eq 0 ]]; then
    warn "Database '${DB_NAME}' has no tables."
    if confirm "Import the newest dump from ${BACKUP_DIR}/ now?"; then
        DO_IMPORT=1
    else
        die "Nothing to serve — the app cannot start against an empty database.
     Load one with: ./scripts/serve-local.sh --import"
    fi
fi

if [[ "$DO_IMPORT" -eq 1 ]]; then
    if [[ -z "$IMPORT_FILE" ]]; then
        [[ -d "$BACKUP_DIR" ]] || die "${BACKUP_DIR}/ does not exist."
        # Sorted by filename: the <database>_YYYYMMDD_HHMMSS stamp makes
        # lexicographic order chronological, and unlike mtime it survives being
        # copied between machines. The database prefix also keeps
        # pre-restore_* snapshots from being picked up automatically.
        # `|| true`: with no matches ls exits non-zero, and under `set -o
        # pipefail` that would abort here instead of reaching the message below.
        IMPORT_FILE="$(ls -1 "$BACKUP_DIR"/"${DB_NAME}"_*.sql "$BACKUP_DIR"/"${DB_NAME}"_*.sql.gz 2>/dev/null | sort -r | head -n1 || true)"
        [[ -n "$IMPORT_FILE" ]] || die "No ${DB_NAME}_*.sql[.gz] found in ${BACKUP_DIR}/."
    fi

    [[ -f "$IMPORT_FILE" ]] || die "Dump not found: ${IMPORT_FILE}"
    [[ -s "$IMPORT_FILE" ]] || die "Dump is empty: ${IMPORT_FILE}"

    # One awk pass, not a grep per check: an early-exiting grep kills the
    # producer with SIGPIPE, and under `set -o pipefail` that reports as a
    # failure of whichever check happened to win the race.
    if [[ "$IMPORT_FILE" == *.gz ]]; then
        PROBE="$(gzip -dc -- "$IMPORT_FILE" | awk '/^CREATE TABLE/{t++} /Dump completed/{m++} END{printf "%d %d\n", t+0, m+0}')" \
            || die "Could not read ${IMPORT_FILE} (corrupt archive?)"
    else
        PROBE="$(awk '/^CREATE TABLE/{t++} /Dump completed/{m++} END{printf "%d %d\n", t+0, m+0}' "$IMPORT_FILE")"
    fi
    read -r TABLES_IN_DUMP MARKER <<<"$PROBE"

    [[ "$TABLES_IN_DUMP" -gt 0 ]] || die "${IMPORT_FILE} contains no CREATE TABLE statements."
    # Every mysqldump ends with this marker; without it the file is truncated.
    [[ "$MARKER" -gt 0 ]] || die "${IMPORT_FILE} has no 'Dump completed' trailer — it is truncated."

    echo
    echo "   Importing  : ${IMPORT_FILE} (${TABLES_IN_DUMP} tables)"
    echo "   Into       : ${DB_NAME} on ${DB_HOST:-127.0.0.1} (${TABLES} tables now, all DROPPED)"
    echo
    confirm "Proceed?" || die "Aborted. Nothing was changed."

    log "Recreating '${DB_NAME}'"
    # DROP rather than relying on the dump's own DROP TABLEs: that also clears
    # tables which no longer exist in the dump.
    mysql_run -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;
                  CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;"

    log "Importing"
    if [[ "$IMPORT_FILE" == *.gz ]]; then
        gzip -dc -- "$IMPORT_FILE" | mysql_run "$DB_NAME"
    else
        mysql_run "$DB_NAME" < "$IMPORT_FILE"
    fi

    TABLES="$(mysql_run -N -B -e \
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema='${DB_NAME}' AND table_type='BASE TABLE';")"
    [[ "$TABLES" -eq "$TABLES_IN_DUMP" ]] \
        || die "Table count mismatch (${TABLES} imported vs ${TABLES_IN_DUMP} expected)."
    ok "${TABLES} tables imported"
fi

USERS="$(mysql_run -N -B -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.ad_user;" 2>/dev/null || echo "n/a")"
if [[ "$USERS" == "0" ]]; then
    warn "ad_user is empty — nobody can log in. Load a data dump with --import."
else
    ok "${TABLES} tables, ${USERS} users"
fi

# --- sql_mode ----------------------------------------------------------------
# MySQL 8+ enables ONLY_FULL_GROUP_BY by default; this codebase predates it
# (225 files use SELECT DISTINCT, 35 use the older loose GROUP BY style). The
# db container sets --sql-mode; a host MySQL has to be told separately.

SQL_MODE="$(mysql_run -N -B -e 'SELECT @@GLOBAL.sql_mode;' 2>/dev/null || echo "")"
if [[ "$SQL_MODE" == *ONLY_FULL_GROUP_BY* ]]; then
    warn "MySQL has ONLY_FULL_GROUP_BY enabled — list pages will fail with"
    warn "  \"Expression #1 of ORDER BY clause is not in SELECT list\"."
    if confirm "Apply SET PERSIST sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'?"; then
        # PERSIST needs SUPER/SYSTEM_VARIABLES_ADMIN and MySQL 8.0+; if it is
        # refused, the session-level fallback is useless here (PHP opens its own
        # connection per request), so say so rather than pretending it worked.
        if mysql_run -e "SET PERSIST sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';" 2>/dev/null; then
            ok "sql_mode persisted (applies to new connections)"
        else
            warn "could not SET PERSIST — run this as an admin user:"
            warn "  mysql -u root -p -e \"SET PERSIST sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';\""
        fi
    fi
else
    ok "sql_mode is compatible"
fi

# ---------------------------------------------------------------------------
# Writable data tree
# ---------------------------------------------------------------------------
# Same set the Docker entrypoint creates. Exports and uploads write here, and a
# missing directory shows up as a 500 on an otherwise healthy page.

for dir in export export/temp uploads/files corrections photos_station csv pdf html txt; do
    mkdir -p "data/$dir"
done
ok "data/ tree ready"

# ---------------------------------------------------------------------------
# Port
# ---------------------------------------------------------------------------

# bash's /dev/tcp: a successful connect means something already listens there.
if (exec 3<>"/dev/tcp/${HOST}/${PORT}") 2>/dev/null; then
    exec 3<&- 2>/dev/null || true
    die "Port ${PORT} on ${HOST} is already in use.
     The Docker stack publishes ${PORT} too — stop it with 'docker compose down',
     or serve on another port:  ./scripts/serve-local.sh --port 8000"
fi

# ---------------------------------------------------------------------------
# Serve
# ---------------------------------------------------------------------------

# Mirrors docker/php.ini: the upload and execution limits are load-bearing for
# imports and PDF/spreadsheet generation, which are exactly the slow paths
# people test locally.
#
# An array, not a string: the error_reporting value contains spaces, and a
# word-split string would hand PHP "-d error_reporting=E_ALL" plus three
# garbage arguments.
PHP_ARGS=(
    -d upload_max_filesize=64M
    -d post_max_size=64M
    -d max_file_uploads=50
    -d max_execution_time=300
    -d max_input_time=300
    -d memory_limit=512M
    -d date.timezone=UTC
    -d log_errors=On
    -d session.cookie_httponly=1
    -d session.use_strict_mode=1
)

if [[ "$DEBUG" -eq 1 ]]; then
    # Errors in the page as well as the terminal, deprecations included.
    PHP_ARGS+=(-d display_errors=On -d display_startup_errors=On -d error_reporting=E_ALL)
else
    # Production-like: nothing rendered to the browser, but php -S still writes
    # its log to this terminal, which is where you want it.
    # E_STRICT is left out of the mask that docker/php.ini uses: the constant is
    # deprecated from PHP 8.4 and naming it is itself a deprecation notice.
    PHP_ARGS+=(
        -d display_errors=Off
        -d display_startup_errors=Off
        -d "error_reporting=E_ALL & ~E_DEPRECATED"
    )
fi

cat <<EOF

  ────────────────────────────────────────────────────────────────
   HydroPacifique — local PHP server
  ────────────────────────────────────────────────────────────────
   URL        : http://${HOST}:${PORT}/
   Database   : ${DB_USER}@${DB_HOST:-127.0.0.1}/${DB_NAME} (${TABLES} tables)
   PHP        : ${PHP_VERSION}$([[ "$DEBUG" -eq 1 ]] && echo "  [debug: errors shown in browser]")
   Config     : ${CONFIG}
   Stop       : Ctrl-C
  ────────────────────────────────────────────────────────────────

  Request log follows.

EOF

# exec: php -S becomes this process, so Ctrl-C reaches it directly and the exit
# status is PHP's.
exec php "${PHP_ARGS[@]}" -S "${HOST}:${PORT}" -t .
