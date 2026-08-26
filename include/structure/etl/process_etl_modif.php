<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — update ETL validity period
Receives JSON: idUser, todayTimeFormatted, idEtl, numEtl,
               date1, heure1, date2, heure2, idStation.
Checks for period overlap (excluding the current ETL), then updates
datetime_first / datetime_end. Logs to TABLE_ACTIONS.
Returns JSON: { js_text, valid_process }
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

$data               = json_decode(file_get_contents('php://input'), true);
$id_user            = $data['idUser'];
$todayTimeFormatted = $data['todayTimeFormatted'];
$idEtl              = $data['idEtl'];
$numEtl             = $data['numEtl'];
$date1              = $data['date1'];
$date2              = $data['date2'];
$heure1             = $data['heure1'];
$heure2             = $data['heure2'];
$id_station         = $data['idStation'];

$datetime1 = datefr_us($date1) . ' ' . $heure1;
$datetime2 = datefr_us($date2) . ' ' . $heure2;

$import_result = '';
$valid_process = false;

// Station lookup (used for the action log)
$station_all_array = [];
$station_all_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station FROM " . TABLE_STATION);
while ($s = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$s['id_station']] = [
        'code_station' => $s['code_station'],
        'nom_station'  => $s['nom_station'],
        'station_type' => $s['station_type'],
    ];
}

// Check for period overlap with other ETLs (excluding the current one)
$ETL_valid_query = tep_db_query($sql_link,
    "SELECT COUNT(*) as nb FROM " . TABLE_DATA_ETL . "
     WHERE id_station=$id_station
     AND id <> $idEtl
     AND NOT (datetime_end < '$datetime1' OR datetime_first > '$datetime2')");
$ETL_data_tab = tep_db_fetch_array($ETL_valid_query);

if ($ETL_data_tab['nb'] < 1)
{
    tep_db_query($sql_link,
        "UPDATE " . TABLE_DATA_ETL
        . " SET datetime_first='$datetime1', datetime_end='$datetime2'"
        . " WHERE id=$idEtl");

    $info_action = "ETL period updated - Station: " . $station_all_array[$id_station]['nom_station']
                 . " - ETL: $date1 $heure1 \u2192 $date2 $heure2";
    tep_db_query($sql_link,
        "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
        . " VALUES ($id_user, 33, '$info_action', '$todayTimeFormatted')");

    $import_result = sprintf(TEXT_ET_MODIF_OK, $numEtl, $date1, $heure1, $date2, $heure2);
    $valid_process = true;
}
else
{
    $import_result = sprintf(TEXT_ET_MODIF_ERR_OVERLAP, $date1, $heure1, $date2, $heure2);
}

echo json_encode([
    'js_text'       => $import_result,
    'valid_process' => $valid_process,
], JSON_UNESCAPED_UNICODE);
?>
