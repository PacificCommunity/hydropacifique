<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezo benchmark delete handler - AJAX server-side process
- Called from include/structure/form_station_repere.php
- Deletes a benchmark record from TABLE_STATION_PIEZO_REPERE
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
$id_repere = isset($dataInfo['id_repere']) ? (int) $dataInfo['id_repere'] : 0;


// -----------------------------------------------
// Initialize variables

$del_repere = true;


// -----------------------------------------------
// Query: Retrieve the benchmark record

$sql_repere   = "SELECT DISTINCT r.id, r.date_debut_valid
                 FROM " . TABLE_STATION_PIEZO_REPERE . " r
                 WHERE id = " . $id_repere;
$repere_query = tep_db_query($sql_link, $sql_repere);
$repere_tab   = tep_db_fetch_array($repere_query);


// -----------------------------------------------
// Delete the record

if (isset($repere_tab))
{
    tep_db_query($sql_link,
        "DELETE FROM " . TABLE_STATION_PIEZO_REPERE . " WHERE id = " . $repere_tab['id']);
}
else
{
    $del_repere = false;
}


// -----------------------------------------------
// Build and return JSON response

$message_info = $del_repere
    ? sprintf(TEXT_REPERE_DELETE_SUCCESS, dateus_fr($repere_tab['date_debut_valid']))
    : TEXT_REPERE_DELETE_FAIL;

echo json_encode([
    'del_repere'   => $del_repere,
    'message_info' => $message_info,
]);
?>