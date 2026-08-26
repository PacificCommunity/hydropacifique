<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — delete a JGE record
Receives JSON: {
    idUser, todayTimeFormatted, territoireId,
    idJge
}

Workflow:
1. Validate the id, fetch the JGE + station info (for the log message)
2. Defensive: verify the JGE belongs to a station of the user's territory
3. Delete the JGE points, then the arms, then the JGE header (cascade)
4. Log the action

Returns JSON: { valid_process, js_text, id_jge }
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
    or die(json_encode(['valid_process' => false, 'js_text' => TEXT_JGE_DEL_ERR_DB]));
mysqli_query($sql_link, 'SET NAMES UTF8');

$data               = json_decode(file_get_contents('php://input'), true);
$id_user            = isset($data['idUser'])             ? (int)$data['idUser']        : 0;
$todayTimeFormatted = isset($data['todayTimeFormatted']) ? $data['todayTimeFormatted'] : '';
$territoire_id      = isset($data['territoireId'])       ? (int)$data['territoireId']  : 0;
$id_jge             = isset($data['idJge'])              ? (int)$data['idJge']         : 0;


// -----------------------------------------------
// Defensive validation

if ($id_jge <= 0)
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_DEL_ERR_INVALID,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Fetch the JGE record + station info (filtered by territory)

$del_jge_query = tep_db_query($sql_link,
    "SELECT DISTINCT jge.id, jge.datetime,
                     s.id_station, s.code_station, s.nom_station
     FROM " . TABLE_DATA_JGE . " jge
     JOIN " . TABLE_STATION  . " s ON jge.id_station = s.id_station
     JOIN " . TABLE_REGION   . " r ON s.id_region    = r.id_region
     WHERE jge.id = $id_jge
       AND r.id_territoire = $territoire_id");

$del_jge = tep_db_fetch_array($del_jge_query);

if (!isset($del_jge['id']))
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_DEL_ERR_NOT_FOUND,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Cascade delete: points → arms → header
// We collect the arm ids first so we can delete their points reliably
// (fixes a bug in the legacy suppr_jge.php which deleted JGE_PTS by id
//  instead of by id_bras)

$bras_ids = [];
$bras_query = tep_db_query($sql_link,
    "SELECT id FROM " . TABLE_DATA_JGE_BRAS . " WHERE id_jge = $id_jge");
while ($b = tep_db_fetch_array($bras_query))
{
    $bras_ids[] = (int) $b['id'];
}

// Delete points belonging to each arm
if (!empty($bras_ids))
{
    $bras_list = implode(',', $bras_ids);
    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_PTS  . " WHERE id_bras IN ($bras_list)");
    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE_BRAS . " WHERE id_jge  = $id_jge");
}

// Delete the JGE header
$del_ok = tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_JGE . " WHERE id = $id_jge");

if (!$del_ok)
{
    echo json_encode([
        'valid_process' => false,
        'js_text'       => TEXT_JGE_DEL_ERR_FAILED,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// -----------------------------------------------
// Log the action — type 12 = JGE deletion

$info_station = $del_jge['code_station'] . ' - ' . $del_jge['nom_station'];
$info_action  = TEXT_JGE_DEL_LOG . ' - ' . $info_station . ' - ' . $del_jge['datetime'];
$info_safe    = mysqli_real_escape_string($sql_link, htmlaccent($info_action));
$today_safe   = mysqli_real_escape_string($sql_link, $todayTimeFormatted);

tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)
     VALUES ($id_user, 12, '$info_safe', '$today_safe')");


// -----------------------------------------------
// Return result as JSON

$js_text = sprintf(TEXT_JGE_DEL_SUCCESS, dateus_fr($del_jge['datetime']), $info_station);

echo json_encode([
    'valid_process' => true,
    'js_text'       => $js_text,
    'id_jge'        => $id_jge,
], JSON_UNESCAPED_UNICODE);
?>