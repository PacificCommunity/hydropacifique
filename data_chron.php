<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Main data access page
Consultation (Graphs) / Correction and Calculations / Export
----------------------------------------
*/

require('include/application_top.php');

// File download from Export
if(isset($_POST['file_download'])) {
    require(DIR_WS_EXPORT . 'process_download.php');
    exit;
}

// Initialize Variables
$message_info = '';
$indice = 0;
$data_step = 1; // Current step
$verif_form = 0; // Form validation
$row = 0;

$select_station_tab = array();
$nb_stations = 0;

$select_id_interval = 0;
$select_label_interval = '';

$select_type_encours = 0; // Rain/Flow/Piezometer
$select_type_chron = 0; // Single chronicle type selection

$sql_chron = '';

$nb_axe = 0;
$axe_encours = '';
$typedata_encours = 0;

$select_list_station_txt = ''; // Variable to list selected stations

// Date preparation
$today = date('d-m-Y');
$timenow = date('H:i:s');

$year_today = date('Y');
$year_first = 1950; // For date range form
$month_today = date('m');

$f_y = $year_today - 80;
$l_y = $year_today;

$date_1 = '01-01-'.$year_first;
$time_1 = '00:00:00';
$date_2 = $today;
$time_2 = $timenow;

$date_format = 'd-m-Y';

$year_1 = $year_first;
$month_1 = '01';
$day_1 = '01';
$year_2 = $year_today;
$month_2 = '12';
$day_2 = cal_days_in_month(CAL_GREGORIAN, $month_2, $year_2);

//--------------------------------------------------
// SQL TABLES - DATA RETRIEVAL

// REGION TABLE
$sql_region = "SELECT DISTINCT id_region, nom_region
                FROM ".TABLE_REGION."
                WHERE id_territoire=".$territoire_id;
$region_query = tep_db_query($sql_link, $sql_region);
$region_array = array();
while ($region = tep_db_fetch_array($region_query)) {
    $region_array[$region['id_region']] = html_entity_decode($region['nom_region'] ?? '');
}

// HYDRO REGION TABLE
$sql_regionhydro = "SELECT DISTINCT id, nom
                    FROM ".TABLE_REGIONHYDRO."
                    WHERE id_territoire=".$territoire_id."
                    ORDER BY nom ASC";
$regionhydro_query = tep_db_query($sql_link, $sql_regionhydro);
$regionhydro_array = array();
while ($regionhydro = tep_db_fetch_array($regionhydro_query)) {
    $regionhydro_array[$regionhydro['id']] = html_entity_decode($regionhydro['nom'] ?? '');
}

// TOUR TABLE
$sql_tournee = "SELECT DISTINCT id, nom
                FROM ".TABLE_TOURNEE."
                WHERE id_territoire=".$territoire_id."
                ORDER BY nom ASC";
$tournee_query = tep_db_query($sql_link, $sql_tournee);
$tournee_array = array();
while ($tournee = tep_db_fetch_array($tournee_query)) {
    $tournee_array[$tournee['id']] = html_entity_decode($tournee['nom'] ?? '');
}

// STATION TABLE
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, active_station, station_type
                    FROM ".TABLE_STATION;
$station_all_query = tep_db_query($sql_link, $sql_station_all);
$station_all_array = array();
while ($station_all = tep_db_fetch_array($station_all_query)) {
    $station_all_array[$station_all['id_station']] = array(
        'nom_station' => $station_all['nom_station'],
        'code_station' => html_entity_decode($station_all['code_station'] ?? ''),
        'type_station' => html_entity_decode($station_all['station_type'] ?? '')
    );
}

// EQUIPMENT TYPE TABLE
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_color_background, type_graph
                FROM ".TABLE_EQ_TYPE."
                WHERE active_eq_type=1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_array = array();
while ($eq_type_tab = tep_db_fetch_array($eq_type_query)) {
    $eq_type_array[$eq_type_tab['id_eq_type']] = array(
        'id_eq_type' => $eq_type_tab['id_eq_type'],
        'nom_eq_type' => html_entity_decode($eq_type_tab['nom_eq_type'] ?? ''),
        'unite_eq_type' => $eq_type_tab['unite_eq_type'],
        'valeur_data_type' => $eq_type_tab['valeur_data_type'],
        'type_color_border' => $eq_type_tab['type_color_border'],
        'type_color_background' => $eq_type_tab['type_color_background'],
        'type_graph' => $eq_type_tab['type_graph']
    );
}

