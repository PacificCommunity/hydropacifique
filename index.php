<?php
/*
 * ============================================================
 * Copyright (c) 2024 - Vai-Natura
 * ============================================================
 * Homepage / Interactive map of monitoring stations
 *
 * OVERVIEW OF THIS FILE
 * ---------------------
 * 1. [PHP] Bootstrap & geographic libraries
 * 2. [PHP] Utility functions (coordinate conversion, HTML helpers)
 * 3. [PHP] Geographic projections per territory
 * 4. [PHP] Load user map preferences (center, zoom)
 * 5. [PHP] Filter flags for the sidebar form
 * 6. [PHP] Load equipment types (eq_type) and data-source origins
 * 7. [PHP] Main station query + loop:
 *          - resolve coordinates (lat/lon → WGS84)
 *          - determine station status & update legend counters
 *          - build tooltip & popup HTML strings
 *          - push everything into $stations_data[] for JSON export
 * 8. [PHP] Build $legend_items[] array for JSON export
 * 9. [PHP] HTML rendering (map container + sidebar)
 * 10.[JS]  PHP → JS data bridge via json_encode()
 * 11.[JS]  Leaflet map initialisation
 * 12.[JS]  Base layers & overlay layers (online / offline / PF-specific)
 * 13.[JS]  GeoJSON loader helper
 * 14.[JS]  Marker creation from stationsData JSON
 * 15.[JS]  Legend widget construction from legendItems JSON
 * 16.[JS]  Map interactions (zoom, move, recenter)
 * 17.[JS]  AJAX: load popup stats on marker click
 * 18.[JS]  AJAX: open last-RA / last-import info boxes
 * ============================================================
 */


// ============================================================
// 1. BOOTSTRAP & GEOGRAPHIC LIBRARIES
// ============================================================

// Load the application configuration file.
// This sets up the database connection ($sql_link), session, constants,
// territory variables ($territoire_id, $territoire_init, $territoire_mapLong, etc.),
// user variables ($id_user, $gestion_data), and lookup arrays
// ($region_array, $commune_array).
require('include/application_top.php');

// Load Composer's autoloader so we can use the proj4php library,
// which handles cartographic projection conversions (Lambert, UTM → WGS84).
require 'vendor/autoload.php';

use proj4php\Proj4php;
use proj4php\Point;
use proj4php\Proj;

// Create the main proj4php engine instance.
// All Proj objects and Point transforms must share the same engine.
$proj4 = new Proj4php();

// Define the target projection: WGS84 (EPSG:4326) only French Polynesia
// Every coordinate in the database will ultimately be converted to this system
// so that Leaflet can consume standard [longitude, latitude] pairs.
$projWGS84 = new Proj('EPSG:4326', $proj4);


// ============================================================
// 2. UTILITY FUNCTIONS
// ============================================================

/**
 * convertCoordinates()
 * --------------------
 * Transforms a point from any supported projection to WGS84.
 *
 * Why: Stations can be stored in Lambert (NC), UTM zone 58S (NC),
 * UTM zone 6S (PF), etc.  This single function handles all conversions
 * by accepting the source projection as a parameter.
 *
 * @param float  $x        Easting / X value in the source projection
 * @param float  $y        Northing / Y value in the source projection
 * @param Proj   $projFrom Source projection object (Lambert, UTM …)
 * @param Proj   $projTo   Target projection object (always $projWGS84 here)
 * @param Proj4php $proj4  Shared proj4php engine
 * @return array           [longitude, latitude] as floats (WGS84)
 */
function convertCoordinates($x, $y, $projFrom, $projTo, $proj4)
{
    $point      = new Point(floatval($x), floatval($y), $projFrom);
    $pointWGS84 = $proj4->transform($projTo, $point);
    return [$pointWGS84->x, $pointWGS84->y]; // [lon, lat]
}


/**
 * dmsToDecimal()
 * --------------
 * Converts a Degrees–Minutes–Seconds string to a decimal degree float.
 *
 * Why: Some older station records store coordinates as DMS strings like
 * "17°32'15.5\"" or "17 32 15.5" instead of plain decimal numbers.
 * This function normalises those inputs before they reach Leaflet.
 *
 * Supported separators: °, ', ", space, dash, colon, Unicode prime (′ ″).
 * Falls back to a looser regex if the strict pattern fails.
 *
 * @param string $dms       Raw DMS string (commas are treated as decimal points)
 * @param string $direction 'S' or 'W' → result is negated; 'N' or 'E' → positive
 * @return float|null       Decimal degrees, or null if parsing fails
 */
function dmsToDecimal($dms, $direction)
{
    // Normalise decimal separator (some locales use a comma)
    $dms = str_replace(',', '.', $dms);

    // Primary pattern: handles °, ', " with optional Unicode primes and spaces
    if (!preg_match('/(\d+)[°\s\-:]+(\d+)[\'′\s\-:]+(\d+(?:\.\d+)?)[\"″\s]?/', $dms, $parts)) {
        // Fallback: any non-digit sequence can be a separator
        if (!preg_match('/(\d+)\D+(\d+)\D+(\d+(?:\.\d+)?)/', $dms, $parts)) {
            error_log("dmsToDecimal: unrecognised format → " . $dms);
            return null;
        }
    }

    // Convert each component to fractional degrees and sum
    $decimal = (int)$parts[1]        // degrees
             + (int)$parts[2]   / 60 // minutes → degrees
             + (float)$parts[3] / 3600; // seconds → degrees

    // Southern and western hemispheres are negative in decimal notation
    if ($direction === 'S' || $direction === 'W') {
        $decimal *= -1;
    }

    return $decimal;
}




/**
 * html_info_line()
 * ----------------
 * Generates a single <p> line for tooltip / popup content blocks.
 *
 * Why: The original code repeated dozens of identical if / string-concat
 * patterns.  This helper centralises the "only render if value is non-empty"
 * guard and produces consistent markup everywhere.
 *
 * @param string $label  The field label (already translated constant)
 * @param string $value  The field value; returns '' if empty / null
 * @param string $style  Optional inline CSS for the <p> tag
 * @return string        HTML string, or '' when $value is empty
 */
function html_info_line($label, $value, $style = '')
{
    if (!tep_not_null($value)) return ''; // skip empty / null values
    $s = $style ? " style=\"{$style}\"" : '';
    return "<p{$s}><span>{$label} : </span>{$value}</p>";
}


// ============================================================
// 3. GEOGRAPHIC PROJECTIONS PER TERRITORY
// ============================================================
// Each territory stores its coordinates in a different system.
// We pre-build the Proj objects here (once, outside any loop)
// so convertCoordinates() can use them cheaply in the station loop.
//
// Note: EPSG codes are NOT used directly because proj4php's built-in
// definitions for several Pacific projections are inaccurate.
// The full +proj= strings below are authoritative.

// --- New Caledonia: Lambert conformal conic (historical cadastral data) ---
$territoriesProjections['NC']['lambert'] = new Proj(
    '+proj=lcc +lat_1=-20.66666666666667 +lat_2=-22.33333333333333 +lat_0=-21.5 +lon_0=166 '
    . '+x_0=400000 +y_0=300000 +ellps=intl +towgs84=197.025,-193.922,175.185,0,0,0,0 +units=m +no_defs',
    $proj4
);

// --- New Caledonia: UTM Zone 58S (modern field data) ---
$territoriesProjections['NC']['utm'] = new Proj(
    '+proj=utm +zone=58 +south +datum=WGS84 +units=m +no_defs',
    $proj4
);

// --- French Polynesia: UTM Zone 6S (covers Tahiti and the Windward Islands) ---
$territoriesProjections['PF']['utm'] = new Proj(
    '+proj=utm +zone=6 +south +datum=WGS84 +units=m +no_defs',
    $proj4
);

// --- Wallis & Futuna: UTM Zone 1S ---
$territoriesProjections['WF']['utm'] = new Proj(
    '+proj=utm +zone=1 +south +datum=WGS84 +units=m +no_defs',
    $proj4
);

// --- New Zealand South Island: UTM Zone 59S (Christchurch, Dunedin) ---
$territoriesProjections['NZ']['utm'] = new Proj(
    '+proj=utm +zone=59 +south +datum=WGS84 +units=m +no_defs',
    $proj4
);

