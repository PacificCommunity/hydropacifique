<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — load the points of one RC for the Edit popup
Receives JSON: { id }
Returns JSON: {
    id, num, datetime_first, datetime_end,
    points: [ {h, q}, ... ]
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

@ini_set('display_errors', '0');
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die(json_encode(['error' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data = json_decode(file_get_contents('php://input'), true);
$id   = isset($data['id']) ? (int)$data['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'bad_id']);
    exit;
}

// Fetch the RC header
$rc = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id, id_station, datetime_first, datetime_end
     FROM " . TABLE_DATA_ETL . " WHERE id=$id"));
if (!$rc) {
    echo json_encode(['error' => 'not_found']);
    exit;
}

// Compute the Ref (num) — same convention used elsewhere (datetime_end DESC)
$num = 0;
$num_query = tep_db_query($sql_link,
    "SELECT id FROM " . TABLE_DATA_ETL
    . " WHERE id_station=" . (int)$rc['id_station']
    . " ORDER BY datetime_end DESC");
$counter = 0;
while ($r = tep_db_fetch_array($num_query)) {
    $counter++;
    if ((int)$r['id'] === $id) { $num = $counter; break; }
}

// Fetch the points (ordered by hauteur for clean line drawing)
$pts_query = tep_db_query($sql_link,
    "SELECT hauteur, debit FROM " . TABLE_DATA_ETL_DATA
    . " WHERE id_etl=$id ORDER BY CAST(hauteur AS DECIMAL(20,5)) ASC");
$points = [];
while ($r = tep_db_fetch_array($pts_query)) {
    $points[] = [
        'h' => (float)$r['hauteur'],
        'q' => (float)$r['debit'],
    ];
}

// Fetch the SG (gauging) points of the RC's period — same filter and
// validation as the new-preview endpoint. These are read-only reference
// points in the Edit popup (no exclusion in this minimal flow).
$dt_first_safe = mysqli_real_escape_string($sql_link, $rc['datetime_first']);
$dt_end_safe   = mysqli_real_escape_string($sql_link, $rc['datetime_end']);

$id_station    = (int) $rc['id_station'];
$jge_query = tep_db_query($sql_link,
    "SELECT DISTINCT jge.id, jge.datetime, jge.depouil_hmoy, jge.depouil_q
     FROM " . TABLE_DATA_JGE . " jge
     WHERE jge.id_station=$id_station
     AND jge.datetime >= '$dt_first_safe'
     AND jge.datetime <= '$dt_end_safe'
     AND jge.depouil_hmoy REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
     AND jge.depouil_hmoy < 9999
     AND jge.depouil_q   REGEXP '^-?[0-9]+(\\.[0-9]+)?$'
     ORDER BY jge.depouil_hmoy ASC");

$jge_points = [];
while ($r = tep_db_fetch_array($jge_query)) {
    $jge_points[] = [
        'h'      => (float) abs($r['depouil_hmoy']),
        'q'      => (float) abs($r['depouil_q']),
        'date'   => date('d-m-Y H:i:s', strtotime($r['datetime'])),
        'id_jge' => (int) $r['id'],
    ];
}

echo json_encode([
    'id'             => $id,
    'id_station'     => (int)$rc['id_station'],
    'num'            => $num,
    'datetime_first' => $rc['datetime_first'],
    'datetime_end'   => $rc['datetime_end'],
    'points'         => $points,
    'jge_points'     => $jge_points,
], JSON_UNESCAPED_UNICODE);
?>