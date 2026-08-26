<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Server-side file loader - called via AJAX from import.php
- Matches station code from filename against the database
- Matches series initial from filename against the database
- Registers the import record and returns the HTML table row for tracking
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


// -----------------------------------------------
// Query: Importable file format definitions
// Note: 'algo' field may contain executable parsing logic — handle with care (security risk)

$sql_import_files   = "SELECT DISTINCT id, name_ext, multi_feuil, separateur, description, algo
                       FROM " . TABLE_IMPORT_FILES . "
                       ORDER BY id ASC";
$import_files_query = tep_db_query($sql_link, $sql_import_files);
while ($import_files_tab = tep_db_fetch_array($import_files_query))
{
    $name_ext = html_entity_decode($import_files_tab['name_ext'] ?? '');

    $import_files[$name_ext] = [
        'id'          => $import_files_tab['id'],
        'multi_feuil' => $import_files_tab['multi_feuil'],
        'separateur'  => $import_files_tab['separateur'],
        'description' => html_entity_decode($import_files_tab['description'] ?? ''),
        'algo'        => $import_files_tab['algo'], // WARNING: may contain executable parsing code
    ];
}


// -----------------------------------------------
// Query: All stations

$sql_station_all   = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
                      FROM " . TABLE_STATION;
$station_all_query = tep_db_query($sql_link, $sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{
    $station_all_array[$station_all['code_station']] = [
        'id_station'   => $station_all['id_station'],
        'nom_station'  => $station_all['nom_station'],
        'station_type' => $station_all['station_type'],
    ];
}


// -----------------------------------------------
// Query: Chronological data types (CI, PI, CIE, etc.)

$sql_type_chron   = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, unite
                     FROM " . TABLE_TYPE_DATA . "
                     ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $type_chron_array[$type_chron_tab['init_type_data']] = [
        'id_data_type'    => $type_chron_tab['id_data_type'],
        'nom_type_data'   => $type_chron_tab['nom_type_data'],
        'unite'           => $type_chron_tab['unite'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
    ];
}


// -----------------------------------------------
// Register non-standard importable types (not stored in TABLE_TYPE_DATA)

$type_chron_array['RA']  = ['id_data_type' => 0, 'nom_type_data' => TEXT_CHRON_TYPE_RA,  'unite' => '-', 'id_eq_type_data' => 0];
$type_chron_array['JGE'] = ['id_data_type' => 0, 'nom_type_data' => TEXT_CHRON_TYPE_JGE, 'unite' => '-', 'id_eq_type_data' => 0];
$type_chron_array['ETL'] = ['id_data_type' => 0, 'nom_type_data' => TEXT_CHRON_TYPE_ETL, 'unite' => '-', 'id_eq_type_data' => 0];
$type_chron_array['REP'] = ['id_data_type' => 0, 'nom_type_data' => TEXT_CHRON_TYPE_REP, 'unite' => '-', 'id_eq_type_data' => 0];


// -----------------------------------------------
// Initialize processing variables

$import_valid = true;
$msg_info     = '';
$tab_html     = '';
$id_station   = 0;
$id_chron     = 0;
$id_ext_file  = 0;


// -----------------------------------------------
// Handle uploaded file (POST + file present)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']))
{
    $file = $_FILES['file'];

    // Retrieve AJAX session metadata (id_user, id_import)
    if (isset($_POST['meta']))
    {
        $metaData  = json_decode($_POST['meta'], true);
        $id_user   = $metaData['id_user'];
        $id_import = $metaData['id_import'];
    }

    if ($file['error'] === UPLOAD_ERR_OK)
    {
        $fileName    = basename($file['name']);
        $tempPath    = $file['tmp_name'];
        $destination = '../../../data/uploads/files/' . $fileName;

        $msg_info .= "\n" . TEXT_LOAD_FILE_LABEL . " : " . $fileName;

        if (move_uploaded_file($tempPath, $destination))
        {
            $decoup_file = explode('.', $fileName);
            $titre_file  = explode('_', $decoup_file[0]);
            $ext_file    = end($decoup_file); // File extension is the last segment

            // ---- Validate file extension ----
            if (isset($import_files[$ext_file]))
            {
                $id_ext_file = $import_files[$ext_file]['id'];

                // ---- Validate station code from filename ----
                if (isset($station_all_array[$titre_file[0]]))
                {
                    $id_station   = $station_all_array[$titre_file[0]]['id_station'];
                    $code_station = $titre_file[0];
                    $nom_station  = $station_all_array[$titre_file[0]]['nom_station'];
                    $type_data    = $station_all_array[$titre_file[0]]['station_type'];

                    // ---- Single-sheet file (one series per file) ----
                    if ($import_files[$ext_file]['multi_feuil'] == 0)
                    {
                        // ---- Validate series initial from filename ----
                        if (isset($type_chron_array[$titre_file[1]]))
                        {
                            $id_chron    = $type_chron_array[$titre_file[1]]['id_data_type'];
                            $init_chron  = $titre_file[1];
                            $nom_chron   = $type_chron_array[$titre_file[1]]['nom_type_data'];
                            $unite_chron = $type_chron_array[$titre_file[1]]['unite'];

                            // Register import tracking record
                            $datetime = date('Y-m-d H:i:s', time());

                            $update_import = "INSERT INTO " . TABLE_IMPORT_SUIVI
                                           . " (id_import, file_import, file_ext, dateheure, id_station, id_chron, id_user, import)"
                                           . " VALUE ('" . $id_import . "', '" . $fileName . "', '" . $ext_file . "', '"
                                           . $datetime . "', '" . $id_station . "', '" . $id_chron . "', '" . $id_user . "', 0)";

                            tep_db_query($sql_link, $update_import);
                            $last_insert_id = mysqli_insert_id($sql_link);

                            // Append conformity message to progress log
                            $msg_info .= TEXT_LOAD_FILE_CONFORM . "\n";
                            $msg_info .= TEXT_LOAD_FILE_STATION_LABEL . " : " . $nom_station . "\n";
                            $msg_info .= TEXT_LOAD_FILE_CHRON_LABEL   . " : " . $init_chron . " - " . $nom_chron . "\n";

                            $graph_chron_link_graph = $id_station . "_" . $type_data . "_" . $id_chron;

                            // ---- Build HTML table row for import tracking ----
                            $tab_html .= "<tr>
                                <td style='height:25px;'>" . $fileName . "</td>
                                <td style='height:25px;cursor:pointer;' title='" . $nom_station . "'>"
                                    . $code_station . " - " . affichemots($nom_station, 8) . "
                                </td>
                                <td style='height:25px;text-align:center;cursor:pointer;' title='" . $nom_chron . "'>
                                    <input type='text' id='dataInit_" . $last_insert_id . "' value='" . $init_chron . "'"
                                        . " readonly style='border:none;font-size:11px;cursor:pointer;'>
                                </td>
                                <td style='height:25px;text-align:center;'>" . $unite_chron . "</td>
                                <td style='height:25px;text-align:center;'>
                                    <input type='checkbox' name='checkFile[]' value='import_" . $last_insert_id . "' checked>
                                </td>
                                <td style='height:25px;text-align:center;'>
                                    <img src='" . DIR_WS_IMG_ICO . "check.png'  style='width:15px;display:none;' id='check_"   . $last_insert_id . "' title='" . TEXT_LOAD_TIP_DATA_OK   . "'>
                                    <img src='" . DIR_WS_IMG_ICO . "delete.png' style='width:15px;display:none;' id='nocheck_" . $last_insert_id . "' title='" . TEXT_LOAD_TIP_DATA_FAIL . "'>
                                    <img src='" . DIR_WS_IMG     . "wait.gif'   style='width:20px;display:none;' id='wait_"    . $last_insert_id . "' title='" . TEXT_LOAD_TIP_DATA_WAIT . "'>
                                </td>
                                <td style='height:25px;text-align:center;'>
                                    <a href='" . DIR_WS_DATA_IMPORT . $id_import . '_' . $last_insert_id . "_" . $init_chron . ".txt' target='blank_'>
                                        <img src='" . DIR_WS_IMG_ICO . "detail.png' style='width:15px;display:none;' id='note_" . $last_insert_id . "' title='" . TEXT_LOAD_TIP_DETAIL . "'>
                                    </a>
                                </td>";

                            // ---- Graph link column: only for standard series (not RA/JGE/ETL/REP) ----
                            if (!in_array($init_chron, ['RA', 'JGE', 'ETL', 'REP']))
                            {
                                $tabForm     = [['name' => 'graph_chron', 'value' => $graph_chron_link_graph]];
                                $tabFormJson = htmlspecialchars(json_encode($tabForm), ENT_QUOTES, 'UTF-8');

                                $tab_html .= "<td style='height:25px;text-align:center;'>
                                    <img src='" . DIR_WS_IMG_ICO . "graph.png'
                                         style='width:15px;cursor:pointer;display:none;'
                                         id='graph_" . $last_insert_id . "'
                                         title='" . TEXT_LOAD_TIP_GRAPH . "'
                                         onclick=\"event.preventDefault();linkSubmitForm('data_chron.php', " . $tabFormJson . ");\">
                                </td>";
                            }
                            else
                            {
                                $tab_html .= "<td><span id='graph_" . $last_insert_id . "'>-</span></td>";
                            }

                            $tab_html .= "</tr>";
                        }
                        else
                        {
                            $import_valid = false;
                            $msg_info .= "\n" . TEXT_LOAD_ERR_NO_CHRON;
                        }
                    }
                }
                else
                {
                    $import_valid = false;
                    $msg_info .= "\n" . TEXT_LOAD_ERR_NO_STATION;
                }
            }
            else
            {
                $import_valid = false;
                $msg_info .= "\n" . TEXT_LOAD_ERR_BAD_EXT . $ext_file;
            }
        }
        else
        {
            $import_valid = false;
            $msg_info .= TEXT_LOAD_ERR_MOVE;
        }
    }
    else
    {
        $import_valid = false;
        $msg_info .= TEXT_LOAD_ERR_UPLOAD . $file['error'];
    }
}
else
{
    $import_valid = false;
    $msg_info .= TEXT_LOAD_ERR_NO_FILE;
}

$msg_info .= "\n"; // Trailing newline for log readability

echo json_encode([
    'msg_info' => $msg_info,
    'tab_html' => $tab_html,
]);
?>