// DATA TYPE AXIS
$sql_data_type_axe = "SELECT DISTINCT id, axe, unite, nb_round FROM ".TABLE_DATA_TYPE_AXE;
$data_type_axe_query = tep_db_query($sql_link, $sql_data_type_axe);
$data_type_axe_array = array();
while ($data_type_axe = tep_db_fetch_array($data_type_axe_query)) {
    $data_type_axe_array[$data_type_axe['id']] = array(
        'axe' => html_entity_decode($data_type_axe['axe'] ?? ''),
        'unite' => html_entity_decode($data_type_axe['unite'] ?? ''),
        'nb_round' => html_entity_decode($data_type_axe['nb_round'] ?? '')
    );
}

// CHRONICLE TYPE TABLE
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode, type_graph, raw_data
                  FROM ".TABLE_TYPE_DATA."
                  ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_array = array();
while ($type_chron_tab = tep_db_fetch_array($type_chron_query)) {
    $type_chron_array[$type_chron_tab['id_data_type']] = array(
        'init_type_data' => html_entity_decode($type_chron_tab['init_type_data'] ?? ''),
        'nom_type_data' => html_entity_decode($type_chron_tab['nom_type_data'] ?? ''),
        'id_eq_type_data' => html_entity_decode($type_chron_tab['id_eq_type_data'] ?? ''),
        'axe_nom' => html_entity_decode($data_type_axe_array[$type_chron_tab['axe_data']]['axe'] ?? ''),
        //'unite' => html_entity_decode($type_chron_tab['unite'] ?? ''),        
        'unite' => html_entity_decode($data_type_axe_array[$type_chron_tab['axe_data']]['unite'] ?? ''),
        'nb_round' => html_entity_decode($data_type_axe_array[$type_chron_tab['axe_data']]['nb_round'] ?? ''),
        'to_periode' => html_entity_decode($type_chron_tab['to_periode'] ?? ''),
        'id_chon_periode' => html_entity_decode($type_chron_tab['id_chon_periode'] ?? ''),
        'type_graph' => html_entity_decode($type_chron_tab['type_graph'] ?? ''),
        'raw_data' => (int) ($type_chron_tab['raw_data'] ?? 0)
    );
}
$json_type_chron_array = json_encode($type_chron_array);

// QUALITY CODE QUERY
$sql_quality = "SELECT DISTINCT id_data_qualite, init_qualite_data, nom_qualite_data, info_qualite_data, id_eq_type
                FROM ".TABLE_DATA_QUALITE."
                WHERE init_qualite_data<>'' ORDER BY init_qualite_data ASC";
$quality_query = tep_db_query($sql_link, $sql_quality);
$quality_array = array();
while ($quality_tab = tep_db_fetch_array($quality_query)) {
    $quality_array[$quality_tab['id_data_qualite']] = array(
        'init_qualite_data' => html_entity_decode($quality_tab['init_qualite_data'] ?? ''),
        'nom_qualite_data' => html_entity_decode($quality_tab['nom_qualite_data'] ?? ''),
        'info_qualite_data' => html_entity_decode($quality_tab['info_qualite_data'] ?? ''),
        'id_eq_type' => html_entity_decode($quality_tab['id_eq_type'] ?? '')
    );
}

//---------------------------------------------------
// FORM CONTROLS
// Depending on choices, the application directs to different steps

// Export - to display only export options
$select_export = false;
if(isset($_GET['export'])) {$select_export = $_GET['export'];}
if(isset($_POST['export'])) {$select_export = $_POST['export'];}

// Step 1: Get sorting data
if(isset($_POST['select_type_data'])) {$select_type_encours = $_POST['select_type_data'];}
if(isset($_POST['select_type_chron'])) {$select_type_chron = $_POST['select_type_chron'];}

