<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
REP (Piezometric benchmark) data importer
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- Replaces all existing benchmarks for the station, then inserts fresh records
- CSV format: 2-row header + data rows; validity dates in columns 6-7
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
require('../../function/sql_function.php');

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
// Parse the import tracking ID sent from AJAX

$jsonIdImport = file_get_contents('php://input');
$idImport     = json_decode($jsonIdImport, true);


// -----------------------------------------------
// Query: Importable file format definitions
// Note: 'algo' field may contain executable parsing logic — handle with care (security risk)

$sql_import_files   = "SELECT DISTINCT id, name_ext, multi_feuil, separateur, description, algo
                       FROM " . TABLE_IMPORT_FILES . "
                       ORDER BY id ASC";
$import_files_query = tep_db_query($sql_link, $sql_import_files);
while ($import_files_tab = tep_db_fetch_array($import_files_query))
{
    $name_ext = mb_convert_encoding($import_files_tab['name_ext'], 'ISO-8859-1', 'UTF-8');

    $import_files[$name_ext] = [
        'id'          => $import_files_tab['id'],
        'multi_feuil' => $import_files_tab['multi_feuil'],
        'separateur'  => $import_files_tab['separateur'],
        'description' => mb_convert_encoding($import_files_tab['description'], 'ISO-8859-1', 'UTF-8'),
        'algo'        => $import_files_tab['algo'], // WARNING: may contain executable parsing code
    ];
}


// -----------------------------------------------
// Query: All stations (indexed by id_station)

$sql_station_all   = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
                      FROM " . TABLE_STATION;
$station_all_query = tep_db_query($sql_link, $sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$station_all['id_station']] = [
        'code_station' => $station_all['code_station'],
        'nom_station'  => $station_all['nom_station'],
        'station_type' => $station_all['station_type'],
    ];
}


// -----------------------------------------------
// Query: Data quality codes (indexed by init_qualite_data)

$sql_quality_data   = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data
                       FROM " . TABLE_DATA_QUALITE;
$quality_data_query = tep_db_query($sql_link, $sql_quality_data);
while ($quality_data_tab = tep_db_fetch_array($quality_data_query))
{
    $quality_data_array[$quality_data_tab['init_qualite_data']] = [
        'id_data_qualite'  => $quality_data_tab['id_data_qualite'],
        'nom_qualite_data' => mb_convert_encoding($quality_data_tab['nom_qualite_data']  ?? '', 'ISO-8859-1', 'UTF-8'),
        'info_qualite_data'=> mb_convert_encoding($quality_data_tab['info_qualite_data'] ?? '', 'ISO-8859-1', 'UTF-8'),
    ];
}


// -----------------------------------------------
// Initialize processing variables

$folder = '../../../data/uploads/';

$import_result_error = '';
$import_result       = '';

$num_ligne      = 0;
$nb_data_import = 0;
$date_debut     = '';
$date_fin       = '';
$rows_deleted   = 0;
$id_station     = 0;
$id_chron       = 0;
$id_ext_file    = 0;

$startTime = microtime(true);


// -----------------------------------------------
// Query: Retrieve the import tracking record for this session

$sql_import   = "SELECT DISTINCT id_import, file_import, file_ext, dateheure, id_station, id_chron, id_user
                 FROM " . TABLE_IMPORT_SUIVI . "
                 WHERE id = " . $idImport;
$import_query = tep_db_query($sql_link, $sql_import);
$import_data  = tep_db_fetch_array($import_query);

$id_station = $import_data['id_station'];


