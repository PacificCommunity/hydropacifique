<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Chronological data importer
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- Reads the CSV, validates rows, inserts data in batches, writes a result log file
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

$folder = '../../../data/uploads/'; // Base upload directory

$import_warning_ligne = '';
$import_result_error  = '';
$import_result        = '';

$num_ligne       = 0;
$nb_data_import  = 0;
$nb_error_date   = 0;
$nb_error_valeur = 0;
$nb_warning_qualite = 0;

$date_debut = '';
$date_fin   = '';

$db_load      = true;
$rows_deleted = 0;

$id_station  = 0;
$id_chron    = 0;
$id_ext_file = 0;

$data_tab  = [];
$meta_tab  = [];
$list_quality_encours = [];

// Retrieve the current highest meta ID to continue auto-incrementing in memory
$meta_id_encours = 0;
$sql_meta_id     = "SELECT MAX(id) as last_id FROM " . TABLE_DATA_META;
$meta_id_query   = tep_db_query($sql_link, $sql_meta_id);
$meta_id_tab     = tep_db_fetch_array($meta_id_query);
if ($meta_id_tab['last_id'] > 0) { $meta_id_encours = $meta_id_tab['last_id']; }

$startTime = microtime(true);


// -----------------------------------------------
// Query: Retrieve the import tracking record for this session

$sql_import   = "SELECT DISTINCT id, id_import, file_import, file_ext, dateheure, id_station, id_chron, id_user
                 FROM " . TABLE_IMPORT_SUIVI . "
                 WHERE id = " . $idImport;
$import_query = tep_db_query($sql_link, $sql_import);
$import_data  = tep_db_fetch_array($import_query);


// -----------------------------------------------
// CSV file reading and row validation

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        // Auto-detect column separator from the first line
        $firstLine  = fgets($handle);
        $separateur = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
        rewind($handle); // Reset file pointer to the beginning

        // Read and parse CSV rows using the detected separator
        while (($data = fgetcsv($handle, 10000, $separateur)) !== false)
        //while (($data = fgetcsv($handle, 10000, ';')) !== false)
        {
            $import_valid = true;
            $num_ligne++;

            // ---- Column 1: Date ----
            if (isset($data[0]))
            {
                $dateString = $data[0];

                // Strip UTF-8 BOM on the first line if present (invisible character that blocks parsing)
                if ($num_ligne === 1 && substr($dateString, 0, 3) === "\xEF\xBB\xBF")
                {
                    $dateString = substr($dateString, 3);
                }

                $data_date = isValidDateImport($dateString);
                if ($data_date == 'Invalid')
                {
                    $import_valid = false;
                    $nb_error_date++;
                }
            }
            else
            {
                $import_valid = false;
                $nb_error_file++;
            }

            // ---- Column 2: Value ----
            if ($import_valid)
            {
                if (isDecimal($data[1])) // isDecimal() normalises comma → dot and checks numeric validity
                {
                    $data_mesure = number_format((float) str_replace(',', '.', $data[1]), 4, '.', '');
                }
                else
                {
                    $import_valid = false;
                    $nb_error_valeur++;
                }
            }

            // ---- Column 3: Quality code ----
            $id_quality_cell = 'null';
            if ($import_valid)
            {
                $quality_ok = false;

                if (isset($data[2]) && isset($quality_data_array[$data[2]]))
                {
                    $id_quality_cell = $quality_data_array[$data[2]]['id_data_qualite'];
                    $quality_ok = true;
                }

                if (!$quality_ok) { $nb_warning_qualite++; }

                // Determine if a new meta record is needed for this quality code
                if (isset($list_quality_encours[$id_quality_cell]))
                {
                    $new_meta = false;
                }
                else
                {
                    $meta_id_encours++;
                    $list_quality_encours[$id_quality_cell] = $meta_id_encours;
                    $new_meta = true;
                }
            }

            // ---- Accumulate valid rows ----
            if ($import_valid)
            {
                $nb_data_import++;

                if (empty($date_debut)) { $date_debut = $data_date; } // First valid row sets start date
                $date_fin = $data_date; // Last valid row sets end date

                if ($new_meta)
                {
                    $meta_tab[] = [
                        'id'      => $list_quality_encours[$id_quality_cell],
                        'station' => $import_data['id_station'],
                        'chron'   => $import_data['id_chron'],
                        'quality' => $id_quality_cell,
                        'user'    => $import_data['id_user'],
                        'source'  => 'Import',
                        'file'    => $import_data['file_import'],
                        'obs'     => '',
                    ];
                }

                $data_tab[] = [
                    'date'    => $data_date,
                    'valeur'  => $data_mesure,
                    'meta_id' => $list_quality_encours[$id_quality_cell],
                ];
            }
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// Database insert: batch insert validated rows into TABLE_DATA_ALL and TABLE_DATA_META

if (isset($data_tab) && sizeof($data_tab))
{
    $import_result .= "\n----\n";
    $import_result .= TEXT_IMPORT_CHRON_FILE    . " : " . $import_data['file_import'] . "\n";
    $import_result .= TEXT_IMPORT_CHRON_STATION . " : " . $station_all_array[$import_data['id_station']]['nom_station'] . "\n";
    $import_result .= TEXT_IMPORT_CHRON_SERIES  . " : "
                    . $type_chron_array[$import_data['id_chron']]['init_type_data']
                    . " - "
                    . $type_chron_array[$import_data['id_chron']]['nom_type_data'] . "\n\n";

    $batchSize = 600; // Rows per batch insert

    mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

    try
    {
        // Delete existing data in the same date range before inserting
        $rows_deleted = deleteDataAndMeta($sql_link, $import_data['id_station'], $import_data['id_chron'], $date_debut, $date_fin);

        // ---- Batch insert: TABLE_DATA_ALL ----
        $query_insert_bloc_data = "INSERT INTO " . TABLE_DATA_ALL . " (dateheure, valeur, id_meta) VALUES ";
        $rows = [];

        foreach ($data_tab as $row)
        {
            $rows[] = "('" . $row['date'] . "', " . $row['valeur'] . ", " . $row['meta_id'] . ")";

            if (count($rows) >= $batchSize)
            {
                if (!mysqli_query($sql_link, $query_insert_bloc_data . implode(', ', $rows)))
                {
                    throw new Exception(mysqli_error($sql_link));
                }
                $rows = [];
            }
        }

        if (count($rows) > 0)
        {
            if (!mysqli_query($sql_link, $query_insert_bloc_data . implode(', ', $rows)))
            {
                throw new Exception(mysqli_error($sql_link));
            }
        }

        // ---- Batch insert: TABLE_DATA_META ----
        $query_insert_bloc_meta = "INSERT INTO " . TABLE_DATA_META
                                . " (id, id_station, id_typedata, id_codequal, id_user, source, file, obs) VALUES ";
        $rows_meta = [];

        foreach ($meta_tab as $meta_row)
        {
            $rows_meta[] = "('" . $meta_row['id'] . "', "
                         . $meta_row['station']  . ", "
                         . $meta_row['chron']    . ", "
                         . $meta_row['quality']  . ", "
                         . $meta_row['user']     . ", "
                         . "'" . mysqli_real_escape_string($sql_link, $meta_row['source']) . "', "
                         . "'" . mysqli_real_escape_string($sql_link, $meta_row['file'])   . "', "
                         . "'" . mysqli_real_escape_string($sql_link, $meta_row['obs'])    . "')";

            if (count($rows_meta) >= $batchSize)
            {
                if (!mysqli_query($sql_link, $query_insert_bloc_meta . implode(', ', $rows_meta)))
                {
                    throw new Exception(mysqli_error($sql_link));
                }
                $rows_meta = [];
            }
        }

        if (count($rows_meta) > 0)
        {
            if (!mysqli_query($sql_link, $query_insert_bloc_meta . implode(', ', $rows_meta)))
            {
                throw new Exception(mysqli_error($sql_link));
            }
        }

        mysqli_commit($sql_link);
    }
    catch (Exception $e)
    {
        $db_load = false;
        mysqli_rollback($sql_link);
        $import_result_error .= TEXT_IMPORT_CHRON_DB_ERROR . $e->getMessage();
    }
}


// -----------------------------------------------
// Build result summary message

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);

