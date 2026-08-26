<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — aquifer deletion, legacy version (TABLE_GEO_AQUIFERE, no territoire filter)
Called by delete_aquifere() in form_geo_aquifere.php.
See also: process_delacquifere.php (current version).
Receives id_aquifere via JSON POST.
Checks whether the aquifer is linked to any station before deleting.
Returns JSON:
  del_aquifere : bool   — true on successful deletion
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
$id_aquifere = $dataInfo['id_aquifere'];

$del_aquifere = true;
$message_info = '';


// -----------------------------------------------
// Fetch the aquifer record (needed for the feedback message)

$aquifere_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom FROM " . TABLE_GEO_AQUIFERE . "
     WHERE id = " . $id_aquifere);
$aquifere_tab = tep_db_fetch_array($aquifere_query);


if (isset($aquifere_tab))
{
    // Guard: count stations that reference this aquifer before deleting
    $ctrl_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_aquifere
         FROM " . TABLE_STATION . "
         WHERE id_aquifere = " . $id_aquifere);
    $ctrl_tab = tep_db_fetch_array($ctrl_query);

    if ($ctrl_tab['nb_station_aquifere'] < 1)
    {
        // No station references this aquifer — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_GEO_AQUIFERE . " WHERE id = " . $id_aquifere);
        $message_info = TEXT_GEO_AQUIFERE_DEL_OK;
    }
    else
    {
        // At least one station references this aquifer — deletion blocked
        $del_aquifere  = false;
        $message_info  = "L'Aquifere - " . $aquifere_tab['nom'] . " - " . TEXT_GEO_AQUIFERE_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_GEO_AQUIFERE_DEL_ERR_STATION;
    }
}
else
{
    // Aquifer not found — should not happen in normal use
    $del_aquifere = false;
    $message_info = TEXT_GEO_AQUIFERE_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_aquifere' => $del_aquifere,
    'message_info' => $message_info,
]);
?>