// --- New Zealand North Island: UTM Zone 60S (Auckland, Wellington) ---
$territoriesProjections['NZ_N']['utm'] = new Proj(
    '+proj=utm +zone=60 +south +datum=WGS84 +units=m +no_defs',
    $proj4
);


// ============================================================
// 4. LOAD USER MAP PREFERENCES
// ============================================================
// The map opens at territory-level defaults, but if the user previously
// panned / zoomed, their last known position is restored from the DB.

// Start from territory-wide defaults (set in application_top.php)
$mapLong    = $territoire_mapLong;
$mapLat     = $territoire_mapLat;
$mapZoom    = $territoire_mapZoom;
$mapMinZoom = $territoire_mapMinZoom;

// Attempt to load the user's last saved map position.
// Uses a prepared statement to prevent SQL injection.
$stmt = $sql_link->prepare(
    "SELECT map_zoom, map_long, map_lat FROM " . TABLE_USER_COORD . " WHERE id_user = ?"
);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$user_coord = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Override defaults only when a saved position actually exists
if (isset($user_coord)) {
    $mapZoom = (float)$user_coord['map_zoom'];
    $mapLong = (float)$user_coord['map_long'];
    $mapLat  = (float)$user_coord['map_lat'];
}


// Retrieve the last base layer selected by the user for the PF map.
// Falls back to an empty string if no preference has been saved yet.
$info_base_prefs = '';

$sql = "SELECT filter_value FROM " . TABLE_USER_FILTER . " 
        WHERE id_user = ? AND filter_id = 'info_base_prefs'";

$stmt = $sql_link->prepare($sql);
$stmt->bind_param("i", $id_user);
$stmt->execute();
$stmt->bind_result($info_base_prefs);
$stmt->fetch();  // if no row found, $info_base_layer stays ''
$stmt->close();

// ============================================================
// 5. FILTER FLAGS FOR THE SIDEBAR FORM
// ============================================================
// These booleans control which filter widgets are rendered by
// filtre_stations_html.php (included later in the HTML section).
// filtre_stations_var.php also reads them to build the WHERE clauses
// ($where_and_from, $where_and_type, etc.) used in the station query.

$affiche_select_from          = true;  // show "data source" selector
$affiche_select_type          = true;  // show "equipment type" selector
$affiche_select_tournee       = false; // ($gestion_data > 0); // show "field tour" only when data management is enabled
$affiche_search               = false; // no free-text search on this page
$affiche_select_riviere       = false; // no river filter on this page
$affiche_select_station       = true;  // show individual station selector
$affiche_select_statut_station = true;  // show status filter (active / historic / …)

// Build all WHERE clause fragments and populate lookup arrays
// ($region_array, $commune_array) used further down.
require(DIR_WS_FILTRE . 'filtre_stations_var.php');


// ============================================================
// 6. LOAD EQUIPMENT TYPES & DATA-SOURCE ORIGINS
// ============================================================

// --- 6a. Equipment types (TABLE_EQ_TYPE) ---
// Each type (e.g. rainfall gauge, flow gauge) has a unique colour and
// graph style.  We load them all once and index by id_eq_type for O(1)
// lookup inside the station loop.

$eq_type_array = []; // indexed by id_eq_type

// Legend counters: $legend[$init_eq_type][$status]
// Replaces the original "variable variables" pattern (${'legend'.$init} = 0)
// with a clean associative array, which is readable, typeable, and debuggable.
$legend = [];

$sql_eq_type = "SELECT DISTINCT id_eq_type, init_eq_type, nom_eq_type, unite_eq_type,
                       valeur_data_type, type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type = 1
                ORDER BY order_eq_type ASC";

$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($row = tep_db_fetch_array($eq_type_query)) {
    // Store the full row so any field is available later by key
    $eq_type_array[$row['id_eq_type']] = $row;

    // Initialise four status counters for this equipment type.
    // They will be incremented in the station loop below.
    $legend[$row['init_eq_type']] = [
        'active'     => 0, // continuous monitoring, currently operational
        'historique' => 0, // continuous monitoring, now closed
        'ponctuel'   => 0, // spot / campaign measurements only
        'panne'      => 0, // out of service / disarmed
    ];
}


// --- 6b. Data-source origins (TABLE_SERVICE) ---
// Each station belongs to one data provider (e.g. DIREN, IRD, private).
// We pre-load them so the station loop can resolve $id_service → name
// without an extra query per station.

$fromData_array = []; // indexed by id_service

$sql_fromData = "SELECT DISTINCT id_service, name, description
                 FROM " . TABLE_SERVICE . "
                 ORDER BY id_service ASC";

$fromData_query = tep_db_query($sql_link, $sql_fromData);
while ($row = tep_db_fetch_array($fromData_query)) {
    $fromData_array[$row['id_service']] = [
        'name'        => html_entity_decode($row['name']        ?? ''),
        'description' => html_entity_decode($row['description'] ?? ''),
    ];
}


// ============================================================
// 7. MAIN STATION QUERY + PROCESSING LOOP
// ============================================================

// Global counters displayed in the sidebar summary panel
$nb_station        = 0;
$nb_station_active = 0;
$nb_station_suivi  = 0;
$nb_station_panne  = 0;

// The WHERE clause fragments come from filtre_stations_var.php (step 5).
// The query returns only the columns needed for the map and popups,
// keeping the payload lean.
$sql_station = "SELECT DISTINCT
                    s.id_station, s.id_service, s.nom_station, s.code_station,
                    s.id_region, s.id_commune, s.vallee_station, s.riviere_station,
                    s.active_station, s.station_type, s.suivi, s.armee,
                    s.date_installation_station, s.date_fermeture_station,
                    s.lamb_station_x, s.lamb_station_y,
                    s.utm_station_x,  s.utm_station_y,
                    s.latitude_station, s.longitude_station, s.altitude_station
                FROM " . TABLE_STATION . " s
                LEFT JOIN " . TABLE_STATION_TO_TOURNEE . " t ON t.id_station = s.id_station
                WHERE s.id_territoire = " . $territoire_id
                . $where_and_from . $where_and_type
                . $where_and_regionhydro . $where_and_region . $where_and_commune
                . $where_and_station . $where_and_tournee
                . $where_and_active . $where_and_suivi . $where_and_armee . "
                ORDER BY code_station DESC";

$station_query = tep_db_query($sql_link, $sql_station);


// $stations_data is the central data structure of this page.
// It is a PHP array that will be JSON-encoded and injected into the
// <script> block as `stationsData`.  JavaScript then iterates it to
// place Leaflet markers — no PHP-generated JS string concatenation needed.
$stations_data = [];