// Periods
if(isset($_POST['date1_encours'])) {$date_1 = $_POST['date1_encours'];}
if(isset($_POST['date2_encours'])) {$date_2 = $_POST['date2_encours'];}

/* Date period selection - currently commented out
if(isset($_POST['select_periode'])) {
    switch($_POST['select_periode']) {
        case 1:
            $year_1 = $_POST['select_year_f'];
            $date_1 = '01-01-'.$year_1;
            $year_2 = $_POST['select_year_l'];
            $date_2 = '31-12-'.$year_2;
            break;
        case 2:
            $year_1 = $_POST['select_year_f'];
            $month_1 = $_POST['select_month_f'];
            $date_1 = '01-'.$month_1.'-'.$year_1;
            $year_2 = $_POST['select_year_l'];
            $month_2 = $_POST['select_month_l'];
            $date_2 = cal_days_in_month(CAL_GREGORIAN, $month_2, $year_2).'-'.$month_2.'-'.$year_2;
            break;
        case 3:
            $date_1 = $_POST['date_f'];
            $tab_date_1 = explode('-', $date_1);
            $month_1 = $tab_date_1[1];
            $year_1 = $tab_date_1[2];
            $date_2 = $_POST['date_l'];
            $tab_date_2 = explode('-', $date_2);
            $month_2 = $tab_date_2[1];
            $year_2 = $tab_date_2[2];
            break;
    }

    // Date format validation
    $date_1_format = DateTime::createFromFormat($date_format, $date_1);
    if (!($date_1_format && $date_1_format->format($date_format) === $date_1)) {
        $verif_form++;
        if(tep_not_null($message_info)) {$message_info .= "<br>";}
        $message_info .= $date_1 . TEXT_INVALID_DATE_FORMAT_1 . $date_format . TEXT_INVALID_DATE_FORMAT_2;
    }

    $date_2_format = DateTime::createFromFormat($date_format, $date_2);
    if (!($date_2_format && $date_2_format->format($date_format) === $date_2)) {
        $verif_form++;
        if(tep_not_null($message_info)) {$message_info .= "<br>";}
        $message_info .= $date_2 . TEXT_INVALID_DATE_FORMAT_1 . $date_format . TEXT_INVALID_DATE_FORMAT_2;
    }

    if($verif_form < 1) {
        $timestamp1 = date_create_from_format('d-m-Y', $date_1)->getTimestamp();
        $timestamp2 = date_create_from_format('d-m-Y', $date_2)->getTimestamp();

        if($timestamp2 < $timestamp1) {
            $verif_form++;
            if(tep_not_null($message_info)) {$message_info .= "<br>";}
            $message_info .= TEXT_END_DATE_BEFORE_START;
        }
    }
}
*/

// Step 2: Display choice based on objective (View/Export/Delete)
if(isset($_POST['valid_chron_step1']) || isset($_GET['id_st'])) {
    // Stations
    if(isset($_POST['target_station_ref']) || isset($_GET['id_st'])) {
        if(isset($_POST['target_station_ref'])) { // From form_chron_step1.php
            $select_station_tab = $_POST['target_station_ref'];
            $list_station_txt = implode(',', $select_station_tab);
            $nb_stations = sizeof($select_station_tab);
        }

        if(isset($_GET['id_st'])) { // From link from another page related to a station
            $list_station_txt = post_secure($sql_link, $_GET['id_st']);
            $nb_stations = 1;
        }
    } else {
        $verif_form++;
        $message_info .= TEXT_SELECT_AT_LEAST_ONE_STATION;
    }

    if($verif_form < 1) {$data_step = 2;} // If step 1 is valid, move to step 2
}

