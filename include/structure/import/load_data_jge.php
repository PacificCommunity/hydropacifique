<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
GAUGING (Streamflow measurement) data importer - NC-specific format
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- Reads a CSV (2-row header + data rows): upserts records into TABLE_DATA_JGE
- Skips columns 15-16 (moulinet IDs) and 21-23 (SIG coords, quality code) — not yet wired
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
// CSV file reading, validation and upsert
// Row layout (0-indexed): [0] station code | [1] agent | [2] date | [3] time |
// [4-6] method/equipment | [7-14] hydraulic measurements |
// [15-16] moulinet IDs (not yet wired) | [17] obs | [18] agents | [19-20] GPS |
// [21-23] SIG coords + quality code (not yet wired) | [24] filename

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

        try
        {
            while (($data = fgetcsv($handle, null, ';')) !== false)
            {
                $import_valid = true;
                $num_ligne++;

                // Skip the first 2 rows (title + column headers)
                if ($num_ligne > 2)
                {
                    // ---- Column 3: Date ----
                    $dateheure_jge = isValidDateImport($data[2]);
                    if ($dateheure_jge == 'Invalid')
                    {
                        $dateheure_jge = '';
                        $import_valid  = false;
                        $import_result_error .= sprintf(TEXT_IMPORT_JGE_ERR_DATE, $num_ligne) . "\n";
                    }

                    if ($import_valid)
                    {
                        // ---- Numeric hydraulic measurement fields ----
                        $depouil_hmoy   = is_numeric($data[7])  ? (float)$data[7]  : null;
                        $depouil_q      = is_numeric($data[8])  ? (float)$data[8]  : null;
                        $depouil_sect   = is_numeric($data[9])  ? (float)$data[9]  : null;
                        $depouil_vmoy   = is_numeric($data[10]) ? (float)$data[10] : null;
                        $depouil_vsurf  = is_numeric($data[11]) ? (float)$data[11] : null;
                        $depouil_rh     = is_numeric($data[12]) ? (float)$data[12] : null;
                        $depouil_profmoy= is_numeric($data[13]) ? (float)$data[13] : null;
                        $depouil_nbvert = is_numeric($data[14]) ? (float)$data[14] : null;

                        // Columns 15-16: moulinet IDs — not yet wired to moulinet table
                        // Columns 21-23: SIG coords + quality code — not yet wired

                        // ---- Text fields ----
                        $obs_jge = htmlspecialchars(trim($data[17]), ENT_QUOTES, 'UTF-8');
                        $agents  = htmlspecialchars(trim($data[18]), ENT_QUOTES, 'UTF-8');
                        $x_gps   = htmlspecialchars(trim($data[19]), ENT_QUOTES, 'UTF-8');
                        $y_gps   = htmlspecialchars(trim($data[20]), ENT_QUOTES, 'UTF-8');
                        $fichier = htmlspecialchars(trim($data[24]), ENT_QUOTES, 'UTF-8');

                        // ---- Upsert: update if record exists for this station + datetime, else insert ----
                        $sql_jge  = "SELECT id FROM " . TABLE_DATA_JGE
                                  . " WHERE id_station = ? AND datetime = ?";
                        $stmt     = $sql_link->prepare($sql_jge);
                        $stmt->bind_param('is', $id_station, $dateheure_jge);
                        $stmt->execute();
                        $result   = $stmt->get_result();

                        if ($row = $result->fetch_assoc())
                        {
                            // Record exists — update it
                            $sql_update_jge = "UPDATE " . TABLE_DATA_JGE . "
                                SET datetime = ?, x_gps = ?, y_gps = ?,
                                    depouil_hmoy = ?, depouil_q = ?, depouil_sect = ?,
                                    depouil_vmoy = ?, depouil_vsurf = ?, depouil_rh = ?,
                                    depouil_profmoy = ?, depouil_nbvert = ?,
                                    code_qualite = ?, obs = ?, fichier = ?
                                WHERE id = ?";
                            $stmt_update = $sql_link->prepare($sql_update_jge);
                            $stmt_update->bind_param(
                                'sssddddddddissi',
                                $dateheure_jge, $x_gps, $y_gps,
                                $depouil_hmoy, $depouil_q, $depouil_sect,
                                $depouil_vmoy, $depouil_vsurf, $depouil_rh,
                                $depouil_profmoy, $depouil_nbvert,
                                $id_code_qualite, $obs_jge, $fichier,
                                $row['id']
                            );
                            $stmt_update->execute();
                            $stmt_update->close();
                        }
                        else
                        {
                            // No existing record — insert new JGE row
                            $sql_insert_jge = "INSERT INTO " . TABLE_DATA_JGE . " (
                                datetime, id_station, x_gps, y_gps,
                                depouil_hmoy, depouil_q, depouil_sect,
                                depouil_vmoy, depouil_vsurf, depouil_rh,
                                depouil_profmoy, depouil_nbvert,
                                code_qualite, obs, fichier
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt_insert = $sql_link->prepare($sql_insert_jge);
                            $stmt_insert->bind_param(
                                'sissddddddddiss',
                                $dateheure_jge, $id_station, $x_gps, $y_gps,
                                $depouil_hmoy, $depouil_q, $depouil_sect,
                                $depouil_vmoy, $depouil_vsurf, $depouil_rh,
                                $depouil_profmoy, $depouil_nbvert,
                                $id_code_qualite, $obs_jge, $fichier
                            );
                            $stmt_insert->execute();
                            $stmt_insert->close();
                        }

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
$import_result .= TEXT_IMPORT_CHRON_SERIES  . " : " . TEXT_IMPORT_JGE_SERIES_LABEL . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
$import_result .= TEXT_IMPORT_CHRON_DURATION  . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
$import_result .= TEXT_IMPORT_JGE_NB_IMPORTED . " : " . $nb_data_import . "\n";
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

$resultFilename = $folder . $import_data['id_import'] . '_JGE.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); sleep(1); }

file_put_contents(
    $resultFilename,
    mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8')
);


// -----------------------------------------------
// Record the import action in the database actions log

$type_action = 37;
$info_action = TEXT_ACTION_IMPORT_JGE . " : " . $import_data['file_import']
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