while ($station = tep_db_fetch_array($station_query)) {

    // ----------------------------------------------------------
    // 7a. Extract raw fields from the current station row
    // ----------------------------------------------------------

    $id_station        = $station['id_station'];
    $id_service        = $station['id_service'];
    $nom_station       = $station['nom_station'];
    $code_station      = $station['code_station'];
    $type_data_station = $station['station_type']; // FK → eq_type_array
    $active_station    = $station['active_station'];
    $suivi_station     = $station['suivi'];   // 1 = continuous monitoring
    $armee_station     = $station['armee'];   // 1 = currently out of service

    // ----------------------------------------------------------
    // 7b. Resolve geographic label strings from pre-loaded arrays
    // ----------------------------------------------------------

    // Hydrological valley / catchment name (stored directly as text)
    $nom_regionhydro = $station['vallee_station'];

    // River name (stored directly as text; will become a FK in a future version)
    $nom_riviere = $station['riviere_station'];

    // Administrative region: look up the name from the pre-loaded array
    $id_region  = $station['id_region'];
    $nom_region = (tep_not_null($id_region) && isset($region_array[$id_region]))
                  ? $region_array[$id_region] : '';

    // Municipality: same pattern
    $id_commune  = $station['id_commune'];
    $nom_commune = (tep_not_null($id_commune) && isset($commune_array[$id_commune]))
                   ? $commune_array[$id_commune] : '';

    // Data-source provider name (pre-loaded in step 6b)
    $from_name        = $fromData_array[$id_service]['name']        ?? '';
    $from_description = $fromData_array[$id_service]['description'] ?? '';

    // Formatted installation / closure dates
    $date_installation = dateus_fr($station['date_installation_station']);
    $date_fermeture    = dateus_fr($station['date_fermeture_station']);

    // Update the global station counters for the sidebar summary
    $nb_station++;
    if ($active_station > 0) $nb_station_active++;
    if ($suivi_station  > 0) $nb_station_suivi++;
    if ($armee_station  > 0) $nb_station_panne++;


    // ----------------------------------------------------------
    // 7c. Resolve geographic coordinates → WGS84 [lon, lat]
    // ----------------------------------------------------------
    // Three possible coordinate sources, tried in priority order:
    //   1. Direct decimal or DMS latitude/longitude fields
    //   2. UTM easting/northing fields
    //   3. Lambert easting/northing fields
    // If none are available the station is skipped (no marker placed).

    // Normalise decimal separators (some locales store "17,532" instead of "17.532")
    $latitude       = str_replace(',', '.', $station['latitude_station']);
    $longitude      = str_replace(',', '.', $station['longitude_station']);
    $utm_station_x  = str_replace(',', '.', $station['utm_station_x']);
    $utm_station_y  = str_replace(',', '.', $station['utm_station_y']);
    $lamb_station_x = str_replace(',', '.', $station['lamb_station_x']);
    $lamb_station_y = str_replace(',', '.', $station['lamb_station_y']);
    $altitude       = round((int)$station['altitude_station'], 0);

    $convertedCoords = null; // will hold [lon, lat] once resolved

    if (tep_not_null($latitude) && tep_not_null($longitude)) {
        // --- Source 1: direct lat/lon (decimal number or DMS string) ---
        // is_numeric() returns false for DMS strings, triggering dmsToDecimal()
        $coords_latitude  = is_numeric($latitude)  ? $latitude  : dmsToDecimal($latitude,  'S');
        $coords_longitude = is_numeric($longitude) ? $longitude : dmsToDecimal($longitude, 'W');
        $convertedCoords  = [floatval($coords_longitude), floatval($coords_latitude)];

    } elseif (tep_not_null($utm_station_x) && tep_not_null($utm_station_y)) {
        // --- Source 2: UTM easting/northing for the current territory ---
        $convertedCoords = convertCoordinates(
            $utm_station_x, $utm_station_y,
            $territoriesProjections[$territoire_init]['utm'],
            $projWGS84, $proj4
        );

    } elseif (tep_not_null($lamb_station_x) && tep_not_null($lamb_station_y)) {
        // --- Source 3: Lambert easting/northing (NC only) ---
        $convertedCoords = convertCoordinates(
            $lamb_station_x, $lamb_station_y,
            $territoriesProjections[$territoire_init]['lambert'],
            $projWGS84, $proj4
        );
    }

    // --- Normalisation Kiribati : repère de longitude continu (pas de saut à 180°) ---
    // Le pays s'étend des îles Gilbert (+173°E) aux îles Line (~-157°W), de part
    // et d'autre de l'antiméridien. On exprime tout le pays en longitudes négatives
    // continues : Gilbert +173 → -187, Phoenix/Line déjà négatives restent inchangées.
    // $convertedCoords[0] est la longitude (le tableau est [lon, lat]).
    if ($territoire_init === 'KI' && $convertedCoords !== null && $convertedCoords[0] > 0) {
        $convertedCoords[0] -= 360;
    }

    // If no coordinate source was available, skip this station entirely.
    // It will not appear on the map and will not be counted in the legend.
    if ($convertedCoords === null) continue;


    // ----------------------------------------------------------
    // 7d. Determine station status, CSS class & update legend counters
    // ----------------------------------------------------------
    // Status logic (mutually exclusive main states, panne can overlay):
    //   suivi > 0 + active > 0  → currently monitored and operational
    //   suivi > 0 + active = 0  → monitored in the past, now closed (historic)
    //   suivi = 0               → only occasional / campaign measurements
    //   armee > 0               → equipment disarmed / out of service (overrides CSS)

    $init_type = $eq_type_array[$type_data_station]['init_eq_type']; // e.g. "pluie", "debit"
    $color_pin = $eq_type_array[$type_data_station]['type_color_border']; // hex colour for the marker

    if ($suivi_station > 0) {
        if ($active_station > 0) {
            // Continuously monitored and currently active
            $status_class = 'statut-active';
            $text_statut  = TEXT_FILTER_STATUTACTIVE;
            $legend[$init_type]['active']++;
        } else {
            // Was monitored but is now closed / historic
            $status_class = 'statut-historique';
            $text_statut  = TEXT_FILTER_STATUTHISTORIQUE;
            $legend[$init_type]['historique']++;
        }
    } else {
        // Spot / campaign measurements only — neither active nor historic
        $status_class = 'statut-ponctuel';
        $text_statut  = TEXT_FILTER_SUIVIPONCTUEL;
        $legend[$init_type]['ponctuel']++;
    }

    // "armee" (disarmed) overrides the visual status with a red indicator.
    // It is additive: a station can be both "active" and "out of service".
    if ($armee_station > 0) {
        $status_class  = 'statut-panne';
        $text_statut  .= ' - <span style="color:#B80000;">' . TEXT_FILTER_ETATPANNE . '</span>';
        $legend[$init_type]['panne']++;
    }


    // ----------------------------------------------------------
    // 7e. Build the shared tooltip / popup content block ($text_content)
    // ----------------------------------------------------------
    // This block is reused in both the hover tooltip and the click popup,
    // so it is built once here.  html_info_line() silently skips empty values.

    $text_content  = html_info_line(TEXT_FROM_DATA,    $from_name);
    $text_content .= html_info_line(TEXT_STATION_TYPE, $eq_type_array[$type_data_station]['nom_eq_type']);
    $text_content .= '<p>-</p>';

    $text_content .= html_info_line(TEXT_STATION_CODE, $code_station);
    $text_content .= '<p><span>' . TEXT_STATION_NOM . ' :</span><br><span style="font-weight:normal;">' . $nom_station . '</span></p>';
    $text_content .= '<p>-</p>';

    // Administrative labels (rendered only when non-empty)
    $text_content .= html_info_line($territoire_region, $nom_region);
    $text_content .= html_info_line(TEXT_FILTER_CITY,  $nom_commune);
    $text_content .= html_info_line(TEXT_FILTER_BV,    $nom_regionhydro, 'margin-top:10px;');
    $text_content .= html_info_line(TEXT_FILTER_RIVER, $nom_riviere);

    // Coordinates display (rounded to 3 decimal places ≈ ~100m precision)
    $text_content .= '<p style="margin-top:5px;">'
        . '<span>' . TEXT_MAP_LONG . ' : </span>' . round($convertedCoords[0], 3)
        . '<span style="margin-left:5px;">' . TEXT_MAP_LAT . ' : </span>' . round($convertedCoords[1], 3)
        . '</p>';

    if (tep_not_null($altitude)) {
        $text_content .= html_info_line(TEXT_MAP_ALT, $altitude . ' m');
    }

    $text_content .= '<p>-</p>';
    $text_content .= '<p style="margin-bottom:5px;"><span>' . TEXT_STATION_STATUT . ' : </span>' . $text_statut . '</p>';
    $text_content .= html_info_line(TEXT_STATION_DATE_INSTALL, $date_installation);
    $text_content .= html_info_line(TEXT_STATION_DATE_CLOSING, $date_fermeture);


    // ----------------------------------------------------------
    // 7f. Build the hover tooltip HTML string
    // ----------------------------------------------------------
    // Shown when the cursor hovers over a marker.
    // Compact: only the shared content block inside a coloured header.

    $text_toolTip  = '<div class="tooltip-map">';
    $text_toolTip .= '<h2 style="background-color:' . $color_pin . ';">'
        . '<span>' . $code_station . ' - ' . $nom_station . '</span></h2>';
    $text_toolTip .= '<div class="tooltip-item" style="padding:5px 10px;">' . $text_content . '</div>';
    $text_toolTip .= '</div>';


    // ----------------------------------------------------------
    // 7g. Build the click popup HTML string
    // ----------------------------------------------------------
    // Shown when the user clicks on a marker.
    // Wider (700px) two-column layout:
    //   Left column  → shared info content + link to station sheet
    //   Right column → AJAX-loaded statistics chart (filled by loadPopupData() in JS)

    $text_popup  = '<div class="tooltip-map" style="width:550px;">';

        // Header bar (colored according to station type)
        $text_popup .= '<h2 style="background-color:' . $color_pin . ';">'
                    .     '<span>' . $code_station . ' - ' . $nom_station . '</span>'
                    . '</h2>';

        // Two-column container
        $text_popup .= '<div class="content-container">';

            // ---- Left column: static station info ----
            $text_popup .= '<div style="width:250px;padding-right:15px;border-right:1px solid #ccc;overflow:hidden;word-break:break-word;">';
                $text_popup .= '<div class="tooltip-item" style="padding:5px 0;">' . $text_content . '</div>';
                $text_popup .= '<div class="tooltip-ligne"></div>';

                // Link to the full station record edit page (opens in a new tab)
                $text_popup .= '<p><a href="modif_station.php?ref=' . $id_station . '" target="_blank" style="font-size:12px;">'
                            .     TEXT_STATION_LINK_FICHE
                            . '</a></p>';
            $text_popup .= '</div>'; // /left column

            // ---- Right column: AJAX-loaded chart ----
            // The div id "popup-container-{id}" is targeted by loadPopupData() in JS
            $text_popup .= '<div style="width:300px;">';
                $text_popup .= '<div id="tooltip-stat">';
                    $text_popup .= '<div id="popup-container-' . $id_station . '">Chargement ...</div>';
                    $text_popup .= '<div class="tooltip-ligne"></div>';

                    // Link to the full chronological data page for this station
                    $text_popup .= '<p><a href="data_chron.php?id_st=' . $id_station . '" target="_blank" style="font-size:12px;">'
                                .     TEXT_STATION_LINK_DATA
                                . '</a></p>';
                $text_popup .= '</div>'; // /#tooltip-stat
            $text_popup .= '</div>'; // /right column

        $text_popup .= '</div>'; // /.content-container

    $text_popup .= '</div>'; // /.tooltip-map

    // ----------------------------------------------------------
    // 7h. Push station data into the JSON export array
    // ----------------------------------------------------------
    // json_encode() later handles all HTML escaping safely,
    // eliminating the need for manual addslashes() calls.

    $stations_data[] = [
        'id'      => $id_station,
        'code'    => $code_station, 
        'coords'  => $convertedCoords, // [lon, lat] — note JS uses [lat, lon] for L.marker
        'color'   => $color_pin,
        'status'  => $status_class,
        'tooltip' => $text_toolTip,
        'popup'   => $text_popup,
    ];

} // end while ($station)


