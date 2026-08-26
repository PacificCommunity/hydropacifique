<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export - Statistics panel builder
- Receives JSON data from an AJAX request
- Queries station, equipment type, and chronological data
- Returns HTML fragments (title, menu, general stats) as JSON
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Ensure proper UTF-8 encoding for accented characters
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Parse incoming JSON payload from AJAX request

$jsonDataGraph = file_get_contents('php://input');
$dataGraph     = json_decode($jsonDataGraph, true);

// Extract parameters from decoded JSON
$territoire_id = $dataGraph['territoireId'];
$id_statut     = $dataGraph['id_statut'];
$cle_station   = $dataGraph['cle_station'];
$type_station  = $dataGraph['type_station'];
$id_typedata   = $dataGraph['id_typedata'];
$min_x         = $dataGraph['min_x'];
$max_x         = $dataGraph['max_x'];


// -----------------------------------------------
// Load translation strings for the active language

require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse date range and compute period duration

$date_min = DateTime::createFromFormat('d-m-Y', $min_x);
$date_max = DateTime::createFromFormat('d-m-Y', $max_x);

$format_min_x = $date_min->format('Y-m-d');
$format_max_x = $date_max->format('Y-m-d');

// Calculate the interval between the two dates
$interval_periode = $date_min->diff($date_max);

// Build a human-readable duration string based on the interval magnitude
if ($interval_periode->y > 0) {
    // More than one year: display years (+ months if applicable)
    $duree_periode = $interval_periode->y . ' ' .
        ($interval_periode->y > 1 ? TEXT_STATS_DURATION_YEARS : TEXT_STATS_DURATION_YEAR);
    if ($interval_periode->m > 0) {
        $duree_periode .= ' ' . TEXT_STATS_DURATION_AND . ' ' .
            $interval_periode->m . ' ' .
            ($interval_periode->m > 1 ? TEXT_STATS_DURATION_MONTHS : TEXT_STATS_DURATION_MONTH);
    }
} elseif ($interval_periode->m > 0) {
    // Less than one year but more than one month: display months (+ days if applicable)
    $duree_periode = $interval_periode->m . ' ' .
        ($interval_periode->m > 1 ? TEXT_STATS_DURATION_MONTHS : TEXT_STATS_DURATION_MONTH);
    if ($interval_periode->d > 0) {
        $duree_periode .= ' ' . TEXT_STATS_DURATION_AND . ' ' .
            $interval_periode->d . ' ' .
            ($interval_periode->d > 1 ? TEXT_STATS_DURATION_DAYS : TEXT_STATS_DURATION_DAY);
    }
} else {
    // Less than one month: display days only
    $duree_periode = $interval_periode->d . ' ' .
        ($interval_periode->d > 1 ? TEXT_STATS_DURATION_DAYS : TEXT_STATS_DURATION_DAY);
}


// -----------------------------------------------
// Query: Station information

$sql_station = "SELECT DISTINCT s.id_station, s.nom_station, s.nom_court, s.code_station
                FROM " . TABLE_STATION . " s
                WHERE s.id_station = " . $cle_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);

$id_station   = $station_tab['id_station'];
$nom_station  = $station_tab['nom_station'];
$code_station = $station_tab['code_station'];


// -----------------------------------------------
// Query: Equipment type (Rain, Flow, etc.)

$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                       type_color_border, type_color_background, type_graph
                FROM " . TABLE_EQ_TYPE . "
                WHERE active_eq_type = 1
                  AND id_eq_type = " . $type_station . "
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_tab   = tep_db_fetch_array($eq_type_query);

$id_eq_type           = $eq_type_tab['id_eq_type'];
$nom_eq_type          = $eq_type_tab['nom_eq_type'];
$unite_eq_type        = $eq_type_tab['unite_eq_type'];
$valeur_data_type     = $eq_type_tab['valeur_data_type'];
$type_color_border    = $eq_type_tab['type_color_border'];
$type_color_background = $eq_type_tab['type_color_background'];
$type_graph           = $eq_type_tab['type_graph'];


// -----------------------------------------------
// Query: Chronological data type (CI, PI, CIE, etc.)

$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                          axe_data, unite, time_scale,
                          to_periode, id_chon_periode, traitement, type_graph
                   FROM " . TABLE_TYPE_DATA . "
                   WHERE id_data_type = " . $id_typedata . "
                   ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_tab   = tep_db_fetch_array($type_chron_query);

