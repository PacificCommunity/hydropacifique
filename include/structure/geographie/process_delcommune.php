<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — town deletion
Called by delete_commune() in form_geo_commune.php.
Receives id_commune via JSON POST.
Checks whether the town is linked to any station before deleting.
Returns JSON:
  del_commune  : bool   — true on successful deletion
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
$id_commune = $dataInfo['id_commune'];

$del_commune  = true;
$message_info = '';


// -----------------------------------------------
// Fetch the town record (needed for the feedback message)

$commune_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_commune, nom_commune FROM " . TABLE_COMMUNE . "
     WHERE id_commune = " . $id_commune);
$commune_tab = tep_db_fetch_array($commune_query);


if (isset($commune_tab))
{
    // Guard: count stations that reference this town before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_commune
         FROM " . TABLE_STATION . "
         WHERE id_commune = " . $id_commune);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_station_commune'] < 1)
    {
        // No station references this town — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_COMMUNE . " WHERE id_commune = " . $id_commune);
        $message_info = "'" . $commune_tab['nom_commune'] . "' " . TEXT_GEO_COMMUNE_DEL_OK;
    }
    else
    {
        // At least one station references this town — deletion blocked
        $del_commune   = false;
        $message_info  = "'" . $commune_tab['nom_commune'] . "' " . TEXT_GEO_COMMUNE_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_GEO_COMMUNE_DEL_ERR_STATION;
    }
}
else
{
    // Town not found — should not happen in normal use
    $del_commune  = false;
    $message_info = TEXT_GEO_COMMUNE_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_commune'  => $del_commune,
    'message_info' => $message_info,
]);
?>