// ============================================================
// 8. BUILD LEGEND ITEMS ARRAY FOR JSON EXPORT
// ============================================================
// Iterates equipment types and their four possible statuses.
// Only entries whose counter is > 0 are included, so the legend
// automatically shows only statuses that actually appear on the map.
// $legend_items is passed to JS as `legendItems` and rendered client-side.

$legend_items = [];

// Map each status key to its CSS class and i18n label
$statut_definitions = [
    'active'     => ['class' => 'statut-active',    'label' => TEXT_FILTER_STATUTACTIVE],
    'historique' => ['class' => 'statut-historique', 'label' => TEXT_FILTER_STATUTHISTORIQUE],
    'ponctuel'   => ['class' => 'statut-ponctuel',   'label' => TEXT_FILTER_SUIVIPONCTUEL],
    'panne'      => ['class' => 'statut-panne',      'label' => TEXT_FILTER_ETATPANNE],
];

foreach ($eq_type_array as $type) {
    $init      = $type['init_eq_type'];
    $nom       = ucfirst($type['nom_eq_type']);
    $color_pin = $type['type_color_border'];

    foreach ($statut_definitions as $key => $def) {
        // Only add a legend entry if at least one station has this status
        if ($legend[$init][$key] > 0) {
            $legend_items[] = [
                'color' => $color_pin,
                'class' => $def['class'],
                'label' => $nom . ' - ' . $def['label'],
            ];
        }
    }
}


// ============================================================
// 9. HTML RENDERING
// ============================================================

// Outputs the <head> block with CSS / meta tags
require(DIR_WS_STRUCTURE . 'header_web.php');

echo "<body>";

	// Hidden div used by other parts of the application for transient messages
	echo "<div id='contenu_info' style='display:none;'></div>";

	require(DIR_WS_STRUCTURE . 'header.php'); // Top banner / branding bar
	include(DIR_WS_BOX . 'nav_accueil.php');  // Main navigation menu

	// Saved collapse state of the right-hand filters panel for this user.
	// Stored in TABLE_USER_MENU (same table/endpoint as the left menu),
	// under menu_id = 'filters'. Default: open (1) when no row exists yet.
	$filters_is_open = 1;
	$sql_filters_state = "SELECT is_open FROM " . TABLE_USER_MENU . "
	                      WHERE id_user = ? AND menu_id = 'filters'";
	if ($stmt = $sql_link->prepare($sql_filters_state))
	{
		$stmt->bind_param("i", $id_user);
		$stmt->execute();
		$stmt->bind_result($filters_is_open_db);
		if ($stmt->fetch()) { $filters_is_open = (int) $filters_is_open_db; }
		$stmt->close();
	}

    $codes_are_shown = 0;
    $sql_codes_state = "SELECT is_open FROM " . TABLE_USER_MENU . "
                        WHERE id_user = ? AND menu_id = 'station_codes'";
    if ($stmt = $sql_link->prepare($sql_codes_state))
    {
        $id_user_int = (int) $id_user;               // forcer le type entier
        $stmt->bind_param("i", $id_user_int);
        $stmt->execute();
        $stmt->store_result();                        // libère/charge le résultat
        $stmt->bind_result($codes_are_shown_db);
        if ($stmt->fetch()) { $codes_are_shown = (int) $codes_are_shown_db; }
        $stmt->close();
    }

	echo "<div id='contour_general'>";
	echo "<div id='contenu_centre'>";

	// Outer wrapper for the map + sidebar block.
	// Height is computed so the map fills the remaining viewport height.
	echo "<div id='cadre_index_cell_map' style='width:98%;height:calc(100% - 20px);'>";

		// --- Top bar: page title + live coordinate readout ---
		echo "<div style='float:left;width:100%;margin-bottom:0;'>";

			// Left: page title
			echo "<div style='float:left;width:28%;'>";
				echo "<p class='aa' style='text-align:left;height:20px;margin-top:3px;font-size:16px;'>";
					echo TEXT_MAP_TITLE;
				echo "</p>";
			echo "</div>";

			// Right: 'Filters' heading + collapse toggle, aligned above the
			// right-hand filter panel (same 16% right column, so the left edge
			// lines up with 'Data Owner'). Teal underline to match the section.
			echo "<div id='filters_header' style='float:right;width:16%;'>";
				echo "<p style='width:95%;margin:0 0 8px 0;padding-bottom:4px;text-align:left;"
				   . "font-size:15px;font-weight:bold;color:#176B87;"
				   . "border-bottom:2px solid #176B87;'>"
				   . "<span>" . TEXT_FILTER_TITLE . "</span>"
				   . "<span id='filters_reset' title=\"" . TEXT_FILTER_RESET . "\""
				   . " style='cursor:pointer;font-size:13px;line-height:15px;margin-left:8px;color:#888;'>&#8635;</span>"
				   . "<span id='filters_toggle' title=\"" . TEXT_FILTER_TOGGLE . "\""
				   . " style='float:right;cursor:pointer;font-size:18px;line-height:18px;'>&raquo;</span>"
				   . "</p>";
			echo "</div>";

			// Middle: recenter button + read-only zoom / lon / lat inputs
			// These inputs are updated in real time by the JS 'moveend' event listener.
			echo "<div style='float:left;width:56%;padding-top:7px;'>";
				echo "<p style='float:left;margin-top:-3px;'>";
					echo "<img id='map_center' src='" . DIR_WS_IMG_ICO . "tracking.png' "
						. "style='width:20px;margin-right:15px;cursor:pointer;' "
						. "title=\"" . TEXT_MAP_BACK . "\" />";
				echo "</p>";
				echo "<p style='float:left;width:40px;font-size:13px;font-weight:bold;'>" . TEXT_MAP_ZOOM . "</p>";
				echo "<input type='text' id='mapZoom_input' readonly style='float:left;width:40px;padding:0;font-size:13px;background:none;border:none;'>";
				echo "<p style='float:left;width:40px;font-size:13px;font-weight:bold;'>" . TEXT_MAP_LONG . "</p>";
				echo "<input type='text' id='mapLong_input' readonly style='float:left;width:70px;padding:0;font-size:13px;background:none;border:none;'>";
				echo "<p style='float:left;width:30px;font-size:13px;font-weight:bold;'>" . TEXT_MAP_LAT . "</p>";
				echo "<input type='text' id='mapLat_input'  readonly style='float:left;width:70px;padding:0;font-size:13px;background:none;border:none;'>";
			echo "</div>";

		echo "</div>"; // top bar

		// --- Leaflet map container (82% width, fills remaining height) ---
		echo "<div id='map' style='float:left;width:82%;height:calc(100% - 50px);box-sizing:border-box;"
			. "font-size:12px;border:2px solid #e0e0e0;border-radius:6px;'></div>";

		// --- Right sidebar (16% width, scrollable) ---
		echo "<div id='filters_panel' style='float:right;width:16%;height:calc(100% - 50px);overflow-y:auto;'>";

			// Filter form (contents rendered by filtre_stations_html.php)
			// On submit the page reloads with updated GET/POST filter parameters.
			echo "<div id='boxpopup' class='select-top' style='width:95%;border:none;box-shadow:none;'>";

				$lien_form = tep_href_link('index.php');
				$name_form = 'form_carto'; // referenced by filtre_stations_html.php
				echo "<form name='{$name_form}' action='{$lien_form}' method='post' enctype='multipart/form-data'>";
					require(DIR_WS_FILTRE . 'filtre_stations_html.php');
				echo "</form>";	
			echo "</div>";

			// Station count summary panel
			// Station count summary - stat cards (2x2 grid)
			echo "<div id='contenu_infos' class='filtre-stats'>";

				echo "<div class='filtre-stat'>";
					echo "<div class='stat-label'>" . TEXT_FILTER_NBSTATION . "</div>";
					echo "<div class='stat-value'>" . number_format($nb_station, 0, '.', ' ') . "</div>";
				echo "</div>";

				echo "<div class='filtre-stat'>";
					echo "<div class='stat-label'>" . TEXT_FILTER_STATUTACTIVE . "</div>";
					echo "<div class='stat-value active'>" . number_format($nb_station_active, 0, '.', ' ') . "</div>";
				echo "</div>";

				echo "<div class='filtre-stat'>";
					echo "<div class='stat-label'>" . TEXT_FILTER_SUIVICONTINU . "</div>";
					echo "<div class='stat-value continu'>" . number_format($nb_station_suivi, 0, '.', ' ') . "</div>";
				echo "</div>";

				echo "<div class='filtre-stat'>";
					echo "<div class='stat-label'>" . TEXT_FILTER_ETATPANNE . "</div>";
					echo "<div class='stat-value panne'>" . number_format($nb_station_panne, 0, '.', ' ') . "</div>";
				echo "</div>";

			echo "</div>";

			// Action buttons (shown only when data management module is active)
			// "Last RA" → shows the most recent field report
			// "Last Import" → shows the most recent data import log
			if ($gestion_data > 0) {
				echo "<div class='filtre-actions'>";

					// Last field report
					echo "<a href='#' id='affiche_last_ra' class='filtre-action-btn'>";
						echo "<svg class='act-ico' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>"
							. "<path d='M14 3v4a1 1 0 0 0 1 1h4'/>"
							. "<path d='M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2z'/>"
							. "</svg>";
						echo "<span>" . TEXT_BUTTON_RA . "</span>";
					echo "</a>";

					// Last imported data
					echo "<a href='#' id='affiche_last_import' class='filtre-action-btn'>";
						echo "<svg class='act-ico' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>"
							. "<path d='M12 3v12'/>"
							. "<path d='m8 11 4 4 4-4'/>"
							. "<path d='M5 21h14'/>"
							. "</svg>";
						echo "<span>" . TEXT_BUTTON_IMPORT . "</span>";
					echo "</a>";

				echo "</div>";
			}

		echo "</div>"; // right sidebar

	echo "<hr>";
	echo "</div>"; // #cadre_index_cell_map
	echo "</div>"; // #contenu_centre
	echo "</div>"; // #contour_general
	