// -----------------------------------------------
// CSV reading, validation and insert
// Row layout (0-indexed): [0] station code | [1] ? | [2] nature | [3] code |
// [4] z value | [5] precision | [6] date_start | [7] date_end |
// [8] nature_g1 | [9] z_g1 | [10] nature_g2 | [11] z_g2 | [12] obs

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

        try
        {
            // Delete all existing benchmark records for this station before re-importing
            $stmt_delete = $sql_link->prepare(
                "DELETE FROM " . TABLE_STATION_PIEZO_REPERE . " WHERE id_station = ?"
            );
            $stmt_delete->bind_param('i', $id_station);
            $stmt_delete->execute();
            $stmt_delete->close();

            while (($data = fgetcsv($handle, null, ';')) !== false)
            {
                $import_valid = true;
                $num_ligne++;

                // Skip the first 2 rows (title + column headers)
                if ($num_ligne > 2)
                {
                    // ---- Column 7: Validity start date (date only, no time) ----
                    $datedebut_rep = '';
                    $dateString    = trim($data[6]);
                    $dateValidated = isValidDateImport($dateString);

                    if ($dateValidated == 'Invalid')
                    {
                        $import_valid = false;
                        $import_result_error .= sprintf(TEXT_IMPORT_REP_ERR_DATE, $num_ligne) . "\n";
                    }
                    else
                    {
                        $datedebut_rep = explode(' ', $dateValidated)[0]; // Date only
                    }

                    // ---- Column 8: Validity end date (optional, date only) ----
                    $datefin_rep = '';
                    $dateString  = trim($data[7]);

                    if ($dateString !== '')
                    {
                        $dateValidated = isValidDateImport($dateString);

                        if ($dateValidated == 'Invalid')
                        {
                            $import_valid = false;
                            $import_result_error .= sprintf(TEXT_IMPORT_REP_ERR_DATE, $num_ligne) . "\n";
                        }
                        else
                        {
                            $datefin_rep = explode(' ', $dateValidated)[0]; // Date only
                        }
                    }

                    // ---- Insert valid row ----
                    if ($import_valid)
                    {
                        $nature_rep    = trim($data[2]);
                        $code_rep      = trim($data[3]);
                        $z_rep         = is_numeric($data[4]) ? (float)$data[4] : null;
                        $precision_rep = trim($data[5]);
                        $nature_g1_rep = trim($data[8]);
                        $z_g1_rep      = is_numeric($data[9])  ? (float)$data[9]  : null;
                        $nature_g2_rep = trim($data[10]);
                        $z_g2_rep      = is_numeric($data[11]) ? (float)$data[11] : null;
                        $obs_rep       = trim($data[12]);

                        $sql_insert_rep = "INSERT INTO " . TABLE_STATION_PIEZO_REPERE . " (
                            id_station, nature_repere, code_repere, z_repere, precision_repere,
                            date_debut_valid, date_fin_valid,
                            nature_repere_1, z_repere_g1, nature_repere_2, z_repere_g2, obs
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmt_insert = $sql_link->prepare($sql_insert_rep);
                        $stmt_insert->bind_param(
                            'issdssssdsds',
                            $id_station, $nature_rep, $code_rep, $z_rep, $precision_rep,
                            $datedebut_rep, $datefin_rep,
                            $nature_g1_rep, $z_g1_rep, $nature_g2_rep, $z_g2_rep, $obs_rep
                        );
                        $stmt_insert->execute();
                        $stmt_insert->close();

                        $nb_data_import++;
                    }
                }
            }

            mysqli_commit($sql_link);
        }
        catch (Exception $e)
        {
            mysqli_rollback($sql_link);
            $import_result_error .= TEXT_IMPORT_CHRON_DB_ERROR . $e->getMessage();
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// Build result summary message

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);

$import_result .= "\n----\n";
$import_result .= TEXT_IMPORT_CHRON_FILE    . " : " . $import_data['file_import'] . "\n";
$import_result .= TEXT_IMPORT_CHRON_STATION . " : " . $station_all_array[$import_data['id_station']]['nom_station'] . "\n";
$import_result .= TEXT_IMPORT_CHRON_SERIES  . " : " . TEXT_IMPORT_REP_SERIES_LABEL . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DURATION  . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
$import_result .= TEXT_IMPORT_REP_NB_IMPORTED . " : " . $nb_data_import . "\n";
$import_result .= TEXT_IMPORT_CHRON_NB_ERRORS . " : " . (($num_ligne - 2) - $nb_data_import) . "\n";

// Delete the source CSV file after successful import
if (file_exists($chemin_file)) { unlink($chemin_file); }

// Update the import tracking record
$query = "UPDATE " . TABLE_IMPORT_SUIVI
       . " SET nb_data='"        . $nb_data_import . "',"
       . "     datetime_first='" . $date_debut     . "',"
       . "     datetime_end='"   . $date_fin       . "',"
       . "     import=1"
       . " WHERE id=" . $idImport;
tep_db_query($sql_link, $query);


// -----------------------------------------------
// Write import result to a .txt log file (ISO-8859-1 for legacy compatibility)

$text_import_result = $import_result . $import_result_error;

$resultFilename = $folder . $import_data['id_import'] . '_REP.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); sleep(1); }

file_put_contents(
    $resultFilename,
    mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8')
);


// -----------------------------------------------
// Record the import action in the database actions log

$type_action = 37;
$info_action = TEXT_ACTION_IMPORT_REP . " : " . $import_data['file_import']
             . " - " . TEXT_ACTION_IMPORT_STATION . " : "
             . $station_all_array[$import_data['id_station']]['nom_station'];

$query = "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure, id_import)
          VALUES (" . $import_data['id_user'] . ", '" . $type_action . "', '" . $info_action . "', '"
        . $import_data['dateheure'] . "', '" . $idImport . "')";
tep_db_query($sql_link, $query);


// -----------------------------------------------
// Return result to AJAX caller

echo json_encode([
    'text'   => $import_result,
    'nbData' => $nb_data_import,
]);
?>