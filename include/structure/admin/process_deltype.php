<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — measurement type (eq_type) deletion
Called by delete_type() in form_type_1.php.
Receives id_typeData via JSON POST.
A measurement type can only be deleted if no row in TABLE_TYPE_DATA
references it via id_eq_type_data.
Returns JSON:
  del_type     : bool   — true on successful deletion
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

header('Content-Type: application/json; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Decode JSON payload sent by the AJAX call
$dataInfo = json_decode(file_get_contents('php://input'), true);
$type_id  = isset($dataInfo['id_typeData']) ? (int) $dataInfo['id_typeData'] : 0;

$del_type     = true;
$message_info = '';


// -----------------------------------------------
// Fetch the measurement type record (needed for the feedback message)

$type_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_eq_type, nom_eq_type FROM " . TABLE_EQ_TYPE . "
     WHERE id_eq_type = " . $type_id);
$type_tab = tep_db_fetch_array($type_query);


if (isset($type_tab))
{
    $nom = html_entity_decode($type_tab['nom_eq_type']);

    // Check for dependent time-series records
    $del_query = tep_db_query($sql_link,
        "SELECT DISTINCT id_data_type FROM " . TABLE_TYPE_DATA
        . " WHERE id_eq_type_data=" . $type_id . " LIMIT 1");
    $del_info = tep_db_fetch_array($del_query);

    if (!isset($del_info['id_data_type']))
    {
        tep_db_query($sql_link, "DELETE FROM " . TABLE_EQ_TYPE . " WHERE id_eq_type=" . $type_id);
        $message_info = sprintf(TEXT_US_TYPE_DEL_OK, $nom);
    }
    else
    {
        $del_type     = false;
        $message_info = sprintf(TEXT_US_TYPE_DEL_ERR_LINKED, $nom);
    }
}
else
{
    // Type not found — should not happen in normal use
    $del_type     = false;
    $message_info = TEXT_US_TYPE_DEL_ERR_NOT_FOUND;
}


// Return JSON response to the client
// Key name MUST match what form_type_1.php reads in its callback
// (was 'del_typedata' before — a copy-paste leftover from the service
// deletion endpoint; the JS read 'del_type' so the success border was
// never applied, even on a successful delete).
echo json_encode([
    'del_type'    => $del_type,
    'message_info' => $message_info,
]);
?>