#!/usr/bin/env bash
#
# Bring a HydroPacifique deployment's reference data to a known-good state:
# install the rows that are missing, and repair the fields that gestion_type.php
# blanks out. Safe to run repeatedly, and safe to run on a healthy database -
# every statement is conditional, nothing is overwritten or renamed.
#
#   ./scripts/seed-reference-data.sh              # report, confirm, apply
#   ./scripts/seed-reference-data.sh --dry-run    # report only, change nothing
#   ./scripts/seed-reference-data.sh -y           # unattended
#
# ---------------------------------------------------------------------------
# WHAT IT INSTALLS, and why the UI cannot
#
#   eq_type  1   Rainfall     gestion_type.php inserts without id_eq_type,
#   eq_type  5   Groundwater  init_eq_type or unite_eq_type (process_type_save.php:163),
#                             so a category created there gets the next
#                             auto-increment id and NULL code/unit. The
#                             behaviour for both is hard-coded against the
#                             literal ids: rain-gauge loading and SUM-not-AVG
#                             stats on id 1 (process_loaddata.php:129), the
#                             benchmark and well-characteristics tabs on id 5
#                             (modif_station.php:393). A category with id 13
#                             appears in the dropdown and does nothing.
#
#   data_type RF   (mm)       The series. These CAN be created in the UI
#   data_type GWL  (cm)       (gestion_type_data.php) - they are here so a fresh
#   data_type JGE  (m3/s)     environment comes up complete. The initial is what
#                             an import filename carries after the underscore:
#                             <CODE>_RF.csv, <CODE>_GWL.csv, <CODE>_JGE.csv.
#                             Without the row an upload fails with "No registered
#                             data series could be identified in the file name."
#
#                             RF and GWL are ordinary time series and take the
#                             3-column CSV. JGE is one of six RESERVED initials
#                             (LAB, TOT, RA, JGE, ETL, REP) that
#                             form_import_step1.php:365-371 routes to a
#                             dedicated loader with its own layout - for JGE,
#                             25 columns and two header rows. See the block at
#                             the end of this file.
#
#   import_files csv          No admin screen exists anywhere in the app: all
#                             twelve files touching import_files only SELECT.
#                             Without this row load_file.php recognises no
#                             extension and every upload fails with
#                             "Extension not registered: csv".
#
# ---------------------------------------------------------------------------
# WHAT IT REPAIRS
#
# Saving gestion_type.php rewrites EVERY existing category, not just the one
# being added: process_type_save.php:119-128 loops over all rows and UPDATEs
# them from posted form values. valeur_data_type and type_graph do not survive
# that round trip, so adding one category silently blanks those two columns on
# all the others - Rainfall stops drawing as bars and loses its cumulative flag,
# with no error shown.
#
# This script refills those columns, and NULL init_eq_type / unite_eq_type,
# for ids 1, 5 and 11 only, and only where the value is NULL, empty or zero.
# It never renames a category and never touches a field that holds real data.
# ---------------------------------------------------------------------------
#
set -euo pipefail

REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

# Fallback container name, used when `docker compose` is not usable from here.
readonly DEFAULT_CONTAINER="hydropacifique-db-1"

ASSUME_YES=0
DRY_RUN=0
CONTAINER=""

