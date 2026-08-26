<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — round deletion
Called by delete_tournee() in form_geo_tournee.php.
Receives id_tournee via JSON POST.
Checks whether the round is linked to any station (via TABLE_STATION_TO_TOURNEE)
before deleting.
Returns JSON:
  del_tournee  : bool   — true on successful deletion
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
$dataInfo   = json_decode(file_get_contents('php://input'), true);
$id_tournee = $dataInfo['id_tournee'];

$del_tournee  = true;
$message_info = '';


// -----------------------------------------------
// Fetch the round record (needed for the feedback message)

$tournee_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom FROM " . TABLE_TOURNEE . "
     WHERE id = " . $id_tournee);
$tournee_tab = tep_db_fetch_array($tournee_query);


if (isset($tournee_tab))
{
    // Guard: count station-to-round links before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_tournee
         FROM " . TABLE_STATION_TO_TOURNEE . "
         WHERE id_tournee = " . $id_tournee);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_station_tournee'] < 1)
    {
        // Round is not assigned to any station — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_TOURNEE . " WHERE id = " . $id_tournee);
        $message_info = "'" . $tournee_tab['nom'] . "' " . TEXT_GEO_TOURNEE_DEL_OK;
    }
    else
    {
        // Round is still assigned to at least one station — deletion blocked
        $del_tournee   = false;
        $message_info  = "'" . $tournee_tab['nom'] . "' " . TEXT_GEO_TOURNEE_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_GEO_TOURNEE_DEL_ERR_STATION;
    }
}
else
{
    // Round not found — should not happen in normal use
    $del_tournee  = false;
    $message_info = TEXT_GEO_TOURNEE_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_tournee'  => $del_tournee,
    'message_info' => $message_info,
]);
?>