echo "</body>";

echo "</html>";

?>

<script>
// ============================================================
// 10. PHP → JS DATA BRIDGE
// ============================================================
// All PHP values are serialised with json_encode() which correctly
// escapes quotes, backslashes, and Unicode — no manual addslashes() needed.
// Grouping all bridge variables at the top makes the boundary explicit.

// --- User & session context ---
var id_user      = <?= json_encode($id_user) ?>;
var hpVersion    = <?= json_encode(HP_VERSION) ?>;  // 'Nomad' triggers offline tile layer
var lang         = <?= json_encode(LANGUAGE) ?>;

// --- Territory identifiers ---
var territoireId   = <?= json_encode($territoire_id) ?>;    // numeric DB id
var territoireInit = <?= json_encode($territoire_init) ?>;  // string code: 'PF', 'NC', 'WF' …

// --- Map viewport (user-saved or territory defaults) ---
var mapLong    = <?= json_encode($mapLong) ?>;
var mapLat     = <?= json_encode($mapLat) ?>;
var mapZoom    = <?= json_encode($mapZoom) ?>;
var mapMinZoom = <?= json_encode($mapMinZoom) ?>;

// --- Territory-level defaults (used by the recenter button) ---
var territoireMapLong = <?= json_encode($territoire_mapLong) ?>;
var territoireMapLat  = <?= json_encode($territoire_mapLat) ?>;
var territoireMapZoom = <?= json_encode($territoire_mapZoom) ?>;

// --- Station dataset ---
// Each entry: { id, coords:[lon,lat], color, status, tooltip, popup }
// Built by the PHP loop in step 7.  The JS loop in step 14 creates markers from it.
var stationsData = <?= json_encode($stations_data) ?>;

// --- Legend dataset ---
// Each entry: { color, class, label }
// Built by the PHP loop in step 8.  The JS block in step 15 renders the widget.
var legendItems = <?= json_encode($legend_items) ?>;

// --- i18n strings needed client-side ---
var textMapFullscreen = <?= json_encode(TEXT_MAP_FULLSCREEN) ?>;
var textMapWindowed   = <?= json_encode(TEXT_MAP_WINDOWED) ?>;
var textMapBack       = <?= json_encode(TEXT_MAP_BACK) ?>;
var textLensDepth = <?= json_encode(TEXT_MAP_LENS_DEPTH) ?>;


// ============================================================
// DOM ELEMENT REFERENCES
// ============================================================
// Cached once here to avoid repeated getElementById() calls.

var mapZoom_input     = document.getElementById('mapZoom_input');
var mapLong_input     = document.getElementById('mapLong_input');
var mapLat_input      = document.getElementById('mapLat_input');
var mapCenter         = document.getElementById('map_center');
var afficheLastRA     = document.getElementById('affiche_last_ra');    // may be null
var afficheLastImport = document.getElementById('affiche_last_import');// may be null
var boxData           = document.getElementById('box_data');
var waitBox           = document.getElementById('cadre_wait');
var contenuBox        = document.getElementById('cadre_index_cell');

// Array holding all Leaflet marker instances (for future bulk operations, e.g. clustering)
var markers = [];


// ============================================================
// 11. LEAFLET MAP INITIALISATION
// ============================================================

var mapOptions = {
    center:   [mapLat, mapLong], // Leaflet expects [lat, lon]
    zoom:      mapZoom,
    minZoom:   mapMinZoom,       // prevent zooming out beyond territory bounds
    maxZoom:   20,               // tile provider limit
    zoomSnap:  0.25,             // allow fractional zoom levels
    zoomDelta: 0.25,             // zoom step per scroll tick
    wheelPxPerZoomLevel: 180,    // slower mouse-wheel zoom for precision
    crs: L.CRS.EPSG3857          // standard Web Mercator (used by all tile providers here)
};
if (territoireInit === 'PF') {mapOptions.crs = L.CRS.EPSG4326; }

var mymap = L.map('map', mapOptions);

// Background colour visible before tiles load (matches typical ocean / sea colour)
mymap.getContainer().style.background = '#547792';

// Fullscreen control (requires leaflet.fullscreen plugin)
mymap.addControl(new L.Control.Fullscreen({
    title:    { 'false': textMapFullscreen, 'true': textMapWindowed },
    position: 'topleft'
}));


// ============================================================
// 12. BASE LAYERS & OVERLAY LAYERS
// ============================================================
// Switched by a Leaflet layer-control widget rendered in step 15.

