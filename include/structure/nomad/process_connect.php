<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
HP-NOMAD
Connection pre-check for the synchronization process.
Tests both the local (Nomad / offline) and the remote (server / online) databases,
then returns a JSON status so the client can decide whether to proceed.
AJAX endpoint, called from sync.php before launching the actual transfer.
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// Load the translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');

ini_set('display_errors', 0);            // do not print warnings to the output
error_reporting(E_ALL & ~E_WARNING);     // keep real errors, hide warnings

header('Content-Type: text/html; charset=utf-8');

$erreur = false;
$process_info = '';

// Local database connection (NOMAD / offline)
try {
    $connLocal = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
    mysqli_query($connLocal, 'SET NAMES UTF8');
    $process_info .= "\n" . TEXT_SYNC_CONN_NOMAD_OK . " \n";
} catch (mysqli_sql_exception $e) {
    $erreur = true;
    $process_info .= "\n" . TEXT_SYNC_CONN_NOMAD_FAIL . " \n";
}

// Remote database connection (SERVER / online)
try {
    $connOnline = mysqli_connect(DB_SERVER_NOMAD, DB_SERVER_USERNAME_NOMAD, DB_SERVER_PASSWORD_NOMAD, DB_DATABASE_NOMAD);
    mysqli_query($connOnline, 'SET NAMES UTF8');
    $process_info .= TEXT_SYNC_CONN_SERVER_OK . " - " . HP_SERVEUR . " - \n\n";
} catch (mysqli_sql_exception $e) {
    $erreur = true;
    $process_info .= TEXT_SYNC_CONN_SERVER_FAIL . " \n\n";
}

$result_info = [
    'erreur'       => $erreur,
    'process_info' => $process_info
];

echo json_encode($result_info);
?>