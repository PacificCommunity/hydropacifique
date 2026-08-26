<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — river deletion
Called by delete_riviere() in form_geo_riviere.php.
Receives id_riviere via JSON POST.
Checks whether the river is linked to any station before deleting.
Returns JSON:
  del_riviere  : bool   — true on successful deletion
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
$id_riviere = $dataInfo['id_riviere'];

$del_riviere  = true;
$message_info = '';


// -----------------------------------------------
// Fetch the river record (needed for the feedback message)

$riviere_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom FROM " . TABLE_RIVIERE . "
     WHERE id = " . $id_riviere);
$riviere_tab = tep_db_fetch_array($riviere_query);


if (isset($riviere_tab))
{
    // Guard: count stations that reference this river before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_riviere
         FROM " . TABLE_STATION . "
         WHERE id_riviere = " . $id_riviere);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_station_riviere'] < 1)
    {
        // No station references this river — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_RIVIERE . " WHERE id = " . $id_riviere);
        $message_info = "'" . $riviere_tab['nom'] . "' " . TEXT_GEO_RIVIERE_DEL_OK;
    }
    else
    {
        // At least one station references this river — deletion blocked
        $del_riviere   = false;
        $message_info  = "'" . $riviere_tab['nom'] . "' " . TEXT_GEO_RIVIERE_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_GEO_RIVIERE_DEL_ERR_STATION;
    }
}
else
{
    // River not found — should not happen in normal use
    $del_riviere  = false;
    $message_info = TEXT_GEO_RIVIERE_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_riviere'  => $del_riviere,
    'message_info' => $message_info,
]);
?>
