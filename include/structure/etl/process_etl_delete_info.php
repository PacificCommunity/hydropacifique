<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — fetch info for the Delete RC confirmation popup
Receives JSON: { ids: [id1, id2, ...] }
Returns JSON: {
    items: [
        { id, num, datetime_first, datetime_end, nb_points },
        ...
    ]
}
The popup uses this to show the user exactly what is about to be deleted.
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
    or die(json_encode(['items' => [], 'error' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data = json_decode(file_get_contents('php://input'), true);
$ids  = isset($data['ids']) && is_array($data['ids']) ? $data['ids'] : [];

if (empty($ids)) {
    echo json_encode(['items' => []]);
    exit;
}

// Sanitize: keep only positive integers
$safe_ids = [];
foreach ($ids as $id) {
    $n = (int) $id;
    if ($n > 0) { $safe_ids[] = $n; }
}
if (empty($safe_ids)) {
    echo json_encode(['items' => []]);
    exit;
}
$id_list = implode(',', $safe_ids);

// Fetch the ETLs requested
$etl_query = tep_db_query($sql_link,
    "SELECT id, id_station, datetime_first, datetime_end
     FROM " . TABLE_DATA_ETL
     . " WHERE id IN ($id_list)");

$etls = [];
$first_station = 0;
while ($r = tep_db_fetch_array($etl_query)) {
    $etls[(int)$r['id']] = $r;
    if ($first_station === 0) { $first_station = (int)$r['id_station']; }
}

// Build the Ref (num) map for the station (ordered by datetime_end DESC,
// same convention used elsewhere).
$num_map = [];
if ($first_station > 0) {
    $num_query = tep_db_query($sql_link,
        "SELECT id FROM " . TABLE_DATA_ETL
        . " WHERE id_station=$first_station ORDER BY datetime_end DESC");
    $counter = 0;
    while ($r = tep_db_fetch_array($num_query)) {
        $counter++;
        $num_map[(int)$r['id']] = $counter;
    }
}

// Count points per ETL in one query
$points_count = [];
$count_query = tep_db_query($sql_link,
    "SELECT id_etl, COUNT(*) as nb FROM " . TABLE_DATA_ETL_DATA
    . " WHERE id_etl IN ($id_list) GROUP BY id_etl");
while ($r = tep_db_fetch_array($count_query)) {
    $points_count[(int)$r['id_etl']] = (int) $r['nb'];
}

$items = [];
foreach ($safe_ids as $id) {
    if (!isset($etls[$id])) continue;
    $items[] = [
        'id'             => $id,
        'num'            => isset($num_map[$id]) ? $num_map[$id] : 0,
        'datetime_first' => $etls[$id]['datetime_first'],
        'datetime_end'   => $etls[$id]['datetime_end'],
        'nb_points'      => isset($points_count[$id]) ? $points_count[$id] : 0,
    ];
}

echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
?>