usage() {
    cat <<'EOF'
Usage: ./scripts/seed-reference-data.sh [options]

  --dry-run          Report what is missing or damaged and exit without writing.
  --container <name> Talk to this container directly instead of using
                     `docker compose exec db` (default: hydropacifique-db-1
                     when compose is unavailable).
  -y, --yes          Skip the confirmation prompt.
  -h, --help         Show this help.

Reads MYSQL_ROOT_PASSWORD and MYSQL_DATABASE from .env in the repo root.
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run)   DRY_RUN=1; shift ;;
        --container) [[ $# -ge 2 ]] || { echo "Error: --container needs a name." >&2; exit 2; }
                     CONTAINER="$2"; shift 2 ;;
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
# Preflight
# ---------------------------------------------------------------------------

log "Preflight"

command -v docker >/dev/null 2>&1 || die "docker is not on PATH."
docker info >/dev/null 2>&1 \
    || die "Cannot talk to the Docker daemon. Is it running, and is your user in the 'docker' group?"

[[ -f .env ]] || die ".env not found in ${REPO_ROOT}. Run this from the deployment directory."

# Read credentials without exporting the whole file into this shell.
env_get() {
    local key="$1"
    sed -n "s/^[[:space:]]*${key}=//p" .env | tail -n1 | sed 's/^"\(.*\)"$/\1/; s/^'\''\(.*\)'\''$/\1/'
}

DB_NAME="$(env_get MYSQL_DATABASE)"; DB_NAME="${DB_NAME:-hp-data-fj}"
DB_ROOT_PW="$(env_get MYSQL_ROOT_PASSWORD)"
[[ -n "$DB_ROOT_PW" ]] || die "MYSQL_ROOT_PASSWORD is not set in .env"

# Prefer compose (it resolves the right container even after a recreate);
# fall back to addressing the container by name.
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

# MYSQL_PWD keeps the password out of the container's process list.
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

db -e 'SELECT 1' >/dev/null 2>&1 \
    || die "Cannot connect to MySQL as root. Check MYSQL_ROOT_PASSWORD in .env and that the db container is healthy."
ok "connected to database '${DB_NAME}'"

# ---------------------------------------------------------------------------
# Inspect
# ---------------------------------------------------------------------------

log "Current state"
db --table "$DB_NAME" -e "
SELECT id_eq_type, init_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_graph, active_eq_type
FROM eq_type ORDER BY order_eq_type;
SELECT id_data_type, init_type_data, nom_type_data, id_eq_type_data FROM data_type ORDER BY id_data_type;
SELECT id, name_ext, separateur, valid FROM import_files ORDER BY id;"

# One round trip, seven counters.
#
# "damaged" = any of the three known categories carrying a blanked column.
# valeur_data_type = 0 is the signature of the form-save bug: the column is a
# 1/2 flag (Ponctuelle / Cumulée) and 0 is never a legitimate value.
COUNTS="$(db -N -B "$DB_NAME" -e "
SELECT
  (SELECT COUNT(*) FROM eq_type      WHERE id_eq_type = 1),
  (SELECT COUNT(*) FROM eq_type      WHERE id_eq_type = 5),
  (SELECT COUNT(*) FROM eq_type      WHERE id_eq_type = 11),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'RF'),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'GWL'),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'JGE'),
  (SELECT COUNT(*) FROM import_files WHERE name_ext = 'csv'),
  (SELECT COUNT(*) FROM eq_type
     WHERE id_eq_type IN (1, 5, 11)
       AND (init_eq_type  IS NULL OR init_eq_type  = ''
         OR unite_eq_type IS NULL OR unite_eq_type = ''
         OR type_graph    IS NULL OR type_graph    = ''
         OR valeur_data_type IS NULL OR valeur_data_type = 0)),
  (SELECT COUNT(*) FROM eq_type
     WHERE id_eq_type NOT IN (1, 5, 11)
       AND (init_eq_type IN ('pluvio', 'piezo')
         OR nom_eq_type IN ('Rainfall', 'Groundwater', 'Ground water', 'Pluvio', 'Piezo')));")"

read -r HAS_EQ1 HAS_EQ5 HAS_EQ11 HAS_RF HAS_GWL HAS_JGE HAS_CSV DAMAGED STRAY <<<"$COUNTS"

# ---------------------------------------------------------------------------
# Plan
# ---------------------------------------------------------------------------

PLAN=""
[[ "$HAS_EQ1"  -eq 0 ]] && PLAN="${PLAN}  INSTALL  eq_type      id 1  Rainfall    (pluvio, mm, cumulative, bar)\n"
[[ "$HAS_EQ5"  -eq 0 ]] && PLAN="${PLAN}  INSTALL  eq_type      id 5  Groundwater (piezo, cm, point, lines)\n"
[[ "$HAS_RF"   -eq 0 ]] && PLAN="${PLAN}  INSTALL  data_type    RF    Rainfall series (mm) under category 1\n"
[[ "$HAS_GWL"  -eq 0 ]] && PLAN="${PLAN}  INSTALL  data_type    GWL   Groundwater Level series (cm) under category 5\n"
[[ "$HAS_JGE"  -eq 0 ]] && PLAN="${PLAN}  INSTALL  data_type    JGE   Stream gauging (m3/s) under category 11 - 25-column layout\n"
[[ "$HAS_CSV"  -eq 0 ]] && PLAN="${PLAN}  INSTALL  import_files csv   separator ';'\n"
[[ "$DAMAGED"  -gt 0 ]] && PLAN="${PLAN}  REPAIR   eq_type      ${DAMAGED} row(s) with blanked init/unit/graph/values\n"

