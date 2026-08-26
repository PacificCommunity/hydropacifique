<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Piezo benchmark table loader - AJAX server-side process
- Called from include/structure/form_station_repere.php
- Returns the benchmark table HTML (rebuilt after a save, no page reload)
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


// Shared table renderer (single source of truth).
// repere_table.php lives in the same folder as this script. Prefer the
// DIR_WS_STATION constant when available, otherwise fall back to __DIR__.
if (defined('DIR_WS_STATION') && is_file(DIR_WS_STATION . 'repere_table.php')) {
    require_once(DIR_WS_STATION . 'repere_table.php');
} else {
    require_once(__DIR__ . '/repere_table.php');
}


// -----------------------------------------------
// Parse JSON input from AJAX request

$dataInfo   = json_decode(file_get_contents('php://input'), true);
$id_station = isset($dataInfo['id_station']) ? (int) $dataInfo['id_station'] : 0;


// -----------------------------------------------
// Build the table HTML and return it as JSON

$tab_html = render_repere_table($sql_link, $id_station);

echo json_encode(['tab_html' => $tab_html]);
?>