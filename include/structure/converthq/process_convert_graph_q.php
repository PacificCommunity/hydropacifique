<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — pending converted discharge series ONLY.

Lightweight companion to process_convert_graph.php. After a H→Q conversion
we only need to refresh the middle (discharge) subplot's "pending" trace,
without re-rendering the whole 3-subplot chart (which would reset the user's
zoom and move the stage / timeline subplots). This endpoint loads just the
pending conversion preview series from TABLE_DATA_ALL_CORRECTION and returns
its x / y / customdata so the client can Plotly.restyle the existing trace.

customdata is built with the SAME logic as process_convert_graph.php
(build_customdata) so the tooltip stays identical.

Receives JSON: idStation, typedataChronQ.
Returns JSON: { x, y, customdata, nb }
----------------------------------------
*/

// Never let PHP notices/warnings leak into the response body — they would
// corrupt the JSON and make the client's JSON.parse fail silently.
@ini_set('display_errors', '0');
error_reporting(0);

require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

define('VALUE_MIN_THRESHOLD', -8888);
define('VALUE_MAX_THRESHOLD', 99999);


// -----------------------------------------------
// Axis lookup (axe label / unit / decimals), keyed by axe id

$data_type_axe_array = [];
$q = tep_db_query($sql_link, "SELECT DISTINCT id, axe, unite, nb_round FROM " . TABLE_DATA_TYPE_AXE);
while ($r = tep_db_fetch_array($q))
{
    $data_type_axe_array[$r['id']] = ['axe' => $r['axe'], 'unite' => $r['unite'], 'nb_round' => $r['nb_round']];
}


// -----------------------------------------------
// Time-series type lookup -> resolves axe decimals for the discharge type

$type_chron_array = [];
$q = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, axe_data FROM " . TABLE_TYPE_DATA);
while ($tc = tep_db_fetch_array($q))
{
    $id_axe = $tc['axe_data'];
    $type_chron_array[$tc['id_data_type']] = [
        'axe_nb_round' => $data_type_axe_array[$id_axe]['nb_round'] ?? 0,
    ];
}


// -----------------------------------------------
// Quality code lookup — same table/columns as the main builder

$code_qual_array = [];
$q = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data FROM " . TABLE_DATA_QUALITE);
while ($cq = tep_db_fetch_array($q))
{
    $code_qual_array[$cq['id_data_qualite']] = [
        'init_qualite_data' => $cq['init_qualite_data'],
        'nom_qualite_data'  => $cq['nom_qualite_data'],
    ];
}


// -----------------------------------------------
// customdata builder — identical to process_convert_graph.php::build_customdata
// [0] date "DD-MM-YYYY HH:MM:SS", [1] rounded value, [2] quality-code field

function build_customdata($dateheure, $valeur, $nb_dec, $id_codequal, $code_qual_array)
{
    $date_formatee = substr($dateheure, 8, 2) . '-'
                   . substr($dateheure, 5, 2) . '-'
                   . substr($dateheure, 0, 4) . ' '
                   . substr($dateheure, 11, 8);

    $qual_init = isset($code_qual_array[$id_codequal])
        ? $code_qual_array[$id_codequal]['init_qualite_data']
        : '';
    $field_qual = ($qual_init !== '' && $qual_init !== null)
        ? '<br><b>' . TEXT_GRAPH_HOVER_QUALCODE . '</b> : ' . $qual_init
        : '';

    return [
        $date_formatee,
        round((float)$valeur, (int)$nb_dec),
        $field_qual,
    ];
}


// -----------------------------------------------
// Inputs

$data             = json_decode(file_get_contents('php://input'), true);
$station_chron    = (int)($data['idStation']      ?? 0);
$typedata_chron_q = (int)($data['typedataChronQ'] ?? 0);

$x_q_n = []; $y_q_n = []; $cd_q_n = [];
$nb_data_q_n = 0;

// Official discharge series (validated data in TABLE_DATA_ALL) — used to
// refresh the main discharge trace after a save.
$x_q = []; $y_q = []; $cd_q = [];
$nb_data_q = 0;

if ($station_chron > 0 && $typedata_chron_q > 0)
{
    $nb_round_q = (int)($type_chron_array[$typedata_chron_q]['axe_nb_round'] ?? 0);

    // ---- Official series (validated) ----
    $chron_q_query = tep_db_query($sql_link,
        "SELECT da.dateheure, da.valeur, dm.id_codequal
         FROM " . TABLE_DATA_ALL . " da
         JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata=" . $typedata_chron_q . "
         AND dm.id_station=" . $station_chron . "
         ORDER BY da.dateheure ASC");

    while ($row = tep_db_fetch_array($chron_q_query))
    {
        $valeur    = $row['valeur'];
        $dateheure = $row['dateheure'];
        if ($valeur > VALUE_MIN_THRESHOLD && $valeur < VALUE_MAX_THRESHOLD)
        {
            $x_q[]  = $dateheure;
            $y_q[]  = $valeur;
            $cd_q[] = build_customdata($dateheure, $valeur, $nb_round_q, $row['id_codequal'], $code_qual_array);
            $nb_data_q++;
        }
        else
        {
            $x_q[]  = $dateheure;
            $y_q[]  = null;
            $cd_q[] = ['', '', ''];
        }
    }

    // ---- Pending converted series (proposal) ----
    $chron_q_n_query = tep_db_query($sql_link,
        "SELECT da.dateheure, da.valeur, dm.id_codequal
         FROM " . TABLE_DATA_ALL_CORRECTION . " da
         JOIN " . TABLE_DATA_META_CORRECTION . " dm ON da.id_meta = dm.id
         WHERE dm.id_typedata=" . $typedata_chron_q . "
         AND dm.id_station=" . $station_chron . "
         AND dm.source='Conversion'
         ORDER BY da.dateheure ASC");

    while ($row = tep_db_fetch_array($chron_q_n_query))
    {
        $valeur    = $row['valeur'];
        $dateheure = $row['dateheure'];

        if ($valeur > VALUE_MIN_THRESHOLD && $valeur < VALUE_MAX_THRESHOLD)
        {
            $x_q_n[]  = $dateheure;
            $y_q_n[]  = $valeur;
            $cd_q_n[] = build_customdata($dateheure, $valeur, $nb_round_q,
                                         $row['id_codequal'], $code_qual_array);
            $nb_data_q_n++;
        }
        else
        {
            // Gap sentinel -> break the line (null), like the full builder.
            $x_q_n[]  = $dateheure;
            $y_q_n[]  = null;
            $cd_q_n[] = ['', '', ''];
        }
    }
}

echo json_encode([
    // Pending proposal (legendgroup tdc_pending)
    'x'          => $x_q_n,
    'y'          => $y_q_n,
    'customdata' => $cd_q_n,
    'nb'         => $nb_data_q_n,
    // Official validated series (legendgroup tdc_q)
    'x_q'          => $x_q,
    'y_q'          => $y_q,
    'customdata_q' => $cd_q,
    'nb_q'         => $nb_data_q,
], JSON_UNESCAPED_UNICODE);
?>