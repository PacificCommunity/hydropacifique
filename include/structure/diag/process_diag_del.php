<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — delete the points of a well log
Receives JSON: { idUser, todayTimeFormatted, id_ra }

DELETEs every row of TABLE_DATA_RA_PIEZO_PROFIL for the given id_ra.
The RA itself (TABLE_DATA_RA) is left untouched — it may carry other
kinds of data attached to it. Without piezo-profil rows the well log
simply stops appearing in process_diag_tab.php (which inner-joins on
TABLE_DATA_RA_PIEZO_PROFIL).

Returns JSON: { valid_process, js_text }
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
$id_user            = isset($data['idUser'])             ? (int) $data['idUser']       : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted'] : '';
$id_ra              = isset($data['id_ra'])              ? (int) $data['id_ra']        : 0;

if ($id_ra <= 0) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Invalid id_ra']);
    exit;
}

// Defensive: verify the RA exists and capture its station for the log.
$check = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id_station FROM " . TABLE_DATA_RA . " WHERE id_ra=" . $id_ra));
if (!$check) {
    echo json_encode(['valid_process' => false, 'js_text' => 'RA not found']);
    exit;
}
$id_station = (int) $check['id_station'];

// Count the points before deletion — useful in the action log.
$nb_row = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT COUNT(*) AS n FROM " . TABLE_DATA_RA_PIEZO_PROFIL . " WHERE id_ra=" . $id_ra));
$nb_points = $nb_row ? (int) $nb_row['n'] : 0;

// ---- DELETE the profile points ----
$ok = tep_db_query($sql_link,
    "DELETE FROM " . TABLE_DATA_RA_PIEZO_PROFIL . " WHERE id_ra=" . $id_ra);

if (!$ok) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Delete failed']);
    exit;
}

// ---- Log the action ----
// Action type 36 = well-log delete (35 = well-log edit in process_diag_edit_save).
// Adjust the numeric code to whatever your platform reserves for this action.
$info_action = "Well log deleted - Station: $id_station"
             . " - RA id: $id_ra"
             . " - Points removed: $nb_points";
$info_safe  = mysqli_real_escape_string($sql_link, $info_action);
$today_safe = mysqli_real_escape_string($sql_link, $todayTimeFormatted);
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 36, '$info_safe', '$today_safe')");

echo json_encode([
    'valid_process' => true,
    'js_text'       => 'Well log deleted',
], JSON_UNESCAPED_UNICODE);
?>