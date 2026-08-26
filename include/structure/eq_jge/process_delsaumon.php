<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — weight (saumon) deletion
Called by delete_eqsaumon() in form_eq_jge_saumons.php.
Receives id_saumon via JSON POST.
A weight can only be deleted if it has never been used in a gauging
arm record (TABLE_DATA_JGE_BRAS.id_saumon).
Returns JSON:
  del_saumon   : bool   — true on successful deletion
  message_info : string — feedback message for the user
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Decode JSON payload sent by the AJAX call
$dataInfo  = json_decode(file_get_contents('php://input'), true);
$id_saumon = $dataInfo['id_saumon'];

$del_saumon   = true;
$message_info = '';


// -----------------------------------------------
// Fetch the weight record (needed for the feedback message)

$saumon_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num FROM " . TABLE_SAUMON . "
     WHERE id = " . $id_saumon);
$saumon_tab = tep_db_fetch_array($saumon_query);


if (isset($saumon_tab))
{
    // Guard: count gauging arm records that reference this weight
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_saumon_jge
         FROM " . TABLE_DATA_JGE_BRAS . "
         WHERE id_saumon = " . $id_saumon);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_saumon_jge'] < 1)
    {
        // Not referenced by any gauging record — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_SAUMON . " WHERE id = " . $id_saumon);
        $message_info = "'" . $saumon_tab['num'] . "' " . TEXT_EJ_SAU_DEL_OK;
    }
    else
    {
        // Still referenced by at least one gauging record — deletion blocked
        $del_saumon    = false;
        $message_info  = "'" . $saumon_tab['num'] . "' " . TEXT_EJ_SAU_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_EJ_SAU_DEL_ERR_JGE;
    }
}
else
{
    // Weight not found — should not happen in normal use
    $del_saumon   = false;
    $message_info = TEXT_EJ_SAU_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_saumon'   => $del_saumon,
    'message_info' => $message_info,
]);
?>
