<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — save edited well-log points
Receives JSON: {
    idUser, todayTimeFormatted,
    id_ra,
    points: [ { profondeur, conductivite, temperature, obs }, ... ]
}

Replaces all rows of TABLE_DATA_RA_PIEZO_PROFIL for the given id_ra
(DELETE then INSERT). Conventions:
- profondeur is stored as a POSITIVE depth (the chart flips its sign)
- temperature may be null
- obs is a string, defaults to ''

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
$id_user            = isset($data['idUser'])             ? (int) $data['idUser']         : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted']   : '';
$id_ra              = isset($data['id_ra'])              ? (int) $data['id_ra']          : 0;
$points             = isset($data['points']) && is_array($data['points']) ? $data['points'] : [];

// ---- Defensive validation ----

if ($id_ra <= 0) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Invalid id_ra']);
    exit;
}
if (count($points) < 1) {
    echo json_encode(['valid_process' => false, 'js_text' => 'Need at least 1 point']);
    exit;
}

// Defensive: verify the RA exists and fetch its station for the log
$check = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT id_station FROM " . TABLE_DATA_RA . " WHERE id_ra=" . $id_ra));
if (!$check) {
    echo json_encode(['valid_process' => false, 'js_text' => 'RA not found']);
    exit;
}
$id_station = (int) $check['id_station'];

// ---- Replace all points: DELETE then INSERT ----
tep_db_query($sql_link,
    "DELETE FROM " . TABLE_DATA_RA_PIEZO_PROFIL . " WHERE id_ra=" . $id_ra);

foreach ($points as $p) {
    if (!isset($p['profondeur']) || !isset($p['conductivite'])) continue;
    $profondeur   = (float) $p['profondeur'];
    $conductivite = (float) $p['conductivite'];

    // Temperature: null when not provided / not numeric
    $temp_sql = 'NULL';
    if (isset($p['temperature']) && is_numeric($p['temperature'])) {
        $temp_sql = (float) $p['temperature'];
    }

    // Obs: free text, escape it
    $obs_safe = mysqli_real_escape_string($sql_link, isset($p['obs']) ? (string) $p['obs'] : '');

    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_DATA_RA_PIEZO_PROFIL
        . " (id_ra, profondeur, conductivite, temperature, obs)"
        . " VALUES ($id_ra, '$profondeur', '$conductivite', $temp_sql, '$obs_safe')");
}

// ---- Log the action ----
// Action type 35 mirrors the ETL action codes (33/34). If your platform
// uses a different action_type code for well-log edits, swap it here.
$info_action = "Well log edited - Station: $id_station"
             . " - RA id: $id_ra"
             . " - Points: " . count($points);
$info_safe  = mysqli_real_escape_string($sql_link, $info_action);
$today_safe = mysqli_real_escape_string($sql_link, $todayTimeFormatted);
tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 35, '$info_safe', '$today_safe')");

echo json_encode([
    'valid_process' => true,
    'js_text'       => 'Well log updated',
], JSON_UNESCAPED_UNICODE);
?>