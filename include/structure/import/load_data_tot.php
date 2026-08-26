<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
TOT (Pluviometry totalizer) data importer - NC-specific format
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- Reads a 6-column CSV: datetime, start value, end value, delta, observation, quality code
- Inserts validated rows into TABLE_DATA_TOT
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
// Query: Chronological data types (indexed by id_data_type)

$sql_type_chron   = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
                     FROM " . TABLE_TYPE_DATA . "
                     ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$type_chron_tab['id_data_type']] = [
        'init_type_data'  => $type_chron_tab['init_type_data'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'unite'           => $type_chron_tab['unite'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
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

$import_result         = '';
$import_result_details = '';
$nb_data_import        = 0;
$nb_warning_qualite    = 0;
$num_ligne             = 0;
$date_debut            = '';
$date_fin              = '';
$db_load               = true;
$rows_deleted          = 0;
$id_station            = 0;
$id_chron              = 0;
$id_ext_file           = 0;
$data_tab              = [];

$startTime = microtime(true);


// -----------------------------------------------
// Query: Retrieve the import tracking record for this session

$sql_import   = "SELECT DISTINCT id_import, file_import, file_ext, dateheure, id_station, id_chron, id_user
                 FROM " . TABLE_IMPORT_SUIVI . "
                 WHERE id = " . $idImport;
$import_query = tep_db_query($sql_link, $sql_import);
$import_data  = tep_db_fetch_array($import_query);


// -----------------------------------------------
// CSV file reading and row validation
// Columns: [0] datetime | [1] start value | [2] end value | [3] delta | [4] obs | [5] quality code

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        while (($data = fgetcsv($handle, 10000, ';')) !== false)
        {
            $import_valid = true;
            $num_ligne++;

            // ---- Column 1: Date ----
            $data_date = '';

            if (isset($data[0]))
            {
                $dateString = $data[0];

                // Strip UTF-8 BOM on the first line if present
                if ($num_ligne === 1 && isset($row[0]) && substr($dateString, 0, 3) === "\xEF\xBB\xBF")
                {
                    $dateString = substr($dateString, 3);
                }

                $data_date = isValidDateImport($dateString);
                if ($data_date == 'Invalid')
                {
                    $import_valid = false;
                    $import_result_details .= sprintf(TEXT_IMPORT_TOT_ERR_DATE, $dateString) . "\n";
                    break;
                }
            }
            else
            {
                $import_valid = false;
                $import_result_details .= TEXT_IMPORT_TOT_ERR_NO_DATE . "\n";
                break;
            }

            // ---- Column 2: Start value ----
            $data_valeurDebut = 'null';
            if ($import_valid)
            {
                if ($data[1] !== '' && is_numeric($data[1]))
                {
                    $data_valeurDebut = (int)$data[1];
                }
                else
                {
                    $import_valid = false;
                    $import_result_details .= TEXT_IMPORT_TOT_ERR_COL2 . "\n";
                    break;
                }
            }

            // ---- Column 3: End value ----
            $data_valeurFin = 'null';
            if ($import_valid)
            {
                if ($data[2] !== '' && is_numeric($data[2]))
                {
                    $data_valeurFin = (int)$data[2];
                }
                else
                {
                    $import_valid = false;
                    $import_result_details .= TEXT_IMPORT_TOT_ERR_COL3 . "\n";
                    break;
                }
            }

            // ---- Column 4: Delta from previous ----
            // If empty, sentinel value 99999 is used
            $data_ecartPrecedent = 'null';
            if ($import_valid)
            {
                if ($data[3] !== '')
                {
                    if (is_numeric($data[3]))
                    {
                        $data_ecartPrecedent = (int)$data[3];
                    }
                    else
                    {
                        $import_valid = false;
                        $import_result_details .= TEXT_IMPORT_TOT_ERR_COL4 . "\n";
                        break;
                    }
                }
                else
                {
                    $data_ecartPrecedent = 99999; // Sentinel for missing delta
                }
            }

            // ---- Column 5: Observation (optional) ----
            $data_obs = '';
            if ($import_valid && isset($data[4]))
            {
                $data_obs = mb_convert_encoding($data[4], 'UTF-8', 'ISO-8859-1');
            }

            // ---- Column 6: Quality code (optional) ----
            $id_data_qualite = 'null';
            if ($import_valid)
            {
                if (isset($data[5]) && isset($quality_data_array[$data[5]]))
                {
                    $id_data_qualite = $quality_data_array[$data[5]]['id_data_qualite'];
                }
                else
                {
                    $nb_warning_qualite++;
                }
            }

            // ---- Accumulate valid rows ----
            if ($import_valid)
            {
                if ($num_ligne == 1) { $date_debut = $data_date; } // First valid row sets start date
                $date_fin = $data_date;                             // Last valid row sets end date

                $data_tab[] = [
                    'dateheure'      => $data_date,
                    'valeurDebut'    => $data_valeurDebut,
                    'valeurFin'      => $data_valeurFin,
                    'ecartPrecedent' => $data_ecartPrecedent,
                    'obs'            => $data_obs,
                    'qualite'        => $id_data_qualite,
                ];
            }
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// Database insert: delete existing range and batch-insert validated rows

if ($import_valid)
{
    mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

    try
    {
        // Step 1: Delete existing TOT records in the same date range
        $sql_delete_tot_data = "DELETE FROM " . TABLE_DATA_TOT
                             . " WHERE id_station = " . $import_data['id_station']
                             . " AND date_heure >= '" . $date_debut . "'"
                             . " AND date_heure <= '" . $date_fin   . "'";
        tep_db_query($sql_link, $sql_delete_tot_data);
        $rows_deleted = mysqli_affected_rows($sql_link);

        // Step 2: Insert validated rows
        $query_insert_tot_data = "INSERT INTO " . TABLE_DATA_TOT
                               . " (`id_station`, `date_heure`, `valeurDebut`, `valeurFin`, `ecartPrecedent`, `obs`, `id_data_qualite`)"
                               . " VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_tot_data = mysqli_prepare($sql_link, $query_insert_tot_data);

        foreach ($data_tab as $row)
        {
            mysqli_stmt_bind_param(
                $stmt_tot_data,
                'isdddsi',
                $import_data['id_station'],
                $row['dateheure'],
                $row['valeurDebut'],
                $row['valeurFin'],
                $row['ecartPrecedent'],
                $row['obs'],
                $row['qualite']
            );
            mysqli_stmt_execute($stmt_tot_data);
            $nb_data_import++;
        }

        // Step 3: Commit
        mysqli_commit($sql_link);
        $import_result_details .= TEXT_IMPORT_TOT_DATA_UPDATED . "\n";
    }
    catch (Exception $e)
    {
        mysqli_rollback($sql_link);
        $import_result_details .= TEXT_IMPORT_TOT_DB_ERROR . $e->getMessage();
    }
    finally
    {
        if (isset($stmt_tot_data) && $stmt_tot_data instanceof mysqli_stmt)
        {
            mysqli_stmt_close($stmt_tot_data);
        }
    }
}


// -----------------------------------------------
// Build result summary message

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);

$import_result .= "\n----\n";
$import_result .= TEXT_IMPORT_CHRON_FILE    . " : " . $import_data['file_import'] . "\n";
$import_result .= TEXT_IMPORT_CHRON_STATION . " : " . $station_all_array[$import_data['id_station']]['nom_station'] . "\n";
$import_result .= TEXT_IMPORT_CHRON_SERIES  . " : " . TEXT_IMPORT_TOT_SERIES_LABEL . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DURATION    . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
$import_result .= TEXT_IMPORT_CHRON_NB_IMPORTED . " : " . $nb_data_import . "\n";

if ($rows_deleted > 0)
{
    $import_result .= TEXT_IMPORT_CHRON_NB_DELETED . " : " . $rows_deleted . "\n";
}

$import_result .= TEXT_IMPORT_TOT_INFO_LABEL . " : \n" . $import_result_details;

$date_debut_tab = explode(' ', $date_debut);
$date_fin_tab   = explode(' ', $date_fin);
$import_result .= TEXT_IMPORT_CHRON_DATE_START . " : " . dateus_fr($date_debut_tab[0]) . "\n";
$import_result .= TEXT_IMPORT_CHRON_DATE_END   . " : " . dateus_fr($date_fin_tab[0])   . "\n";

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

$resultFilename = $folder . $import_data['id_import'] . '_TOT.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); sleep(1); }

file_put_contents(
    $resultFilename,
    mb_convert_encoding($import_result, 'ISO-8859-1', 'UTF-8')
);


// -----------------------------------------------
// Record the import action in the database actions log

$type_action = 4;
$info_action = TEXT_ACTION_IMPORT_TOT . " : " . $import_data['file_import']
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