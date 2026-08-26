<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Station record save (Create or Update)
Asynchronous AJAX server-side process
----------------------------------------
*/

// ----------------------------------------------
// Script configuration requirements

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/gestion_erreur.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// JSON response header
header('Content-Type: application/json; charset=utf-8');



// Database connection	
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE);
if (!$sql_link) {
    // Do not expose connection details to the client
    http_response_code(500);
    echo json_encode(['erreur' => true, 'msg_info' => TEXT_ERROR_SERVER_GENERIC]);
    exit;
}
mysqli_set_charset($sql_link, 'utf8mb4');


// Constants for station types (avoids magic numbers)
define('STATION_TYPE_PIEZOMETRIC', 5);


// Helper: retrieves a POST value secured via post_secure (XSS + SQL escape)
function get_post_secure($sql_link, $key, $default = '') {
    return isset($_POST[$key]) ? post_secure($sql_link, $_POST[$key]) : $default;
}

// Helper: safely converts a numeric field for SQL (avoids storing 'NULL' as a string)
function sql_numeric_or_null($value) {
    if ($value === null || $value === '' || $value === 'NULL') {
        return 'NULL';
    }
    return "'" . floatval($value) . "'";
}

// Helper: safely converts a text field for SQL (returns NULL or escaped string)
function sql_string_or_null($sql_link, $value) {
    if ($value === null || $value === '') {
        return 'NULL';
    }
    return "'" . mysqli_real_escape_string($sql_link, $value) . "'";
}

// Helper: validates a date in dd-mm-yyyy format and returns ['valid', 'us', 'dt']
function parse_date_fr($date_str, $date_format = 'd-m-Y') {
    if (!tep_not_null($date_str)) {
        return ['valid' => true, 'us' => null, 'dt' => null];
    }
    $dt = DateTime::createFromFormat($date_format, $date_str);
    if ($dt && $dt->format($date_format) === $date_str) {
        return ['valid' => true, 'us' => $dt->format('Y-m-d'), 'dt' => $dt];
    }
    return ['valid' => false, 'us' => null, 'dt' => null];
}

// -------------------------------------------------------------

// Global variables initialization
$msg_info_send = '';
$msg_info = '';
$msg_info_caract = '';
$msg_info_repere = '';
$erreur = false;
$newStation = false;

// Array initialization to avoid undefined index warnings
$tab_caracteristique_post = [];
$tab_repere_post = [];
$array_caract = [];
$array_repere = [];

$date_format = 'd-m-Y';