$init_type_data  = $type_chron_tab['init_type_data'];
$nom_type_data   = $type_chron_tab['nom_type_data'];
$id_eq_type_data = $type_chron_tab['id_eq_type_data'];
$axe_data        = isset($type_chron_tab['axe_data']) ? $type_chron_tab['axe_data'] : '';
$unite           = $type_chron_tab['unite'];
$time_scale      = $type_chron_tab['time_scale'];
$to_periode      = $type_chron_tab['to_periode'];
$id_chon_periode = $type_chron_tab['id_chon_periode'];
$traitement      = $type_chron_tab['traitement'];
$typegraph       = $type_chron_tab['type_graph'];


// -----------------------------------------------
// Query: Axis definition for the data type

$sql_data_type_axe = "SELECT DISTINCT id, axe, unite
                       FROM " . TABLE_DATA_TYPE_AXE . "
                       WHERE id = " . $axe_data;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_tab   = tep_db_fetch_array($data_type_axe_query);

$axe        = isset($data_type_axe_tab['axe'])   ? $data_type_axe_tab['axe']   : '';
$axe_unite = isset($data_type_axe_tab['unite'])  ? $data_type_axe_tab['unite'] : '';


// -----------------------------------------------
// Build HTML fragment: Statistics panel title

$html_stats_title =
    $code_station . " - " . $nom_station . "  &middot;  " . $init_type_data . " - " . $nom_type_data;


// -----------------------------------------------
// Build HTML fragment: Navigation menu (tab buttons)

$html_stats_menu = "
    <button id='global' class='bstats active' onClick=\"statsChron('global')\">
        " . TEXT_STATS_BTN_GENERAL . "
    </button>

    <button id='byyear' class='bstats' onClick=\"statsChron('byyear')\">
        " . TEXT_STATS_BTN_BYYEAR . "
    </button>

    <button id='bymonth' class='bstats' onClick=\"statsChron('bymonth')\">
        " . TEXT_STATS_BTN_BYMONTH . "
    </button>

    <button id='bydays' class='bstats' onClick=\"statsChron('bydays')\">
        " . TEXT_STATS_BTN_BYDAYS . "
    </button>
";

// Conditionally add the Low-flow button:
// - Only for the French Polynesia instance (INIT_T == 'PF')
// - Only when data type is hydrometry (id_eq_type_data == 11) at daily time scale (time_scale == 1)
if (INIT_T == 'PF') {
    if ($id_eq_type_data == 11 && $time_scale == 1) {
        $html_stats_menu .= "
            <button id='lowflow' class='bstats-adv' onClick=\"statsChron('lowflow')\">
                " . TEXT_STATS_BTN_LOWFLOW . "
            </button>
        ";
    }
}


// -----------------------------------------------
// Build HTML fragment: General statistics summary block

$html_stats_general = "
    <div class='stats-cards'>
        <div class='stats-card'>
            <div class='stats-card-label'>" . TEXT_STATS_PERIOD . "</div>
            <div class='stats-card-value'>" . $min_x . " &rarr; " . $max_x . "</div>
        </div>
        <div class='stats-card'>
            <div class='stats-card-label'>" . TEXT_STATS_DURATION . "</div>
            <div class='stats-card-value'>" . $duree_periode . "</div>
        </div>
        <div class='stats-card'>
            <div class='stats-card-label'>" . TEXT_STATS_DATA . "</div>
            <div class='stats-card-value'>" . $axe . " (" . $axe_unite . ")</div>
        </div>
        <div class='stats-card'>
            <div class='stats-card-label'>" . TEXT_STATS_CHRONIQUE . "</div>
            <div class='stats-card-value'>" . $init_type_data . " - " . $nom_type_data . "</div>
        </div>
    </div>
";


// -----------------------------------------------
// Encode and return the HTML fragments as a JSON response

$responseData = [
    'html_stats_title'   => $html_stats_title,
    'html_stats_menu'    => $html_stats_menu,
    'html_stats_general' => $html_stats_general,
    'station_code'       => $code_station,
    'station_nom'        => $nom_station,
    'chron_init'         => $init_type_data,
];

echo json_encode($responseData);
?>