# id 11 is never created here: the hydrometry category ships with every dump.
# Its absence means something is wrong that this script should not paper over.
if [[ "$HAS_EQ11" -eq 0 ]]; then
    warn "eq_type id 11 (Surface water) does not exist on this server."
    warn "  Every dump carries it, so this database may not be what you think it is."
    warn "  Not creating it - check the restore before going further."
fi

# A category created through gestion_type.php carries the wrong id. Deleting it
# would orphan any station pointing at it, so this needs a human decision.
if [[ "$STRAY" -gt 0 ]]; then
    echo
    warn "Found ${STRAY} Rainfall/Groundwater categor(ies) with an unexpected id."
    warn "Created through gestion_type.php, which cannot set id_eq_type."
    warn "The hard-coded logic tests id 1 and id 5, so these rows do nothing."
    echo
    db --table "$DB_NAME" -e "
SELECT e.id_eq_type, e.init_eq_type, e.nom_eq_type, e.unite_eq_type,
       (SELECT COUNT(*) FROM station s WHERE s.station_type = e.id_eq_type) AS stations_using_it
FROM eq_type e
WHERE e.id_eq_type NOT IN (1, 5, 11)
  AND (e.init_eq_type IN ('pluvio','piezo')
    OR e.nom_eq_type IN ('Rainfall','Groundwater','Ground water','Pluvio','Piezo'));"
    warn "Resolve by hand - this script will not delete a category:"
    warn "  stations_using_it = 0 -> DELETE FROM eq_type WHERE id_eq_type = <id>;"
    warn "  otherwise             -> UPDATE station SET station_type = <1 or 5> WHERE station_type = <id>;"
    warn "                           then delete the old row."
    echo
fi

if [[ -z "$PLAN" ]]; then
    echo
    ok "Nothing to do - reference data is complete and undamaged."
    exit 0
fi

echo
echo "  Changes for '${DB_NAME}':"
printf '%b' "$PLAN"
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
# The whole block is the desired state expressed idempotently, so it is run
# wholesale rather than assembled from the detection above:
#
#   INSERT IGNORE  - primary key is explicit, an existing row is left alone
#   NOT EXISTS     - import_files.name_ext has no unique key, so INSERT IGNORE
#                    would happily add a duplicate
#   UPDATE ... AND (col IS NULL OR col = '')
#                  - only ever fills a blank; a row holding real values is
#                    untouched, and nothing is renamed

log "Applying"

db "$DB_NAME" <<'SQL'
-- --- Install ---------------------------------------------------------------

INSERT IGNORE INTO `eq_type`
  (id_eq_type, init_eq_type, nom_eq_type, unite_eq_type, interval_eq_type,
   valeur_data_type, active_eq_type, order_eq_type,
   type_color_border, type_color_background, type_graph)
VALUES
  (1, 'pluvio', 'Rainfall',    'mm', 1, 2, 1, 2, '#2CA02C', '#D5F5E3', 'bar'),
  (5, 'piezo',  'Groundwater', 'cm', 1, 1, 1, 3, '#A04000', '#AED6F1', 'lines');

-- The series initial is what an import filename carries after the underscore:
-- HY-001_RF.csv, HY-003_GWL.csv. Rename either here and the filenames must
-- change to match.
INSERT IGNORE INTO `data_type`
  (id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data,
   unite, nb_round, time_scale, to_periode, id_chon_periode, traitement,
   type_graph, raw_data)
VALUES
  (66, 'RF',  'Rainfall',          1,  1, 'mm',   1, 1, 1, 0, 0, 'bar',   0),
  (67, 'GWL', 'Groundwater Level', 5,  1, 'cm',   1, 1, 1, 0, 0, 'lines', 0),
  -- JGE is a reserved initial: load_file.php still requires this row to exist,
  -- but the upload is then routed to load_data_jge.php, which writes data_jge
  -- rather than the ordinary time-series tables.
  (68, 'JGE', 'Stream gauging',    11, 1, 'm3/s', 3, 1, 1, 0, 0, 'lines', 0);

INSERT INTO `import_files` (name_ext, multi_feuil, separateur, description, valid)
SELECT * FROM (
    SELECT 'csv'                                                     AS name_ext,
           0                                                         AS multi_feuil,
           ';'                                                       AS separateur,
           'Fichier CSV - 3 colonnes : date_heure ; valeur ; qualite' AS description,
           1                                                         AS valid
) AS candidate
WHERE NOT EXISTS (SELECT 1 FROM `import_files` WHERE name_ext = 'csv');


