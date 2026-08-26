<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — hydrological region deletion
Called by delete_regionhydro() in form_geo_regionhydro.php.
Receives id_regionhydro via JSON POST.
Checks whether the region is linked to any river or station before deleting.
Both dependencies block deletion.
Returns JSON:
  del_regionhydro : bool   — true on successful deletion
  message_info    : string — feedback message for the user
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
$dataInfo       = json_decode(file_get_contents('php://input'), true);
$id_regionhydro = $dataInfo['id_regionhydro'];

$del_regionhydro = true;
$message_info    = '';


// -----------------------------------------------
// Fetch the hydrological region record (needed for the feedback message)

$regionhydro_query = tep_db_query($sql_link,
    "SELECT DISTINCT id, nom FROM " . TABLE_REGIONHYDRO . "
     WHERE id = " . $id_regionhydro);
$regionhydro_tab = tep_db_fetch_array($regionhydro_query);


if (isset($regionhydro_tab))
{
    // Guard 1: count rivers that belong to this hydrological region
    $ctrl_riviere_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_riviere_regionhydro
         FROM " . TABLE_RIVIERE . "
         WHERE id_regionhydro = " . $id_regionhydro);
    $ctrl_riviere_tab = tep_db_fetch_array($ctrl_riviere_query);

    // Guard 2: count stations directly assigned to this hydrological region
    $ctrl_station_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_regionhydro
         FROM " . TABLE_STATION . "
         WHERE id_regionhydro = " . $id_regionhydro);
    $ctrl_station_tab = tep_db_fetch_array($ctrl_station_query);

    if ($ctrl_riviere_tab['nb_riviere_regionhydro'] < 1 && $ctrl_station_tab['nb_station_regionhydro'] < 1)
    {
        // Hydrological region is not referenced anywhere — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_REGIONHYDRO . " WHERE id = " . $id_regionhydro);
        $message_info = "'" . $regionhydro_tab['nom'] . "' " . TEXT_GEO_REGIONHYDRO_DEL_OK;
    }
    else
    {
        // Still referenced by a river or a station — deletion blocked
        $del_regionhydro  = false;
        $message_info     = "'" . $regionhydro_tab['nom'] . "' " . TEXT_GEO_REGIONHYDRO_DEL_ERR_LINKED . "<br>";
        $message_info    .= TEXT_GEO_REGIONHYDRO_DEL_ERR_DEPENDENCY;
    }
}
else
{
    // Hydrological region not found — should not happen in normal use
    $del_regionhydro = false;
    $message_info    = TEXT_GEO_REGIONHYDRO_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_regionhydro' => $del_regionhydro,
    'message_info'    => $message_info,
]);
?>