var baseMaps    = {}; // mutually exclusive layers (radio buttons in the control)
var overlayMaps = {}; // toggleable layers (checkboxes in the control)

if (hpVersion === 'Nomad' || !navigator.onLine) {
    // --- Offline mode: use locally cached tiles for New Caledonia ---
    // Tile files must be placed in ./map/tiles-nc/{z}/{x}/{y}.png
    var osmLayer = L.tileLayer('./map/tiles-nc/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors, ODbL',
        maxZoom: 12
    });
    osmLayer.addTo(mymap);
    baseMaps = { "OpenStreetMap": osmLayer };

} else {

	// Parse saved map preferences; fall back to defaults if nothing saved yet.
	// json_encode ensures safe output even if the value contains quotes or special characters.
	var mapPrefs     = JSON.parse(<?php echo json_encode($info_base_prefs ?: '{}'); ?>);

	    
    // --- Overlay layers specific to French Polynesia ---
    if (territoireInit === 'PF') 
	{
		// getTefenuaLayer() — helper to build a WMTS tile layer from the Tefenua geoportal.
        // Tefenua is the official cartographic portal of French Polynesia (tefenua.gov.pf).
        // All layers share the same WMTS endpoint; only the layer name, format and opacity differ.
        // Must be declared inside the PF block because it is only valid / useful here.
        //
        // @param {string} layerName  Tefenua layer identifier (e.g. 'TEFENUA:FOND')
        // @param {string} format     MIME type returned by the server (e.g. 'image/jpeg')
        // @param {number} opacity    Layer opacity 0–1 (default 1)
        // @returns {L.TileLayer}
        function getTefenuaLayer(layerName, format, opacity) {
            format  = format  || 'image/jpeg';
            opacity = (opacity !== undefined) ? opacity : 1;
            return L.tileLayer(
                'https://www.tefenua.gov.pf/api/wmts?' +
                'Service=WMTS&Version=1.0.0&Request=GetTile' +
                '&Layer='         + encodeURIComponent(layerName) +
                '&Format='        + encodeURIComponent(format) +
                '&Style=default' +
                '&TileMatrixSet=' + encodeURIComponent('EPSG:4326') +
                '&TileMatrix={z}&TileCol={x}&TileRow={y}',
                { attribution: 'Source: Tefenua', tileSize: 256, opacity: opacity }
            );
        }
 
        // --- Tefenua base / thematic layers (commented out = available but not active by default) ---
        var tefenuaSatellite = getTefenuaLayer('TEFENUA:IMAGE_PUBLIQUE', 'image/png8', 0.8);
        var tefenuaPlan      = getTefenuaLayer('TEFENUA:FOND',                    'image/jpeg', 1);
        var tefenuaTopo      = getTefenuaLayer('TEFENUA:CARTE_TOPO',              'image/png8');
        var cadastreTefenua  = getTefenuaLayer('TEFENUA:CADASTRE',                'image/png');
        var pprTefenua       = getTefenuaLayer('TEFENUA:PPR',                     'image/png8', 0.6);
        var pgaTefenua       = getTefenuaLayer('TEFENUA:PLAN_GENERAL_AMENAGEMENT','image/png8', 0.6);
 
        // To add a Tefenua layer as a base map, uncomment it above AND add it here:
        baseMaps["Plan Officiel (Tefenua)"]  = tefenuaPlan;
        baseMaps["Satellite (Tefenua)"]      = tefenuaSatellite;

		var defaultLayer = tefenuaPlan;
 
        // --- GeoJSON overlay layers (loaded asynchronously) ---


        var layerBvTahiti     = L.featureGroup(); // catchment boundaries
        var layerRiversTahiti = L.featureGroup(); // river network

        loadGeoJSON('./map/geojson/pf/bv_tahiti.json',     layerBvTahiti,     '#e74c3c', '', false);
        loadGeoJSON('./map/geojson/pf/rivers_tahiti.json', layerRiversTahiti, '#3F72AF', '', false);

        overlayMaps = {
            "BV Tahiti (VN)":       layerBvTahiti,
            "Rivières Tahiti (VN)": layerRiversTahiti,
            "Carte Topographique": tefenuaTopo,
            "Cadastre":            cadastreTefenua,
            "PGA (Aménagement)":   pgaTefenua,
            "PPR (Risques)":       pprTefenua,
        };
    }
    else if (territoireInit === 'KI')
	{
		// --- Online mode base layers (Kiribati) ---
		var satelliteLayer = L.tileLayer(
			'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
			{ attribution: '&copy; ArcgisOnline', maxZoom: 20 }
		);
		var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap', maxZoom: 20
		});

		// OpenTopoMap (contour lines, useful for hydrological context)
		var openTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenTopoMap', maxZoom: 20
		});

		var defaultLayer = satelliteLayer;
		baseMaps = {
			"Satellite (Esri)": satelliteLayer,
			"OpenStreetMap":    osmLayer,
			"OpenTopo":         openTopoMap
		};

		// --- GeoJSON overlay layer (Kiribati) : lentilles d'eau douce ---
		// Coloration par classe de profondeur (champ "Depth"), reprenant la
		// symbologie ArcGIS : 0-3 bleu, 3-6 orange, 6-10 rouge, >10 violet.
		var fwlColors = {
			'0 - 3':  '#1f9fe0', // bleu
			'3 - 6':  '#f5a623', // orange
			'6 - 10': '#e2231a', // rouge
			'> 10':   '#c800c8'  // violet / magenta
		};

		var layerFwlKiribati = L.featureGroup(); // FW lens polygons

		fetch('./map/geojson/ki/ki-fwl.json')
			.then(function (r) { return r.json(); })
			.then(function (data) {
				L.geoJSON(data, {
					// même normalisation antiméridien que les stations KI (PHP étape 7c)
					coordsToLatLng: function (coords) {
						var lon = coords[0];
						if (lon > 0) lon -= 360;
						return L.latLng(coords[1], lon);
					},
					// couleur déterminée polygone par polygone selon Depth
					style: function (feature) {
						var depth = feature.properties && feature.properties.Depth;
						var col   = fwlColors[depth] || '#888888'; // gris si classe inconnue
						return {
							color:       col,   // bordure
							weight:      1.5,
							fillColor:   col,   // remplissage
							fillOpacity: 0.55
						};
					},
					onEachFeature: function (feature, layer) {
						var depth = (feature.properties && feature.properties.Depth) || '?';
						layer.bindPopup('<b>' + textLensDepth + '</b> ' + depth + ' m');
					}
				}).addTo(layerFwlKiribati);
			})
			.catch(function (e) { console.error('ki-fwl.json:', e); });

		overlayMaps = {
			" Freshwater lens": layerFwlKiribati,
		};
	}
	else
	{
		// --- Online mode: remote tile providers ---

		// Esri World Imagery (satellite) — default base layer
		var satelliteLayer = L.tileLayer(
			'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
			{ attribution: '&copy; ArcgisOnline', maxZoom: 16 }
		);

		// OpenStreetMap (road / toponym labels)
		var osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap', maxZoom: 16
		});

		// OpenTopoMap (contour lines, useful for hydrological context)
		var openTopoMap = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenTopoMap', maxZoom: 16
		});

		// Satellite is the default base layer for all territories
		var defaultLayer = satelliteLayer;

		baseMaps = {
			"Satellite (Esri)": satelliteLayer,
			"OpenStreetMap":    osmLayer,
			"OpenTopo":         openTopoMap		};

	}

	// If a valid saved preference exists and matches a known layer, use it instead.
	if (mapPrefs.base && baseMaps[mapPrefs.base]) {
		defaultLayer = baseMaps[mapPrefs.base];
	}
	defaultLayer.addTo(mymap);

	// Restore overlay layers
	if (Array.isArray(mapPrefs.overlays)) {
		mapPrefs.overlays.forEach(function(layerName) {
			if (overlayMaps[layerName]) {
				overlayMaps[layerName].addTo(mymap);
			}
		});
	}
}


