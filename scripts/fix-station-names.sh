#!/usr/bin/env bash
#
# Repair station rows that break the application: characters the importers
# cannot handle, and longitudes that strand a marker on the far side of the map.
#
#   ./scripts/fix-station-names.sh                 # docker stack, report + confirm
#   ./scripts/fix-station-names.sh --local         # host MySQL instead of docker
#   ./scripts/fix-station-names.sh --dry-run       # report only
#   ./scripts/fix-station-names.sh --fix-codes     # also turn _ into - in station codes
#   ./scripts/fix-station-names.sh --no-coords     # leave longitudes alone
#
# ---------------------------------------------------------------------------
# WHY
#
# APOSTROPHE in nom_station  ->  import appears to hang
#
#   Seven of the eight import loaders build the audit-log INSERT by
#   concatenating the station name straight into the SQL string, e.g.
#   load_data_chron.php:453-458. A name like "Nuku'alofa River Water Level"
#   closes the string literal early and the statement dies with a syntax error.
#
#   The damage is subtle: that INSERT runs AFTER the measurement data has been
#   committed, so the import itself succeeds. What fails is the audit-log write,
#   which throws an uncaught exception, so the script never reaches its
#   json_encode() response. The browser waits for a reply that never comes and
#   the upload looks like it has hung. Only the log entry is actually lost.
#
#   load_data_ra.php:636 already escapes this correctly; the other seven do not.
#   Renaming the stations sidesteps the bug without touching the code, but does
#   not fix it: a name typed with an apostrophe later will break again.
#
# UNDERSCORE in code_station  ->  import rejected outright
#
#   load_file.php:141-163 reads the station code as everything before the FIRST
#   underscore in the filename, and the series initial as what follows. A
#   station coded HY_001 can never be imported to: "HY_001_RF.csv" is read as
#   station "HY", series "001". Reported by default, fixed with --fix-codes,
#   because changing a station code affects every filename anyone already has.
#
# LONGITUDE ACROSS 180 DEGREES  ->  station missing from the map
#
#   Pacific territories straddle the antimeridian. Nadi sits at +177.4E and
#   Nuku'alofa at -175.2W: about 750 km apart on the ground, but 352 degrees
#   apart as numbers. index.php:469 hands the raw longitude to Leaflet, which
#   draws the marker where the number says - on the opposite edge of the world
#   from every other station. The row is fine, passes every filter and is never
#   skipped by the coordinate check; you simply cannot find its pin.
#
#   index.php:469-471 already carries a fix for exactly this, but it only fires
#   when the territory initials are 'KI', so a territory such as 'Pacific' is
#   left broken.
#
#   Each affected longitude is rewritten into the continuous frame anchored on
#   its own territory's map centre (geo_territoire.mapLong): 177.448194 becomes
#   -182.551806, the same physical point 360 degrees round, now 7.3 degrees west
#   of Nuku'alofa instead of 352 degrees east of it. carto.php and
#   process_index_map.php read the same column, so all three maps are fixed at
#   once. The cost is that the stored value is no longer canonical WGS84 - see
#   the closing note. Skip it with --no-coords.
# ---------------------------------------------------------------------------
#
set -euo pipefail

REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

readonly DEFAULT_CONTAINER="hydropacifique-db-1"
readonly CONFIG="include/config_plateform.php"

ASSUME_YES=0
DRY_RUN=0
FIX_CODES=0
FIX_COORDS=1
USE_LOCAL=0
CONTAINER=""

