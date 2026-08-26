<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezo characteristics delete handler - AJAX server-side process
- Called from include/structure/form_station_caracteristique.php
- Deletes a well characteristics record from TABLE_STATION_PIEZO_CARACTERISTIQUE
----------------------------------------
*/

// -----------------------------------------------
// Core dependencies: config, DB tables, functions

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Ensure proper UTF-8 encoding for accented characters
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');


// -----------------------------------------------
// Load translation strings for the active language

require('../../text_content_' . LANGUAGE . '.php');


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo  = json_decode(file_get_contents('php://input'), true);
$id_caract = isset($dataInfo['id_caract']) ? (int) $dataInfo['id_caract'] : 0;


// -----------------------------------------------
// Initialize variables

$del_caract = true;


// -----------------------------------------------
// Query: Retrieve the characteristics record

$sql_caract   = "SELECT DISTINCT c.id, c.date
                 FROM " . TABLE_STATION_PIEZO_CARACTERISTIQUE . " c
                 WHERE id = " . $id_caract;
$caract_query = tep_db_query($sql_link, $sql_caract);
$caract_tab   = tep_db_fetch_array($caract_query);


// -----------------------------------------------
// Delete the record

if (isset($caract_tab))
{
    tep_db_query($sql_link,
        "DELETE FROM " . TABLE_STATION_PIEZO_CARACTERISTIQUE . " WHERE id = " . $caract_tab['id']);
}
else
{
    $del_caract = false;
}


// -----------------------------------------------
// Build and return JSON response

$message_info = $del_caract
    ? sprintf(TEXT_CARACT_DELETE_SUCCESS, dateus_fr($caract_tab['date']))
    : TEXT_CARACT_DELETE_FAIL;

echo json_encode([
    'del_caract'   => $del_caract,
    'message_info' => $message_info,
]);
?>