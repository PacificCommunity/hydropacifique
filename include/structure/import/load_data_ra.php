<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Activity Report (RA) data importer - NC-specific format
- Called after file selection and validation in load_file.php
- Server-side script called via AJAX from import.php
- Reads a CSV with RA field layout varying by station type (Pluvio/Piezo/Hydro)
- Inserts one row per valid RA record into TABLE_DATA_RA
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
// Set timezone for this territory (Pacific/Noumea)
// Adjust per deployment territory if needed

date_default_timezone_set('Pacific/Noumea');
$today = date('Y-m-d H:i:s');


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
// Query: Equipment types (Rain, Flow, etc.)

$sql_eq_type   = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type,
                         type_color_border, type_color_background, type_graph
                  FROM " . TABLE_EQ_TYPE . "
                  WHERE active_eq_type = 1
                  ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
while ($eq_type_tab = tep_db_fetch_array($eq_type_query))
{
    $eq_type_array[$eq_type_tab['id_eq_type']] = [
        'id_eq_type'             => $eq_type_tab['id_eq_type'],
        'nom_eq_type'            => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type'          => $eq_type_tab['unite_eq_type'],
        'valeur_data_type'       => $eq_type_tab['valeur_data_type'],
        'type_color_border'      => $eq_type_tab['type_color_border'],
        'type_color_background'  => $eq_type_tab['type_color_background'],
        'type_graph'             => $eq_type_tab['type_graph'],
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
// Query: Data quality codes

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

$import_warning_ligne = '';
$import_result_error  = '';
$import_result        = '';

$num_ligne      = 0;
$nb_data_import = 0;
$date_debut     = '';
$date_fin       = '';
$db_load        = true;
$rows_deleted   = 0;
$id_station     = 0;
$id_chron       = 0;
$id_ext_file    = 0;
$data_tab       = [];

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
// Helper: decode a CSV cell to UTF-8 and sanitize for HTML output

function sanitizeCsvCell($value)
{
    if (mb_detect_encoding($value, 'UTF-8', true) !== 'UTF-8')
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}


// -----------------------------------------------
// CSV file reading and row-by-row processing

if ($import_data['file_ext'] == 'csv')
{
    $chemin_file = $folder . 'files/' . $import_data['file_import'];

    if (($handle = fopen($chemin_file, 'r')) !== false)
    {
        // Read and strip UTF-8 BOM from the first line if present
        $firstLine = fgets($handle);
        if (substr($firstLine, 0, 3) === "\xEF\xBB\xBF")
        {
            $firstLine = substr($firstLine, 3);
        }

        // Rewind to start so fgetcsv reads from the beginning
        fseek($handle, 0);

        while (($data = fgetcsv($handle, 10000, ';')) !== false)
        {
            $import_valid = true;
            $num_ligne++;

            // -----------------------------------------------
            // Reset all RA field variables for each row
            // Required to avoid carry-over between rows for missing columns

            $id_agent = $data_date = $appareil_k7 = $appareil_type = $appareil_num = $appareil_heure = null;
            $plu_taille_auget = $etat_ra = null;
            
            $cassette_time_save_jj = $cassette_time_save_hh = $cassette_time_save_mm = $cassette_time_save_ss = null;
            $cassette_last_save_jj = $cassette_last_save_hh  = $cassette_last_save_mm  = $cassette_last_save_ss = null;

            $cote_limni_heure = $cote_limni_hsonde = $cote_limni_hech = $cote_limni_hech2 = $num_sonde = null;
            $plu_tot_type = $plu_tot_first = $plu_tot_last = $plu_tot_heure_basc = null;
            $plu_cumul_tot = $plu_cumul_plu = $plu_diff_tot_plu = $plu_recalage_heure_plu = $plu_test_auget = null;
            $plu_nb_basculement = $nb_octets = $batt_num = $batt_tension = $num_k7 = $heure_init_k7 = null;
            $h_sonde_k7 = $ctrl_diff_ech_sonde = $plu_heure_bascul1_cassette = null;
            $ctrl_recal_sonde = $ctrl_recal_heure_sonde = $purge_sonde = $jaugeage = null;
            $plu_ra_bouchage = $plu_ra_huile_tot = $debrous = $eau_batt = $transfert = $memoire_delete = null;
            $obs = $aprevoir = $nom_file = $obs_file = $pre_marquant = $fait_marquant = $agents = null;
            $piezo_toitnappesonde = $piezo_conductivite = $piezo_temperature = null;
            $piezo_recalage_diff = $piezo_recalage_sonde = $piezo_recalage_heure_sonde = null;
            $piezo_nature_repere = $piezo_instrument = $piezo_num_instrument = null;
            $piezo_prof_toitnappe = $piezo_prof_totale = $piezo_z_mNGNC = null;
            $coord_x = $coord_y = $piezo_gps_precision = $piezo_systeme_coord = null;
            $piezo_pompage_encours = $piezo_pompage_proche = $piezo_pluie_crue = $piezo_temps_sec = $piezo_photos = null;

            // -----------------------------------------------
            // Skip the first 2 rows (title + column headers)

            if ($num_ligne > 2)
            {
                // Only process rows matching this station's code
                if ($data[0] == $station_all_array[$id_station]['code_station'])
                {
                    $dateString  = $data[2];
                    $heureString = $data[3];

                    // Use date+time combined if time is already embedded in the date field
                    $dateheureString = preg_match('/\d{2}:\d{2}(:\d{2})?/', $dateString)
                        ? $dateString
                        : $dateString . ' ' . $heureString;

                    $data_date = isValidDateImport($dateString);
                    if ($data_date == 'Invalid') { $data_date = ''; $import_valid = false; }


                    // -----------------------------------------------
                    // Pluvio station (type 1): map CSV columns to RA fields

                    if ($station_all_array[$id_station]['station_type'] == 1)
                    {
                        $appareil_k7   = sanitizeCsvCell($data[4]);
                        $appareil_type = sanitizeCsvCell($data[5]);
                        $appareil_num  = sanitizeCsvCell($data[6]);

                        $appareil_heure = isValidTimeImport($data[7]);
                        if ($appareil_heure == 'Invalid') { $appareil_heure = ''; }

                        $plu_tot_type = sanitizeCsvCell($data[8]);

                        if (is_numeric($data[9]))  { $plu_tot_first = (int)$data[9]; }
                        if (is_numeric($data[10])) { $plu_tot_last  = (int)$data[10]; }

                        $plu_tot_heure_basc = isValidTimeImport($data[11]);
                        if ($plu_tot_heure_basc == 'Invalid') { $plu_tot_heure_basc = ''; }

                        if (is_numeric($data[12])) { $cassette_time_save_jj = (int)$data[12]; }
                        if (is_numeric($data[13])) { $cassette_time_save_hh = (int)$data[13]; }
                        if (is_numeric($data[14])) { $cassette_time_save_mm = (int)$data[14]; }
                        if (is_numeric($data[15])) { $cassette_time_save_ss = (int)$data[15]; }
                        if (is_numeric($data[16])) { $cassette_last_save_jj = (int)$data[16]; }
                        if (is_numeric($data[17])) { $cassette_last_save_hh = (int)$data[17]; }
                        if (is_numeric($data[18])) { $cassette_last_save_mm = (int)$data[18]; }
                        if (is_numeric($data[19])) { $cassette_last_save_ss = (int)$data[19]; }
                        if (is_numeric($data[20])) { $plu_nb_basculement = (int)$data[20]; }

                        $nb_octets    = sanitizeCsvCell($data[21]);
                        $batt_num     = sanitizeCsvCell($data[22]);
                        $batt_tension = sanitizeCsvCell($data[23]);
                        $num_k7       = sanitizeCsvCell($data[24]);

                        $heure_init_k7 = isValidTimeImport($data[25]);
                        if ($heure_init_k7 == 'Invalid') { $heure_init_k7 = ''; }

                        $plu_heure_bascul1_cassette = isValidTimeImport($data[26]);
                        if ($plu_heure_bascul1_cassette == 'Invalid') { $plu_heure_bascul1_cassette = ''; }

                        if (is_numeric($data[27])) { $plu_cumul_tot    = (int)$data[27]; }
                        if (is_numeric($data[28])) { $plu_cumul_plu    = (int)$data[28]; }
                        if (is_numeric($data[29])) { $plu_diff_tot_plu = (int)$data[29]; }

                        $plu_recalage_heure_plu = isValidTimeImport($data[30]);
                        if ($plu_recalage_heure_plu == 'Invalid') { $plu_recalage_heure_plu = ''; }

                        $plu_test_auget = sanitizeCsvCell($data[31]);

                        if (tep_not_null($data[32])) { $plu_ra_bouchage  = 1; }
                        if (tep_not_null($data[33])) { $debrous          = 1; }
                        if (tep_not_null($data[34])) { $eau_batt         = 1; }
                        if (tep_not_null($data[35])) { $plu_ra_huile_tot = 1; }
                        if (tep_not_null($data[36])) { $transfert        = 1; }
                        if (tep_not_null($data[37])) { $memoire_delete   = 1; }

                        $obs      = sanitizeCsvCell($data[38]);
                        $aprevoir = sanitizeCsvCell($data[39]);
                        $agents   = sanitizeCsvCell($data[40]);

                        if (is_numeric($data[41])) { $coord_x = (float)$data[41]; }
                        if (is_numeric($data[42])) { $coord_y = (float)$data[42]; }

                        // Cols 43-44 : champs obsolètes (Commentaire, Nom OE2)
                        $nom_file = sanitizeCsvCell($data[45]);
                        $obs_file = sanitizeCsvCell($data[46]);

                        // Note : ce CSV n'a que 47 colonnes, donc pas de pre_marquant/fait_marquant
                        if (isset($data[47]) && tep_not_null($data[47])) { $pre_marquant  = 1; }
                        if (isset($data[48]) && tep_not_null($data[48])) { $fait_marquant = 1; }
                    }


                    // -----------------------------------------------
                    // Piezo station (type 5): map CSV columns to RA fields

                    if ($station_all_array[$id_station]['station_type'] == 5)
                    {
                        $appareil_type = sanitizeCsvCell($data[4]); // Fixed probe type
                        $appareil_num  = sanitizeCsvCell($data[5]); // Fixed probe number

                        $appareil_heure = isValidTimeImport($data[6]);
                        if ($appareil_heure == 'Invalid') { $appareil_heure = ''; }

                        $piezo_instrument     = sanitizeCsvCell($data[7]); // Manual probe type
                        $piezo_num_instrument = sanitizeCsvCell($data[8]); // Manual probe number

                        if (is_numeric($data[9]))  { $piezo_toitnappesonde = (float)$data[9];  } // Fixed probe water table depth
                        if (is_numeric($data[10])) { $piezo_conductivite   = (float)$data[10]; }
                        if (is_numeric($data[11])) { $piezo_temperature    = (float)$data[11]; }
                        if (is_numeric($data[12])) { $piezo_prof_toitnappe = (float)$data[12]; } // Manual reading (col 13 = same value in cm, skipped)
                        if (is_numeric($data[14])) { $piezo_prof_totale    = (float)$data[14]; }
                        if (is_numeric($data[15])) { $piezo_recalage_diff  = (float)$data[15]; } // Diff: manual - fixed

                        $piezo_recalage_sonde = sanitizeCsvCell($data[16]);

                        $piezo_recalage_heure_sonde = isValidTimeImport($data[17]);
                        if ($piezo_recalage_heure_sonde == 'Invalid') { $piezo_recalage_heure_sonde = ''; }

                        $nb_octets    = sanitizeCsvCell($data[18]);
                        if (tep_not_null($data[19])) { $memoire_delete = 1; }
                        $batt_tension = sanitizeCsvCell($data[20]);

                        $piezo_nature_repere = sanitizeCsvCell($data[21]);

                        // Column 22: Z(mNGNC) - stored in benchmark table, not updated here
                        if (is_numeric($data[22])) { $piezo_z_mNGNC = (float)$data[22]; }

                        if (tep_not_null($data[23])) { $piezo_pompage_encours = 1; }
                        if (tep_not_null($data[24])) { $piezo_pompage_proche  = 1; }
                        if (tep_not_null($data[25])) { $piezo_pluie_crue      = 1; }
                        if (tep_not_null($data[26])) { $piezo_temps_sec       = 1; }

                        $obs      = sanitizeCsvCell($data[27]);
                        $aprevoir = sanitizeCsvCell($data[28]);
                        $agents   = sanitizeCsvCell($data[29]);

                        if (is_numeric($data[30])) { $coord_x = (float)$data[30]; }
                        if (is_numeric($data[31])) { $coord_y = (float)$data[31]; }

                        $nom_file = sanitizeCsvCell($data[32]);
                        $obs_file = sanitizeCsvCell($data[33]);

                        if (isset($data[34]) && tep_not_null($data[34])) { $pre_marquant  = 1; }
                        if (isset($data[35]) && tep_not_null($data[35])) { $fait_marquant = 1; }
                    }


                    // -----------------------------------------------
                    // Hydro station (type 11): map CSV columns to RA fields

                    if ($station_all_array[$id_station]['station_type'] == 11)
                    {
                        $appareil_k7   = sanitizeCsvCell($data[4]);
                        $appareil_type = sanitizeCsvCell($data[5]);
                        $appareil_num  = sanitizeCsvCell($data[6]);

                        $appareil_heure = isValidTimeImport($data[7]);
                        if ($appareil_heure == 'Invalid') { $appareil_heure = ''; }

                        $cote_limni_heure = isValidTimeImport($data[8]);
                        if ($cote_limni_heure == 'Invalid') { $cote_limni_heure = ''; }

                        if (is_numeric($data[9]))  { $cote_limni_hsonde = (float)$data[9];  }
                        if (is_numeric($data[10])) { $cote_limni_hech   = (float)$data[10]; }
                        if (is_numeric($data[11])) { $cote_limni_hech2  = (float)$data[11]; }                        

                                               if (is_numeric($data[12])) { $cassette_time_save_jj = (int)$data[12]; }
                        if (is_numeric($data[13])) { $cassette_time_save_hh = (int)$data[13]; }
                        if (is_numeric($data[14])) { $cassette_time_save_mm = (int)$data[14]; }
                        //if (is_numeric($data[15])) { $cassette_time_save_ss = (int)$data[15]; }
                        if (is_numeric($data[15])) { $cassette_last_save_jj  = (int)$data[15]; }
                        if (is_numeric($data[16])) { $cassette_last_save_hh  = (int)$data[16]; }
                        if (is_numeric($data[17])) { $cassette_last_save_mm  = (int)$data[17]; }
                        //if (is_numeric($data[18])) { $cassette_last_save_ss  = (int)$data[18]; }

                        $num_sonde    = sanitizeCsvCell($data[18]);
                        $nb_octets    = sanitizeCsvCell($data[19]);
                        $batt_num     = sanitizeCsvCell($data[20]);
                        $batt_tension = sanitizeCsvCell($data[21]);
                        $num_k7       = sanitizeCsvCell($data[22]);

                        $heure_init_k7 = isValidTimeImport($data[23]);
                        if ($heure_init_k7 == 'Invalid') { $heure_init_k7 = ''; }

                        if (is_numeric($data[24])) { $h_sonde_k7          = (float)$data[24]; }
                        if (is_numeric($data[25])) { $ctrl_diff_ech_sonde = (float)$data[25]; }

                        $ctrl_recal_sonde = sanitizeCsvCell($data[26]);

                        $ctrl_recal_heure_sonde = isValidTimeImport($data[27]);
                        if ($ctrl_recal_heure_sonde == 'Invalid') { $ctrl_recal_heure_sonde = ''; }

                        if (tep_not_null($data[28])) { $purge_sonde    = 1; }
                        if (tep_not_null($data[29])) { $jaugeage       = 1; }
                        if (tep_not_null($data[30])) { $debrous        = 1; }
                        if (tep_not_null($data[31])) { $eau_batt       = 1; }
                        if (tep_not_null($data[32])) { $transfert      = 1; }
                        if (tep_not_null($data[33])) { $memoire_delete = 1; }

                        $obs      = sanitizeCsvCell($data[34]);
                        $aprevoir = sanitizeCsvCell($data[35]);
                        $agents   = sanitizeCsvCell($data[36]);

                        if (is_numeric($data[37])) { $coord_x = (float)$data[37]; }
                        if (is_numeric($data[38])) { $coord_y = (float)$data[38]; }

                        $nom_file = sanitizeCsvCell($data[39]);
                        $obs_file = sanitizeCsvCell($data[40]);

                        if (isset($data[41]) && tep_not_null($data[41])) { $pre_marquant  = 1; }
                        if (isset($data[42]) && tep_not_null($data[42])) { $fait_marquant = 1; }
                    }
                }
                else
                {
                    $import_valid = false; // Station code mismatch — skip row
                }


                // -----------------------------------------------
                // Insert valid row into TABLE_DATA_RA

                if ($import_valid)
                {
                    // Delete any existing RA record for the same station + datetime before inserting
                    $sql_delete_data = "DELETE FROM " . TABLE_DATA_RA
                                     . " WHERE id_station = " . $id_station
                                     . " AND date_heure_ra = '" . $data_date . "'";

                    $query_insert_data = "
                        INSERT INTO " . TABLE_DATA_RA . "
                        (`datetime_saisie`, `id_agent_user`, `id_station`, `date_heure_ra`,
                         `id_eq_type`, `type_appareil`, `num_appareil`, `heure_appareil`,
                         `plu_taille_auget`, `etat_ra`, `hydro_heure_cote`, `hydro_h_sonde`,
                         `hydro_h_echelle_1`, `hydro_h_echelle_2`, `hydro_num_sonde`,
                         `plu_tot_type`, `plu_tot_first`, `plu_tot_last`, `plu_tot_heure_basc`,
                         `plu_cumul_tot`, `plu_cumul_plu`, `plu_diff_tot_plu`, `plu_recalage_heure_plu`,
                         `plu_test_auget`, `plu_nb_basculement`, `nb_octet`, `num_batterie`,
                         `tension_batterie`, `num_cassette`, `heure_init_cassette`, `hydro_h_sonde_cassette`,
                         `plu_heure_bascul1_cassette`, 
                         `cassette_time_save_jj`, `cassette_time_save_hh`, `cassette_time_save_mm`, `cassette_time_save_ss`,
                         `cassette_last_save_jj`, `cassette_last_save_hh`, `cassette_last_save_mm`, `cassette_last_save_ss`,
                         `hydro_recalage_sonde`, `hydro_recalage_heure_sonde`,
                         `hydro_purge_sonde`, `hydro_ra_jaugeage`, `plu_ra_bouchage`, `plu_ra_huile_tot`,
                         `ra_debroussaillage`, `ra_eau_batterie`, `ra_transfert_data`, `ra_delete_memory`,
                         `ra_obs`, `ra_futur`, `name_file_data`, `obs_file_data`, `pre_marquant`,
                         `fait_marquant`, `agents_complement`, `piezo_toitnappesonde`, `piezo_conductivite`,
                         `piezo_temperature`, `piezo_recalage_diff`, `piezo_recalage_sonde`,
                         `piezo_recalage_heure_sonde`, `piezo_nature_repere`,
                         `piezo_instrument`, `piezo_num_instrument`, `piezo_prof_toitnappe`,
                         `piezo_prof_totale`, `piezo_x_terrain`, `piezo_y_terrain`, `piezo_gps_precision`,
                         `piezo_systeme_coord`, `piezo_pompage_encours`, `piezo_pompage_proche`,
                         `piezo_pluie_crue`, `piezo_temps_sec`, `piezo_photos`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                                ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

                    try
                    {
                        tep_db_query($sql_link, $sql_delete_data);

                        $stmt = $sql_link->prepare($query_insert_data);
                        $stmt->bind_param(
                            'sssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssssss',
                            $today,          $id_agent,       $id_station,     $data_date,
                            $station_all_array[$id_station]['station_type'],
                            $appareil_type,  $appareil_num,   $appareil_heure,
                            $plu_taille_auget, $etat_ra,
                            $cote_limni_heure, $cote_limni_hsonde, $cote_limni_hech, $cote_limni_hech2, $num_sonde,
                            $plu_tot_type, $plu_tot_first, $plu_tot_last, $plu_tot_heure_basc,
                            $plu_cumul_tot, $plu_cumul_plu, $plu_diff_tot_plu, $plu_recalage_heure_plu,
                            $plu_test_auget, $plu_nb_basculement,
                            $nb_octets, $batt_num, $batt_tension,
                            $num_k7, $heure_init_k7, $h_sonde_k7,
                            $plu_heure_bascul1_cassette,
                            $cassette_time_save_jj, $cassette_time_save_hh, $cassette_time_save_mm, $cassette_time_save_ss,
                            $cassette_last_save_jj, $cassette_last_save_hh, $cassette_last_save_mm, $cassette_last_save_ss,
                            $ctrl_recal_sonde, $ctrl_recal_heure_sonde, $purge_sonde,
                            $jaugeage,
                            $plu_ra_bouchage, $plu_ra_huile_tot,
                            $debrous, $eau_batt, $transfert, $memoire_delete,
                            $obs, $aprevoir,
                            $nom_file, $obs_file,
                            $pre_marquant, $fait_marquant,
                            $agents,
                            $piezo_toitnappesonde, $piezo_conductivite, $piezo_temperature, $piezo_recalage_diff,
                            $piezo_recalage_sonde, $piezo_recalage_heure_sonde, $piezo_nature_repere,
                            $piezo_instrument, $piezo_num_instrument, $piezo_prof_toitnappe, $piezo_prof_totale,
                            $coord_x, $coord_y, $piezo_gps_precision, $piezo_systeme_coord,
                            $piezo_pompage_encours, $piezo_pompage_proche, $piezo_pluie_crue, $piezo_temps_sec,
                            $piezo_photos
                        );

                        $stmt->execute();
                        mysqli_commit($sql_link);

                        $nb_data_import++;
                    }
                    catch (Exception $e)
                    {
                        $db_load = false;
                        mysqli_rollback($sql_link);
                        $import_result_error .= TEXT_IMPORT_CHRON_DB_ERROR . $e->getMessage();
                    }
                }
            }
        }

        fclose($handle);
    }
}


// -----------------------------------------------
// Build result summary header (only if data_tab has content — mirrors original logic)

if (isset($data_tab) && sizeof($data_tab))
{
    $import_result .= "\n----\n";
    $import_result .= TEXT_IMPORT_CHRON_FILE    . " : " . $import_data['file_import'] . "\n";
    $import_result .= TEXT_IMPORT_CHRON_STATION . " : " . $station_all_array[$id_station]['nom_station'] . "\n";
    $import_result .= TEXT_IMPORT_CHRON_SERIES  . " : " . TEXT_IMPORT_RA_SERIES_LABEL . " - "
                    . $eq_type_array[$station_all_array[$id_station]['station_type']]['nom_eq_type'] . "\n\n";
}


// -----------------------------------------------
// Build result summary message

$endTime       = microtime(true);
$executionTime = number_format($endTime - $startTime, 1);

if ($db_load)
{
    $import_result .= TEXT_IMPORT_CHRON_DONE . "\n\n";
    $import_result .= TEXT_IMPORT_CHRON_DURATION    . " : " . $executionTime . " " . TEXT_IMPORT_CHRON_SEC . "\n";
    $import_result .= TEXT_IMPORT_RA_NB_IMPORTED    . " : " . $nb_data_import . "\n";
    $import_result .= TEXT_IMPORT_CHRON_NB_ERRORS   . " : " . (($num_ligne - 2) - $nb_data_import) . "\n";

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
    $import_result .= TEXT_IMPORT_CHRON_FAIL . "\n";
}

$import_result .= "\n";


// -----------------------------------------------
// Write import result to a .txt log file (ISO-8859-1 for legacy compatibility)

$text_import_result = $import_result . "\n" . $import_result_error;

$resultFilename = $folder . $import_data['id_import'] . '_RA.txt';

if (file_exists($resultFilename)) { unlink($resultFilename); sleep(1); }

file_put_contents(
    $resultFilename,
    mb_convert_encoding($text_import_result, 'ISO-8859-1', 'UTF-8')
);


// -----------------------------------------------
// Record the import action in the database actions log (using prepared statement)

$type_action = 37;
$info_action = TEXT_ACTION_IMPORT_RA . " : " . $import_data['file_import']
             . " - " . TEXT_ACTION_IMPORT_STATION . " : "
             . $station_all_array[$import_data['id_station']]['nom_station'];

$info_action = mysqli_real_escape_string($sql_link, $info_action);

$query = "INSERT INTO " . TABLE_ACTIONS . " (id_user, type_action, info, dateheure, id_import) VALUES (?, ?, ?, ?, ?)";
$stmt  = $sql_link->prepare($query);
$stmt->bind_param('issss',
    $import_data['id_user'],
    $type_action,
    $info_action,
    $import_data['dateheure'],
    $idImport
);
$stmt->execute();


// -----------------------------------------------
// Return result to AJAX caller

echo json_encode([
    'text'   => $import_result,
    'nbData' => $nb_data_import,
]);
?>