usage() {
    cat <<'EOF'
Usage: ./scripts/fix-station-names.sh [options]

  --local            Use the MySQL on this machine, with the credentials in
                     include/config_plateform.php, instead of the db container.
  --container <name> Address this container directly rather than using
                     `docker compose exec db` (default: hydropacifique-db-1).
  --fix-codes        Also replace '_' with '-' in station codes. Off by default:
                     it changes what every import filename must be called.
  --no-coords        Do not rewrite longitudes that sit more than 180 degrees
                     from their territory's map centre. On by default: without
                     it those stations have no findable pin on the map.
  --dry-run          Report what would change and exit without writing.
  -y, --yes          Skip the confirmation prompt.
  -h, --help         Show this help.

Prints an undo block before writing anything.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --local)     USE_LOCAL=1; shift ;;
        --container) [[ $# -ge 2 ]] || { echo "Error: --container needs a name." >&2; exit 2; }
                     CONTAINER="$2"; shift 2 ;;
        --fix-codes) FIX_CODES=1; shift ;;
        --no-coords) FIX_COORDS=0; shift ;;
        --dry-run)   DRY_RUN=1; shift ;;
        -y|--yes)    ASSUME_YES=1; shift ;;
        -h|--help)   usage; exit 0 ;;
        *)           echo "Unknown argument: $1" >&2; usage >&2; exit 2 ;;
    esac
done

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Connect
# ---------------------------------------------------------------------------

log "Preflight"

if [[ "$USE_LOCAL" -eq 1 ]]; then
    [[ -f "$CONFIG" ]] || die "$CONFIG not found - needed for the local database credentials."

    # Read the constants with PHP rather than parsing quotes: the file is plain
    # define() calls, so this is exact, and values travel via the environment.
    cfg() {
        HP_FILE="$CONFIG" HP_KEY="$1" php <<'PHP' 2>/dev/null
<?php
require getenv('HP_FILE');
$key = getenv('HP_KEY');
echo defined($key) ? constant($key) : '';
PHP
    }

    command -v php >/dev/null 2>&1 || die "php is not on PATH (needed to read $CONFIG)."

    DB_HOST="$(cfg DB_SERVER)";          DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_NAME="$(cfg DB_DATABASE)";        DB_NAME="${DB_NAME:-hp-data-fj}"
    DB_USER="$(cfg DB_SERVER_USERNAME)"; DB_USER="${DB_USER:-root}"
    DB_PASS="$(cfg DB_SERVER_PASSWORD)"

    # Pick the newest client on the box, not the first on PATH: Anaconda ships a
    # 5.7 mysql that shadows Homebrew's and misbehaves against a 8/9 server.
    MYSQL_BIN=""; MYSQL_VER=""
    for cand in $(which -a mysql 2>/dev/null) /opt/homebrew/bin/mysql \
                /opt/homebrew/opt/mysql-client/bin/mysql /usr/local/bin/mysql /usr/bin/mysql; do
        [[ -x "$cand" ]] || continue
        ver="$("$cand" --version 2>/dev/null | sed -n 's/.*Distrib \([0-9][0-9.]*\).*/\1/p' || true)"
        [[ -n "$ver" ]] || ver="$("$cand" --version 2>/dev/null | sed -n 's/.*Ver \([0-9][0-9.]*\).*/\1/p' || true)"
        [[ -n "$ver" ]] || continue
        if [[ -z "$MYSQL_BIN" ]] || php -r 'exit(version_compare($argv[1],$argv[2],">") ? 0 : 1);' "$ver" "$MYSQL_VER"; then
            MYSQL_BIN="$cand"; MYSQL_VER="$ver"
        fi
    done
    [[ -n "$MYSQL_BIN" ]] || die "No mysql client found."

    db() {
        if [[ -n "$DB_PASS" ]]; then
            MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "$DB_HOST" -u "$DB_USER" --default-character-set=utf8mb4 "$@"
        else
            "$MYSQL_BIN" -h "$DB_HOST" -u "$DB_USER" --default-character-set=utf8mb4 "$@"
        fi
    }
    ok "local MySQL ${MYSQL_VER} as ${DB_USER}@${DB_HOST}"
