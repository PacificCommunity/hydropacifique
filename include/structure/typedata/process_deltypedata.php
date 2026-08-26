<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — time-series type deletion
Called by delete_typedata() in form_typedata_chron.php.
Receives id_typedata via JSON POST.
A time-series type can only be deleted if no data record in TABLE_DATA_META
references it via id_typedata.
Returns JSON:
  del_typedata : bool   — true on successful deletion
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
$id_chron = $dataInfo['id_typeChron'];

$del_chron    = true;
$message_info = '';


// -----------------------------------------------
// Fetch the time-series type record (needed for the feedback message)

$chronique_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data FROM " . TABLE_TYPE_DATA . "
     WHERE id_data_type = " . $id_chron);
$chronique_tab = tep_db_fetch_array($chronique_query);


if (isset($chronique_tab))
{
    // Guard: count data_meta records that reference this type before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_chron
         FROM " . TABLE_DATA_META . "
         WHERE id_typedata = " . $id_chron);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_chron'] < 1)
    {
        // No data record references this type — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_TYPE_DATA . " WHERE id_data_type = " . $id_chron);
        $message_info = "'" . $chronique_tab['init_type_data'] . "' " . TEXT_TD_CHRON_DEL_OK;
    }
    else
    {
        // At least one data record uses this type — deletion blocked
        $del_chron     = false;
        $message_info  = "'" . $chronique_tab['init_type_data'] . "' " . TEXT_TD_CHRON_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_TD_CHRON_DEL_ERR_DATA;
    }
}
else
{
    // Type not found — should not happen in normal use
    $del_chron    = false;
    $message_info = TEXT_TD_CHRON_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_typedata' => $del_chron,
    'message_info' => $message_info,
]);
?>
