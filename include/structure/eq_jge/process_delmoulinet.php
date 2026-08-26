<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — current meter deletion
Called by delete_eqmoulinet() in form_eq_jge_moulinets.php.
Receives id_moulinet via JSON POST.
A current meter can only be deleted if it has never been used in a gauging
arm record (TABLE_DATA_JGE_BRAS.id_moulinet).
Returns JSON:
  del_moulinet : bool   — true on successful deletion
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
$dataInfo    = json_decode(file_get_contents('php://input'), true);
$id_moulinet = $dataInfo['id_moulinet'];

$del_moulinet = true;
$message_info = '';


// -----------------------------------------------
// Fetch the current meter record (needed for the feedback message)

$moulinet_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num FROM " . TABLE_MOULINET . "
     WHERE id = " . $id_moulinet);
$moulinet_tab = tep_db_fetch_array($moulinet_query);


if (isset($moulinet_tab))
{
    // Guard: count gauging arm records that reference this current meter
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_moulinet_jge
         FROM " . TABLE_DATA_JGE_BRAS . "
         WHERE id_moulinet = " . $id_moulinet);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_moulinet_jge'] < 1)
    {
        // Not referenced by any gauging record — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_MOULINET . " WHERE id = " . $id_moulinet);
        $message_info = "'" . $moulinet_tab['num'] . "' " . TEXT_EJ_MOUL_DEL_OK;
    }
    else
    {
        // Still referenced by at least one gauging record — deletion blocked
        $del_moulinet  = false;
        $message_info  = "'" . $moulinet_tab['num'] . "' " . TEXT_EJ_MOUL_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_EJ_MOUL_DEL_ERR_JGE;
    }
}
else
{
    // Current meter not found — should not happen in normal use
    $del_moulinet = false;
    $message_info = TEXT_EJ_MOUL_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_moulinet' => $del_moulinet,
    'message_info' => $message_info,
]);
?>