// Check that the request sent from the client is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Cast all IDs used in SQL queries to integers (security)
    $id_user_agent = isset($_POST['id_user_agent']) ? intval($_POST['id_user_agent']) : 0;
    $territoire_id = isset($_POST['territoire_id']) ? intval($_POST['territoire_id']) : 0;
    $id_station = isset($_POST['id_station']) ? intval($_POST['id_station']) : 0;
    
    // Minimal authentication check
    if ($id_user_agent < 1) {
        echo json_encode(['erreur' => true, 'msg_info' => TEXT_ERROR_USER_NOT_IDENTIFIED]);
        exit;
    }

    // ----------------------------------------- 
    // Form data retrieval (using helper)

    $select_fromData = get_post_secure($sql_link, 'select_fromData');

    // General
    $code_station = get_post_secure($sql_link, 'code_station');
    if (!tep_not_null($code_station)) {
        $erreur = true;
        $msg_info .= TEXT_ERROR_CODE_STATION_REQUIRED . "<br>";
    }

    $nom_station = get_post_secure($sql_link, 'nom_station');
    if (!tep_not_null($nom_station)) {
        $erreur = true;
        $msg_info .= TEXT_ERROR_NAME_STATION_REQUIRED . "<br>";
    }

    $nom_court = get_post_secure($sql_link, 'nom_court');
    $num_irh = get_post_secure($sql_link, 'num_irh');

    // Select list IDs must be cast to int (security)
    $select_region = isset($_POST['select_region']) ? intval($_POST['select_region']) : 0;
    $select_commune = isset($_POST['select_commune']) ? intval($_POST['select_commune']) : 0;
    $regionhydro_station = isset($_POST['select_regionhydro']) ? intval($_POST['select_regionhydro']) : 0;
    $riviere_id          = isset($_POST['select_riviere'])     ? intval($_POST['select_riviere'])     : 0;

    $site_station = get_post_secure($sql_link, 'site_station');

    // Station type and states
    $station_type = isset($_POST['select_type_mesure']) ? intval($_POST['select_type_mesure']) : 0;
    $active_station = isset($_POST['select_statut_station']) ? intval($_POST['select_statut_station']) : 0;
    $suivi_station = isset($_POST['select_suivi_station']) ? intval($_POST['select_suivi_station']) : 0;
    $armee_station = isset($_POST['check_armee_station']) ? 1 : 0;

    // Geography
    $vallee_station = '';
    $riviere_station = get_post_secure($sql_link, 'riviere_station');
    $altitude_station = get_post_secure($sql_link, 'altitude_station');
    $orientation_station = get_post_secure($sql_link, 'orientation_station');

    // GPS data
    $latitude_station = get_post_secure($sql_link, 'latitude_station');
    $longitude_station = get_post_secure($sql_link, 'longitude_station');
    $utm_station_x = get_post_secure($sql_link, 'utm_station_x');
    $utm_station_y = get_post_secure($sql_link, 'utm_station_y');
    $ign_station_x = get_post_secure($sql_link, 'ign_station_x');
    $ign_station_y = get_post_secure($sql_link, 'ign_station_y');
    $lamb_station_x = get_post_secure($sql_link, 'lamb_station_x');
    $lamb_station_y = get_post_secure($sql_link, 'lamb_station_y');

    // Dates with helper, isolated variables to avoid cross-section leaks
    $date_1_station = isset($_POST['date_installation_station']) ? $_POST['date_installation_station'] : '';
    $date_installation = parse_date_fr($date_1_station, $date_format);
    if (!$date_installation['valid']) {
        $erreur = true;
        $msg_info .= TEXT_ERROR_DATE_INSTALLATION_FORMAT . "<br>";
    }
    $date_1_station_us = $date_installation['us'];

    $date_2_station = isset($_POST['date_fermeture_station']) ? $_POST['date_fermeture_station'] : '';
    $date_fermeture = parse_date_fr($date_2_station, $date_format);
    if (!$date_fermeture['valid']) {
        $erreur = true;
        $msg_info .= TEXT_ERROR_DATE_DECOMMISSION_FORMAT . "<br>";
    }
    $date_2_station_us = $date_fermeture['us'];

    // Date order comparison only if both dates are valid
    if ($date_installation['dt'] !== null && $date_fermeture['dt'] !== null) {
        if ($date_fermeture['dt'] <= $date_installation['dt']) {
            $erreur = true;
            $msg_info .= TEXT_ERROR_DATE_DECOMMISSION_ORDER . "<br>";
        }
    }

    // Description
    $description_station = get_post_secure($sql_link, 'description_station');
    // Note: ideally we would store raw text and apply nl2br/htmlspecialchars at display time.
    // For now we keep the current behavior for compatibility with other scripts.
    $description_station_html = nl2br(htmlspecialchars($description_station, ENT_QUOTES, 'UTF-8'));

    // To be added later
    $source_info = '';
    $transmission_station = '';

    // Access tab (refactored with helper)
    $proprietaire = get_post_secure($sql_link, 'proprietaire');
    $contact_nom = get_post_secure($sql_link, 'contact_nom');
    $contact_phone = get_post_secure($sql_link, 'contact_phone');
    $contact_mail = get_post_secure($sql_link, 'contact_mail');
    $contact_adresse = get_post_secure($sql_link, 'contact_adresse');
    $contact_bp = get_post_secure($sql_link, 'contact_bp');
    $contact_cp = get_post_secure($sql_link, 'contact_cp');
    $contact_commune = isset($_POST['contact_commune']) ? intval($_POST['contact_commune']) : 0;
    $info_access = get_post_secure($sql_link, 'info_access');
    $pedestre_access = isset($_POST['pedestre_access']) ? 1 : 0;
    $time_access = get_post_secure($sql_link, 'time_access');
    $difficulty_access = get_post_secure($sql_link, 'difficulty_access');
    $remarque_access = get_post_secure($sql_link, 'remarque_access');


    // -------------------------------
    // Characteristics tab (Piezometric stations only)
    if ($station_type == STATION_TYPE_PIEZOMETRIC)
    {
        // CHARACTERISTICS
        if (isset($_POST['new_caract'])) { $array_caract[] = 0; }
        
        $sql_caract = "SELECT DISTINCT c.id
                        FROM ".TABLE_STATION_PIEZO_CARACTERISTIQUE." c
                        WHERE id_station = ".intval($id_station);
        $caract_query = tep_db_query($sql_link, $sql_caract);
        while ($caract_tab = tep_db_fetch_array($caract_query)) {
            $array_caract[] = intval($caract_tab['id']);
        }

        foreach ($array_caract as $key => $id_caract) 
        {
            $tab_caracteristique_post[$id_caract] = []; // Explicit array init

            // Depth
            $prof = get_post_secure($sql_link, 'prof_' . $id_caract);
            $prof = str_replace(',', '.', $prof);
            if (tep_not_null($prof) && !validNumeric($prof)) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_DEPTH_NUMERIC . "<br>";
                $prof = null; // Use null (not 'NULL' string) for SQL helpers
            }
            $tab_caracteristique_post[$id_caract]['prof'] = $prof;
            
            $tab_caracteristique_post[$id_caract]['materiaux_tete'] = get_post_secure($sql_link, 'materiaux_tete_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['dim_tete_ext'] = get_post_secure($sql_link, 'dim_tete_ext_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['materiaux_tub_inter'] = get_post_secure($sql_link, 'materiaux_tub_inter_' . $id_caract);
            
            // Casing diameter
            $diam = get_post_secure($sql_link, 'diam_tub_inter_' . $id_caract);
            $diam = str_replace(',', '.', $diam);
            if (tep_not_null($diam) && !validNumeric($diam)) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_CASING_SIZE_NUMERIC . "<br>";
                $diam = null;
            }
            $tab_caracteristique_post[$id_caract]['diam_tub_inter'] = $diam;
                        
            $tab_caracteristique_post[$id_caract]['schema_tete'] = get_post_secure($sql_link, 'schema_tete_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['materiaux_dalle'] = get_post_secure($sql_link, 'materiaux_dalle_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['dim_dalle'] = get_post_secure($sql_link, 'dim_dalle_' . $id_caract);
            
            // Cap/Casing distance
            $dist_capto = get_post_secure($sql_link, 'dist_capto_tube_' . $id_caract);
            $dist_capto = str_replace(',', '.', $dist_capto);
            if (tep_not_null($dist_capto) && !validNumeric($dist_capto)) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_DIST_CAP_TUBE . "<br>";
                $dist_capto = null;
            }
            $tab_caracteristique_post[$id_caract]['dist_capto_tube'] = $dist_capto;
            
            // Casing/Slab distance
            $dist_td = get_post_secure($sql_link, 'dist_tube_dalle_' . $id_caract);
            $dist_td = str_replace(',', '.', $dist_td);
            if (tep_not_null($dist_td) && !validNumeric($dist_td)) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_DIST_TUBE_SLAB . "<br>";
                $dist_td = null;
            }
            $tab_caracteristique_post[$id_caract]['dist_tube_dalle'] = $dist_td;
            
            // Slab/Ground distance
            $dist_ds = get_post_secure($sql_link, 'dist_dalle_sol_' . $id_caract);
            $dist_ds = str_replace(',', '.', $dist_ds);
            if (tep_not_null($dist_ds) && !validNumeric($dist_ds)) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_DIST_SLAB_GROUND . "<br>";
                $dist_ds = null; // Correct key is set to null (previous bug: wrong key was targeted)
            }
            $tab_caracteristique_post[$id_caract]['dist_dalle_sol'] = $dist_ds;
            
            $tab_caracteristique_post[$id_caract]['etat'] = get_post_secure($sql_link, 'etat_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['utilisation'] = get_post_secure($sql_link, 'utilisation_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['equipement_exploitation'] = get_post_secure($sql_link, 'equipement_exploitation_' . $id_caract);
            $tab_caracteristique_post[$id_caract]['obs'] = get_post_secure($sql_link, 'obs_' . $id_caract);

            // Characteristic date (isolated, does not pollute other sections)
            $date_caract_input = isset($_POST['date_caract_' . $id_caract]) ? $_POST['date_caract_' . $id_caract] : '';
            $date_caract_parsed = parse_date_fr($date_caract_input, $date_format);
            if (!$date_caract_parsed['valid']) {
                $erreur = true;
                $msg_info_caract .= TEXT_ERROR_WELL_DATE_FORMAT . "<br>";
            }
            $tab_caracteristique_post[$id_caract]['date'] = $date_caract_parsed['us'];

            $tab_caracteristique_post[$id_caract]['schema_protect'] = isset($_POST['schema_protect_' . $id_caract]) ? 1 : 0;
            $tab_caracteristique_post[$id_caract]['presence_capot'] = isset($_POST['presence_capot_' . $id_caract]) ? 1 : 0;
            $tab_caracteristique_post[$id_caract]['activite'] = isset($_POST['activite_' . $id_caract]) ? 1 : 0;
        }

        // REFERENCE MARKS
        // Only treat the "new" row (key 0) as a record to create when its
        // Start date is filled in. An empty new row is ignored, so clicking
        // Save without entering anything does not create a blank benchmark.
        if (isset($_POST['date_debut_valid_0']) && tep_not_null(trim($_POST['date_debut_valid_0'])))
        {
            $array_repere[] = 0;
        }

        $sql_repere = "SELECT DISTINCT r.id
                        FROM ".TABLE_STATION_PIEZO_REPERE." r
                        WHERE id_station = ".intval($id_station);
        $repere_query = tep_db_query($sql_link, $sql_repere);
        while ($repere_tab = tep_db_fetch_array($repere_query)) {
            $array_repere[] = intval($repere_tab['id']);
        }

        foreach ($array_repere as $key => $id_repere) 
        {
            $tab_repere_post[$id_repere] = []; // Explicit array init

            // Isolated variables for each reference mark (no leak from previous section)
            $date_debut_input = isset($_POST['date_debut_valid_' . $id_repere]) ? $_POST['date_debut_valid_' . $id_repere] : '';

            // Start date is required for any benchmark being saved. The empty
            // "new" row (key 0) was already excluded above, so an empty Start
            // date here means an existing row was cleared: refuse the save.
            if (!tep_not_null(trim($date_debut_input))) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_DATE_START_REQUIRED . "<br>";
            }

            $date_debut_parsed = parse_date_fr($date_debut_input, $date_format);
            if (!$date_debut_parsed['valid']) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_DATE_START_FORMAT . "<br>";
            }
            $tab_repere_post[$id_repere]['date_debut_valid'] = $date_debut_parsed['us'];

            $date_fin_input = isset($_POST['date_fin_valid_' . $id_repere]) ? $_POST['date_fin_valid_' . $id_repere] : '';
            $date_fin_parsed = parse_date_fr($date_fin_input, $date_format);
            if (!$date_fin_parsed['valid']) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_DATE_END_FORMAT . "<br>";
            }
            $tab_repere_post[$id_repere]['date_fin_valid'] = $date_fin_parsed['us'];

            // Date order comparison within the SAME reference mark
            if ($date_debut_parsed['dt'] !== null && $date_fin_parsed['dt'] !== null) {
                if ($date_fin_parsed['dt'] <= $date_debut_parsed['dt']) {
                    $erreur = true;
                    $msg_info_repere .= TEXT_ERROR_REF_DATE_ORDER . "<br>";
                }
            }

            $tab_repere_post[$id_repere]['nature_repere'] = get_post_secure($sql_link, 'nature_repere_' . $id_repere);
            $tab_repere_post[$id_repere]['code_repere'] = get_post_secure($sql_link, 'code_repere_' . $id_repere);
            
            // Z reference
            $z_rep = get_post_secure($sql_link, 'z_repere_' . $id_repere);
            $z_rep = str_replace(',', '.', $z_rep);
            if (tep_not_null($z_rep) && !validNumeric($z_rep)) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_Z_NUMERIC . "<br>";
                $z_rep = null;
            }
            $tab_repere_post[$id_repere]['z_repere'] = $z_rep;
            
            $tab_repere_post[$id_repere]['precision_repere'] = get_post_secure($sql_link, 'precision_repere_' . $id_repere);
            $tab_repere_post[$id_repere]['nature_repere_1'] = get_post_secure($sql_link, 'nature_repere_1_' . $id_repere);
            
            // Z surveyor reading 1
            $z_g1 = get_post_secure($sql_link, 'z_repere_g1_' . $id_repere);
            $z_g1 = str_replace(',', '.', $z_g1);
            if (tep_not_null($z_g1) && !validNumeric($z_g1)) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_Z_SURVEYOR_1 . "<br>";
                $z_g1 = null;
            }
            $tab_repere_post[$id_repere]['z_repere_g1'] = $z_g1;

            $tab_repere_post[$id_repere]['nature_repere_2'] = get_post_secure($sql_link, 'nature_repere_2_' . $id_repere);
            
            // Z surveyor reading 2
            $z_g2 = get_post_secure($sql_link, 'z_repere_g2_' . $id_repere);
            $z_g2 = str_replace(',', '.', $z_g2);
            if (tep_not_null($z_g2) && !validNumeric($z_g2)) {
                $erreur = true;
                $msg_info_repere .= TEXT_ERROR_REF_Z_SURVEYOR_2 . "<br>";
                $z_g2 = null;
            }
            $tab_repere_post[$id_repere]['z_repere_g2'] = $z_g2;

            $tab_repere_post[$id_repere]['obs'] = get_post_secure($sql_link, 'obs_' . $id_repere);
        }
    }


    // --------------------------------------------------------------------------
    // Save data to the database

    if (!$erreur)
    {   
        tep_db_query($sql_link, "START TRANSACTION");
        
        try {
            if ($id_station < 1) // New station
            {
                // Check uniqueness of code_station
                $code_station_esc = mysqli_real_escape_string($sql_link, $code_station);
                $sql_station_verif = "SELECT DISTINCT s.id_station FROM ".TABLE_STATION." s WHERE s.code_station='".$code_station_esc."'";
                $station_verif_query = tep_db_query($sql_link, $sql_station_verif);
                $station_verif = tep_db_fetch_array($station_verif_query);

                // Correct check (isset() would always be true on a false return)
                if ($station_verif !== false && !empty($station_verif) && isset($station_verif['id_station']))
                {	
                    $erreur = true;
                    $msg_info .= TEXT_ERROR_CODE_STATION_DUPLICATE_1 . $code_station . TEXT_ERROR_CODE_STATION_DUPLICATE_2;
                    // Immediate rollback on validation error within transaction
                    tep_db_query($sql_link, "ROLLBACK");
                }
                else
                {
                    $query = "INSERT INTO " . TABLE_STATION . " (id_station, code_station) VALUES (0, '".$code_station_esc."')";
                    tep_db_query($sql_link, $query);

                    $id_station = mysqli_insert_id($sql_link); 
                    $newStation = true;     

                    $msg_info_send .= "<span style='font-size:16px;'>" . TEXT_STATION_SAVE_NEW_SUCCESS . "</span><br><br>";
                    $msg_info .= TEXT_STATION_SAVE_LABEL . " " . $nom_station;

                    $type_action = 38;
                    $info_action = TEXT_ACTION_CREATE_STATION . $code_station . " - " . $nom_station . "<br>";
                }            
            }
            else
            {
                $msg_info_send .= "<span style='font-size:16px;'>" . TEXT_STATION_SAVE_UPDATE_SUCCESS . "</span><br><br>";
                $msg_info .= TEXT_STATION_SAVE_LABEL . " " . $nom_station;
                
                $type_action = 38;
                $info_action = TEXT_ACTION_UPDATE_STATION . $code_station . " - " . $nom_station;
            }


            if (!$erreur)
            {
                // Systematic escaping of all text variables
                $nom_station_esc = mysqli_real_escape_string($sql_link, $nom_station);
                $nom_court_esc = mysqli_real_escape_string($sql_link, $nom_court);
                $code_station_esc = mysqli_real_escape_string($sql_link, $code_station);
                $num_irh_esc = mysqli_real_escape_string($sql_link, $num_irh);
                $site_station_esc = mysqli_real_escape_string($sql_link, $site_station);
                $vallee_station_esc = mysqli_real_escape_string($sql_link, $vallee_station);
                //$riviere_station_esc = mysqli_real_escape_string($sql_link, $riviere_station);
                $altitude_station_esc = mysqli_real_escape_string($sql_link, $altitude_station);
                $orientation_station_esc = mysqli_real_escape_string($sql_link, $orientation_station);
                $longitude_esc = mysqli_real_escape_string($sql_link, $longitude_station);
                $latitude_esc = mysqli_real_escape_string($sql_link, $latitude_station);
                $utm_x_esc = mysqli_real_escape_string($sql_link, $utm_station_x);
                $utm_y_esc = mysqli_real_escape_string($sql_link, $utm_station_y);
                $ign_x_esc = mysqli_real_escape_string($sql_link, $ign_station_x);
                $ign_y_esc = mysqli_real_escape_string($sql_link, $ign_station_y);
                $lamb_x_esc = mysqli_real_escape_string($sql_link, $lamb_station_x);
                $lamb_y_esc = mysqli_real_escape_string($sql_link, $lamb_station_y);
                $description_esc = mysqli_real_escape_string($sql_link, $description_station_html);
                $source_info_esc = mysqli_real_escape_string($sql_link, $source_info);
                $transmission_esc = mysqli_real_escape_string($sql_link, $transmission_station);

                // Cast IDs to int
                $select_fromData_int = intval($select_fromData);
                $id_station_int = intval($id_station);
                $territoire_int = intval($territoire_id);

                //riviere_station='".$riviere_station_esc."',

                $query = "UPDATE ".TABLE_STATION." SET 
                    id_service='".$select_fromData_int."',
                    nom_station='".$nom_station_esc."', 
                    nom_court='".$nom_court_esc."', 
                    code_station='".$code_station_esc."', 
                    num_irh='".$num_irh_esc."', 
                    id_territoire='".$territoire_int."', 
                    id_region='".intval($select_region)."',
                    id_regionhydro='".intval($regionhydro_station)."',
                    id_riviere='".intval($riviere_id)."',
                    id_commune='".intval($select_commune)."', 
                    site_station='".$site_station_esc."',
                    vallee_station='".$vallee_station_esc."', 
                    altitude_station='".$altitude_station_esc."', 
                    orientation_station='".$orientation_station_esc."',
                    longitude_station='".$longitude_esc."', 
                    latitude_station='".$latitude_esc."',
                    utm_station_x='".$utm_x_esc."', 
                    utm_station_y='".$utm_y_esc."',
                    ign_station_x='".$ign_x_esc."', 
                    ign_station_y='".$ign_y_esc."',
                    lamb_station_x='".$lamb_x_esc."', 
                    lamb_station_y='".$lamb_y_esc."',					
                    station_type='".intval($station_type)."',
                    date_installation_station=" . ($date_1_station_us === null ? 'NULL' : "'".$date_1_station_us."'") . ",
                    date_fermeture_station=" . ($date_2_station_us === null ? 'NULL' : "'".$date_2_station_us."'") . ", 
                    description_station='".$description_esc."',
                    active_station='".intval($active_station)."', 
                    suivi='".intval($suivi_station)."', 					
                    armee='".intval($armee_station)."', 
                    source_info='".$source_info_esc."',
                    transmission_station='".$transmission_esc."'
                    WHERE id_station=".$id_station_int;

                tep_db_query($sql_link, $query);

                // ----------------------------------
                // Access data (simplified: single DELETE + INSERT instead of SELECT/DELETE/INSERT/UPDATE)
                
                $proprietaire_esc = mysqli_real_escape_string($sql_link, $proprietaire);
                $contact_nom_esc = mysqli_real_escape_string($sql_link, $contact_nom);
                $contact_phone_esc = mysqli_real_escape_string($sql_link, $contact_phone);
                $contact_mail_esc = mysqli_real_escape_string($sql_link, $contact_mail);
                $contact_adresse_esc = mysqli_real_escape_string($sql_link, $contact_adresse);
                $contact_bp_esc = mysqli_real_escape_string($sql_link, $contact_bp);
                $contact_cp_esc = mysqli_real_escape_string($sql_link, $contact_cp);
                $info_access_esc = mysqli_real_escape_string($sql_link, $info_access);
                $time_access_esc = mysqli_real_escape_string($sql_link, $time_access);
                $difficulty_esc = mysqli_real_escape_string($sql_link, $difficulty_access);
                $remarque_esc = mysqli_real_escape_string($sql_link, $remarque_access);

                $query_del_access = "DELETE FROM ".TABLE_STATION_ACCESS." WHERE id_station = ".$id_station_int;
                tep_db_query($sql_link, $query_del_access);
                
                $query_insert_access = "INSERT INTO ".TABLE_STATION_ACCESS." 
                    (id_station, proprietaire, contact_nom, contact_phone, contact_mail, contact_adresse, 
                     contact_bp, contact_cp, contact_commune, info_access, pedestre_access, 
                     time_access, difficulty_access, remarque_access) 
                    VALUES (
                        ".$id_station_int.",
                        '".$proprietaire_esc."',
                        '".$contact_nom_esc."',
                        '".$contact_phone_esc."',
                        '".$contact_mail_esc."',
                        '".$contact_adresse_esc."',
                        '".$contact_bp_esc."',
                        '".$contact_cp_esc."',
                        '".intval($contact_commune)."',
                        '".$info_access_esc."',
                        '".intval($pedestre_access)."',
                        '".$time_access_esc."',
                        '".$difficulty_esc."',
                        '".$remarque_esc."'
                    )";
                tep_db_query($sql_link, $query_insert_access);

                // ----------------------------------
                // Piezometric station
                if ($station_type == STATION_TYPE_PIEZOMETRIC)
                {
                    if (!empty($array_caract))
                    {
                        foreach ($array_caract as $key => $id_caract) 
                        {
                            $id_caract_update = $id_caract;
                            if ($id_caract < 1) {
                                $query = "INSERT INTO ".TABLE_STATION_PIEZO_CARACTERISTIQUE." (id_station) VALUES ('".$id_station_int."')";
                                tep_db_query($sql_link, $query);
                                $id_caract_update = mysqli_insert_id($sql_link); 
                            }

                            $c = $tab_caracteristique_post[$id_caract];
                            $date_caract_us = $c['date'];

                            // Use helpers to properly handle NULL (numeric vs text fields)
                            $query = "UPDATE ".TABLE_STATION_PIEZO_CARACTERISTIQUE." SET 
                                date=".($date_caract_us === null ? "NULL" : "'".$date_caract_us."'").", 
                                prof=".sql_numeric_or_null($c['prof']).",
                                materiaux_tete=".sql_string_or_null($sql_link, $c['materiaux_tete']).",
                                dim_tete_ext=".sql_string_or_null($sql_link, $c['dim_tete_ext']).",
                                materiaux_tub_inter=".sql_string_or_null($sql_link, $c['materiaux_tub_inter']).",
                                diam_tub_inter=".sql_numeric_or_null($c['diam_tub_inter']).",
                                materiaux_dalle=".sql_string_or_null($sql_link, $c['materiaux_dalle']).",
                                dim_dalle=".sql_string_or_null($sql_link, $c['dim_dalle']).",
                                dist_capto_tube=".sql_numeric_or_null($c['dist_capto_tube']).",
                                dist_tube_dalle=".sql_numeric_or_null($c['dist_tube_dalle']).",
                                dist_dalle_sol=".sql_numeric_or_null($c['dist_dalle_sol']).",
                                presence_capot='".intval($c['presence_capot'])."',
                                etat=".sql_string_or_null($sql_link, $c['etat']).",
                                activite='".intval($c['activite'])."',
                                utilisation=".sql_string_or_null($sql_link, $c['utilisation']).",
                                equipement_exploitation=".sql_string_or_null($sql_link, $c['equipement_exploitation']).",
                                schema_tete=".sql_string_or_null($sql_link, $c['schema_tete']).",
                                schema_protect='".intval($c['schema_protect'])."',
                                obs=".sql_string_or_null($sql_link, $c['obs'])."
                                WHERE id=".intval($id_caract_update);
                        
                            tep_db_query($sql_link, $query);
                        }
                    }

                    if (!empty($array_repere))
                    {
                        foreach ($array_repere as $key => $id_repere) 
                        {
                            $id_repere_update = $id_repere;
                            if ($id_repere < 1) {
                                $query = "INSERT INTO ".TABLE_STATION_PIEZO_REPERE." (id_station) VALUES ('".$id_station_int."')";
                                tep_db_query($sql_link, $query);
                                $id_repere_update = mysqli_insert_id($sql_link); 
                            }

                            $r = $tab_repere_post[$id_repere];
                            $date_debut_valid_us = $r['date_debut_valid'];
                            $date_fin_valid_us = $r['date_fin_valid'];

                            $query = "UPDATE ".TABLE_STATION_PIEZO_REPERE." SET 
                                date_debut_valid=".($date_debut_valid_us === null ? "NULL" : "'".$date_debut_valid_us."'").", 
                                date_fin_valid=".($date_fin_valid_us === null ? "NULL" : "'".$date_fin_valid_us."'").", 
                                nature_repere=".sql_string_or_null($sql_link, $r['nature_repere']).",
                                code_repere=".sql_string_or_null($sql_link, $r['code_repere']).",
                                z_repere=".sql_numeric_or_null($r['z_repere']).",
                                precision_repere=".sql_string_or_null($sql_link, $r['precision_repere']).",
                                nature_repere_1=".sql_string_or_null($sql_link, $r['nature_repere_1']).",
                                z_repere_g1=".sql_numeric_or_null($r['z_repere_g1']).",
                                nature_repere_2=".sql_string_or_null($sql_link, $r['nature_repere_2']).",
                                z_repere_g2=".sql_numeric_or_null($r['z_repere_g2']).",
                                obs=".sql_string_or_null($sql_link, $r['obs'])."
                                WHERE id=".intval($id_repere_update);
                        
                            tep_db_query($sql_link, $query);
                        }
                    }
                }

                // Log action in the actions table
                $today_us = date('Y-m-d H:i:s'); 
                $info_action_esc = mysqli_real_escape_string($sql_link, $info_action); 

                $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
                          VALUES (".intval($id_user_agent).", '".intval($type_action)."', '".$info_action_esc."', '".$today_us."')";
                tep_db_query($sql_link, $query);

                // Commit only if every operation succeeded
                tep_db_query($sql_link, "COMMIT");
            }

        } catch (Exception $e) 
        {        
            tep_db_query($sql_link, "ROLLBACK");
            
            // Log on server side, return a generic message to the client
            error_log("Error station_save.php: " . $e->getMessage());
            
            $msg_info_send .= TEXT_ERROR_DB_WRITE . "<br>";
            $msg_info_send .= TEXT_ERROR_RETRY_OR_CONTACT;

            $erreur = true;
        }
    }
    else
    {
        $msg_info_send .= "<span style='font-size:16px;'>" . TEXT_ERROR_SAVE_FAILED . "</span><br><br>";
        $erreur = true;
    }
}
else
{
    $msg_info_send .= "<span style='font-size:16px;'>" . TEXT_ERROR_REQUEST_METHOD . "</span><br><br>";
    $erreur = true;
}


$msg_info_send .= $msg_info . $msg_info_caract . $msg_info_repere; 


// Build the response array
$responseData = array(
    'erreur' => $erreur,
    'new_station' => $newStation,    
    'id_station' => intval($id_station),    
    'msg_info' => $msg_info_send
);

echo json_encode($responseData);
?>