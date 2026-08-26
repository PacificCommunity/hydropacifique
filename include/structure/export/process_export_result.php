<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export tracking logger
- Displays and lists the export history
- Writes a summary text file on the server
- Records the export action in the database actions log
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
// Parse incoming JSON payload from AJAX request

$jsonData = file_get_contents('php://input');
$data     = json_decode($jsonData, true);

$text_result     = $data['text_result'];
$id_user         = $data['id_user'];
$date_export     = $data['date_export'];
$folder_download = $data['folder_download'];
$chemin_folder   = $data['chemin_folder'];


// -----------------------------------------------
// Write export summary to a text file (ISO-8859-1 encoded for legacy compatibility)

$chemin_folder_process = '../../../' . DIR_WS_DATA_EXPORT;
$resultFilename        = $chemin_folder_process . '/' . $folder_download . '.txt';

file_put_contents($resultFilename, mb_convert_encoding($text_result, 'ISO-8859-1', 'UTF-8'));


// -----------------------------------------------
// Record the export action in the database actions log

$type_action = 36;
$info_action = TEXT_ACTION_EXPORT;

$query = "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure, file_export)
          VALUES (" . $id_user . ", '" . $type_action . "', '" . $info_action . "', '" . $date_export . "', '" . $folder_download . ".tar')";

tep_db_query($sql_link, $query);
?>