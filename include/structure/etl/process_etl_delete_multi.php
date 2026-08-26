<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — delete one or more rating curves
Receives JSON: {
    idUser, todayTimeFormatted,
    idStation,
    ids: [id1, id2, ...]
}
Deletes the curves and their points, logs the action.
Returns JSON: { valid_process, js_text, nb_deleted }
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
    or die(json_encode(['valid_process' => false, 'js_text' => 'db_connect']));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data               = json_decode(file_get_contents('php://input'), true);
$id_user            = isset($data['idUser'])             ? (int)$data['idUser']        : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted'] : '';
$id_station         = isset($data['idStation'])          ? (int)$data['idStation']     : 0;
$ids                = isset($data['ids']) && is_array($data['ids']) ? $data['ids']     : [];

// Sanitize ids
$safe_ids = [];
foreach ($ids as $id) {
    $n = (int) $id;
    if ($n > 0) { $safe_ids[] = $n; }
}
if (empty($safe_ids)) {
    echo json_encode(['valid_process' => false, 'js_text' => 'No RC specified']);
    exit;
}
$id_list = implode(',', $safe_ids);

// Verify that all RCs belong to the expected station (defensive — prevents
// a malicious or buggy client from deleting RCs of another station)
$check_query = tep_db_query($sql_link,
    "SELECT COUNT(*) as nb FROM " . TABLE_DATA_ETL
    . " WHERE id IN ($id_list) AND id_station=$id_station");
$check = tep_db_fetch_array($check_query);
if ((int)$check['nb'] !== count($safe_ids)) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Station mismatch']);
    exit;
}

// Delete the points first, then the headers
tep_db_query($sql_link,
    "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl IN ($id_list)");
$del_ok = tep_db_query($sql_link,
    "DELETE FROM " . TABLE_DATA_ETL      . " WHERE id     IN ($id_list)");

if (!$del_ok) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Delete failed']);
    exit;
}

$nb_deleted = count($safe_ids);

// Log the action — type 33 = "Rating curve - RC" (any change to a curve)
$info_action = "RC deleted - Station: $id_station - IDs: " . implode(',', $safe_ids)
             . " - Count: $nb_deleted";
$info_safe   = mysqli_real_escape_string($sql_link, $info_action);
$today_safe  = mysqli_real_escape_string($sql_link, $todayTimeFormatted);
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 33, '$info_safe', '$today_safe')");

echo json_encode([
    'valid_process' => true,
    'js_text'       => 'Deleted',
    'nb_deleted'    => $nb_deleted,
], JSON_UNESCAPED_UNICODE);
?>