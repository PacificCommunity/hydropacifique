<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — service deletion
Called by delete_service() in form_service_1.php.
Receives idService via JSON POST.
A service can only be deleted if no row in TABLE_STATION references
it via id_service.
Returns JSON:
  del_service  : bool   — true on successful deletion
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
$dataInfo  = json_decode(file_get_contents('php://input'), true);
$idService = isset($dataInfo['idService']) ? (int) $dataInfo['idService'] : 0;

$del_service  = true;
$message_info = '';


// -----------------------------------------------
// Fetch the service record (needed for the feedback message)

$service_query = tep_db_query($sql_link,
    "SELECT id_service, name FROM " . TABLE_SERVICE . " WHERE id_service = " . $idService);
$service_tab = tep_db_fetch_array($service_query);

if (isset($service_tab))
{
    $nom = html_entity_decode($service_tab['name']);

    // Check for dependent stations
    $del_query = tep_db_query($sql_link,
        "SELECT id_station FROM " . TABLE_STATION
        . " WHERE id_service=" . $idService . " LIMIT 1");
    $del_info = tep_db_fetch_array($del_query);

    if (!isset($del_info['id_station']))
    {
        tep_db_query($sql_link, "DELETE FROM " . TABLE_SERVICE . " WHERE id_service=" . $idService);
        $message_info = sprintf(TEXT_SV_DEL_OK, $nom);
    }
    else
    {
        $del_service  = false;
        $message_info = sprintf(TEXT_SV_DEL_ERR_LINKED, $nom);
    }
}
else
{
    // Service not found — should not happen in normal use
    $del_service  = false;
    $message_info = TEXT_SV_DEL_ERR_NOT_FOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_service'  => $del_service,
    'message_info' => $message_info,
]);
?>