<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — propeller deletion
Called by delete_eqhelice() in form_eq_jge_helices.php.
Receives id_helice via JSON POST.
A propeller can only be deleted if it has never been used in a gauging
arm record (TABLE_DATA_JGE_BRAS.id_helice).
Returns JSON:
  del_helice   : bool   — true on successful deletion
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
$id_helice = $dataInfo['id_helice'];

$del_helice   = true;
$message_info = '';


// -----------------------------------------------
// Fetch the propeller record (needed for the feedback message)

$helice_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, num FROM " . TABLE_HELICE . "
     WHERE id = " . $id_helice);
$helice_tab = tep_db_fetch_array($helice_query);


if (isset($helice_tab))
{
    // Guard: count gauging arm records that reference this propeller
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_helice_jge
         FROM " . TABLE_DATA_JGE_BRAS . "
         WHERE id_helice = " . $id_helice);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_helice_jge'] < 1)
    {
        // Not referenced by any gauging record — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_HELICE . " WHERE id = " . $id_helice);
        $message_info = "'" . $helice_tab['num'] . "' " . TEXT_EJ_HEL_DEL_OK;
    }
    else
    {
        // Still referenced by at least one gauging record — deletion blocked
        $del_helice    = false;
        $message_info  = "'" . $helice_tab['num'] . "' " . TEXT_EJ_HEL_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_EJ_HEL_DEL_ERR_JGE;
    }
}
else
{
    // Propeller not found — should not happen in normal use
    $del_helice   = false;
    $message_info = TEXT_EJ_HEL_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_helice'   => $del_helice,
    'message_info' => $message_info,
]);
?>
