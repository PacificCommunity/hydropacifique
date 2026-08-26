<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — data gaps (lacunes) table

For a given station + chronicle type, scans the chronological points in
date order and detects gaps: runs of "marker" values (|v| in
9999 / 99999 / 8888 / 88888) that stand for missing data. For each gap it
reports:
  - date_first : date of the first marker point (gap start)
  - date_end   : date of the last marker point (gap end)
  - nb_points  : number of marker points in the gap
  - quality code + obs / obs_user of the data_meta the START point belongs
    to (every data_all row carries an id_meta, marker rows included).

Receives JSON: idStation, typedataChron, xDateMin, xDateMax (FR, optional)
Returns JSON:
  { station, chronique, station_code, station_nom, chron_init,
    rows:[ {qual_init,qual_nom,color,date_first,date_end,nb_points,obs,obs_user} ] }
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

// Gap marker values (same set as process_graph_multi.php)
$markers = [9999, 99999, 8888, 88888];


// -----------------------------------------------
// Station + chronicle labels

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
// Quality code lookup

$code_qual_array = [];
$cq_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, couleur_qualite_data
     FROM " . TABLE_DATA_QUALITE . " ORDER BY id_data_qualite");
while ($cq = tep_db_fetch_array($cq_query))
{
    $code_qual_array[$cq['id_data_qualite']] = [
        'init'    => html_entity_decode($cq['init_qualite_data'] ?? ''),
        'nom'     => html_entity_decode($cq['nom_qualite_data']  ?? ''),
        'couleur' => $cq['couleur_qualite_data'] ?? '',
    ];
}


// -----------------------------------------------
// data_meta lookup (id_codequal / obs / obs_user) keyed by id_meta,
// so a gap's START point can be linked back to its meta block.

$meta_info = [];
$mi_query = tep_db_query($sql_link,
    "SELECT id, id_codequal, obs, obs_user
     FROM " . TABLE_DATA_META . "
     WHERE id_station  = " . $station_chron  . "
     AND   id_typedata = " . $typedata_chron);
while ($mi = tep_db_fetch_array($mi_query))
{
    $meta_info[$mi['id']] = [
        'id_codequal' => $mi['id_codequal'],
        'obs'         => $mi['obs']      ?? '',
        'obs_user'    => $mi['obs_user'] ?? '',
    ];
}


// -----------------------------------------------
// Scan points in date order; detect marker runs as gaps.

$rows = [];

$pts_query = tep_db_query($sql_link,
    "SELECT da.dateheure, da.valeur, da.id_meta
     FROM " . TABLE_DATA_ALL  . " da
     JOIN " . TABLE_DATA_META . " dm ON da.id_meta = dm.id
     WHERE dm.id_station  = " . $station_chron  . "
     AND   dm.id_typedata = " . $typedata_chron . "
     AND   da.dateheure  >= '" . $date_1 . " 00:00:00'
     AND   da.dateheure  <= '" . $date_2 . " 23:59:59'
     ORDER BY da.dateheure ASC");

$gap_open    = false;
$gap_first   = '';
$gap_last    = '';
$gap_meta    = null;
$gap_count   = 0;

function gaps_push(&$rows, $code_qual_array, $meta_info, $gap_meta, $gap_first, $gap_last, $gap_count)
{
    // Resolve the quality code from the START point's meta.
    $qinit = ''; $qnom = ''; $color = ''; $obs = ''; $obs_user = '';
    if ($gap_meta !== null && isset($meta_info[$gap_meta]))
    {
        $mi  = $meta_info[$gap_meta];
        $obs      = $mi['obs'];
        $obs_user = $mi['obs_user'];
        $cq  = $code_qual_array[$mi['id_codequal']] ?? null;
        if ($cq)
        {
            $qinit = $cq['init'];
            $qnom  = $cq['nom'];
            $color = tep_not_null($cq['couleur']) ? $cq['couleur'] : '';
        }
    }

    $rows[] = [
        'qual_init'  => $qinit,
        'qual_nom'   => $qnom,
        'color'      => $color,
        'date_first' => date('d/m/Y H:i', strtotime($gap_first)),
        'date_end'   => date('d/m/Y H:i', strtotime($gap_last)),
        'nb_points'  => $gap_count,
        'obs'        => $obs,
        'obs_user'   => $obs_user,
    ];
}

while ($p = tep_db_fetch_array($pts_query))
{
    $is_marker = in_array(abs($p['valeur']), $markers);

    if ($is_marker)
    {
        if (!$gap_open)
        {
            // Open a new gap on the first marker point.
            $gap_open  = true;
            $gap_first = $p['dateheure'];
            $gap_meta  = $p['id_meta'];
            $gap_count = 0;
        }
        $gap_last  = $p['dateheure'];
        $gap_count++;
    }
    else
    {
        if ($gap_open)
        {
            // A valid value closes the current gap.
            gaps_push($rows, $code_qual_array, $meta_info, $gap_meta, $gap_first, $gap_last, $gap_count);
            $gap_open = false;
            $gap_meta = null;
        }
    }
}

// Trailing gap still open at the end of the series.
if ($gap_open)
{
    gaps_push($rows, $code_qual_array, $meta_info, $gap_meta, $gap_first, $gap_last, $gap_count);
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