else
    command -v docker >/dev/null 2>&1 || die "docker is not on PATH. Use --local for a host MySQL."
    docker info >/dev/null 2>&1 \
        || die "Cannot talk to the Docker daemon. Is it running, and is your user in the 'docker' group?"
    [[ -f .env ]] || die ".env not found in ${REPO_ROOT}. Run this from the deployment directory."

    env_get() {
        local key="$1"
        sed -n "s/^[[:space:]]*${key}=//p" .env | tail -n1 | sed 's/^"\(.*\)"$/\1/; s/^'\''\(.*\)'\''$/\1/'
    }

    DB_NAME="$(env_get MYSQL_DATABASE)"; DB_NAME="${DB_NAME:-hp-data-fj}"
    DB_ROOT_PW="$(env_get MYSQL_ROOT_PASSWORD)"
    [[ -n "$DB_ROOT_PW" ]] || die "MYSQL_ROOT_PASSWORD is not set in .env"

    USE_COMPOSE=0
    if [[ -z "$CONTAINER" ]]; then
        if docker compose version >/dev/null 2>&1; then
            DC=(docker compose); USE_COMPOSE=1
        elif command -v docker-compose >/dev/null 2>&1; then
            DC=(docker-compose); USE_COMPOSE=1
        else
            CONTAINER="$DEFAULT_CONTAINER"
        fi
    fi

    db() {
        if [[ "$USE_COMPOSE" -eq 1 ]]; then
            "${DC[@]}" exec -T -e MYSQL_PWD="$DB_ROOT_PW" db \
                mysql -u root --default-character-set=utf8mb4 "$@"
        else
            docker exec -i -e MYSQL_PWD="$DB_ROOT_PW" "$CONTAINER" \
                mysql -u root --default-character-set=utf8mb4 "$@"
        fi
    }

    if [[ "$USE_COMPOSE" -eq 1 ]]; then
        ok "using: ${DC[*]} exec db"
    else
        docker inspect "$CONTAINER" >/dev/null 2>&1 || die "No such container: ${CONTAINER}"
        ok "using: docker exec ${CONTAINER}"
    fi
fi

db -e 'SELECT 1' >/dev/null 2>&1 || die "Cannot connect to MySQL. Check the credentials and that the server is up."
ok "connected to database '${DB_NAME}'"

# ---------------------------------------------------------------------------
# Longitude expressions, defined once
# ---------------------------------------------------------------------------
# These four fragments appear in the report, the undo block, the UPDATE and the
# verification, and they have to agree exactly or the script will claim to have
# fixed something it did not. They assume the aliases `s` for station and `t`
# for geo_territoire.
#
# LON_NUM      longitude as a number, accepting the locale comma as a decimal
#              point, the same way index.php:433 does.
# LON_IS_NUM   true for plain decimals only. Some older rows hold DMS strings
#              like "17 32 45 S", which index.php feeds to dmsToDecimal(); there
#              is no safe arithmetic to do on those, so they are left alone and
#              reported instead.
# LON_OFF      true when the station is more than half a world from its own
#              territory's map centre - the marker Leaflet cannot place near its
#              neighbours.
# LON_SHIFTED  the longitude moved a whole number of turns into the frame
#              anchored on that centre. ROUND(diff/360) is 0 for a row already
#              in frame, which is what makes the UPDATE a no-op on a re-run, and
#              the two TRIMs drop the padding that DECIMAL(12,6) would otherwise
#              leave behind ("-182.550000" -> "-182.55"). The CAST always
#              produces a decimal point, so trimming zeros cannot eat a digit.

readonly LON_NUM="CAST(REPLACE(s.longitude_station, ',', '.') AS DECIMAL(12,6))"
readonly LON_IS_NUM="REPLACE(s.longitude_station, ',', '.') REGEXP '^-?[0-9]+([.][0-9]+)?\$'"
readonly LON_OFF="${LON_IS_NUM} AND ABS(${LON_NUM} - t.mapLong) > 180"
readonly LON_SHIFTED="TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM CAST(
             ${LON_NUM} - 360 * ROUND((${LON_NUM} - t.mapLong) / 360) AS CHAR)))"

