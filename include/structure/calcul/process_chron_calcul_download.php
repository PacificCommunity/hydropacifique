<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Correction series CSV export handler - AJAX server-side process
- Generates a CSV file from correction data on the server
- Returns filename to client for download trigger
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
// Parse JSON input from AJAX request

$data            = json_decode(file_get_contents('php://input'), true);
$id_meta_correct = $data['id_meta_correct'];


// -----------------------------------------------
// Query: Correction meta record

$meta_correction_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT mc.id, mc.id_station, mc.id_typedata, mc.id_codequal, mc.info_correction
     FROM " . TABLE_DATA_META_CORRECTION . " mc WHERE mc.id = " . $id_meta_correct));

$detail_correction_part = explode(':', $meta_correction_tab['info_correction']);
if (count($detail_correction_part) > 1)
{
    $detail_correction = str_replace(' ', '', trim($detail_correction_part[1]));
}
else
{
    $detail_correction = str_replace(' ', '', $meta_correction_tab['info_correction']);
}


// -----------------------------------------------
// Query: Station info

$station_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
     FROM " . TABLE_STATION . " WHERE id_station = " . $meta_correction_tab['id_station']));

$code_station = $station_tab['code_station'];
$nom_station  = html_entity_decode($station_tab['nom_station'] ?? '');


// -----------------------------------------------
// Query: Series type info

$type_chron_tab = tep_db_fetch_array(tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data
     FROM " . TABLE_TYPE_DATA . " WHERE id_data_type = " . $meta_correction_tab['id_typedata']));

$init_type_data = $type_chron_tab['init_type_data'];
$nom_type_data  = $type_chron_tab['nom_type_data'];


// -----------------------------------------------
// Query: Quality code (optional)

$init_qualite_data = '';
if (tep_not_null($meta_correction_tab['id_codequal']))
{
    $quality_tab = tep_db_fetch_array(tep_db_query($sql_link,
        "SELECT DISTINCT id_data_qualite, init_qualite_data
         FROM " . TABLE_DATA_QUALITE . " WHERE id_data_qualite = " . $meta_correction_tab['id_codequal']));

    $init_qualite_data = $quality_tab['init_qualite_data'] ?? '';
}


// -----------------------------------------------
// Build CSV filename and output directory

$nom_station_filename = ucfirst(strtolower(nettoyerNomFichier($nom_station)));
$Filename             = $code_station . '_' . $init_type_data . '_' . $detail_correction
                      . '_' . $nom_station_filename . '.csv';

$chemin_folder         = 'data/export/temp';
$chemin_folder_process = '../../../' . $chemin_folder;
$csvFilename           = $chemin_folder_process . '/' . $Filename;

// Create or empty the export directory
if (!is_dir($chemin_folder_process))
{
    mkdir($chemin_folder_process, 0755, true);
}
else
{
    foreach (glob($chemin_folder_process . '/*') as $file)
    {
        if (is_file($file)) { unlink($file); }
    }
}


// -----------------------------------------------
// Query: Correction data, build CSV content

$startTime = microtime(true);
$content   = '';

$data_chron_query = tep_db_query($sql_link,
    "SELECT da.dateheure, da.valeur
     FROM " . TABLE_DATA_ALL_CORRECTION . " da
     WHERE da.id_meta = " . $id_meta_correct . "
     ORDER BY da.dateheure ASC");

while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
{
    $content .= $data_chron_tab['dateheure'] . ";" . $data_chron_tab['valeur'] . ";" . $init_qualite_data . "\n";
}
file_put_contents($csvFilename, $content);

if (isset($data_chron_query)) { mysqli_free_result($data_chron_query); }

$executionTime = number_format(microtime(true) - $startTime, 1);


// -----------------------------------------------
// Return filename and execution time as JSON

echo json_encode([
    'statut'        => true,
    'executionTime' => $executionTime,
    'csvFile'       => $Filename,
]);
?>
