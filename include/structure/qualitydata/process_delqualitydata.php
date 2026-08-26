<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — quality code deletion
Called by delete_qualitydata() in form_qualitydata.php.
Receives id_qualitydata via JSON POST.
A quality code can only be deleted if it has never been assigned to any
data record — checked against both TABLE_DATA_META (id_codequal) and
TABLE_DATA_ETL_DATA (code_qualite).
Returns JSON:
  del_qualitydata : bool   — true on successful deletion
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
$id_qualitydata = $dataInfo['id_qualitydata'];

$del_qualitydata = true;
$message_info    = '';


// -----------------------------------------------
// Fetch the quality code record (needed for the feedback message)

$qualitydata_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_qualite, init_qualite_data
     FROM " . TABLE_DATA_QUALITE . "
     WHERE id_data_qualite = " . $id_qualitydata);
$qualitydata_tab = tep_db_fetch_array($qualitydata_query);


if (isset($qualitydata_tab))
{
    // Guard: check whether this code is referenced in TABLE_DATA_META or TABLE_DATA_ETL_DATA.
    // A single EXISTS subquery covers both tables efficiently.
    $verif_query = tep_db_query($sql_link,
        "SELECT EXISTS (
            SELECT 1 FROM (
                SELECT 1 FROM " . TABLE_DATA_META     . " WHERE id_codequal   = " . $id_qualitydata . "
                UNION
                SELECT 1 FROM " . TABLE_DATA_ETL_DATA . " WHERE code_qualite  = " . $id_qualitydata . "
            ) AS subquery
            LIMIT 1
         ) AS id_cq_exists");
    $verif_tab = tep_db_fetch_array($verif_query);

    if ($verif_tab['id_cq_exists'] != 1)
    {
        // Code is not used in any data record — safe to delete
        tep_db_query($sql_link,
            "DELETE FROM " . TABLE_DATA_QUALITE . " WHERE id_data_qualite = " . $id_qualitydata);
        $message_info = "'" . $qualitydata_tab['init_qualite_data'] . "' " . TEXT_QD_DEL_OK;
    }
    else
    {
        // Code is still referenced by at least one data record — deletion blocked
        $del_qualitydata = false;
        $message_info    = "'" . $qualitydata_tab['init_qualite_data'] . "' " . TEXT_QD_DEL_ERR_LINKED . "<br>";
        $message_info   .= TEXT_QD_DEL_ERR_DATA;
    }
}
else
{
    // Quality code not found — should not happen in normal use
    $del_qualitydata = false;
    $message_info    = TEXT_QD_DEL_ERR_NOTFOUND;
}


// Return JSON response to the client
echo json_encode([
    'del_qualitydata' => $del_qualitydata,
    'message_info'    => $message_info,
]);
?>