# ---------------------------------------------------------------------------
# Inspect
# ---------------------------------------------------------------------------
# Counted separately so the plan can say exactly what will change. The
# apostrophe is written as CHAR(39) throughout: it travels through bash, a
# heredoc and SQL, and quoting it literally at every layer is how this kind of
# script acquires bugs.

COUNTS="$(db -N -B "$DB_NAME" -e "
SELECT
  (SELECT COUNT(*) FROM station WHERE nom_station  LIKE CONCAT('%', CHAR(39), '%')),
  (SELECT COUNT(*) FROM station WHERE code_station LIKE CONCAT('%', CHAR(39), '%')),
  (SELECT COUNT(*) FROM station WHERE code_station LIKE '%\_%'),
  (SELECT COUNT(*) FROM station s
     JOIN geo_territoire t ON t.id_territoire = s.id_territoire
    WHERE ${LON_OFF}),
  (SELECT COUNT(*) FROM station s
    WHERE s.longitude_station <> '' AND NOT (${LON_IS_NUM}));")"

read -r N_NAME N_CODE_APOS N_CODE_UNDER N_LON N_LON_DMS <<<"$COUNTS"

log "Stations needing attention"

if [[ "$N_NAME" -gt 0 || "$N_CODE_APOS" -gt 0 ]]; then
    echo
    echo "  Apostrophes (break the audit-log INSERT; import appears to hang):"
    db --table "$DB_NAME" -e "
SELECT id_station, code_station, nom_station,
       REPLACE(nom_station, CHAR(39), '') AS nom_station_after
FROM station
WHERE nom_station LIKE CONCAT('%', CHAR(39), '%')
   OR code_station LIKE CONCAT('%', CHAR(39), '%')
ORDER BY id_station;"
fi

if [[ "$N_CODE_UNDER" -gt 0 ]]; then
    echo
    echo "  Underscores in station codes (no file can ever be imported to these):"
    db --table "$DB_NAME" -e "
SELECT id_station, code_station, nom_station,
       REPLACE(code_station, '_', '-') AS code_station_after
FROM station WHERE code_station LIKE '%\_%' ORDER BY id_station;"
fi

if [[ "$N_LON" -gt 0 ]]; then
    echo
    echo "  Longitudes on the wrong side of the map (marker drawn half a world away):"
    db --table "$DB_NAME" -e "
SELECT s.id_station, s.code_station, s.nom_station,
       t.nom_territoire, t.mapLong AS map_centre,
       s.longitude_station AS longitude_before,
       ${LON_SHIFTED} AS longitude_after
FROM station s
JOIN geo_territoire t ON t.id_territoire = s.id_territoire
WHERE ${LON_OFF}
ORDER BY s.id_station;"
fi

# ---------------------------------------------------------------------------
# Plan
# ---------------------------------------------------------------------------

PLAN=""
[[ "$N_NAME"       -gt 0 ]] && PLAN="${PLAN}  FIX      ${N_NAME} station name(s): remove apostrophes\n"
[[ "$N_CODE_APOS"  -gt 0 ]] && PLAN="${PLAN}  FIX      ${N_CODE_APOS} station code(s): remove apostrophes\n"

if [[ "$N_CODE_UNDER" -gt 0 ]]; then
    if [[ "$FIX_CODES" -eq 1 ]]; then
        PLAN="${PLAN}  FIX      ${N_CODE_UNDER} station code(s): replace _ with -\n"
    else
        echo
        warn "${N_CODE_UNDER} station code(s) contain an underscore and can never be imported to."
        warn "Not changed without --fix-codes: a code change means every import filename"
        warn "for that station has to be renamed to match."
    fi
fi

if [[ "$N_LON" -gt 0 ]]; then
    if [[ "$FIX_COORDS" -eq 1 ]]; then
        PLAN="${PLAN}  FIX      ${N_LON} longitude(s): shift into the territory's frame\n"
    else
        echo
        warn "${N_LON} station(s) have a longitude more than 180 degrees from their"
        warn "territory's map centre and cannot be found on the map. Not changed"
        warn "because --no-coords was given."
    fi
