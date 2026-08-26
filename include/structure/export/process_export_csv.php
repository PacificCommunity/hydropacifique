<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
CSV export generator
- Receives a JSON payload from an AJAX request
- Writes a CSV file directly to the server as a background async task
- Handles all data types: standard series, RA, JGE, ETL, LAB, TOT, REP, CTE, DIAC
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
// Parse incoming JSON payload from AJAX request

$jsonData = file_get_contents('php://input');
$data     = json_decode($jsonData, true);

$Filename        = $data['Filename'];
$folder_download = $data['folder_download'];
$chemin_folder   = $data['chemin_folder'];
$id_station      = $data['id_station'];
$code_station    = $data['code_station'];
$nom_station     = $data['nom_station'];
$init_chron      = $data['init_chron']; // Series type identifier: RA, LAB, TOT, JGE, ETL, REP, CTE, DIAC, or standard
$sql_chron       = $data['sql_chron'];
$nbdata_chron    = $data['nbdata_chron'];
$entete_col      = $data['entete_col'];


// -----------------------------------------------
// Query: Station equipment type (Pluvio / Hydro / Piezo)

$sql_station   = "SELECT DISTINCT station_type FROM " . TABLE_STATION . " WHERE id_station = " . $id_station;
$station_query = tep_db_query($sql_link, $sql_station);
$station_tab   = tep_db_fetch_array($station_query);
$type_eq       = $station_tab['station_type'];


// -----------------------------------------------
// Query: Data quality codes

$sql_quality   = "SELECT DISTINCT id_data_qualite, init_qualite_data FROM " . TABLE_DATA_QUALITE;
$quality_query = tep_db_query($sql_link, $sql_quality);
while ($quality_tab = tep_db_fetch_array($quality_query))
{
    $quality_array[$quality_tab['id_data_qualite']] = html_entity_decode($quality_tab['init_qualite_data'] ?? '');
}


// -----------------------------------------------
// Resolve output file path and create directory if needed

$chemin_folder_process = '../../../' . $chemin_folder;
$csvFilename           = $chemin_folder_process . '/' . $Filename;

if (!is_dir($chemin_folder_process))
{
    // Create directory recursively with standard permissions
    mkdir($chemin_folder_process, 0755, true);
}


// -----------------------------------------------
// Start execution timer

$total_time = 0;
$startTime  = microtime(true);


// -----------------------------------------------
// STANDARD SERIES EXPORT
// Writes: timestamp, value, quality code
// Excludes special series types handled separately below