// ============================================================
// 13. GEOJSON LOADER HELPER
// ============================================================
/**
 * loadGeoJSON()
 * Fetches a GeoJSON file and adds it to a Leaflet FeatureGroup.
 *
 * @param {string}         url            Path or URL to the .json file
 * @param {L.FeatureGroup} conteneur      Target Leaflet layer group
 * @param {string}         couleurBordure Stroke colour (CSS hex or name)
 * @param {string}         couleurFond    Fill colour (CSS hex or name)
 * @param {boolean}        afficherFond   true → semi-transparent fill; false → outline only
 * @param {function}       coordsToLatLng Optional per-point transform (e.g. KI antimeridian
 *                                        normalisation). When omitted, Leaflet's default
 *                                        [lon,lat] → [lat,lon] behaviour is used, so existing
 *                                        layers (PF, etc.) are unaffected.
 */
function loadGeoJSON(url, conteneur, couleurBordure, couleurFond, afficherFond, coordsToLatLng) {
    couleurBordure = couleurBordure || '#2980b9';
    couleurFond    = couleurFond    || '#3498db';
    afficherFond   = (afficherFond === undefined) ? true : afficherFond;

    fetch(url)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var options = {
                style: {
                    color:       couleurBordure,
                    weight:      2,
                    fillColor:   couleurFond,
                    fillOpacity: afficherFond ? 0.3 : 0
                },
                onEachFeature: function(feature, layer) {
                    // Show the feature name in a popup on click
                    var nom = feature.properties.Nom_BV || feature.properties.NOM || 'Sans nom';
                    layer.bindPopup('<b>Détail :</b> ' + nom);

                    // Highlight on hover
                    layer.on('mouseover', function() { this.setStyle({ weight: 4 }); });
                    layer.on('mouseout',  function() { this.setStyle({ weight: 2 }); });
                }
            };

            // Optional coordinate transform (e.g. Kiribati antimeridian normalisation).
            // Only applied when a function is explicitly passed in.
            if (typeof coordsToLatLng === 'function') {
                options.coordsToLatLng = coordsToLatLng;
            }

            L.geoJSON(data, options).addTo(conteneur);
        });
}


// ============================================================
// 14. MARKER CREATION FROM stationsData JSON
// ============================================================
// Replaces the original PHP-generated JS string.
// One clean forEach loop instead of hundreds of concatenated var declarations.

/**
 * createDynamicIcon()
 * Returns a Leaflet DivIcon with a coloured pin and a status ring.
 * The pin shape and ring colour are controlled entirely by CSS classes
 * (.marker-pin, .statut-active, .statut-historique, .statut-ponctuel, .statut-panne).
 *
 * @param {string} pinColor    Hex colour for the pin body
 * @param {string} statusClass CSS class that controls the ring / indicator colour
 * @returns {L.DivIcon}
 */
function createDynamicIcon(pinColor, statusClass) {
    return L.divIcon({
        className: 'custom-div-icon',
        html: '<div style="background-color:' + pinColor + ';" class="marker-pin ' + statusClass + '"></div>',
        iconSize:    null, // CSS controls actual dimensions via --map-zoom-size
        iconAnchor:  [0, 0],
        popupAnchor: [0, 0]
    });
}

stationsData.forEach(function(s) {
    // Note: coords is [lon, lat] but L.marker expects [lat, lon]
    var marker = L.marker([s.coords[1], s.coords[0]], {
        icon: createDynamicIcon(s.color, s.status)
    })
    .bindTooltip(s.tooltip) // shown on hover
    .bindPopup(s.popup, { maxWidth: 'auto', className: 'custom-popup' }) // shown on click
    .addTo(mymap);

    // When the popup opens, trigger AJAX to load station statistics into the right column
    marker.on('popupopen', function() { loadPopupData(s.id); });

    markers.push(marker); // keep a reference for potential future use (clustering, etc.)
});


// ============================================================
// 15. LEGEND WIDGET
// ============================================================
// Built from legendItems (step 10) — only statuses actually present on the map appear.
// Collapsible legend control (closed by default).
var legendHTML = 
    '<div class="legend collapsed" id="legendWidget">' +
        '<div class="legend-header">' +
            '<h4><?= TEXT_MAP_LEGEND_TITLE ?></h4>' +
            '<button class="legend-toggle" id="legendToggle" title="Agrandir">+</button>' +
        '</div>' +
        '<div class="legend-body">';

legendItems.forEach(function(item) {
    legendHTML +=
        '<div class="legend-item">' +
            '<div class="legend-marker-wrapper">' +
                '<div class="marker-pin ' + item.class + '" style="background-color:' + item.color + ';"></div>' +
            '</div>' +
            '<span style="margin-left:15px;">' + item.label + '</span>' +
        '</div>';
});

legendHTML += '</div></div>';

// Wrap the HTML string in a Leaflet Control so it is properly positioned on the map
var LegendControl = L.Control.extend({
    onAdd: function() {
        var div = L.DomUtil.create('div', 'leaflet-control legend-control');
        div.innerHTML = legendHTML;
        // Empêche les clics sur la légende de se propager à la carte
        L.DomEvent.disableClickPropagation(div);
        L.DomEvent.disableScrollPropagation(div);
        return div;
    }
});
new LegendControl({ position: 'bottomleft' }).addTo(mymap);

// ---- Toggle (réduire/agrandir) ----
document.getElementById('legendToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    var widget = document.getElementById('legendWidget');
    var toggle = document.getElementById('legendToggle');
    widget.classList.toggle('collapsed');
    toggle.textContent = widget.classList.contains('collapsed') ? '+' : '−';
    toggle.title = widget.classList.contains('collapsed') ? 'Agrandir' : 'Réduire';
});


// ============================================================
// 15b. ÉTIQUETTES DE CODE STATION (toggle + persistance)
// ============================================================
var codeLabelMarkers = [];
var codesAreShown    = <?php echo (int) $codes_are_shown; ?> === 1;
var idUserCodes      = <?php echo json_encode($id_user); ?>;

var emptyIcon = L.divIcon({ className: 'station-code-anchor', html: '', iconSize: [1, 1], iconAnchor: [0, 0] });

stationsData.forEach(function(s) {
    var m = L.marker([s.coords[1], s.coords[0]], { icon: emptyIcon, interactive: false, keyboard: false });
    m.bindTooltip(s.code, { permanent: true, direction: 'right', offset: [8, 0], className: 'station-code-label' });
    codeLabelMarkers.push(m);
});

function applyCodeLabels(show) {
    codeLabelMarkers.forEach(function(m) {
        if (show) { if (!mymap.hasLayer(m)) m.addTo(mymap); }
        else      { if (mymap.hasLayer(m))  mymap.removeLayer(m); }
    });
}

function persistCodeLabels(show) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'include/structure/box/process_menu.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify({ id_user: idUserCodes, menu_id: 'station_codes', is_open: show ? 1 : 0 }));
}

var CodeToggleControl = L.Control.extend({
    onAdd: function() {
        var div = L.DomUtil.create('div', 'leaflet-control code-toggle-control');
        div.innerHTML = '<label class="code-toggle-label"><input type="checkbox" id="codeToggle"' + (codesAreShown ? ' checked' : '') + '> <?= TEXT_MAP_SHOW_CODES ?></label>';
        L.DomEvent.disableClickPropagation(div);
        return div;
    }
});
new CodeToggleControl({ position: 'bottomleft' }).addTo(mymap);

document.getElementById('codeToggle').addEventListener('change', function() {
    applyCodeLabels(this.checked);
    persistCodeLabels(this.checked);
});

applyCodeLabels(codesAreShown);

// ============================================================
// 16. MAP CONTROLS & INTERACTIONS
// ============================================================

// Attach layer switcher and scale bar to the map
L.control.layers(baseMaps, overlayMaps, { collapsed: false }).addTo(mymap);
L.control.scale({ imperial: false }).addTo(mymap); // metric scale only


// -----------------------------------------------
// Collapse/expand the right-hand filters panel
// Same persistence mechanism as the left menu: state saved per user in
// TABLE_USER_MENU (menu_id = 'filters') via process_menu.php. When collapsed,
// the map widens to fill the freed space; Leaflet is told to recompute size.

