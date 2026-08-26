<?php
/*
----------------------------------------
Copyright (c) 2025 - Vai-Natura
----------------------------------------
AJAX endpoint — delete a single gauging arm (bras)
Receives JSON: {
    idUser, todayTimeFormatted, territoireId,
    idJge, idBras
}

Workflow:
1. Validate the arm id, fetch the arm + parent JGE + station info (for the log message)
2. Defensive: verify the arm belongs to a station of the user's territory
3. Delete the arm points (by id_bras), then the arm record
4. Decrement the arm count (nb_bras) on the parent gauging record (floor at 0)
5. Log the action (type 14 = arm deletion)

Returns JSON: { valid_process, js_text, id_jge, id_bras }
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
    or die(json_encode(['valid_process' => false, 'js_text' => TEXT_JGE_BRAS_DEL_ERR_DB]));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data               = json_decode(file_get_contents('php://input'), true);
$id_user            = isset($data['idUser'])             ? (int)$data['idUser']        : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted'] : '';
$territoire_id      = isset($data['territoireId'])       ? (int)$data['territoireId']  : 0;
$id_jge             = isset($data['idJge'])              ? (int)$data['idJge']         : 0;
$id_bras            = isset($data['idBras'])             ? (int)$data['idBras']        : 0;


// -----------------------------------------------
// Defensive validation

if ($id_bras <= 0)
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_BRAS_DEL_ERR_INVALID,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Fetch the arm + parent JGE + station info (filtered by territory)
// The join chain BRAS -> JGE -> STATION -> REGION guarantees the arm
// belongs to a station within the user's territory before deleting.

$del_bras_query = tep_db_query($sql_link,
    "SELECT DISTINCT b.id, b.id_jge,
                     jge.datetime,
                     s.id_station, s.code_station, s.nom_station
     FROM " . TABLE_DATA_JGE_BRAS . " b
     JOIN " . TABLE_DATA_JGE       . " jge ON b.id_jge       = jge.id
     JOIN " . TABLE_STATION        . " s   ON jge.id_station = s.id_station
     JOIN " . TABLE_REGION         . " r   ON s.id_region    = r.id_region
     WHERE b.id = $id_bras
       AND r.id_territoire = $territoire_id");

$del_bras = tep_db_fetch_array($del_bras_query);

if (!isset($del_bras['id']))
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_BRAS_DEL_ERR_NOT_FOUND,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Use the parent JGE id resolved from the DB (authoritative, not the client value)
$id_jge = (int) $del_bras['id_jge'];


// -----------------------------------------------
// Delete points then the arm record
// NOTE: points must be matched on id_bras (the arm they belong to),
// not on id — this was the bug in the legacy suppr_jge_bras.php.

tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_PTS  . " WHERE id_bras = $id_bras");

$del_ok = tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_BRAS . " WHERE id = $id_bras");

if (!$del_ok)
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_BRAS_DEL_ERR_FAILED,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Decrement arm count on the parent gauging record (floor at 0)

tep_db_query($sql_link,
    "UPDATE " . TABLE_DATA_JGE . "
     SET nb_bras = CASE
                       WHEN nb_bras > 0 THEN nb_bras - 1
                       ELSE 0
                   END
     WHERE id = $id_jge");


// -----------------------------------------------
// Log the action — type 14 = arm (bras) deletion

$info_station = $del_bras['code_station'] . ' - ' . $del_bras['nom_station'];
$info_action  = TEXT_JGE_BRAS_DEL_LOG . ' - ' . $info_station . ' - ' . $del_bras['datetime'];
$info_safe    = mysqli_real_escape_string($sql_link, htmlaccent($info_action));
$today_safe   = mysqli_real_escape_string($sql_link, $todayTimeFormatted);

tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
     VALUES ($id_user, 14, '$info_safe', '$today_safe')");


// -----------------------------------------------
// Return result as JSON

echo json_encode([
    'valid_process' => true,
    'js_text'       => TEXT_JGE_BRAS_DEL_SUCCESS,
    'id_jge'        => $id_jge,
    'id_bras'       => $id_bras,
], JSON_UNESCAPED_UNICODE);
?>