fi

if [[ "$N_LON_DMS" -gt 0 ]]; then
    echo
    warn "${N_LON_DMS} station(s) store longitude as a DMS string rather than a decimal."
    warn "Those are never touched here - check them by hand if one is missing"
    warn "from the map."
fi

if [[ -z "$PLAN" ]]; then
    echo
    # Only claim what was actually checked: with --no-coords a stranded station
    # has just been warned about, and must not be described as fine here.
    ok "Nothing to apply - no apostrophes, and no codes needing a rewrite."
    if [[ "$FIX_COORDS" -eq 1 ]]; then
        ok "Every station sits in its territory's map frame."
    fi
    exit 0
fi

echo
echo "  Changes for '${DB_NAME}':"
printf '%b' "$PLAN"

# ---------------------------------------------------------------------------
# Undo block - printed before anything is written
# ---------------------------------------------------------------------------

echo
echo "  To undo afterwards, keep these statements:"
echo
db -N -B "$DB_NAME" -e "
SELECT CONCAT('      UPDATE station SET nom_station=', CHAR(39), REPLACE(nom_station, CHAR(39), CONCAT(CHAR(39), CHAR(39))), CHAR(39),
              ', code_station=', CHAR(39), code_station, CHAR(39),
              ' WHERE id_station=', id_station, ';')
FROM station
WHERE nom_station  LIKE CONCAT('%', CHAR(39), '%')
   OR code_station LIKE CONCAT('%', CHAR(39), '%')
   OR code_station LIKE '%\_%'
ORDER BY id_station;"

if [[ "$FIX_COORDS" -eq 1 && "$N_LON" -gt 0 ]]; then
    db -N -B "$DB_NAME" -e "
SELECT CONCAT('      UPDATE station SET longitude_station=', CHAR(39), s.longitude_station, CHAR(39),
              ' WHERE id_station=', s.id_station, ';')
FROM station s
JOIN geo_territoire t ON t.id_territoire = s.id_territoire
WHERE ${LON_OFF}
ORDER BY s.id_station;"
fi
echo

if [[ "$DRY_RUN" -eq 1 ]]; then
    ok "--dry-run: nothing was written."
    exit 0
fi

if [[ "$ASSUME_YES" -ne 1 ]]; then
    printf '   Apply these changes? [y/N] '
    read -r reply
    [[ "$reply" == [yY]* ]] || die "Aborted. Nothing was changed."
fi

# ---------------------------------------------------------------------------
# Apply
# ---------------------------------------------------------------------------
# Each statement is a no-op once clean, so re-running is harmless.

log "Applying"

db "$DB_NAME" <<'SQL'
UPDATE station SET nom_station  = REPLACE(nom_station,  CHAR(39), '')
 WHERE nom_station  LIKE CONCAT('%', CHAR(39), '%');

UPDATE station SET code_station = REPLACE(code_station, CHAR(39), '')
 WHERE code_station LIKE CONCAT('%', CHAR(39), '%');
SQL

if [[ "$FIX_CODES" -eq 1 ]]; then
    db "$DB_NAME" <<'SQL'
UPDATE station SET code_station = REPLACE(code_station, '_', '-')
 WHERE code_station LIKE '%\_%';
SQL
fi

if [[ "$FIX_COORDS" -eq 1 ]]; then
    db "$DB_NAME" -e "
UPDATE station s
  JOIN geo_territoire t ON t.id_territoire = s.id_territoire
   SET s.longitude_station = ${LON_SHIFTED}
 WHERE ${LON_OFF};"
fi

# ---------------------------------------------------------------------------
# Verify
# ---------------------------------------------------------------------------

log "Verifying"