(function()
{
    var filtersToggle = document.getElementById('filters_toggle');
    var filtersPanel  = document.getElementById('filters_panel');
    var filtersHeader = document.getElementById('filters_header');
    var mapDiv        = document.getElementById('map');

    if (!filtersToggle || !filtersPanel || !mapDiv) { return; }

    var idUserFilters    = <?php echo json_encode($id_user); ?>;
    var filtersMenuId    = 'filters';
    var filtersIsOpenInit = <?php echo (int) $filters_is_open; ?>; // 1 = open, 0 = collapsed

    // Apply a given state (open/collapsed) to the DOM
    function applyFiltersState(isOpen)
    {
        if (isOpen)
        {
            filtersPanel.style.display = '';
            filtersPanel.style.width   = '16%';
            mapDiv.style.width         = '82%';
            filtersToggle.innerHTML    = '&raquo;'; // » : click to collapse
        }
        else
        {
            filtersPanel.style.display = 'none';
            mapDiv.style.width         = '100%';
            filtersToggle.innerHTML    = '&laquo;'; // « : click to expand
        }

        // Let Leaflet recompute the map size after the width change
        setTimeout(function() { mymap.invalidateSize(); }, 50);
    }

    // The collapse arrow lives inside the header; when collapsed we keep just
    // the arrow visible so the panel can be reopened. Move the toggle to stay
    // reachable by anchoring it in the always-present header block.
    // (filtersHeader stays in the top bar, so the arrow is always visible.)

    // Restore the saved state on load
    applyFiltersState(filtersIsOpenInit === 1);

    // Toggle on click + persist
    filtersToggle.addEventListener('click', function()
    {
        var willBeOpen = (filtersPanel.style.display === 'none'); // currently collapsed -> will open
        applyFiltersState(willBeOpen);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'include/structure/box/process_menu.php', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            id_user: idUserFilters,
            menu_id: filtersMenuId,
            is_open: willBeOpen ? 1 : 0
        }));
    });

    // Reset filters to their default state (Status = Active, rest = All).
    // Data Owner (from_services) and map preferences are preserved.
    // Deletes the saved rows server-side, then reloads so
    // filtre_stations_var.php falls back to its built-in defaults.
    var filtersReset = document.getElementById('filters_reset');
    if (filtersReset)
    {
        filtersReset.addEventListener('click', function()
        {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'include/structure/filtre/process_filter_reset.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onreadystatechange = function()
            {
                if (xhr.readyState === 4)
                {
                    // Reload to a clean URL so no POST filter params persist
                    window.location.href = window.location.pathname;
                }
            };

            xhr.send(JSON.stringify({ id_user: idUserFilters }));
        });
    }
})();

// Populate the read-only coordinate inputs with initial values
mapZoom_input.value = mymap.getZoom();
var center = mymap.getCenter();
mapLong_input.value = center.lng.toFixed(5);
mapLat_input.value  = center.lat.toFixed(5);


// --- Adaptive marker size based on zoom level ---
// Marker size is controlled by the CSS custom property --map-zoom-size.
// Size is stepped in 0.5-zoom increments to avoid an update on every tiny fractional change.
// Range: 20px (min zoom) → 50px (max zoom).
var lastZoom = -1;

function updateMarkerSize() {
    var zoom        = mymap.getZoom();
    var steppedZoom = Math.round(zoom * 2) / 2; // round to nearest 0.5

    if (steppedZoom !== lastZoom) {
        // Linear interpolation: add 4px per zoom level above 10, clamped to [20, 50]
        var sizeValue = Math.max(20, Math.min(20 + (steppedZoom - 10) * 4, 50));
        document.documentElement.style.setProperty('--map-zoom-size', sizeValue + 'px');
        lastZoom = steppedZoom;
    }
}

mymap.on('zoomend', updateMarkerSize);
updateMarkerSize(); // run once on page load to set the initial size


// --- Persist map position after panning / zooming (AJAX, fire-and-forget) ---
// Saves the current viewport to TABLE_USER_COORD so the next page load
// restores the user's last position (step 4 on the PHP side).
mymap.on('moveend', function() {
    var c = mymap.getCenter();

    // Update the read-only header inputs
    mapZoom_input.value = mymap.getZoom();
    mapLong_input.value = c.lng.toFixed(5);
    mapLat_input.value  = c.lat.toFixed(5);

    // Persist to the database (no response handling needed)
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'include/structure/index/process_index_map_coord.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify({
        idUser:  id_user,
        mapZoom: mymap.getZoom(),
        mapLong: c.lng.toFixed(5),
        mapLat:  c.lat.toFixed(5)
    }));
});



// Trigger on any layer change
mymap.on('baselayerchange overlayadd overlayremove', saveMapPrefs);


// --- Recenter button: fly back to territory-level defaults ---
mapCenter.onclick = function() {
    mymap.setView([territoireMapLat, territoireMapLong], territoireMapZoom);
};



// Build and save the full map preference object (base layer + active overlays).
function saveMapPrefs() 
{
    // Identify the active base layer
    var activeBase = '';
    Object.keys(baseMaps).forEach(function(name) {
        if (mymap.hasLayer(baseMaps[name])) activeBase = name;
    });

    // Identify all active overlay layers
    var activeOverlays = [];
    Object.keys(overlayMaps).forEach(function(name) {
        if (mymap.hasLayer(overlayMaps[name])) activeOverlays.push(name);
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'include/structure/filtre/process_filter.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify({
        id_user:      <?php echo json_encode($id_user); ?>,
        filter_id:    'info_base_prefs',
        filter_value: JSON.stringify({ base: activeBase, overlays: activeOverlays })
    }));
}



// ============================================================
// 17. AJAX: LOAD POPUP STATISTICS ON MARKER CLICK
// ============================================================
/**
 * loadPopupData()
 * ---------------
 * Called by the 'popupopen' event listener attached to each marker (step 14).
 * Sends idStation to the server and injects the returned HTML + chart JS
 * into the right column of the popup (#popup-container-{id}).
 *
 * The server-side script (process_stats_index.php) returns a JSON object:
 *   { html_text: "<...>", js_graph: "var chart = new Chart(...);" }
 *
 * @param {number} idStation  Primary key of the station
 */
function loadPopupData(idStation) {
    var contentPopup = document.getElementById('popup-container-' + idStation);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'include/structure/stats/process_stats_index.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var resp = JSON.parse(xhr.responseText);

            // Inject the HTML fragment into the popup's right column
            contentPopup.innerHTML = resp['html_text'];

            // Execute the chart initialisation code returned by the server.
            // eval() is intentional here: the server returns a Chart.js call
            // that references the canvas id injected by html_text above.
            eval(resp['js_graph']);
        }
    };

    xhr.send(JSON.stringify({
        territoireId: territoireId,
        lang:         lang,
        idStation:    idStation
    }));
}


// ============================================================
// 18. AJAX: INFO BOX FOR LAST RA / LAST IMPORT
// ============================================================
/**
 * openDataBox()
 * -------------
 * Generic helper that opens the floating info box (#box_data),
 * shows a loading spinner, fires an AJAX request to the given endpoint,
 * and injects the returned HTML fragment.
 *
 * Used by both the "Last RA" and "Last Import" sidebar links so the
 * two near-identical handlers from the original code are merged into one.
 *
 * The server-side scripts return: { js_html: "<...>" }
 *
 * @param {string} endpoint   URL of the PHP processor (relative path)
 * @param {string} titleText  Text to display in the box title bar
 */
function openDataBox(endpoint, titleText) {
    if (!boxData) return; // guard: element might not exist on this page variant

    var titleBox = document.getElementById('title_box_data_info');

    // Show the box with an empty title while loading
    boxData.style.display = 'block';
    titleBox.innerHTML    = '';
    waitBox.style.display = 'block';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', endpoint, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var resp = JSON.parse(xhr.responseText);

            waitBox.style.display = 'none';
            titleBox.innerHTML    = titleText;
            contenuBox.innerHTML  = resp['js_html'];

            // Make the box draggable (requires initDraggable() defined elsewhere)
            initDraggable('title_box_data', 'box_data');
        }
    };

    // Only the territory id is needed; the PHP script filters data accordingly
    xhr.send(JSON.stringify({ territoireId: territoireId }));
}

// Attach openDataBox() to the "Last RA" link if it exists in the DOM
if (afficheLastRA) {
    afficheLastRA.onclick = function() {
        openDataBox(
            'include/structure/index/process_index_last_ra.php',
            '<?= TEXT_INDEX_LAST_FIELD ?>'
        );
    };
}

// Attach openDataBox() to the "Last Import" link if it exists in the DOM
if (afficheLastImport) {
    afficheLastImport.onclick = function() {
        openDataBox(
            'include/structure/index/process_index_last_import.php',
            '<?= TEXT_INDEX_LAST_IMPORT ?>'
        );
    };
}

</script>