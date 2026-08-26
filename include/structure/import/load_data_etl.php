<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
ETL (Rating curve) data importer
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- CSV format: paired columns (datetime_start | datetime_end) per curve,
  followed by (height | flow) data rows — one pair per rating curve
- Replaces all existing ETL data for the station, then inserts fresh records
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

$import_result         = '';
$import_result_details = '';
$nb_data_import        = 0;
$num_ligne             = 0;
$date_debut            = '';
$date_fin              = '';
$rows_deleted          = 0;
$id_station            = 0;
$id_ext_file           = 0;
$import_valid          = true;

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
// CSV reading: pivot the file into column arrays
// Each pair of columns represents one rating curve:
//   even column = height values (row 0 = datetime_start)
//   odd column  = flow values   (row 0 = datetime_end)

$columns = [];

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        $num_ligne = 0;

        while (($row = fgetcsv($handle, null, ';')) !== false)
        {
            $num_ligne++;

            // Strip UTF-8 BOM from the first cell of the first row if present
            if ($num_ligne === 1 && isset($row[0]) && substr($row[0], 0, 3) === "\xEF\xBB\xBF")
            {
                $row[0] = substr($row[0], 3);
            }

            foreach ($row as $index => $value)
            {
                $columns[$index][] = $value; // Accumulate each column as an array of values
            }
        }

        // Validate that column count is even (each curve requires a pair)
        if (count($columns) % 2 !== 0)
        {
            $import_valid = false;
            $import_result_details .= TEXT_IMPORT_ETL_ERR_ODD_COLS . "\n";
        }

        fclose($handle);
    }
    else
    {
        $import_valid = false;
    }
}
else
{
    $import_valid = false;
}


// -----------------------------------------------
// Validate and build in-memory data structures for each curve pair

$temp_etl      = [];
$temp_data_etl = [];

if ($import_valid)
{
    $num_etl = 0;

    for ($i = 0; $i < count($columns); $i += 2)
    {
        $num_etl++;

        $hauteur_tab = array_slice($columns[$i],     1); // Skip header row
        $debit_tab   = array_slice($columns[$i + 1], 1);

        // ---- Validate matched row counts ----
        if (count($hauteur_tab) !== count($debit_tab))
        {
            $import_valid = false;
            $import_result_details .= sprintf(
                TEXT_IMPORT_ETL_ERR_HQ_MISMATCH,
                $columns[$i][0],
                $columns[$i + 1][0]
            ) . "\n";
            break;
        }

        // ---- Validate datetime_start (header of even column) ----
        $datetimeFirstString = $columns[$i][0];
        $datetimeFirst       = isValidDateImport($datetimeFirstString);
        if ($datetimeFirst == 'Invalid')
        {
            $import_valid = false;
            $import_result_details .= sprintf(TEXT_IMPORT_ETL_ERR_DATE, $datetimeFirstString) . "\n";
            break;
        }

        // ---- Validate datetime_end (header of odd column) ----
        $datetimeEndString = $columns[$i + 1][0];
        $datetimeEnd       = isValidDateImport($datetimeEndString);
        if ($datetimeEnd == 'Invalid')
        {
            $import_valid = false;
            $import_result_details .= sprintf(TEXT_IMPORT_ETL_ERR_DATE, $datetimeEndString) . "\n";
            break;
        }

        if (!$import_valid) { continue; }

        $temp_etl[$num_etl] = [
            'datetime_first' => $datetimeFirst,
            'datetime_end'   => $datetimeEnd,
        ];

        // ---- Validate and accumulate height/flow pairs ----
        foreach ($hauteur_tab as $index => $hauteur)
        {
            $debit = $debit_tab[$index];

            if ($hauteur !== '' && $debit !== '')
            {
                if (is_numeric($hauteur))
                {
                    $hauteur_verif = (float)$hauteur;
                }
                else
                {
                    $import_valid = false;
                    $import_result_details .= sprintf(TEXT_IMPORT_ETL_ERR_HAUTEUR, $index, $hauteur) . "\n";
                    break;
                }

                if (is_numeric($debit))
                {
                    $debit_verif = (float)$debit;
                }
                else
                {
                    $import_valid = false;
                    $import_result_details .= TEXT_IMPORT_ETL_ERR_DEBIT . "\n";
                    break;
                }

                $temp_data_etl[$num_etl][] = [
                    'hauteur' => $hauteur_verif,
                    'debit'   => $debit_verif,
                ];
            }
        }
    }
}


