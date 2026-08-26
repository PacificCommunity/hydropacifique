<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — graph axis deletion
Called by delete_axe() in form_typedata_axe.php.
Receives id_axe via JSON POST.
An axis can only be deleted if no time-series type in TABLE_TYPE_DATA
references it via axe_data.
Returns JSON:
  del_axe      : bool   — true on successful deletion
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
$dataInfo = json_decode(file_get_contents('php://input'), true);
$id_axe   = $dataInfo['id_axe'];

$del_axe      = true;
$message_info = '';


// -----------------------------------------------
// Fetch the axis record (needed for the feedback message)

$axe_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, axe FROM " . TABLE_DATA_TYPE_AXE . "
     WHERE id = " . $id_axe);
$axe_tab = tep_db_fetch_array($axe_query);


if (isset($axe_tab))
{
    // Guard: count time-series types that reference this axis before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_axe
         FROM " . TABLE_TYPE_DATA . "
         WHERE axe_data = " . $id_axe);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_axe'] < 1)
    {
        // No time-series type references this axis — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_TYPE_AXE . " WHERE id = " . $id_axe);
        $message_info = "'" . $axe_tab['axe'] . "' " . TEXT_TD_AXE_DEL_OK;
    }
    else
    {
        // At least one time-series type uses this axis — deletion blocked
        $del_axe       = false;
        $message_info  = "'" . $axe_tab['axe'] . "' " . TEXT_TD_AXE_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_TD_AXE_DEL_ERR_CHRON;
    }
}
else
{
    // Axis not found — should not happen in normal use
    $del_axe      = false;
    $message_info = TEXT_TD_AXE_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_axe'      => $del_axe,
    'message_info' => $message_info,
]);
?>