if (!in_array($init_chron, ['RA', 'JGE', 'ETL', 'LAB', 'TOT', 'REP', 'CTE', 'DIAC']))
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for correct character encoding in Excel

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $quality_valeur = '';
            if (isset($quality_array[$data_chron_tab['id_codequal']]) && tep_not_null($quality_array[$data_chron_tab['id_codequal']]))
            {
                $quality_valeur = $quality_array[$data_chron_tab['id_codequal']];
            }

            fputcsv($handle, [
                $data_chron_tab['dateheure'],
                $data_chron_tab['valeur'],
                $quality_valeur,
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// LAB SERIES EXPORT
// Writes: timestamp, value, total, quality code, observation

if ($init_chron === 'LAB')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $quality_valeur = '';
            if (isset($quality_array[$data_chron_tab['id_codequal']]) && tep_not_null($quality_array[$data_chron_tab['id_codequal']]))
            {
                $quality_valeur = $quality_array[$data_chron_tab['id_codequal']];
            }

            fputcsv($handle, [
                $data_chron_tab['dateheure'],
                $data_chron_tab['valeur'],
                $data_chron_tab['total'],
                $quality_valeur,
                $data_chron_tab['obs'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// TOT SERIES EXPORT
// Writes: timestamp, start value, end value, delta value, quality code, observation

if ($init_chron === 'TOT')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $quality_valeur = '';
            if (isset($quality_array[$data_chron_tab['id_codequal']]) && tep_not_null($quality_array[$data_chron_tab['id_codequal']]))
            {
                $quality_valeur = $quality_array[$data_chron_tab['id_codequal']];
            }

            fputcsv($handle, [
                $data_chron_tab['dateheure'],
                $data_chron_tab['valeurDebut'],
                $data_chron_tab['valeurFin'],
                $data_chron_tab['valeur'],
                $quality_valeur,
                $data_chron_tab['obs'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// RA (Activity Report) EXPORT
// Column headers vary by equipment type: Pluvio (1), Hydro (11), Piezo (5)

if ($init_chron === 'RA')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        // Title row
        fputcsv($handle, [TEXT_CSV_TITLE_RA . ' ' . $nom_station . ' - ' . $code_station], ';');

        // Column headers depend on equipment type
        if ($type_eq == 1) // Pluvio
        {
            $data = [
                TEXT_CSV_COL_STATION_NUM,       TEXT_CSV_COL_STATION_NAME,
                TEXT_CSV_COL_PLU_RELEVE_DATE,   TEXT_CSV_COL_PLU_RELEVE_HEURE,
                TEXT_CSV_COL_PLU_APP_K7,        TEXT_CSV_COL_PLU_APP_TYPE,
                TEXT_CSV_COL_PLU_APP_NUM,        TEXT_CSV_COL_PLU_APP_HEURE,
                TEXT_CSV_COL_PLU_TOT_TYPE,      TEXT_CSV_COL_PLU_TOT_ARRIVE,
                TEXT_CSV_COL_PLU_TOT_DEPART,    TEXT_CSV_COL_PLU_TOT_HEURE,
                TEXT_CSV_COL_PLU_DUR_JJ,        TEXT_CSV_COL_PLU_DUR_HH,        TEXT_CSV_COL_PLU_DUR_MM,        TEXT_CSV_COL_PLU_DUR_SS,
                TEXT_CSV_COL_PLU_LAST_JJ,       TEXT_CSV_COL_PLU_LAST_HH,       TEXT_CSV_COL_PLU_LAST_MM,       TEXT_CSV_COL_PLU_LAST_SS,
                TEXT_CSV_COL_PLU_NB_BASC,       TEXT_CSV_COL_PLU_NB_OCTET,
                TEXT_CSV_COL_PLU_BAT_NUM,        TEXT_CSV_COL_PLU_BAT_TENSION,
                TEXT_CSV_COL_PLU_K7_NUM,         TEXT_CSV_COL_PLU_K7_INIT,      TEXT_CSV_COL_PLU_K7_FIRST_BASC,
                TEXT_CSV_COL_PLU_CUMUL_TOT,     TEXT_CSV_COL_PLU_CUMUL_PLU,     TEXT_CSV_COL_PLU_DIFF,
                TEXT_CSV_COL_PLU_CALAGE_HEURE,  TEXT_CSV_COL_PLU_TEST_AUGET,
                TEXT_CSV_COL_PLU_BOUCHAGE,      TEXT_CSV_COL_PLU_DEBROUSSAILLAGE,
                TEXT_CSV_COL_PLU_EAU_BAT,       TEXT_CSV_COL_PLU_HUILE_TOT,
                TEXT_CSV_COL_PLU_TRANSFERT,     TEXT_CSV_COL_PLU_MEM_EFFACEE,
                TEXT_CSV_COL_OBS,               TEXT_CSV_COL_FUTURE,            TEXT_CSV_COL_AGENTS,
                TEXT_CSV_COL_COORD_X,           TEXT_CSV_COL_COORD_Y,
                TEXT_CSV_COL_PLU_COMMENTAIRE,   TEXT_CSV_COL_PLU_NOM_OE2,
                TEXT_CSV_COL_FILE_NAME,         TEXT_CSV_COL_FILE_OBS,
                TEXT_CSV_COL_PRE_EVENT,         TEXT_CSV_COL_EVENT,
            ];
        }

        if ($type_eq == 11) // Hydro
        {
            $data = [
                TEXT_CSV_COL_STATION_NUM,        TEXT_CSV_COL_STATION_NAME,
                TEXT_CSV_COL_PLU_RELEVE_DATE,    TEXT_CSV_COL_PLU_RELEVE_HEURE,
                TEXT_CSV_COL_PLU_APP_K7,         TEXT_CSV_COL_PLU_APP_TYPE,
                TEXT_CSV_COL_PLU_APP_NUM,         TEXT_CSV_COL_PLU_APP_HEURE,
                TEXT_CSV_COL_HYD_COTE_HEURE,    TEXT_CSV_COL_HYD_COTE_SONDE,
                TEXT_CSV_COL_HYD_COTE_ECHL,     TEXT_CSV_COL_HYD_COTE_ECHL2,
                TEXT_CSV_COL_PLU_DUR_JJ,         TEXT_CSV_COL_PLU_DUR_HH,         TEXT_CSV_COL_PLU_DUR_MM,         //TEXT_CSV_COL_PLU_DUR_SS,
                TEXT_CSV_COL_PLU_LAST_JJ,        TEXT_CSV_COL_PLU_LAST_HH,        TEXT_CSV_COL_PLU_LAST_MM,        //TEXT_CSV_COL_PLU_LAST_SS,
                TEXT_CSV_COL_HYD_NUM_SONDE,      TEXT_CSV_COL_HYD_NB_OCTET,
                TEXT_CSV_COL_HYD_BAT_NUM,        TEXT_CSV_COL_HYD_BAT_TENSION,
                TEXT_CSV_COL_HYD_K7_NUM,         TEXT_CSV_COL_HYD_K7_INIT,        TEXT_CSV_COL_HYD_K7_SONDE,
                TEXT_CSV_COL_HYD_CTRL_HECH_HSPI, TEXT_CSV_COL_HYD_CTRL_RECAL_SONDE, TEXT_CSV_COL_HYD_CTRL_RECAL_DATA,
                TEXT_CSV_COL_HYD_PURGE,          TEXT_CSV_COL_HYD_JAUGEAGE,
                TEXT_CSV_COL_HYD_DEBROUSSAILLAGE, TEXT_CSV_COL_HYD_EAU_BAT,
                TEXT_CSV_COL_HYD_TRANSFERT,      TEXT_CSV_COL_HYD_MEM_EFFACEE,
                TEXT_CSV_COL_OBS,                TEXT_CSV_COL_FUTURE,              TEXT_CSV_COL_AGENTS,
                TEXT_CSV_COL_COORD_X,            TEXT_CSV_COL_COORD_Y,
                TEXT_CSV_COL_FILE_NAME,          TEXT_CSV_COL_FILE_OBS,
                TEXT_CSV_COL_PRE_EVENT,          TEXT_CSV_COL_EVENT,
            ];
        }

        if ($type_eq == 5) // Piezo
        {
            $data = [
                TEXT_CSV_COL_STATION_NUM,           TEXT_CSV_COL_STATION_NAME,
                TEXT_CSV_COL_PLU_RELEVE_DATE,       TEXT_CSV_COL_PLU_RELEVE_HEURE,
                TEXT_CSV_COL_PIE_SONDE_FIXE_TYPE,   TEXT_CSV_COL_PIE_SONDE_FIXE_NUM,    TEXT_CSV_COL_PIE_SONDE_FIXE_HEURE,
                TEXT_CSV_COL_PIE_SONDE_MAN_TYPE,    TEXT_CSV_COL_PIE_SONDE_MAN_NUM,
                TEXT_CSV_COL_PIE_MESURE_TOIT_M,     TEXT_CSV_COL_PIE_MESURE_COND,       TEXT_CSV_COL_PIE_MESURE_TEMP,
                TEXT_CSV_COL_PIE_MAN_TOIT_M,        TEXT_CSV_COL_PIE_MAN_TOIT_CM,       TEXT_CSV_COL_PIE_PROF_OUV,
                TEXT_CSV_COL_PIE_CTRL_DIFF,         TEXT_CSV_COL_PIE_CTRL_RECAL_SONDE,  TEXT_CSV_COL_PIE_CTRL_RECAL_HEURE,
                TEXT_CSV_COL_PIE_MEM_NB,            TEXT_CSV_COL_PIE_MEM_EFFACEE,
                TEXT_CSV_COL_PIE_BAT,
                TEXT_CSV_COL_PIE_NATURE_REPERE,     TEXT_CSV_COL_PIE_Z_REPERE,
                TEXT_CSV_COL_PIE_POMPAGE_ENCOURS,   TEXT_CSV_COL_PIE_POMPAGE_PROCHE,
                TEXT_CSV_COL_PIE_PLUIE_CRUE,        TEXT_CSV_COL_PIE_TEMPS_SEC,
                TEXT_CSV_COL_OBS,                   TEXT_CSV_COL_FUTURE,                TEXT_CSV_COL_AGENTS,
                TEXT_CSV_COL_COORD_X,               TEXT_CSV_COL_COORD_Y,
                TEXT_CSV_COL_FILE_NAME,             TEXT_CSV_COL_FILE_OBS,
                TEXT_CSV_COL_PRE_EVENT,             TEXT_CSV_COL_EVENT,
            ];
        }

        fputcsv($handle, $data, ';');

        // -----------------------------------------------
        // RA data rows

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $tab_date_heure_ra = explode(' ', $data_chron_tab['date_heure_ra'] ?? '');
            $date_ra  = str_replace('-', '/', $tab_date_heure_ra[0]);
            $heure_ra = $tab_date_heure_ra[1];

            // Sanitize text fields: remove line breaks
            $data_chron_tab['ra_obs']   = isset($data_chron_tab['ra_obs'])   ? str_replace(["\r", "\n"], ' ', $data_chron_tab['ra_obs'])   : '';
            $data_chron_tab['ra_futur'] = isset($data_chron_tab['ra_futur']) ? str_replace(["\r", "\n"], ' ', $data_chron_tab['ra_futur']) : '';

            // Convert boolean-style fields: replace falsy values with empty string for cleaner CSV output
            foreach (['plu_ra_bouchage', 'ra_debroussaillage', 'ra_eau_batterie', 'plu_ra_huile_tot',
                      'ra_transfert_data', 'ra_delete_memory', 'hydro_purge_sonde', 'hydro_ra_jaugeage',
                      'piezo_pompage_encours', 'piezo_pompage_proche', 'piezo_pluie_crue', 'piezo_temps_sec',
                      'pre_marquant', 'fait_marquant'] as $field)
            {
                if (isset($data_chron_tab[$field]) && $data_chron_tab[$field] < 1)
                {
                    $data_chron_tab[$field] = '';
                }
            }

            $hechhsonde              = '';
            $duree_enregistrement_JJ = '';
            $duree_enregistrement_HH = '';
            $duree_enregistrement_MM = '';
            $duree_enregistrement_SS = '';
            $last_enregistrement_JJ  = '';
            $last_enregistrement_HH  = '';
            $last_enregistrement_MM  = '';
            $last_enregistrement_SS  = '';
            $coord_x                 = '';
            $coord_y                 = '';
            $commentaire             = '';
            $nom_oe2                 = '';

            if ($type_eq == 1) // Pluvio
            {
                $data = [
                    $code_station,                                    $nom_station ?? '',
                    $date_ra,                                         $heure_ra,
                    $data_chron_tab['num_cassette'],                  $data_chron_tab['type_appareil'] ?? '',
                    $data_chron_tab['num_appareil'],                  $data_chron_tab['heure_appareil'],
                    $data_chron_tab['plu_tot_type'],                  $data_chron_tab['plu_tot_first'],
                    $data_chron_tab['plu_tot_last'],                  $data_chron_tab['plu_tot_heure_basc'],
                    $duree_enregistrement_JJ,                         $duree_enregistrement_HH,     $duree_enregistrement_MM,     $duree_enregistrement_SS,
                    $last_enregistrement_JJ,                          $last_enregistrement_HH,      $last_enregistrement_MM,      $last_enregistrement_SS,
                    $data_chron_tab['plu_nb_basculement'],            $data_chron_tab['nb_octet'],
                    $data_chron_tab['num_batterie'],                  $data_chron_tab['tension_batterie'],
                    $data_chron_tab['num_cassette'],                  $data_chron_tab['heure_init_cassette'],
                    $data_chron_tab['plu_heure_bascul1_cassette'],
                    $data_chron_tab['plu_cumul_tot'],                 $data_chron_tab['plu_cumul_plu'],
                    $data_chron_tab['plu_diff_tot_plu'],
                    $data_chron_tab['plu_recalage_heure_plu'],        $data_chron_tab['plu_test_auget'],
                    $data_chron_tab['plu_ra_bouchage'],               $data_chron_tab['ra_debroussaillage'],
                    $data_chron_tab['ra_eau_batterie'],               $data_chron_tab['plu_ra_huile_tot'],
                    $data_chron_tab['ra_transfert_data'],             $data_chron_tab['ra_delete_memory'],
                    $data_chron_tab['ra_obs'] ?? '',                  $data_chron_tab['ra_futur'] ?? '',
                    $data_chron_tab['agents_complement'],
                    $coord_x,                                         $coord_y,
                    $commentaire,                                     $nom_oe2,
                    $data_chron_tab['name_file_data'],                $data_chron_tab['obs_file_data'] ?? '',
                    $data_chron_tab['pre_marquant'],                  $data_chron_tab['fait_marquant'],
                ];
            }

            if ($type_eq == 11) // Hydro
            {
                $data = [
                    $code_station,                                    $nom_station ?? '',
                    $date_ra,                                         $heure_ra,
                    $data_chron_tab['num_cassette'],                  $data_chron_tab['type_appareil'] ?? '',
                    $data_chron_tab['num_appareil'],                  $data_chron_tab['heure_appareil'],
                    $data_chron_tab['hydro_heure_cote'],              $data_chron_tab['hydro_h_sonde'],
                    $data_chron_tab['hydro_h_echelle_1'],             $data_chron_tab['hydro_h_echelle_2'],
                    $duree_enregistrement_JJ,                         $duree_enregistrement_HH,     $duree_enregistrement_MM,     $duree_enregistrement_SS,
                    $last_enregistrement_JJ,                          $last_enregistrement_HH,      $last_enregistrement_MM,      $last_enregistrement_SS,
                    $data_chron_tab['hydro_num_sonde'],               $data_chron_tab['nb_octet'] ?? '',
                    $data_chron_tab['num_batterie'],                  $data_chron_tab['tension_batterie'] ?? '',
                    $data_chron_tab['num_cassette'],                  $data_chron_tab['heure_init_cassette'],
                    $data_chron_tab['hydro_h_sonde_cassette'],
                    $hechhsonde,                                      $data_chron_tab['hydro_recalage_sonde'],
                    $data_chron_tab['hydro_recalage_heure_sonde'],
                    $data_chron_tab['hydro_purge_sonde'],             $data_chron_tab['hydro_ra_jaugeage'],
                    $data_chron_tab['ra_debroussaillage'],            $data_chron_tab['ra_eau_batterie'],
                    $data_chron_tab['ra_transfert_data'],             $data_chron_tab['ra_delete_memory'],
                    $data_chron_tab['ra_obs'] ?? '',                  $data_chron_tab['ra_futur'] ?? '',
                    $data_chron_tab['agents_complement'],
                    $coord_x,                                         $coord_y,
                    $data_chron_tab['name_file_data'],                $data_chron_tab['obs_file_data'] ?? '',
                    $data_chron_tab['pre_marquant'],                  $data_chron_tab['fait_marquant'],
                ];
            }

            if ($type_eq == 5) // Piezo
            {
                // Sanitize multi-line text fields specific to Piezo
                foreach (['piezo_nature_repere', 'piezo_x_terrain', 'piezo_y_terrain'] as $field)
                {
                    $data_chron_tab[$field] = isset($data_chron_tab[$field])
                        ? str_replace(["\r", "\n"], ' ', $data_chron_tab[$field])
                        : '';
                }

                $sondefixe_toitnappe_m  = $data_chron_tab['piezo_toitnappesonde']  ?? '';
                $sondefixe_conductivite = $data_chron_tab['piezo_conductivite']     ?? '';
                $sondefixe_temperature  = $data_chron_tab['piezo_temperature']      ?? '';

                $manuelle_toitnappe_m   = $data_chron_tab['piezo_prof_toitnappe']   ?? '';
                $manuelle_toitnappe_cm  = $manuelle_toitnappe_m !== '' ? (float)$manuelle_toitnappe_m * 100 : '';
                $manuelle_proftotale_m  = $data_chron_tab['piezo_prof_totale']      ?? '';

                // Difference between manual and probe reading (empty if either value is missing)
                $diff_manuelle_fixe = ($sondefixe_toitnappe_m === '' || $manuelle_toitnappe_m === '')
                    ? ''
                    : (float)$manuelle_toitnappe_m - (float)$sondefixe_toitnappe_m;

                $coord_x = $data_chron_tab['piezo_x_terrain'] ?? '';
                $coord_y = $data_chron_tab['piezo_y_terrain'] ?? '';

                $data = [
                    $code_station,                                       $nom_station ?? '',
                    $date_ra,                                            $heure_ra,
                    $data_chron_tab['type_appareil']  ?? '',             $data_chron_tab['num_appareil']    ?? '',
                    $data_chron_tab['heure_appareil'] ?? '',
                    $data_chron_tab['piezo_instrument']     ?? '',       $data_chron_tab['piezo_num_instrument'] ?? '',
                    $sondefixe_toitnappe_m,                              $sondefixe_conductivite,             $sondefixe_temperature,
                    $manuelle_toitnappe_m,                               $manuelle_toitnappe_cm,              $manuelle_proftotale_m,
                    $diff_manuelle_fixe,                                 $data_chron_tab['piezo_recalage_sonde']       ?? '',
                    $data_chron_tab['piezo_recalage_heure_sonde'] ?? '',
                    $data_chron_tab['nb_octet']        ?? '',            $data_chron_tab['ra_delete_memory'],
                    $data_chron_tab['tension_batterie'] ?? '',
                    $data_chron_tab['piezo_nature_repere'],              '',  // Z repère: not in table
                    $data_chron_tab['piezo_pompage_encours'],            $data_chron_tab['piezo_pompage_proche'],
                    $data_chron_tab['piezo_pluie_crue'],                 $data_chron_tab['piezo_temps_sec'],
                    $data_chron_tab['ra_obs']    ?? '',                  $data_chron_tab['ra_futur']    ?? '',
                    $data_chron_tab['agents_complement'] ?? '',
                    $coord_x,                                            $coord_y,
                    $data_chron_tab['name_file_data'] ?? '',             $data_chron_tab['obs_file_data'] ?? '',
                    $data_chron_tab['pre_marquant'],                     $data_chron_tab['fait_marquant'],
                ];
            }

            fputcsv($handle, $data, ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// JGE (Streamflow Measurements) EXPORT

if ($init_chron === 'JGE')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        // Title row
        fputcsv($handle, [TEXT_CSV_TITLE_JGE . ' ' . $nom_station . ' - ' . $code_station], ';');

        // Column headers
        fputcsv($handle, [
            TEXT_CSV_COL_STATION_NUM,      TEXT_CSV_COL_STATION_NAME,
            TEXT_CSV_COL_DATE,
            TEXT_CSV_COL_JGE_START_HEURE,  TEXT_CSV_COL_JGE_START_H_ECHL,
            TEXT_CSV_COL_JGE_END_HEURE,    TEXT_CSV_COL_JGE_END_H_ECHL,
            TEXT_CSV_COL_JGE_HMOY,         TEXT_CSV_COL_JGE_Q,
            TEXT_CSV_COL_JGE_SECT,         TEXT_CSV_COL_JGE_VMOY,
            TEXT_CSV_COL_JGE_VSURF,        TEXT_CSV_COL_JGE_RH,
            TEXT_CSV_COL_JGE_PROFMOY,      TEXT_CSV_COL_JGE_NBVERT,
            TEXT_CSV_COL_JGE_MOULINET,     TEXT_CSV_COL_JGE_HELICE,
            TEXT_CSV_COL_OBS,              TEXT_CSV_COL_AGENTS,
            TEXT_CSV_COL_JGE_COORD_GPS_X,  TEXT_CSV_COL_JGE_COORD_GPS_Y,
            TEXT_CSV_COL_JGE_COORD_SIG_X,  TEXT_CSV_COL_JGE_COORD_SIG_Y,
            TEXT_CSV_COL_FILE_NAME,        TEXT_CSV_COL_QUALITY,
        ], ';');

        // Data rows
        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $tab_date_heure_jge = explode(' ', $data_chron_tab['datetime'] ?? '');
            $date_jge = str_replace('-', '/', $tab_date_heure_jge[0]);

            fputcsv($handle, [
                $code_station,                          $nom_station,
                $date_jge,
                $data_chron_tab['heure_first'],         $data_chron_tab['h_ech_first'],
                $data_chron_tab['heure_end'],           $data_chron_tab['h_ech_end'],
                $data_chron_tab['depouil_hmoy'],        $data_chron_tab['depouil_q'],
                $data_chron_tab['depouil_sect'],        $data_chron_tab['depouil_vmoy'],
                $data_chron_tab['depouil_vsurf'],       $data_chron_tab['depouil_rh'],
                $data_chron_tab['depouil_profmoy'],     $data_chron_tab['depouil_nbvert'],
                $data_chron_tab['id_moulinet'],         $data_chron_tab['id_helice'],
                $data_chron_tab['obs'],
                '', // Agents: not yet managed in this table
                $data_chron_tab['x_gps'],               $data_chron_tab['y_gps'],
                '', '', // GIS coordinates: not in table yet
                $data_chron_tab['fichier'],             $data_chron_tab['code_qualite'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// ETL (Rating Curve) EXPORT
// Pivoted format: each rating curve occupies two columns (start date / end date),
// with paired height/flow values written row by row below

if ($init_chron === 'ETL')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        $data_dates  = []; // Stores [start, end] date pairs for header row
        $data_values = []; // Stores [height, flow] arrays per rating curve

        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            // Format start and end timestamps
            $tab_first  = explode(' ', $data_chron_tab['datetime_first'] ?? '');
            $data_dates[] = [
                str_replace('-', '/', $tab_first[0]) . ' ' . $tab_first[1],
                str_replace('-', '/', explode(' ', $data_chron_tab['datetime_end'] ?? '')[0])
                    . ' ' . (explode(' ', $data_chron_tab['datetime_end'] ?? '')[1] ?? ''),
            ];

            // Query height/flow pairs for this rating curve
            $sql_etl_data = "SELECT DISTINCT ed.id, ed.hauteur, ed.debit, ed.code_qualite
                             FROM " . TABLE_DATA_ETL_DATA . " ed
                             WHERE ed.id_etl = " . $data_chron_tab['id'] . "
                             ORDER BY ed.hauteur ASC";

            $etl_data_query = tep_db_query($sql_link, $sql_etl_data);
            $temp_values    = [];
            while ($etl_data_tab = tep_db_fetch_array($etl_data_query))
            {
                $temp_values[] = [$etl_data_tab['hauteur'], $etl_data_tab['debit']];
            }
            $data_values[] = $temp_values;
        }

        // Build header row: alternating start/end date pairs
        $header = [];
        foreach ($data_dates as $dates)
        {
            $header[] = $dates[0];
            $header[] = $dates[1];
        }
        fputcsv($handle, $header, ';');

        // Find the maximum number of height/flow rows across all rating curves
        $max_rows = 0;
        foreach ($data_values as $values) { $max_rows = max($max_rows, count($values)); }

        // Write data rows, padding with empty cells where a curve has fewer entries
        for ($row_index = 0; $row_index < $max_rows; $row_index++)
        {
            $row = [];
            foreach ($data_values as $values)
            {
                if (isset($values[$row_index]))
                {
                    $row[] = $values[$row_index][0]; // Height
                    $row[] = $values[$row_index][1]; // Flow
                }
                else
                {
                    $row[] = ''; // Pad to maintain column alignment
                    $row[] = '';
                }
            }
            fputcsv($handle, $row, ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// REP (Piezometric Benchmarks) EXPORT

if ($init_chron === 'REP')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        // Title row
        fputcsv($handle, [TEXT_CSV_TITLE_REP . ' ' . $nom_station . ' - ' . $code_station], ';');

        // Column headers
        fputcsv($handle, [
            TEXT_CSV_COL_STATION_NUM,      TEXT_CSV_COL_STATION_NAME,
            TEXT_CSV_COL_REP_NATURE,       TEXT_CSV_COL_REP_CODE,
            TEXT_CSV_COL_REP_Z,            TEXT_CSV_COL_REP_PRECISION,
            TEXT_CSV_COL_REP_DATE_START,   TEXT_CSV_COL_REP_DATE_END,
            TEXT_CSV_COL_REP_NATURE_GEO1,  TEXT_CSV_COL_REP_Z_GEO1,
            TEXT_CSV_COL_REP_NATURE_GEO2,  TEXT_CSV_COL_REP_Z_GEO2,
            TEXT_CSV_COL_OBS,
        ], ';');

        // Data rows
        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $date_debut_valid = str_replace('-', '/', explode(' ', $data_chron_tab['date_debut_valid'] ?? '')[0]);
            $date_fin_valid   = str_replace('-', '/', explode(' ', $data_chron_tab['date_fin_valid']   ?? '')[0]);

            $data_chron_tab['obs'] = isset($data_chron_tab['obs'])
                ? str_replace(["\r", "\n"], ' ', $data_chron_tab['obs'])
                : '';

            fputcsv($handle, [
                $code_station,                         $nom_station,
                $data_chron_tab['nature_repere'],      $data_chron_tab['code_repere'],
                $data_chron_tab['z_repere'],           $data_chron_tab['precision_repere'],
                $date_debut_valid,                     $date_fin_valid,
                $data_chron_tab['nature_repere_1'],    $data_chron_tab['z_repere_g1'],
                $data_chron_tab['nature_repere_2'],    $data_chron_tab['z_repere_g2'],
                $data_chron_tab['obs'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// CTE (Piezometric Station Characteristics) EXPORT

if ($init_chron === 'CTE')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        // Title row
        fputcsv($handle, [TEXT_CSV_TITLE_CTE . ' ' . $nom_station . ' - ' . $code_station], ';');

        // Column headers
        fputcsv($handle, [
            TEXT_CSV_COL_STATION_NUM,       TEXT_CSV_COL_STATION_NAME,    TEXT_CSV_COL_DATE,
            TEXT_CSV_COL_CTE_PROF,
            TEXT_CSV_COL_CTE_MAT_TETE,      TEXT_CSV_COL_CTE_DIM_EXT,
            TEXT_CSV_COL_CTE_MAT_TUB,       TEXT_CSV_COL_CTE_DIM_TUB,
            TEXT_CSV_COL_CTE_MAT_DALLE,     TEXT_CSV_COL_CTE_DIM_DALLE,   TEXT_CSV_COL_CTE_CAPOT,
            TEXT_CSV_COL_CTE_DIST_CAPOT_TUBE, TEXT_CSV_COL_CTE_DIST_TUBE_DALLE, TEXT_CSV_COL_CTE_DIST_DALLE_SOL,
            TEXT_CSV_COL_CTE_ETAT,          TEXT_CSV_COL_CTE_ACTIVITE,
            TEXT_CSV_COL_CTE_USAGE,         TEXT_CSV_COL_CTE_EQUIPEMENT,
            TEXT_CSV_COL_CTE_SCHEMA,        TEXT_CSV_COL_CTE_PROTECTION,
            TEXT_CSV_COL_OBS,
        ], ';');

        // Data rows
        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $date_cte = str_replace('-', '/', explode(' ', $data_chron_tab['date'] ?? '')[0]);

            // Convert boolean-style fields to empty string when falsy
            foreach (['presence_capot', 'activite', 'schema_tete', 'schema_protect'] as $field)
            {
                if (isset($data_chron_tab[$field]) && $data_chron_tab[$field] < 1)
                {
                    $data_chron_tab[$field] = '';
                }
            }

            // Sanitize multi-line text fields
            foreach (['etat', 'utilisation', 'equipement_exploitation', 'obs'] as $field)
            {
                $data_chron_tab[$field] = isset($data_chron_tab[$field])
                    ? str_replace(["\r", "\n"], ' ', $data_chron_tab[$field])
                    : '';
            }

            fputcsv($handle, [
                $code_station,                               $nom_station,                 $date_cte,
                $data_chron_tab['prof'],
                $data_chron_tab['materiaux_tete'],           $data_chron_tab['dim_tete_ext'],
                $data_chron_tab['materiaux_tub_inter'],      $data_chron_tab['diam_tub_inter'],
                $data_chron_tab['materiaux_dalle'],          $data_chron_tab['dim_dalle'],
                $data_chron_tab['presence_capot'],
                $data_chron_tab['dist_capto_tube'],          $data_chron_tab['dist_tube_dalle'],
                $data_chron_tab['dist_dalle_sol'],
                $data_chron_tab['etat'],                     $data_chron_tab['activite'],
                $data_chron_tab['utilisation'],              $data_chron_tab['equipement_exploitation'],
                $data_chron_tab['schema_tete'],              $data_chron_tab['schema_protect'],
                $data_chron_tab['obs'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// DIAC (Conductivity Log) EXPORT

if ($init_chron === 'DIAC')
{
    $handle = fopen($csvFilename, 'w');
    if ($handle !== false)
    {
        fwrite($handle, "\xEF\xBB\xBF");

        // Column headers
        fputcsv($handle, [
            TEXT_CSV_COL_STATION_NUM,       TEXT_CSV_COL_STATION_NAME,   TEXT_CSV_COL_DATE,
            TEXT_CSV_COL_DIAC_PROFONDEUR,
            TEXT_CSV_COL_DIAC_CONDUCTIVITE,
            TEXT_CSV_COL_DIAC_TEMPERATURE,
            TEXT_CSV_COL_OBS,
        ], ';');

        // Data rows
        $data_chron_query = tep_db_query($sql_link, $sql_chron);
        while ($data_chron_tab = tep_db_fetch_array($data_chron_query))
        {
            $date_diac = str_replace('-', '/', explode(' ', $data_chron_tab['date_heure_ra'] ?? '')[0]);

            $data_chron_tab['obs'] = isset($data_chron_tab['obs'])
                ? str_replace(["\r", "\n"], ' ', $data_chron_tab['obs'])
                : '';

            fputcsv($handle, [
                $code_station,                      $nom_station,       $date_diac,
                $data_chron_tab['profondeur'],
                $data_chron_tab['conductivite'],
                $data_chron_tab['temperature'],
                $data_chron_tab['obs'],
            ], ';');
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// Free result set and compute execution time

if (isset($data_chron_query)) { mysqli_free_result($data_chron_query); }

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);
$total_time   += $executionTime;

echo json_encode($executionTime, JSON_UNESCAPED_UNICODE);
?>