-- --- Repair the columns gestion_type.php blanks ----------------------------
-- Surface water (11)
UPDATE `eq_type` SET init_eq_type     = 'hydro' WHERE id_eq_type = 11 AND (init_eq_type  IS NULL OR init_eq_type  = '');
UPDATE `eq_type` SET unite_eq_type    = 'cm'    WHERE id_eq_type = 11 AND (unite_eq_type IS NULL OR unite_eq_type = '');
UPDATE `eq_type` SET type_graph       = 'lines' WHERE id_eq_type = 11 AND (type_graph    IS NULL OR type_graph    = '');
UPDATE `eq_type` SET valeur_data_type = 1       WHERE id_eq_type = 11 AND (valeur_data_type IS NULL OR valeur_data_type = 0);

-- Rainfall (1) - cumulative, drawn as bars
UPDATE `eq_type` SET init_eq_type     = 'pluvio' WHERE id_eq_type = 1 AND (init_eq_type  IS NULL OR init_eq_type  = '');
UPDATE `eq_type` SET unite_eq_type    = 'mm'     WHERE id_eq_type = 1 AND (unite_eq_type IS NULL OR unite_eq_type = '');
UPDATE `eq_type` SET type_graph       = 'bar'    WHERE id_eq_type = 1 AND (type_graph    IS NULL OR type_graph    = '');
UPDATE `eq_type` SET valeur_data_type = 2        WHERE id_eq_type = 1 AND (valeur_data_type IS NULL OR valeur_data_type = 0);

-- Groundwater (5)
UPDATE `eq_type` SET init_eq_type     = 'piezo' WHERE id_eq_type = 5 AND (init_eq_type  IS NULL OR init_eq_type  = '');
UPDATE `eq_type` SET unite_eq_type    = 'cm'    WHERE id_eq_type = 5 AND (unite_eq_type IS NULL OR unite_eq_type = '');
UPDATE `eq_type` SET type_graph       = 'lines' WHERE id_eq_type = 5 AND (type_graph    IS NULL OR type_graph    = '');
UPDATE `eq_type` SET valeur_data_type = 1       WHERE id_eq_type = 5 AND (valeur_data_type IS NULL OR valeur_data_type = 0);

-- interval_eq_type is 1 on every row that came from the source database; the
-- form never posts it, so a UI-created row arrives as 0.
UPDATE `eq_type` SET interval_eq_type = 1 WHERE id_eq_type IN (1, 5, 11) AND (interval_eq_type IS NULL OR interval_eq_type = 0);
SQL

# ---------------------------------------------------------------------------
# Verify
# ---------------------------------------------------------------------------

log "Verifying"

VERIFY="$(db -N -B "$DB_NAME" -e "
SELECT
  (SELECT COUNT(*) FROM eq_type      WHERE id_eq_type = 1 AND init_eq_type = 'pluvio'),
  (SELECT COUNT(*) FROM eq_type      WHERE id_eq_type = 5 AND init_eq_type = 'piezo'),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'RF'),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'GWL'),
  (SELECT COUNT(*) FROM data_type    WHERE init_type_data = 'JGE'),
  (SELECT COUNT(*) FROM import_files WHERE name_ext = 'csv'),
  (SELECT COUNT(*) FROM eq_type
     WHERE id_eq_type IN (1, 5, 11)
       AND (init_eq_type  IS NULL OR init_eq_type  = ''
         OR unite_eq_type IS NULL OR unite_eq_type = ''
         OR type_graph    IS NULL OR type_graph    = ''
         OR valeur_data_type IS NULL OR valeur_data_type = 0));")"

read -r V_EQ1 V_EQ5 V_RF V_GWL V_JGE V_CSV V_DAMAGED <<<"$VERIFY"

FAILED=""
[[ "$V_EQ1" -ge 1 ]]     || FAILED="${FAILED} eq_type(id=1)"
[[ "$V_EQ5" -ge 1 ]]     || FAILED="${FAILED} eq_type(id=5)"
[[ "$V_RF"  -ge 1 ]]     || FAILED="${FAILED} data_type(RF)"
[[ "$V_GWL" -ge 1 ]]     || FAILED="${FAILED} data_type(GWL)"
[[ "$V_JGE" -ge 1 ]]     || FAILED="${FAILED} data_type(JGE)"
[[ "$V_CSV" -ge 1 ]]     || FAILED="${FAILED} import_files(csv)"
[[ "$V_DAMAGED" -eq 0 ]] || FAILED="${FAILED} ${V_DAMAGED}_row(s)_still_blank"
[[ -z "$FAILED" ]] || die "Verification failed:${FAILED}"

