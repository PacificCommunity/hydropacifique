<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — data qualification table (data_meta summary)

Companion to process_graph_meta.php (which draws the colored timeline).
For a given station + chronicle type, this returns the list of data_meta
blocks (one row per id_meta) so the client can show them in a sortable
popup table and export them to CSV.

Each row carries: quality code (init + name), color, period (first/last
date of the data points attached to the meta), number of points, and the
observation / comment fields.

Receives JSON:
  idStation, typedataChron, xDateMin, xDateMax (FR dd-mm-yyyy, optional)
Returns JSON:
  {
    station   : "code - name",
    chronique : "init - name",
    rows      : [ { qual_init, qual_nom, color, date_first, date_end,
                    nb_points, obs, obs_user }, ... ]
  }
----------------------------------------
*/

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

$data           = json_decode(file_get_contents('php://input'), true);
$min_x          = $data['xDateMin']       ?? '';
$max_x          = $data['xDateMax']       ?? '';
$station_chron  = (int)($data['idStation']      ?? 0);
$typedata_chron = (int)($data['typedataChron']  ?? 0);

$date_1 = '1950-01-01';
$date_2 = date('Y-m-d');
if (tep_not_null($min_x) && tep_not_null($max_x))
{
    $date_1 = datefr_us($min_x);
    $date_2 = datefr_us($max_x);
}


// -----------------------------------------------
// Station label (code - name)

$station_label = '';
$station_code  = '';
$station_nom   = '';
$st_query = tep_db_query($sql_link,
    "SELECT code_station, nom_station FROM " . TABLE_STATION . "
     WHERE id_station = " . $station_chron);
if ($st = tep_db_fetch_array($st_query))
{
    $station_code  = $st['code_station'];
    $station_nom   = html_entity_decode($st['nom_station'] ?? '');
    $station_label = $station_code . ' - ' . $station_nom;
}


// -----------------------------------------------
// Chronicle label (init - name)

$chron_label = '';
$chron_init  = '';
$tc_query = tep_db_query($sql_link,
    "SELECT init_type_data, nom_type_data FROM " . TABLE_TYPE_DATA . "
     WHERE id_data_type = " . $typedata_chron);
if ($tc = tep_db_fetch_array($tc_query))
{
    $chron_init  = $tc['init_type_data'];
    $chron_label = $chron_init . ' - ' . html_entity_decode($tc['nom_type_data'] ?? '');
}


// -----------------------------------------------
// Quality code lookup — init / name / color

$code_qual_array = [];
$cq_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, couleur_qualite_data
     FROM " . TABLE_DATA_QUALITE . " ORDER BY id_data_qualite");
while ($cq = tep_db_fetch_array($cq_query))
{
    $code_qual_array[$cq['id_data_qualite']] = [
        'init_qualite_data'    => html_entity_decode($cq['init_qualite_data'] ?? ''),
        'nom_qualite_data'     => html_entity_decode($cq['nom_qualite_data']  ?? ''),
        'couleur_qualite_data' => $cq['couleur_qualite_data'] ?? '',
    ];
}


// -----------------------------------------------
// One row per data_meta block (same query shape as process_graph_meta.php)

$rows = [];

$meta_query = tep_db_query($sql_link,
    "SELECT dm.id          AS id_meta,
            dm.id_codequal AS id_codequal,
            dm.obs         AS obs,
            dm.obs_user    AS obs_user,
            MIN(da.dateheure) AS date_first,
            MAX(da.dateheure) AS date_end,
            COUNT(*)          AS nb_points
     FROM " . TABLE_DATA_META . " dm
     JOIN " . TABLE_DATA_ALL  . " da ON da.id_meta = dm.id
     WHERE dm.id_station  = " . $station_chron  . "
     AND   dm.id_typedata = " . $typedata_chron . "
     AND   da.dateheure  <= '" . $date_2 . " 23:59:59'
     AND   da.dateheure  >= '" . $date_1 . " 00:00:00'
     GROUP BY dm.id, dm.id_codequal, dm.obs, dm.obs_user
     ORDER BY date_first ASC");

while ($m = tep_db_fetch_array($meta_query))
{
    $cq        = $code_qual_array[$m['id_codequal']] ?? null;
    $qual_init = $cq ? $cq['init_qualite_data'] : '';
    $qual_nom  = $cq ? $cq['nom_qualite_data']  : '';
    $color     = ($cq && tep_not_null($cq['couleur_qualite_data']))
               ? $cq['couleur_qualite_data'] : '';

    $rows[] = [
        'qual_init'  => $qual_init,
        'qual_nom'   => $qual_nom,
        'color'      => $color,
        'date_first' => date('d/m/Y H:i', strtotime($m['date_first'])),
        'date_end'   => date('d/m/Y H:i', strtotime($m['date_end'])),
        'nb_points'  => (int)$m['nb_points'],
        'obs'        => $m['obs']      ?? '',
        'obs_user'   => $m['obs_user'] ?? '',
    ];
}

echo json_encode([
    'station'      => $station_label,
    'chronique'    => $chron_label,
    'station_code' => $station_code,
    'station_nom'  => $station_nom,
    'chron_init'   => $chron_init,
    'rows'         => $rows,
], JSON_UNESCAPED_UNICODE);
?>