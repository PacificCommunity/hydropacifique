<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure for saving a Field Report (RA) - Creation or Modification
- post_secure: Function available in include/function/ to control and correct form inputs.
  Security function against JS and PHP injection
- For numeric, date or time field validation, empty values are considered valid.
  Refer to validation functions validDate(), validTime(), validNumeric()

Asynchronous AJAX server-side process
----------------------------------------
*/

// Configuration
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/gestion_erreur.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die(TEXT_DB_CONNECTION_ERROR);
mysqli_query($sql_link, 'SET NAMES UTF8');

//---------------------------------------------------------------
// SQL TABLES - DATA RETRIEVAL

// USER TABLE
$sql_user_list = "SELECT DISTINCT id, id_statut, login, nom, prenom FROM ".TABLE_USER;
$user_list_query = tep_db_query($sql_link, $sql_user_list);
$user_list_array = array();
while ($user_list = tep_db_fetch_array($user_list_query)) {
    $id = $user_list['id'];
    $id_statut = $user_list['id_statut'];
    $login = html_entity_decode($user_list['login'] ?? '');
    $nom = ucfirst(strtolower(html_entity_decode($user_list['nom'] ?? '')));
    $prenom = ucfirst(strtolower(html_entity_decode($user_list['prenom'] ?? '')));

    $user_list_array[$id] = array(
        'id_statut' => $id_statut,
        'login' => $login,
        'nom' => $nom,
        'prenom' => $prenom
    );
}

// STATION TABLE
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
                    FROM ".TABLE_STATION."
                    ORDER BY nom_station ASC";
$station_all_query = tep_db_query($sql_link, $sql_station_all);
$station_all_array = array();
while ($station_all = tep_db_fetch_array($station_all_query)) {
    $nom_station = html_entity_decode($station_all['nom_station'] ?? '');

    $station_all_array[$station_all['id_station']] = array(
        'code_station' => $station_all['code_station'],
        'nom_station' => $nom_station,
        'station_type' => $station_all['station_type']
    );
}