// Step 3: After chronicles are selected, user chooses to edit graphs, export or calculate
if(isset($_POST['button_graph']) || isset($_POST['button_export']) || isset($_POST['button_calcul']) || isset($_POST['graph_chron'])) 
{
    if(isset($_POST['date_1'])) {$date_1 = $_POST['date_1'];}
    if(isset($_POST['date_2'])) {$date_2 = $_POST['date_2'];}

    if(!empty($_POST['check_chron']) || !empty($_POST['graph_chron'])) {
        $check_chron = [];

        if(!empty($_POST['check_chron'])) {
            $check_chron = array_merge($check_chron, $_POST['check_chron']);
        }

        if(!empty($_POST['graph_chron'])) {
            $check_chron[] = $_POST['graph_chron']; // Single value from import page
        }

        $nb_data_all_check = 0;

        foreach($check_chron as $value_chron) 
        {
            $chron_array = explode("_", $value_chron);
            $station_chron = $chron_array[0];
            $typedata_station = $chron_array[1];
            $typedata_chron = $chron_array[2];

            $check_ra = false; $check_jge = false; $check_etl = false; $check_lab = false;
            $check_tot = false; $check_rep = false; $check_cte = false; $check_diac = false;
            if($typedata_chron == 'ra') {$check_ra = true;}
            if($typedata_chron == 'jge') {$check_jge = true;} // Gauging
            if($typedata_chron == 'etl') {$check_etl = true;} // ETL
            if($typedata_chron == '55') {$check_lab = true;} // lab -> NC
            if($typedata_chron == '58') {$check_tot = true;} // tot -> NC
            if($typedata_chron == 'rep') {$check_rep = true;} // Piezometer station reference
            if($typedata_chron == 'cte') {$check_cte = true;} // Piezometer station characteristics
            if($typedata_chron == 'diac') {$check_diac = true;} // Conductivity log

            $nbdata_station_chron = 0;

            if(isset($_POST['nb_'.$station_chron.'_'.$typedata_station.'_'.$typedata_chron])) {
                $nbdata_station_chron = $_POST['nb_'.$station_chron.'_'.$typedata_station.'_'.$typedata_chron];
            }

            $nb_data_all_check += $nbdata_station_chron;

            $nbdata_chron_array[$station_chron][$typedata_chron] = $nbdata_station_chron;

            if(!$check_ra && !$check_jge && !$check_etl && !$check_lab && !$check_tot && !$check_rep && !$check_cte && !$check_diac) {
                if(($type_chron_array[$typedata_chron]['unite'] != $axe_encours) || ($typedata_station != $typedata_encours)) {
                    $axe_encours = $type_chron_array[$typedata_chron]['unite'];
                    $typedata_encours = $typedata_station;
                    $nb_axe++;

                    $axe_tab[$nb_axe] = array(
                        'typedata_station' => $typedata_station,
                        'axe_nom' => $type_chron_array[$typedata_chron]['axe_nom'],
                        'unite' => $type_chron_array[$typedata_chron]['unite']
                    );
                }

                // Prepare query to get chronicle data
                $sql_chron = "SELECT da.dateheure, da.valeur, dm.id_station, dm.id, dm.id_typedata, dm.id_codequal, dm.obs, dm.obs_user
                              FROM ".TABLE_DATA_ALL." da
                              JOIN ".TABLE_DATA_META." dm ON da.id_meta=dm.id
                              WHERE dm.id_typedata = ".$typedata_chron."
                              AND dm.id_station = ".$station_chron."
                              AND da.dateheure >= '".datefr_us($date_1)." 00:00:00'
                              AND da.dateheure <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY da.dateheure ASC";

                $chron_axe_array[$station_chron][$typedata_chron] = $nb_axe;
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_lab) {
                $sql_chron = "SELECT DISTINCT lab.id, lab.date_heure as dateheure, lab.cumul as valeur, lab.total, lab.id_data_qualite as id_codequal, lab.obs
                              FROM ".TABLE_DATA_LAB." lab
                              WHERE lab.id_station=".$station_chron."
                              AND lab.date_heure >= '".datefr_us($date_1)." 00:00:00'
                              AND lab.date_heure <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY lab.date_heure ASC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_tot) {
                $sql_chron = "SELECT DISTINCT tot.id, tot.date_heure as dateheure, tot.valeurDebut, tot.valeurFin, tot.ecartPrecedent as valeur, tot.id_data_qualite as id_codequal, tot.obs
                              FROM ".TABLE_DATA_TOT." tot
                              WHERE tot.id_station=".$station_chron."
                              AND tot.date_heure >= '".datefr_us($date_1)." 00:00:00'
                              AND tot.date_heure <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY tot.date_heure ASC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_ra) {
                $sql_chron = "SELECT DISTINCT ra.id_ra, ra.date_heure_ra, ra.id_eq_type,
                              ra.type_appareil, ra.num_appareil, ra.heure_appareil, ra.plu_taille_auget, ra.etat_ra,
                              ra.hydro_heure_cote, ra.hydro_h_sonde, ra.hydro_h_echelle_1, ra.hydro_h_echelle_2, ra.hydro_num_sonde,
                              ra.plu_tot_type, ra.plu_tot_first, ra.plu_tot_last, ra.plu_tot_heure_basc,
                              ra.plu_cumul_tot, ra.plu_cumul_plu, ra.plu_diff_tot_plu, ra.plu_recalage_heure_plu, ra.plu_test_auget, ra.plu_nb_basculement,
                              ra.nb_octet, ra.num_batterie, ra.tension_batterie, ra.num_cassette, ra.heure_init_cassette,
                              ra.hydro_h_sonde_cassette, ra.plu_heure_bascul1_cassette,
                              ra.hydro_recalage_sonde, ra.hydro_recalage_heure_sonde, ra.hydro_purge_sonde,
                              ra.hydro_ra_jaugeage, ra.plu_ra_bouchage, ra.plu_ra_huile_tot, ra.ra_debroussaillage, ra.ra_eau_batterie, ra.ra_transfert_data, ra.ra_delete_memory,
                              ra.piezo_conductivite, ra.piezo_temperature, ra.piezo_recalage_diff, ra.piezo_nature_repere,
                              ra.piezo_instrument, ra.piezo_num_instrument, ra.piezo_prof_toitnappe, ra.piezo_prof_totale, ra.piezo_x_terrain, ra.piezo_y_terrain, ra.piezo_gps_precision,
                              ra.piezo_systeme_coord, ra.piezo_pompage_encours, ra.piezo_pompage_proche, ra.piezo_pluie_crue, ra.piezo_temps_sec, ra.piezo_photos,
                              ra.ra_obs, ra.ra_futur, ra.name_file_data, ra.obs_file_data, ra.pre_marquant, ra.fait_marquant, ra.agents_complement
                              FROM ".TABLE_DATA_RA." ra
                              WHERE ra.id_station=".$station_chron."
                              AND ra.date_heure_ra >= '".datefr_us($date_1)." 00:00:00'
                              AND ra.date_heure_ra <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY ra.date_heure_ra DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_jge) {
                $sql_chron = "SELECT DISTINCT jge.id, jge.id_station, jge.datetime, jge.x_gps, jge.y_gps,
                              jge.nb_bras, jge.dist_site, jge.dist_site, jge.id_methode, jge.id_typejge,
                              jge.depouil_hmoy, jge.depouil_q, jge.depouil_sect, jge.depouil_vmoy, jge.depouil_vsurf,
                              jge.depouil_rh, jge.depouil_profmoy, jge.depouil_nbvert,
                              jge.code_qualite, jge.obs, jge.fichier,
                              b.heure_first, b.h_ech_first, b.heure_end, b.h_ech_end,
                              b.id_moulinet, b.id_helice
                              FROM ".TABLE_DATA_JGE." jge
                              JOIN ".TABLE_DATA_JGE_BRAS." b ON b.id_jge=jge.id
                              WHERE jge.id_station=".$station_chron."
                              AND jge.datetime >= '".datefr_us($date_1)." 00:00:00'
                              AND jge.datetime <= '".datefr_us($date_2)." 23:59:59'
                              AND b.num_bras=1
                              ORDER BY jge.datetime DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_etl && !isset($_POST['button_graph'])) {
                $sql_chron = "SELECT DISTINCT etl.id, etl.id_station, etl.datetime_first, etl.datetime_end
                              FROM ".TABLE_DATA_ETL." etl
                              WHERE etl.id_station=".$station_chron."
                              AND etl.datetime_end >= '".datefr_us($date_1)." 00:00:00'
                              AND etl.datetime_first <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY etl.datetime_first DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_rep && !isset($_POST['button_graph'])) {
                $sql_chron = "SELECT DISTINCT rep.id, rep.id_station, rep.nature_repere, rep.code_repere, rep.z_repere,
                              rep.precision_repere, rep.date_debut_valid, rep.date_fin_valid,
                              rep.nature_repere_1, rep.z_repere_g1, rep.nature_repere_2, rep.z_repere_g2,
                              rep.obs
                              FROM ".TABLE_STATION_PIEZO_REPERE." rep
                              WHERE rep.id_station=".$station_chron."
                              AND rep.date_debut_valid >= '".datefr_us($date_1)." 00:00:00'
                              AND rep.date_debut_valid <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY rep.date_debut_valid DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_cte && !isset($_POST['button_graph'])) {
                $sql_chron = "SELECT DISTINCT cte.id, cte.id_station, cte.date, cte.prof,
                              cte.materiaux_tete, cte.dim_tete_ext, cte.materiaux_tub_inter, cte.diam_tub_inter,
                              cte.materiaux_dalle, cte.dim_dalle,
                              cte.dist_capto_tube, cte.dist_tube_dalle, cte.dist_dalle_sol,
                              cte.presence_capot, cte.etat, cte.activite, cte.utilisation, cte.equipement_exploitation,
                              cte.schema_tete, cte.schema_protect,
                              cte.obs
                              FROM ".TABLE_STATION_PIEZO_CARACTERISTIQUE." cte
                              WHERE cte.id_station=".$station_chron."
                              AND cte.date >= '".datefr_us($date_1)." 00:00:00'
                              AND cte.date <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY cte.date DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }

            if($check_diac && !isset($_POST['button_graph'])) {
                $sql_chron = "SELECT ra.date_heure_ra, pp.profondeur, pp.conductivite, pp.temperature, pp.obs
                              FROM ".TABLE_DATA_RA_PIEZO_PROFIL." pp
                              JOIN ".TABLE_DATA_RA." ra ON pp.id_ra=ra.id_ra
                              WHERE ra.id_station = ".$station_chron."
                              AND ra.date_heure_ra >= '".datefr_us($date_1)." 00:00:00'
                              AND ra.date_heure_ra <= '".datefr_us($date_2)." 23:59:59'
                              ORDER BY ra.date_heure_ra DESC";
                $station_chron_array[$station_chron][$typedata_chron] = $sql_chron;
            }
        }

        if(isset($_POST['button_export'])) {
            $zip = false;
            if(isset($_POST['export_zip'])) {$zip = true;}

            $entete_col = false;
            if(isset($_POST['entete_col'])) {$entete_col = true;}

            $format_export = 'csv';
            if($format_export == 'csv') {require(DIR_WS_EXPORT . 'data_chron_export_csv.php');}
            exit;
        }

        if(isset($_POST['button_calcul'])) {
            require(DIR_WS_CALCUL . 'data_chron_calcul.php');
            exit;
        }

        if(isset($_POST['button_graph']) || isset($_POST['graph_chron'])) {
            if(isset($_POST['one_graph'])) {
                /*
                if(!$check_ra && !$check_jge) {
                    require(DIR_WS_GRAPH . 'graph_chron_one.php');
                    exit;
                }
                */
                require(DIR_WS_GRAPH . 'graph_chron_one.php');
                    exit;
            } else {
                require(DIR_WS_GRAPH . 'graph_chron.php');
                exit;
            }
        }
    } else {
        $data_step = 2;
        $list_station_txt = $_POST['select_list_station_txt'];
        $nb_stations = count(explode(",", $list_station_txt));
        $message_info .= TEXT_SELECT_AT_LEAST_ONE_CHRONIC;
    }
}

// Change date period
if(isset($_POST['button_change_date'])) {
    $data_step = 2;
    $list_station_txt = $_POST['select_list_station_txt'];
    $nb_stations = count(explode(",", $list_station_txt));
}

// DATE_ALL TABLE
$where_typedata = '';
if($select_type_encours > 0) {$where_typedata = "WHERE dm.id_typedata = ".$select_type_encours;}

// Display appropriate form based on step
if($data_step == 1) {require(DIR_WS_CHRON . 'form_chron_step1.php');}
if($data_step == 2) {require(DIR_WS_CHRON . 'form_chron_step2.php');}
?>