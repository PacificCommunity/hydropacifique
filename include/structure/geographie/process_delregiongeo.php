<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — geographic region deletion
Called by delete_regiongeo() in form_geo_regiongeo.php.
Receives id_region via JSON POST.
Checks whether the region is linked to any town or station before deleting.
Both dependencies block deletion: a region must be free of all towns and all
direct station assignments before it can be removed.
Returns JSON:
  del_region   : bool   — true on successful deletion
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
$id_region = $dataInfo['id_region'];

$del_region   = true;
$message_info = '';


// -----------------------------------------------
// Fetch the region record (needed for the feedback message)

$regiongeo_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_region, nom_region FROM " . TABLE_REGION . "
     WHERE id_region = " . $id_region);
$regiongeo_tab = tep_db_fetch_array($regiongeo_query);


if (isset($regiongeo_tab))
{
    // Guard 1: count towns that belong to this region
    $ctrl_commune_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_commune_region
         FROM " . TABLE_COMMUNE . "
         WHERE id_region = " . $id_region);
    $ctrl_commune_tab = tep_db_fetch_array($ctrl_commune_query);

    // Guard 2: count stations directly assigned to this region
    $ctrl_station_query = tep_db_query($sql_link,
        "SELECT DISTINCT count(*) as nb_station_region
         FROM " . TABLE_STATION . "
         WHERE id_region = " . $id_region);
    $ctrl_station_tab = tep_db_fetch_array($ctrl_station_query);

    if ($ctrl_commune_tab['nb_commune_region'] < 1 && $ctrl_station_tab['nb_station_region'] < 1)
    {
        // Region is not referenced anywhere — safe to delete
        tep_db_query($sql_link, "DELETE FROM " . TABLE_REGION . " WHERE id_region = " . $id_region);
        $message_info = "'" . $regiongeo_tab['nom_region'] . "' " . TEXT_GEO_REGION_DEL_OK;
    }
    else
    {
        // Region is still referenced — deletion blocked
        $del_region    = false;
        $message_info  = "'" . $regiongeo_tab['nom_region'] . "' " . TEXT_GEO_REGION_DEL_ERR_LINKED . "<br>";
        $message_info .= TEXT_GEO_REGION_DEL_ERR_DEPENDENCY;
    }
}
else
{
    // Region not found — should not happen in normal use
    $del_region   = false;
    $message_info = TEXT_GEO_REGION_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_region'   => $del_region,
    'message_info' => $message_info,
]);
?>