// Initialize Global Variables
$msg_info_send = '';
$msg_info = '';
$erreur = false;
$tab_html = '';
$newRA = false;
$row = 0;

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user_agent = isset($_POST['id_user_agent']) ? $_POST['id_user_agent'] : '';
    $territoire_id = isset($_POST['territoire_id']) ? $_POST['territoire_id'] : '';
    $id_ra = isset($_POST['id_ra']) ? $_POST['id_ra'] : '';
    $type_data = isset($_POST['type_data']) ? $_POST['type_data'] : '';

    $check_valid_ra = 0;
    if(isset($_POST['check_valid_ra'])) {$check_valid_ra = 1;}

    $select_station_ra = isset($_POST['select_station_ra']) ? $_POST['select_station_ra'] : '';

    if(!isset($station_all_array[$select_station_ra])) {
        $erreur = true;
        $msg_info .= TEXT_STATION_NOT_EXIST."<br>";
    }

    $date_heure_saisie_fr = isset($_POST['date_heure_saisie_fr']) ? $_POST['date_heure_saisie_fr'] : '';
    $date_heure_saisie_us = '';
    if(tep_not_null($date_heure_saisie_fr)) {
        $date_heure_saisie_tab = explode(" ", $date_heure_saisie_fr);
        $date_heure_saisie_us = datefr_us($date_heure_saisie_tab[0]).' '.$date_heure_saisie_tab[1];
    }
    // Convert empty value to NULL and quote properly (avoids 'Data truncated' on DATETIME column)
    $date_heure_saisie_us = preparerSQL($date_heure_saisie_us);

    // ALL - Reading Data
    $date_releve = isset($_POST['date_releve']) ? $_POST['date_releve'] : '';
    if(!validDate($date_releve)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_READING_DATE_FORMAT."<br>";
        $date_releve = '';
    }

    $heure_releve = isset($_POST['heure_releve']) ? $_POST['heure_releve'] : '';
    if(!validTime($heure_releve)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_READING_TIME_FORMAT."<br>";
        $heure_releve = '';
    }

    if(!$erreur) {
        $date_heure_ra = $date_releve . ' ' . $heure_releve;
        $date_heure_ra_us = datefr_us($date_releve) . ' ' . $heure_releve;
    }

    $fichier_releve = isset($_POST['fichier_releve']) ? $_POST['fichier_releve'] : '';
    $fichier_releve = post_secure($sql_link, $fichier_releve);

    // ALL - Device Information
    $type_appareil = isset($_POST['select_type_appareil']) ? $_POST['select_type_appareil'] : '';
    $type_appareil = post_secure($sql_link, $type_appareil);
    $num_appareil = isset($_POST['select_num_appareil']) ? $_POST['select_num_appareil'] : '';
    $num_appareil = post_secure($sql_link, $num_appareil);

    $heure_appareil = isset($_POST['heure_appareil']) ? $_POST['heure_appareil'] : '';
    if(!validTime($heure_appareil)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_DEVICE_TIME_FORMAT."<br>";
        $heure_appareil = '';
    }

    // ALL - Device Status
    $nb_octet = isset($_POST['nb_octet']) ? $_POST['nb_octet'] : '';
    $nb_octet = post_secure($sql_link, $nb_octet);

    $num_batterie = isset($_POST['num_batterie']) ? $_POST['num_batterie'] : '';
    $num_batterie = post_secure($sql_link, $num_batterie);
    $tension_batterie = isset($_POST['tension_batterie']) ? $_POST['tension_batterie'] : '';
    $tension_batterie = post_secure($sql_link, $tension_batterie);

    $hydro_num_sonde = isset($_POST['select_hydro_num_sonde']) ? $_POST['select_hydro_num_sonde'] : '';
    $hydro_num_sonde = post_secure($sql_link, $hydro_num_sonde);

    // ALL - New Cassette (old equipment, obsolete in future)
    $num_cassette = isset($_POST['num_cassette']) ? $_POST['num_cassette'] : '';
    $num_cassette = post_secure($sql_link, $num_cassette);

    $heure_init_cassette = isset($_POST['heure_init_cassette']) ? $_POST['heure_init_cassette'] : '';
    if(!validTime($heure_init_cassette)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_CASSETTE_INIT_TIME_FORMAT."<br>";
        $heure_init_cassette = '';
    }

    $hydro_h_sonde_cassette = isset($_POST['hydro_h_sonde_cassette']) ? $_POST['hydro_h_sonde_cassette'] : '';
    if(!validNumeric($hydro_h_sonde_cassette)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_CASSETTE_PROBE_HEIGHT."<br>";
        $hydro_h_sonde_cassette = '';
    }
    $hydro_h_sonde_cassette = preparerSQL($hydro_h_sonde_cassette);

    $plu_heure_bascul1_cassette = isset($_POST['plu_heure_bascul1_cassette']) ? $_POST['plu_heure_bascul1_cassette'] : '';
    if(!validTime($plu_heure_bascul1_cassette)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_CASSETTE_FIRST_TIP_TIME_FORMAT."<br>";
        $plu_heure_bascul1_cassette = '';
    }

    // RAIN - Totalizer
    $plu_tot_type = isset($_POST['plu_tot_type']) ? $_POST['plu_tot_type'] : '';
    $plu_tot_type = post_secure($sql_link, $plu_tot_type);

    $plu_tot_first = isset($_POST['plu_tot_first']) ? $_POST['plu_tot_first'] : '';
    if(!validNumeric($plu_tot_first)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_FIRST_TOTALIZER_VALUE."<br>";
        $plu_tot_first = '';
    }
    $plu_tot_first = preparerSQL($plu_tot_first);

    $plu_tot_last = isset($_POST['plu_tot_last']) ? $_POST['plu_tot_last'] : '';
    if(!validNumeric($plu_tot_last)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_LAST_TOTALIZER_VALUE."<br>";
        $plu_tot_last = '';
    }
    $plu_tot_last = preparerSQL($plu_tot_last);

    $plu_tot_heure_basc = isset($_POST['plu_tot_heure_basc']) ? $_POST['plu_tot_heure_basc'] : '';
    if(!validTime($plu_tot_heure_basc)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TOTALIZER_TIP_TIME_FORMAT."<br>";
        $plu_tot_heure_basc = '';
    }

    // RAIN - Control
    $plu_cumul_tot = isset($_POST['plu_cumul_tot']) ? $_POST['plu_cumul_tot'] : '';
    if(!validNumeric($plu_cumul_tot)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TOTALIZER_CUMUL_VALUE."<br>";
        $plu_cumul_tot = NULL;
    }
    $plu_cumul_tot = preparerSQL($plu_cumul_tot);

    $plu_cumul_plu = isset($_POST['plu_cumul_plu']) ? $_POST['plu_cumul_plu'] : '';
    if(!validNumeric($plu_cumul_plu)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_RAIN_CUMUL_VALUE."<br>";
        $plu_cumul_plu = '';
    }
    $plu_cumul_plu = preparerSQL($plu_cumul_plu);

    $plu_diff_tot_plu = isset($_POST['plu_diff_tot_plu']) ? $_POST['plu_diff_tot_plu'] : '';
    if(!validNumeric($plu_diff_tot_plu)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TOTALIZER_RAIN_DIFF_VALUE."<br>";
        $plu_diff_tot_plu = '';
    }
    $plu_diff_tot_plu = preparerSQL($plu_diff_tot_plu);

    $plu_recalage_heure_plu = isset($_POST['plu_recalage_heure_plu']) ? $_POST['plu_recalage_heure_plu'] : '';
    if(!validTime($plu_recalage_heure_plu)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_RAIN_ADJUSTMENT_TIME_FORMAT."<br>";
        $plu_recalage_heure_plu = '';
    }

    $plu_test_auget = isset($_POST['plu_test_auget']) ? $_POST['plu_test_auget'] : '';
    $plu_test_auget = post_secure($sql_link, $plu_test_auget);

    $plu_nb_basculement = isset($_POST['plu_nb_basculement']) ? $_POST['plu_nb_basculement'] : '';
    if(!validNumeric($plu_nb_basculement)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TIPPINGS_COUNT_VALUE."<br>";
        $plu_nb_basculement = '';
    }
    $plu_nb_basculement = preparerSQL($plu_nb_basculement);

    // HYDRO - Water Level Measurement
    $hydro_heure_cote = isset($_POST['hydro_heure_cote']) ? $_POST['hydro_heure_cote'] : '';
    if(!validTime($hydro_heure_cote)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_WATER_LEVEL_TIME_FORMAT."<br>";
        $hydro_heure_cote = '';
    }

    $hydro_h_sonde = isset($_POST['hydro_h_sonde']) ? $_POST['hydro_h_sonde'] : '';
    if(!validNumeric($hydro_h_sonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PROBE_HEIGHT_VALUE."<br>";
        $hydro_h_sonde = '';
    }
    $hydro_h_sonde = preparerSQL($hydro_h_sonde);

    $hydro_h_echelle_1 = isset($_POST['hydro_h_echelle_1']) ? $_POST['hydro_h_echelle_1'] : '';
    if(!validNumeric($hydro_h_echelle_1)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_SCALE_HEIGHT_VALUE."<br>";
        $hydro_h_echelle_1 = '';
    }
    $hydro_h_echelle_1 = preparerSQL($hydro_h_echelle_1);

    $hydro_h_echelle_2 = isset($_POST['hydro_h_echelle_2']) ? $_POST['hydro_h_echelle_2'] : '';
    if(!validNumeric($hydro_h_echelle_2)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_SCALE_HEIGHT_2_VALUE."<br>";
        $hydro_h_echelle_2 = '';
    }
    $hydro_h_echelle_2 = preparerSQL($hydro_h_echelle_2);

    $hydro_recalage_sonde = isset($_POST['hydro_recalage_sonde']) ? $_POST['hydro_recalage_sonde'] : '';
    if(!validNumeric($hydro_recalage_sonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PROBE_ADJUSTMENT_VALUE."<br>";
        $hydro_recalage_sonde = '';
    }
    $hydro_recalage_sonde = preparerSQL($hydro_recalage_sonde);

    $hydro_recalage_heure_sonde = isset($_POST['hydro_recalage_heure_sonde']) ? $_POST['hydro_recalage_heure_sonde'] : '';
    if(!validTime($hydro_recalage_heure_sonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PROBE_TIME_ADJUSTMENT_FORMAT."<br>";
        $hydro_recalage_heure_sonde = '';
    }

    $check_purge_sonde = 0;
    if(isset($_POST['check_purge_sonde'])) {$check_purge_sonde = 1;}

    // PIEZO - Well Measurements
    $piezo_toitnappesonde = isset($_POST['piezo_toitnappesonde']) ? $_POST['piezo_toitnappesonde'] : '';
    if(!validNumeric($piezo_toitnappesonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_WATER_TABLE_DEPTH_VALUE."<br>";
        $piezo_toitnappesonde = '';
    }
    $piezo_toitnappesonde = preparerSQL($piezo_toitnappesonde);

    $piezo_conductivite = isset($_POST['piezo_conductivite']) ? $_POST['piezo_conductivite'] : '';
    if(!validNumeric($piezo_conductivite)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_CONDUCTIVITY_VALUE."<br>";
        $piezo_conductivite = '';
    }
    $piezo_conductivite = preparerSQL($piezo_conductivite);

    $piezo_temperature = isset($_POST['piezo_temperature']) ? $_POST['piezo_temperature'] : '';
    if(!validNumeric($piezo_temperature)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TEMPERATURE_VALUE."<br>";
        $piezo_temperature = '';
    }
    $piezo_temperature = preparerSQL($piezo_temperature);

    $piezo_recalage_diff = isset($_POST['piezo_recalage_diff']) ? $_POST['piezo_recalage_diff'] : '';
    if(!validNumeric($piezo_recalage_diff)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PIEZO_ADJUSTMENT_VALUE."<br>";
        $piezo_recalage_diff = '';
    }
    $piezo_recalage_diff = preparerSQL($piezo_recalage_diff);

    $piezo_recalage_sonde = isset($_POST['piezo_recalage_sonde']) ? $_POST['piezo_recalage_sonde'] : '';
    if(!validNumeric($piezo_recalage_sonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PIEZO_PROBE_ADJUSTMENT_VALUE."<br>";
        $piezo_recalage_sonde = '';
    }
    $piezo_recalage_sonde = preparerSQL($piezo_recalage_sonde);

    $piezo_recalage_heure_sonde = isset($_POST['piezo_recalage_heure_sonde']) ? $_POST['piezo_recalage_heure_sonde'] : '';
    if(!validTime($piezo_recalage_heure_sonde)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_PIEZO_PROBE_TIME_ADJUSTMENT_FORMAT."<br>";
        $piezo_recalage_heure_sonde = '';
    }

    // PIEZO - Water Table Measurement
    $piezo_nature_repere = isset($_POST['select_piezo_nature_repere']) ? $_POST['select_piezo_nature_repere'] : '';
    $piezo_nature_repere = post_secure($sql_link, $piezo_nature_repere);

    $piezo_instrument = isset($_POST['select_piezo_instrument']) ? $_POST['select_piezo_instrument'] : '';
    $piezo_instrument = post_secure($sql_link, $piezo_instrument);

    $piezo_num_instrument = isset($_POST['select_piezo_num_instrument']) ? $_POST['select_piezo_num_instrument'] : '';
    $piezo_num_instrument = post_secure($sql_link, $piezo_num_instrument);

    $piezo_prof_toitnappe = isset($_POST['piezo_prof_toitnappe']) ? $_POST['piezo_prof_toitnappe'] : '';
    if(!validNumeric($piezo_prof_toitnappe)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_MANUAL_WATER_TABLE_DEPTH_VALUE."<br>";
        $piezo_prof_toitnappe = '';
    }
    $piezo_prof_toitnappe = preparerSQL($piezo_prof_toitnappe);

    $piezo_prof_totale = isset($_POST['piezo_prof_totale']) ? $_POST['piezo_prof_totale'] : '';
    if(!validNumeric($piezo_prof_totale)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_TOTAL_WELL_DEPTH_VALUE."<br>";
        $piezo_prof_totale = '';
    }
    $piezo_prof_totale = preparerSQL($piezo_prof_totale);

    // PIEZO - Measurement Position
    $piezo_x_terrain = isset($_POST['piezo_x_terrain']) ? $_POST['piezo_x_terrain'] : '';
    if(!validNumeric($piezo_x_terrain)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_GPS_X_VALUE."<br>";
        $piezo_x_terrain = '';
    }
    $piezo_x_terrain = preparerSQL($piezo_x_terrain);

    $piezo_y_terrain = isset($_POST['piezo_y_terrain']) ? $_POST['piezo_y_terrain'] : '';
    if(!validNumeric($piezo_y_terrain)) {
        $erreur = true;
        $msg_info .= TEXT_INVALID_GPS_Y_VALUE."<br>";
        $piezo_y_terrain = '';
    }
    $piezo_y_terrain = preparerSQL($piezo_y_terrain);

    $piezo_gps_precision = isset($_POST['piezo_gps_precision']) ? $_POST['piezo_gps_precision'] : '';
    $piezo_gps_precision = post_secure($sql_link, $piezo_gps_precision);

    $piezo_systeme_coord = isset($_POST['piezo_systeme_coord']) ? $_POST['piezo_systeme_coord'] : '';
    $piezo_systeme_coord = post_secure($sql_link, $piezo_systeme_coord);

    // Piezometric profile
    if($type_data == 5) {
        for($i = 1; $i <= 15; $i++) {
            $prof = isset($_POST['piezo_profil_prof_'.$i]) ? $_POST['piezo_profil_prof_'.$i] : '';
            $prof = post_secure($sql_link, $prof);

            $conduct = isset($_POST['piezo_profil_conduct_'.$i]) ? $_POST['piezo_profil_conduct_'.$i] : '';
            $conduct = post_secure($sql_link, $conduct);

            $temp = isset($_POST['piezo_profil_temp_'.$i]) ? $_POST['piezo_profil_temp_'.$i] : '';
            $temp = post_secure($sql_link, $temp);

            if (tep_not_null($prof) && validNumeric($prof) && tep_not_null($conduct) && validNumeric($conduct)) {
                if (tep_not_null($temp) && !validNumeric($temp)) {
                    $temp = '';
                }

                $tab_ra_profil[] = [
                    'prof' => $prof,
                    'conduct' => $conduct,
                    'temp' => $temp
                ];
            }
        }
    }

    // ALL - Observations / Actions
    $ra_obs = isset($_POST['ra_obs']) ? $_POST['ra_obs'] : '';
    $ra_obs = post_secure($sql_link, $ra_obs);

    // NOTEWORTHY ACTION
    $check_faitmarquant = 0;
    if(isset($_POST['check_faitmarquant'])) {$check_faitmarquant = 1;}

    // IMPORTANT ACTION
    $check_premarquant = 0;
    if(isset($_POST['check_premarquant'])) {$check_premarquant = 1;}

    // HYDRO
    $check_jaugeage = 0;
    if(isset($_POST['check_jaugeage'])) {$check_jaugeage = 1;}

    // RAIN
    $check_bouchage = 0;
    if(isset($_POST['check_bouchage'])) {$check_bouchage = 1;}

    // RAIN
    $check_huile = 0;
    if(isset($_POST['check_huile'])) {$check_huile = 1;}

    // RAIN + HYDRO
    $check_debrouss = 0;
    if(isset($_POST['check_debrouss'])) {$check_debrouss = 1;}

    // RAIN + HYDRO
    $check_eaubat = 0;
    if(isset($_POST['check_eaubat'])) {$check_eaubat = 1;}

    // RAIN + HYDRO
    $check_transfert = 0;
    if(isset($_POST['check_transfert'])) {$check_transfert = 1;}

    // ALL
    $check_deletememory = 0;
    if(isset($_POST['check_deletememory'])) {$check_deletememory = 1;}

    // PIEZO
    $check_pompage_encours = 0;
    if(isset($_POST['check_pompage_encours'])) {$check_pompage_encours = 1;}

    $check_pompage_proche = 0;
    if(isset($_POST['check_pompage_proche'])) {$check_pompage_proche = 1;}

    $check_piezo_pluie_crue = 0;
    if(isset($_POST['check_piezo_pluie_crue'])) {$check_piezo_pluie_crue = 1;}

    $check_piezo_temps_sec = 0;
    if(isset($_POST['check_piezo_temps_sec'])) {$check_piezo_temps_sec = 1;}

    $check_piezo_photos = 0;
    if(isset($_POST['check_piezo_photos'])) {$check_piezo_photos = 1;}

    // ALL - Future actions
    $ra_futur = isset($_POST['ra_futur']) ? $_POST['ra_futur'] : '';
    $ra_futur = post_secure($sql_link, $ra_futur);

    // Additional agents
    $agents_complement = isset($_POST['agents_complement']) ? $_POST['agents_complement'] : '';
    $agents_complement = post_secure($sql_link, $agents_complement);

    // Data saving in database
    if(!$erreur) {
        $type_action = 1; // Field Report action
        $dateheure_action = date("Y-m-d H:i:s");

        if($id_ra < 1) { // New RA
            $query = "INSERT INTO ".TABLE_DATA_RA." (etat_ra, id_agent_user, id_station, id_eq_type, date_heure_ra)
                      VALUES ('".$check_valid_ra."','".$id_user_agent."','".$select_station_ra."','".$type_data."','".$date_heure_ra_us."')";

            tep_db_query($sql_link, $query);
            $id_ra = mysqli_insert_id($sql_link);

            if(HP_VERSION == 'Nomad') {
                $query_nomad = "UPDATE ".TABLE_DATA_RA." SET from_nomad=1, new_nomad=1 WHERE id_ra=".$id_ra;
                tep_db_query($sql_link, $query_nomad);
            }

            $newRA = true;

            $msg_info_send .= "<span style='font-size:16px;'>".TEXT_NEW_RA_CREATED."</span><br><br>";
            $msg_info .= TEXT_STATION_DATE_INFO.": ".$station_all_array[$select_station_ra]['nom_station']." - ".TEXT_DATE.": ".$date_heure_ra;

            // Record action in ACTION table
            $info_action = TEXT_NEW_RA_ACTION."<br>".$msg_info;
            $info_action = post_secure($sql_link, $info_action);

            $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure)
                      VALUES (".$id_user_agent.",'".$type_action."','".$info_action."','".$dateheure_action."')";
            tep_db_query($sql_link, $query);
        }

        // Update RA (New or Modified)
        $query = "UPDATE ".TABLE_DATA_RA." SET
                    etat_ra='".$check_valid_ra."',
                    datetime_saisie=".$date_heure_saisie_us.",
                    id_station='".$select_station_ra."',
                    date_heure_ra='".$date_heure_ra_us."',
                    name_file_data='".$fichier_releve."',
                    type_appareil='".$type_appareil."',
                    num_appareil='".$num_appareil."',
                    heure_appareil='".$heure_appareil."',
                    hydro_heure_cote='".$hydro_heure_cote."',
                    hydro_h_sonde=".$hydro_h_sonde.",
                    hydro_h_echelle_1=".$hydro_h_echelle_1.",
                    hydro_h_echelle_2=".$hydro_h_echelle_2.",
                    hydro_num_sonde='".$hydro_num_sonde."',
                    plu_tot_type='".$plu_tot_type."',
                    plu_tot_first=".$plu_tot_first.",
                    plu_tot_last=".$plu_tot_last.",
                    plu_tot_heure_basc='".$plu_tot_heure_basc."',
                    plu_cumul_tot=".$plu_cumul_tot.",
                    plu_cumul_plu=".$plu_cumul_plu.",
                    plu_diff_tot_plu=".$plu_diff_tot_plu.",
                    plu_recalage_heure_plu='".$plu_recalage_heure_plu."',
                    plu_test_auget='".$plu_test_auget."',
                    plu_nb_basculement=".$plu_nb_basculement.",
                    nb_octet='".$nb_octet."',
                    num_batterie='".$num_batterie."',
                    tension_batterie='".$tension_batterie."',
                    num_cassette='".$num_cassette."',
                    heure_init_cassette='".$heure_init_cassette."',
                    hydro_h_sonde_cassette=".$hydro_h_sonde_cassette.",
                    plu_heure_bascul1_cassette='".$plu_heure_bascul1_cassette."',
                    hydro_recalage_sonde=".$hydro_recalage_sonde.",
                    hydro_recalage_heure_sonde='".$hydro_recalage_heure_sonde."',
                    hydro_purge_sonde='".$check_purge_sonde."',
                    hydro_ra_jaugeage='".$check_jaugeage."',
                    plu_ra_bouchage='".$check_bouchage."',
                    plu_ra_huile_tot='".$check_huile."',
                    ra_debroussaillage='".$check_debrouss."',
                    ra_eau_batterie='".$check_eaubat."',
                    ra_transfert_data='".$check_transfert."',
                    ra_delete_memory='".$check_deletememory."',
                    piezo_toitnappesonde=".$piezo_toitnappesonde.",
                    piezo_conductivite=".$piezo_conductivite.",
                    piezo_temperature=".$piezo_temperature.",
                    piezo_recalage_diff=".$piezo_recalage_diff.",
                    piezo_recalage_sonde=".$piezo_recalage_sonde.",
                    piezo_recalage_heure_sonde='".$piezo_recalage_heure_sonde."',
                    piezo_nature_repere='".$piezo_nature_repere."',
                    piezo_instrument='".$piezo_instrument."',
                    piezo_num_instrument='".$piezo_num_instrument."',
                    piezo_prof_toitnappe=".$piezo_prof_toitnappe.",
                    piezo_prof_totale=".$piezo_prof_totale.",
                    piezo_x_terrain=".$piezo_x_terrain.",
                    piezo_y_terrain=".$piezo_y_terrain.",
                    piezo_gps_precision='".$piezo_gps_precision."',
                    piezo_systeme_coord='".$piezo_systeme_coord."',
                    piezo_pompage_encours='".$check_pompage_encours."',
                    piezo_pompage_proche='".$check_pompage_proche."',
                    piezo_pluie_crue='".$check_piezo_pluie_crue."',
                    piezo_temps_sec='".$check_piezo_temps_sec."',
                    piezo_photos='".$check_piezo_photos."',
                    agents_complement='".$agents_complement."',
                    ra_obs='".$ra_obs."',
                    fait_marquant='".$check_faitmarquant."',
                    ra_futur='".$ra_futur."',
                    pre_marquant='".$check_premarquant."'
                  WHERE id_ra=".$id_ra;

        tep_db_query($sql_link, $query);

        if(HP_VERSION == 'Nomad') {
            $query_nomad = "UPDATE ".TABLE_DATA_RA." SET from_nomad=1 WHERE id_ra=".$id_ra;
            tep_db_query($sql_link, $query_nomad);
        }

        // Piezometric profile saving
        if($type_data == 5) {
            tep_db_query($sql_link, "DELETE FROM ".TABLE_DATA_RA_PIEZO_PROFIL." WHERE id_ra = ".$id_ra);

            if(isset($tab_ra_profil)) {
                $sqlSave_profilPiezo = '';

                foreach($tab_ra_profil as $key => $value) {
                    $sqlSave_profilPiezo .= "('".$id_ra."','".$value['prof']."','".$value['conduct']."','".$value['temp']."'),";
                }

                $sqlSave_profilPiezo = rtrim($sqlSave_profilPiezo, ',');
                tep_db_query($sql_link, "INSERT INTO ".TABLE_DATA_RA_PIEZO_PROFIL."
                                          (id_ra, profondeur, conductivite, temperature)
                                          VALUES " . $sqlSave_profilPiezo);
            }
        }

        // Success message if not a new RA
        if(!$newRA) {
            $msg_info_send .= "<span style='font-size:16px;'>".TEXT_RA_SUCCESSFULLY_SAVED."</span><br><br>";
            $msg_info .= TEXT_STATION_DATE_INFO.": ".$station_all_array[$select_station_ra]['nom_station']." - ".TEXT_DATE.": ".$date_heure_ra;

            // Record action in ACTION table
            $info_action = TEXT_RA_MODIFICATION_ACTION."<br>".$msg_info;
            $info_action = post_secure($sql_link, $info_action);

            $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure)
                      VALUES (".$id_user_agent.",'".$type_action."','".$info_action."','".$dateheure_action."')";
            tep_db_query($sql_link, $query);
        }
    } else {
        $msg_info_send .= "<span style='font-size:16px;'>".TEXT_RA_SAVE_ERROR."</span><br><br>";
    }
} else {
    $msg_info_send .= "<span style='font-size:16px;'>".TEXT_SERVER_DATA_ERROR."</span><br><br>";
}

$msg_info_send .= $msg_info;

// Response data
$responseData = array(
    'id_ra' => $id_ra,
    'type_data' => $type_data,
    'new_ra' => $newRA,
    'erreur' => $erreur,
    'msg_info' => $msg_info_send
);

// Encode and send response
echo json_encode($responseData);
?>