AFTER="$(db -N -B "$DB_NAME" -e "
SELECT
  (SELECT COUNT(*) FROM station WHERE nom_station  LIKE CONCAT('%', CHAR(39), '%')),
  (SELECT COUNT(*) FROM station WHERE code_station LIKE CONCAT('%', CHAR(39), '%')),
  (SELECT COUNT(*) FROM station WHERE code_station LIKE '%\_%'),
  (SELECT COUNT(*) FROM station s
     JOIN geo_territoire t ON t.id_territoire = s.id_territoire
    WHERE ${LON_OFF});")"

read -r A_NAME A_CODE_APOS A_CODE_UNDER A_LON <<<"$AFTER"

[[ "$A_NAME"      -eq 0 ]] || die "${A_NAME} station name(s) still contain an apostrophe."
[[ "$A_CODE_APOS" -eq 0 ]] || die "${A_CODE_APOS} station code(s) still contain an apostrophe."
if [[ "$FIX_CODES" -eq 1 ]]; then
    [[ "$A_CODE_UNDER" -eq 0 ]] || die "${A_CODE_UNDER} station code(s) still contain an underscore."
fi
if [[ "$FIX_COORDS" -eq 1 ]]; then
    [[ "$A_LON" -eq 0 ]] || die "${A_LON} station(s) are still off the map."
fi

# Longitude is listed alongside the type so a missing pin can be diagnosed from
# this table alone: every station in one territory should now read as a run of
# nearby numbers, with no outlier 360 degrees from the rest.
db --table "$DB_NAME" -e "
SELECT s.id_station, s.code_station, s.nom_station, e.nom_eq_type,
       s.latitude_station, s.longitude_station
FROM station s
LEFT JOIN eq_type e ON e.id_eq_type = s.station_type
ORDER BY s.id_territoire, s.id_station;"

ok "station names are safe to import against"
[[ "$FIX_COORDS" -eq 1 ]] && ok "every station sits in its territory's map frame"

[[ "$A_CODE_UNDER" -gt 0 ]] && \
    warn "${A_CODE_UNDER} code(s) still contain an underscore - re-run with --fix-codes to change them."

cat <<'EOF'

  ────────────────────────────────────────────────────────────────
   NOTE
  ────────────────────────────────────────────────────────────────
   This works around the bug, it does not fix it. The station form
   still accepts apostrophes, so a name typed as Nuku'alofa will
   break imports again, and the symptom is a hang rather than an
   error message.

   The real fix is one line in each of the seven import loaders
   that build the audit-log INSERT by string concatenation:

     $info_action = mysqli_real_escape_string($sql_link, $info_action);

   load_data_ra.php:636 already does exactly this. The others -
   load_data_chron, load_data, load_data_jge, load_data_etl,
   load_data_lab, load_data_tot, load_data_rep - do not.

   An uploaded FILENAME containing an apostrophe hits the same
   line, and no amount of renaming stations prevents that.

   The longitude shift is a workaround too, and it has a cost: a
   station stored as -182.551806 is no longer canonical WGS84, so
   every screen that prints the raw column now shows a longitude
   below -180 -

     index.php:542                    map popup
     pdf_station.php:383              station PDF sheet
     process_station_pdf.php:463      station PDF sheet
     form_station_1.php:278           the edit form

   That last one matters most: opening a shifted station in
   modif_station.php and saving writes -182.551806 straight back,
   and anyone who "corrects" it to 177.448194 silently loses the
   pin again. Nothing in ctrl_station.php or process_station_save.php
   normalises what gets typed in, so every new station west of 180
   needs this script run again.

   The real fix is to keep WGS84 in the column and normalise when
   the map is drawn, which is what index.php:469-471 already does
   for Kiribati alone:

     if ($convertedCoords !== null) {
         while ($convertedCoords[0] - $territoire_mapLong >  180) $convertedCoords[0] -= 360;
         while ($convertedCoords[0] - $territoire_mapLong < -180) $convertedCoords[0] += 360;
     }

   carto.php:160 and process_index_map.php:277 need the same.
  ────────────────────────────────────────────────────────────────

EOF