// -----------------------------------------------
// Database transaction: replace all ETL data for this station

if ($import_valid)
{
    mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

    try
    {
        // Step 1: Collect all existing ETL IDs for this station
        $deleted_etl_ids  = [];
        $select_etl       = "SELECT id FROM " . TABLE_DATA_ETL . " WHERE id_station = " . intval($id_station);
        $query_select_etl = tep_db_query($sql_link, $select_etl);

        while ($etl_tab = tep_db_fetch_array($query_select_etl))
        {
            $deleted_etl_ids[] = $etl_tab['id'];
        }

        // Step 2: Delete associated height/flow data rows
        if (!empty($deleted_etl_ids))
        {
            $ids_to_delete = implode(',', $deleted_etl_ids);
            tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl IN (" . $ids_to_delete . ")");
        }

        // Step 3: Delete the ETL curve headers
        tep_db_query($sql_link, "DELETE FROM " . TABLE_DATA_ETL . " WHERE id_station = " . intval($id_station));

        // Step 4: Insert new ETL curve headers
        $insert_etl  = "INSERT INTO " . TABLE_DATA_ETL . " (`id_station`, `datetime_first`, `datetime_end`) VALUES (?, ?, ?)";
        $stmt_etl    = mysqli_prepare($sql_link, $insert_etl);

        foreach ($temp_etl as $num_etl => $etl_info)
        {
            mysqli_stmt_bind_param($stmt_etl, 'iss',
                $id_station,
                $etl_info['datetime_first'],
                $etl_info['datetime_end']
            );
            mysqli_stmt_execute($stmt_etl);

            $new_etl_id = mysqli_insert_id($sql_link);

            // Step 5: Insert height/flow measurements for this curve
            $insert_etl_data = "INSERT INTO " . TABLE_DATA_ETL_DATA . " (`id_etl`, `hauteur`, `debit`) VALUES (?, ?, ?)";
            $stmt_etl_data   = mysqli_prepare($sql_link, $insert_etl_data);

            foreach ($temp_data_etl[$num_etl] as $measurement)
            {
                mysqli_stmt_bind_param($stmt_etl_data, 'idd',
                    $new_etl_id,
                    $measurement['hauteur'],
                    $measurement['debit']
                );
                mysqli_stmt_execute($stmt_etl_data);
                $nb_data_import++;
            }

            mysqli_stmt_close($stmt_etl_data);
        }

        // Step 6: Commit
        mysqli_commit($sql_link);
        $import_result_details .= TEXT_IMPORT_ETL_DATA_UPDATED . "\n";
    }
    catch (Exception $e)
    {
        mysqli_rollback($sql_link);
        $import_result_details .= TEXT_IMPORT_ETL_DB_ERROR . $e->getMessage();
    }
    finally
    {
        if (isset($stmt_etl) && $stmt_etl instanceof mysqli_stmt)
        {
            mysqli_stmt_close($stmt_etl);
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
$import_result .= TEXT_IMPORT_CHRON_SERIES  . " : " . TEXT_IMPORT_ETL_SERIES_LABEL . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DURATION    . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
$import_result .= TEXT_IMPORT_CHRON_NB_IMPORTED . " : " . $nb_data_import . "\n";
$import_result .= TEXT_IMPORT_TOT_INFO_LABEL    . " : \n" . $import_result_details . "\n";

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

$text_import_result = $import_result;

$resultFilename = $folder . $import_data['id_import'] . '_ETL.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); sleep(1); }

file_put_contents(
    $resultFilename,
    mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8')
);


// -----------------------------------------------
// Record the import action in the database actions log

$type_action = 33;
$info_action = TEXT_ACTION_IMPORT_ETL . " : " . $import_data['file_import']
             . " - " . TEXT_ACTION_IMPORT_STATION . " : "
             . $station_all_array[$import_data['id_station']]['nom_station'];

$query = "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure, id_import)
          VALUES (" . $import_data['id_user'] . ", '" . $type_action . "', '" . $info_action . "', '"
        . $import_data['dateheure'] . "', '" . $idImport . "')";
tep_db_query($sql_link, $query);


// -----------------------------------------------
// Return result to AJAX caller

echo json_encode([
    'text'   => $text_import_result,
    'nbData' => $nb_data_import,
]);
?>