if ($db_load)
{
    $import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
    $import_result .= TEXT_IMPORT_CHRON_DURATION   . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
    $import_result .= TEXT_IMPORT_CHRON_NB_IMPORTED . " : " . $nb_data_import . "\n";
    $import_result .= TEXT_IMPORT_CHRON_NB_ERRORS   . " : " . ($num_ligne - $nb_data_import) . "\n";

    if ($rows_deleted > 0)
    {
        $import_result .= TEXT_IMPORT_CHRON_NB_DELETED . " : " . $rows_deleted . "\n";
    }

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
}
else
{
    $import_result .= TEXT_IMPORT_CHRON_FAIL . "\n\n";
    $import_result .= $import_result_error;
}

$import_result .= "\n";


// -----------------------------------------------
// Write detailed import log to a .txt file (ISO-8859-1 for legacy compatibility)

if ($nb_error_date    > 0) { $import_warning_ligne .= TEXT_IMPORT_WARN_DATE    . $nb_error_date    . " " . TEXT_IMPORT_WARN_LINE   . "\n"; }
if ($nb_error_valeur  > 0) { $import_warning_ligne .= TEXT_IMPORT_WARN_VALEUR  . $nb_error_valeur  . " " . TEXT_IMPORT_WARN_LINE   . "\n"; }
if ($nb_warning_qualite > 0) { $import_warning_ligne .= TEXT_IMPORT_WARN_QUALITE . $nb_warning_qualite . " " . TEXT_IMPORT_WARN_LINE_Q . "\n"; }

$text_import_result = $import_result . $import_result_error . $import_warning_ligne . "----\n";

$resultFilename = $folder
                . $import_data['id_import'] . '_'
                . $import_data['id']        . '_'
                . $type_chron_array[$import_data['id_chron']]['init_type_data'] . '.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); } // Remove any previous version

file_put_contents(
    $resultFilename,
    mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8'),
    FILE_APPEND
);


// -----------------------------------------------
// Record the import action in the database actions log

$type_action = 37;
$info_action = TEXT_ACTION_IMPORT . " : " . $import_data['file_import']
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