db --table "$DB_NAME" -e "
SELECT id_eq_type, init_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_graph
FROM eq_type ORDER BY order_eq_type;
SELECT id_data_type, init_type_data, nom_type_data, id_eq_type_data FROM data_type ORDER BY id_data_type;
SELECT id, name_ext, separateur, valid FROM import_files ORDER BY id;
SELECT s.station_type, e.nom_eq_type, COUNT(*) AS stations
FROM station s LEFT JOIN eq_type e ON e.id_eq_type = s.station_type
GROUP BY s.station_type, e.nom_eq_type ORDER BY s.station_type;"

ok "reference data complete and undamaged"

# A station row pointing at a category that no longer exists renders with a
# blank type and drops out of type-filtered lists.
ORPHANS="$(db -N -B "$DB_NAME" -e \
    "SELECT COUNT(*) FROM station s
      WHERE NOT EXISTS (SELECT 1 FROM eq_type e WHERE e.id_eq_type = s.station_type);" 2>/dev/null || echo 0)"
[[ "$ORPHANS" -gt 0 ]] && warn "${ORPHANS} station(s) point at a category id that does not exist (see the last table above)."

# ---------------------------------------------------------------------------
# Related production gap: sql_mode
# ---------------------------------------------------------------------------
# Not fixed here - the db container takes its sql_mode from the --sql-mode flag
# in docker-compose.yml, so SET PERSIST would be undone by the next restart.

SQL_MODE="$(db -N -B -e 'SELECT @@GLOBAL.sql_mode;' 2>/dev/null || echo "")"
if [[ "$SQL_MODE" == *STRICT_TRANS_TABLES* || "$SQL_MODE" == *ONLY_FULL_GROUP_BY* ]]; then
    echo
    warn "This server still runs with sql_mode='${SQL_MODE}'."
    warn "Creating a station will fail with \"Field 'id_station_old' doesn't have a default value\"."
    warn "Fix: set --sql-mode=NO_ENGINE_SUBSTITUTION in docker-compose.yml, then:"
    warn "  docker compose up -d db"
fi

cat <<EOF

  ────────────────────────────────────────────────────────────────
   DONE
  ────────────────────────────────────────────────────────────────
   Do NOT add categories through Paramétrage / Types
   (gestion_type.php). Saving that form rewrites every existing
   category and blanks valeur_data_type and type_graph on all of
   them - silently, with no error. Re-run this script if someone
   does; it repairs exactly that damage.

   Adding a station:
     1. Code MUST NOT contain an underscore - HY-001, not HY_001.
        load_file.php reads the station code as everything before
        the first '_' in the filename, so an underscore there makes
        every import fail with "No station could be identified".
     2. Upload <CODE>_<SERIES>.csv, where <SERIES> is the series
        initial:  RF = rainfall      -> HY-001_RF.csv
                  GWL = groundwater  -> HY-003_GWL.csv
                  WLR = river level  -> TGA-NKL-WLR_WLR.csv
                  JGE = gauging      -> TGA-NKL-JGE_JGE.csv

   Two CSV layouts, not one:

   RF / GWL / WLR - ordinary series, load_data_chron.php
     3 columns, NO header row unless you tick the box:
       date_heure ; valeur ; qualite
       13/01/2018 10:53:24 ; 1250.5 ;

   JGE - reserved initial, load_data_jge.php
     25 columns, and the FIRST TWO ROWS ARE ALWAYS SKIPPED
     (title + header), so data starts on line 3. Separator ';'
     is hard-coded in that loader and ignores import_files.
     Columns the loader actually reads (0-based):
       [2]  date          [13] mean depth m
       [7]  mean stage cm [14] number of verticals
       [8]  discharge Q   [17] observations
       [9]  wetted area   [18] agents
       [10] mean velocity [19] GPS X
       [11] surf velocity [20] GPS Y
       [12] hydraulic R   [24] file name
     Rows upsert on (station, datetime): re-importing the same
     timestamp updates the gauging rather than duplicating it.
  ────────────────────────────────────────────────────────────────

EOF
