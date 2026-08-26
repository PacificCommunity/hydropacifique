<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
AJAX endpoint — validate and save H→Q conversion
Receives JSON: timezone_php, typedataChronQ, xDateMin, xDateMax,
               idStation, id_user, id_meta_correction.
Copies the converted data from TABLE_DATA_ALL_CORRECTION to
TABLE_DATA_ALL, deletes the temporary correction records, and logs
the action. Returns JSON: { js_text }
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/stats.php');
require('../../function/sql_function.php');

require('../../text_content_' . LANGUAGE . '.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Cannot connect to the database');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Station lookup (for the action log)
$station_all_array = [];
$station_all_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
     FROM " . TABLE_STATION . " ORDER BY nom_station ASC");
while ($s = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$s['id_station']] = [
        'code_station' => $s['code_station'],
        'nom_station'  => html_entity_decode($s['nom_station'] ?? ''),
        'station_type' => $s['station_type'],
    ];
}

// Time-series types lookup (for the action log)
$type_chron_array = [];
$type_chron_query = tep_db_query($sql_link,
    "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data,
                     axe_data, unite, to_periode, id_chon_periode
     FROM " . TABLE_TYPE_DATA . " ORDER BY init_type_data ASC");
while ($tc = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$tc['id_data_type']] = [
        'init_type_data' => $tc['init_type_data'],
        'nom_type_data'  => $tc['nom_type_data'],
    ];
}

$data               = json_decode(file_get_contents('php://input'), true);
$timezone_php       = $data['timezone_php'];
$typedata_chron_q   = $data['typedataChronQ'];
$datetime_correction_first = $data['xDateMin'];
$datetime_correction_end   = $data['xDateMax'];
$station_chron      = $data['idStation'];
$id_user            = $data['id_user'];
$id_meta_correction = $data['id_meta_correction'];
$idCodeQual         = 0;

date_default_timezone_set($timezone_php);
$today          = new DateTime();
$today_formated = $today->format('Y-m-d H:i:s');

// ---- Insert new meta record ----
$sql_insert_bloc_meta = "INSERT INTO " . TABLE_DATA_META
    . " (id_station, id_typedata, id_codequal, id_user, source, obs)"
    . " VALUES ($station_chron, $typedata_chron_q, $idCodeQual, $id_user, 'Conversion', '')";
tep_db_query($sql_link, $sql_insert_bloc_meta);
$meta_id_encours = mysqli_insert_id($sql_link);

// ---- Copy converted data from correction table to production table ----
$sql_copyData = "INSERT INTO " . TABLE_DATA_ALL . " (dateheure, valeur, id_meta)
                 SELECT dateheure, valeur, $meta_id_encours
                 FROM " . TABLE_DATA_ALL_CORRECTION . "
                 WHERE id_meta=$id_meta_correction";

// Delete existing production data in the conversion period
$sql_info_meta = "SELECT DISTINCT id, datetime_first, datetime_end
                  FROM " . TABLE_DATA_META_CORRECTION . "
                  WHERE id=$id_meta_correction AND source='Conversion'
                  ORDER BY id DESC LIMIT 1";
$info_meta_query = tep_db_query($sql_link, $sql_info_meta);
$info_meta = tep_db_fetch_array($info_meta_query);

deleteDataAndMeta($sql_link, $station_chron, $typedata_chron_q,
    $info_meta['datetime_first'], $info_meta['datetime_end']);

tep_db_query($sql_link, $sql_copyData);

// ---- Remove all temporary conversion records for this station ----
$sql_meta_del = "SELECT DISTINCT id FROM " . TABLE_DATA_META_CORRECTION
    . " WHERE id_station=" . (int)$station_chron . " AND source='Conversion'";
$meta_del_query = tep_db_query($sql_link, $sql_meta_del);
$ids_to_delete  = [];
while ($meta_del = tep_db_fetch_array($meta_del_query))
{
    $ids_to_delete[] = (int)$meta_del['id'];
}
if (!empty($ids_to_delete))
{
    $ids_list = implode(',', $ids_to_delete);
    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ALL_CORRECTION    . " WHERE id_meta IN ($ids_list)");
    tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_META_CORRECTION   . " WHERE id IN ($ids_list)");
}

// ---- Action log ----
$info_action  = TEXT_HQ_LOG_INFO_PREFIX;
$info_action .= TEXT_HQ_LOG_STATION_PREFIX
    . $station_all_array[$station_chron]['nom_station'] . "\n";
$info_action .= TEXT_HQ_LOG_CHRON_PREFIX
    . $type_chron_array[$typedata_chron_q]['init_type_data'] . " - "
    . $type_chron_array[$typedata_chron_q]['nom_type_data'];
$info_action = post_secure($sql_link, $info_action);

tep_db_query($sql_link,
    "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure)"
    . " VALUES ($id_user, 39, '$info_action', '$today_formated')");

$result = TEXT_HQ_VALID_OK;

echo json_encode(['js_text' => $result], JSON_UNESCAPED_UNICODE);
?>
