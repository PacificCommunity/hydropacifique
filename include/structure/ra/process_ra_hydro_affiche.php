<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure to display an Activity Report (RA) in the display block
Field form display - Hydrology River
Asynchronous AJAX server-side process
----------------------------------------
*/

// Configuration
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die('Unable to connect to database!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// Get JSON data
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extract data
$territoire_id = $dataInfo['territoire_id'];
$timezone_php = $dataInfo['timezone_php'];
$id_user = $dataInfo['id_user'];
$id_ra = $dataInfo['id_ra'];
$where_station = $dataInfo['where_station'];
$check_modif = $dataInfo['check_modif'];
$ra_nav_array = json_decode($dataInfo['ra_nav_json'], true);

// Time management
date_default_timezone_set($timezone_php);

$today_date = new DateTime();
$today_date_formatted = $today_date->format('d-m-Y');

$today_time = new DateTime();
$today_time_formatted = $today_time->format('H:i:s');

$today_datetime = new DateTime();
$today_datetime_formatted = $today_datetime->format('d-m-Y H:i:s');

// Initialize variables
$ra_exist = false;
$type_data = 11; // 11: Hydrology
$unite_ra = "cm";

if($id_ra > 0) {
    $id_type_ra = $ra_nav_array[$id_ra]['id_type_ra'];
    $prev_id_ra = $ra_nav_array[$id_ra]['prev_id_ra'];
    $prev_type_ra = $ra_nav_array[$id_ra]['prev_type_ra'];
    $next_id_ra = $ra_nav_array[$id_ra]['next_id_ra'];
    $next_type_ra = $ra_nav_array[$id_ra]['next_type_ra'];
    $num_ra = $ra_nav_array[$id_ra]['num_ra'];
    $nb_ra = $ra_nav_array[$id_ra]['nb_ra'];

    $first_id_ra = array_key_first($ra_nav_array);
    $first_type_ra = $ra_nav_array[$first_id_ra]['id_type_ra'];

    $last_id_ra = array_key_last($ra_nav_array);
    $last_type_ra = $ra_nav_array[$last_id_ra]['id_type_ra'];
} else {
    $id_type_ra = $type_data;
    $prev_id_ra = 0;
    $prev_type_ra = 0;
    $next_id_ra = 0;
    $next_type_ra = 0;
    $num_ra = 1;
    $nb_ra = 1;

    $first_id_ra = 0;
    $first_type_ra = 0;

    $last_id_ra = 0;
    $last_type_ra = 0;

    $ra_exist = true;
}

// =============================================================================
// GET REFERENCE DATA
// =============================================================================

// User list
$sql_user_list = "SELECT DISTINCT id, id_statut, login, nom, prenom FROM ".TABLE_USER;
$user_list_query = tep_db_query($sql_link, $sql_user_list);
$user_list_array = array();
while ($user_list = tep_db_fetch_array($user_list_query)) {
    $id = $user_list['id'];
    $id_statut = $user_list['id_statut'];
    $login = htmlaccent(html_entity_decode($user_list['login'] ?? ''));
    $nom = ucfirst(strtolower(htmlaccent(html_entity_decode($user_list['nom'] ?? ''))));
    $prenom = ucfirst(strtolower(htmlaccent(html_entity_decode($user_list['prenom'] ?? ''))));

    $user_list_array[$id] = array(
        'id_statut' => $id_statut,
        'login' => $login,
        'nom' => $nom,
        'prenom' => $prenom
    );
}

// Station list
$sql_station_all = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.station_type, s.active_station,
                    s.id_region, s.id_regionhydro, s.id_riviere, s.id_tournee, s.id_commune
                    FROM ".TABLE_STATION." s
                    LEFT JOIN ".TABLE_STATION_TO_TOURNEE." t ON t.id_station = s.id_station
                    WHERE s.station_type=".$type_data." ".$where_station."
                    ORDER BY s.nom_station ASC";
$station_all_query = tep_db_query($sql_link, $sql_station_all);
$station_all_array = array();
while ($station_all = tep_db_fetch_array($station_all_query)) {
    $nom_station = htmlaccent(html_entity_decode($station_all['nom_station'] ?? ''));

    $station_all_array[$station_all['id_station']] = array(
        'code_station' => $station_all['code_station'],
        'nom_station' => $nom_station,
        'station_type' => $station_all['station_type']
    );
}

// Equipment types
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border,
                type_color_background, type_graph
                FROM ".TABLE_EQ_TYPE."
                WHERE active_eq_type=1
                ORDER BY order_eq_type ASC";
$eq_type_query = tep_db_query($sql_link, $sql_eq_type);
$eq_type_array = array();
while ($eq_type_tab = tep_db_fetch_array($eq_type_query)) {
    $eq_type_array[$eq_type_tab['id_eq_type']] = array(
        'nom_eq_type' => $eq_type_tab['nom_eq_type'],
        'unite_eq_type' => $eq_type_tab['unite_eq_type'],
        'valeur_data_type' => $eq_type_tab['valeur_data_type'],
        'type_graph' => $eq_type_tab['type_graph'],
        'type_color_border' => $eq_type_tab['type_color_border'],
        'type_color_background' => $eq_type_tab['type_color_background']
    );
}

// Agent list
$sql_agent = "SELECT DISTINCT id, nom, prenom
              FROM ".TABLE_AGENT."
              WHERE terrain=1
              ORDER BY nom ASC";
$agent_query = tep_db_query($sql_link, $sql_agent);
$agent_array = array();
while($agent = tep_db_fetch_array($agent_query)) {
    $nom_agent = noaccent(html_entity_decode($agent['nom'] ?? ''));
    $prenom_agent = noaccent(html_entity_decode($agent['prenom'] ?? ''));
    $prenom_agent_initial = $prenom_agent[0] . ".";

    $agent_array[$agent['id']] = $prenom_agent_initial." ".$nom_agent;
}

// =============================================================================
// INITIALIZE VARIABLES
// =============================================================================
$tab_html = '';
$row = 0;

// General variables
$from_nomad = 1;
$new_nomad = 1;
$hp_load = 0;

$nom_data = $eq_type_array[$type_data]['nom_eq_type'];
$etat_ra = 0;

$date_heure_saisie_fr = $today_datetime_formatted;
$date_ra = $today_date_formatted;
$heure_ra = '';

$id_agent_user = $id_user;
$id_station = 0;

$name_file_data = '';

// Equipment variables
$type_appareil = '';
$num_appareil = '';

$heure_appareil = '';
$hydro_num_sonde = '';

$nb_octet = '';
$num_batterie = '';
$tension_batterie = '';

// Water level variables
$hydro_heure_cote = '';
$hydro_h_sonde = '';
$hydro_h_echelle_1 = '';
$hydro_h_echelle_2 = '';

// Old equipment variables
$num_cassette = '';
$heure_init_cassette = '';
$hydro_h_sonde_cassette = '';

$cassette_time_save_jj = '';
$cassette_time_save_hh = '';
$cassette_time_save_mm = '';
$cassette_time_save_ss = '';
$cassette_last_save_jj = '';
$cassette_last_save_hh = '';
$cassette_last_save_mm = '';
$cassette_last_save_ss = '';

// Control variables
$hech_hsonde = '';
$hydro_recalage_sonde = '';
$hydro_recalage_heure_sonde = '';
$hydro_purge_sonde = 0;

// Observations
$check_jaugeage = 0;
$check_debrouss = 0;
$check_eaubat = 0;
$check_transfert = 0;
$check_deletememory = 0;
$ra_obs = '';
$ra_futur = '';

$obs_file_data = '';
$check_premarquant = 0;
$check_faitmarquant = 0;


$agents_complement = '';

if($id_ra > 0) {
    $sql_RA = "SELECT DISTINCT ra.id_ra, ra.from_nomad, ra.new_nomad, ra.hp_load,
                ra.datetime_saisie, ra.id_agent_user, ra.id_station,
                ra.date_heure_ra, ra.id_eq_type,
                ra.type_appareil, ra.num_appareil, ra.heure_appareil, ra.etat_ra,
                ra.nb_octet, ra.num_batterie, ra.tension_batterie, ra.num_cassette, ra.heure_init_cassette,
                ra.hydro_heure_cote, ra.hydro_h_sonde, ra.hydro_h_echelle_1, ra.hydro_h_echelle_2, ra.hydro_num_sonde,
                ra.hydro_h_sonde_cassette,
                ra.cassette_time_save_jj, ra.cassette_time_save_hh, ra.cassette_time_save_mm, ra.cassette_time_save_ss,
                ra.cassette_last_save_jj, ra.cassette_last_save_hh, ra.cassette_last_save_mm, ra.cassette_last_save_ss,
                ra.hydro_recalage_sonde, ra.hydro_recalage_heure_sonde, ra.hydro_purge_sonde, ra.hydro_ra_jaugeage,
                ra.ra_debroussaillage, ra.ra_eau_batterie, ra.ra_transfert_data, ra.ra_delete_memory,
                ra.ra_obs, ra.ra_futur, ra.name_file_data, ra.obs_file_data, ra.pre_marquant, ra.fait_marquant, ra.agents_complement
                FROM ".TABLE_DATA_RA." ra
                WHERE id_ra = ".$id_ra;

    $RA_query = tep_db_query($sql_link, $sql_RA);

    if (tep_db_num_rows($RA_query) > 0) {
        $ra_exist = true;
        $RA_tab = tep_db_fetch_array($RA_query);

        $from_nomad = $RA_tab['from_nomad'];
        $new_nomad = $RA_tab['new_nomad'];
        $hp_load = $RA_tab['hp_load'];

        $etat_ra = $RA_tab['etat_ra'];

        if(tep_not_null($RA_tab['id_agent_user'])) {
            $id_agent_user = $RA_tab['id_agent_user'];
        } else {
            $id_agent_user = 0;
        }

        $id_station = $RA_tab['id_station'];

        if($RA_tab['datetime_saisie'] !== null) {
            $date_heure_saisie_tab = explode(" ", $RA_tab['datetime_saisie']);
            $date_heure_saisie_fr = dateus_fr($date_heure_saisie_tab[0]).' '.$date_heure_saisie_tab[1];
        } else {
            $date_heure_saisie_fr = '';
        }

        $date_heure_ra_tab = explode(" ", $RA_tab['date_heure_ra']);
        $date_ra = dateus_fr($date_heure_ra_tab[0]);
        $heure_ra = $date_heure_ra_tab[1];
        if($heure_ra == '00:00:00') {$heure_ra = '';}

        $name_file_data = $RA_tab['name_file_data'];

        $type_appareil = $RA_tab['type_appareil'];
        $num_appareil = $RA_tab['num_appareil'];

        $heure_appareil = $RA_tab['heure_appareil'];
        if($heure_appareil == '00:00:00') {$heure_appareil = '';}

        $hydro_num_sonde = $RA_tab['hydro_num_sonde'];
        $nb_octet = nettoyer_et_echapper($RA_tab['nb_octet']);
        $num_batterie = nettoyer_et_echapper($RA_tab['num_batterie']);
        $tension_batterie = nettoyer_et_echapper($RA_tab['tension_batterie']);

        $hydro_heure_cote = $RA_tab['hydro_heure_cote'];
        if($hydro_heure_cote == '00:00:00') {$hydro_heure_cote = '';}

        $hydro_h_sonde = $RA_tab['hydro_h_sonde'];
        $hydro_h_echelle_1 = $RA_tab['hydro_h_echelle_1'];
        $hydro_h_echelle_2 = $RA_tab['hydro_h_echelle_2'];

        $hydro_h_sonde_cassette = $RA_tab['hydro_h_sonde_cassette'];
        $num_cassette = $RA_tab['num_cassette'];

        $heure_init_cassette = $RA_tab['heure_init_cassette'];
        if($heure_init_cassette == '00:00:00') {$heure_init_cassette = '';}        

        $cassette_time_save_jj = $RA_tab['cassette_time_save_jj'];
        $cassette_time_save_hh = $RA_tab['cassette_time_save_hh'];
        $cassette_time_save_mm = $RA_tab['cassette_time_save_mm'];
        $cassette_time_save_ss = $RA_tab['cassette_time_save_ss'];
        $cassette_last_save_jj = $RA_tab['cassette_last_save_jj'];
        $cassette_last_save_hh = $RA_tab['cassette_last_save_hh'];
        $cassette_last_save_mm = $RA_tab['cassette_last_save_mm'];
        $cassette_last_save_ss = $RA_tab['cassette_last_save_ss'];

        $hech_hsonde = round($hydro_h_echelle_1 - $hydro_h_sonde, 2);
        $hydro_recalage_sonde = $RA_tab['hydro_recalage_sonde'];

        $hydro_recalage_heure_sonde = $RA_tab['hydro_recalage_heure_sonde'];
        if($hydro_recalage_heure_sonde == '00:00:00') {$hydro_recalage_heure_sonde = '';}

        $hydro_purge_sonde = $RA_tab['hydro_purge_sonde'];

        $check_jaugeage = $RA_tab['hydro_ra_jaugeage'];
        $check_debrouss = $RA_tab['ra_debroussaillage'];
        $check_eaubat = $RA_tab['ra_eau_batterie'];
        $check_transfert = $RA_tab['ra_transfert_data'];
        $check_deletememory = $RA_tab['ra_delete_memory'];

        $ra_obs = nettoyer_et_echapper($RA_tab['ra_obs']);
        $ra_futur = nettoyer_et_echapper($RA_tab['ra_futur']);

        $name_file_data = nettoyer_et_echapper($RA_tab['name_file_data']);
        $obs_file_data = nettoyer_et_echapper($RA_tab['obs_file_data']);

        $check_premarquant = $RA_tab['pre_marquant'];
        $check_faitmarquant = $RA_tab['fait_marquant'];

        $agents_complement = nettoyer_et_echapper($RA_tab['agents_complement']);
    }
}

// =============================================================================
// HTML EDITION
// =============================================================================
$border_cadre = 'border: 4px solid '.$eq_type_array[$type_data]['type_color_border'].';';
$background_cadre = 'background-color:'.$eq_type_array[$type_data]['type_color_background'].';';

// ============================================================================
// MAIN POPUP CONTAINER
// ============================================================================
$tab_html .= "<div id='cadre_view' class='cadre_view' style='width:1240px;margin-top:10px;max-height: 95vh; overflow-y: auto;".$border_cadre.$background_cadre."'>";
    $tab_html .= "<form id='formRA'>";
        $tab_html .= "<input type='hidden' value='".$type_data."' name='type_data' id='type_data'>";
        $tab_html .= "<input type='hidden' value='".$id_ra."' name='id_ra' id='id_ra'>";
        $tab_html .= "<div id='cadre_limit'>";

        if(isset($station_all_array) && sizeof($station_all_array) > 0) {
            if($ra_exist) {

                // ====================================================================
                // HEADER : Title + station selection + entry info
                // ====================================================================
                $tab_html .= "<table id='tab_titre_popup' cellspacing='0'>";
                    $tab_html .= "<tr>";
                        $tab_html .= "<td class='titre'>";

                            $affiche_modif = 'display:none;';
                            $checked = '';
                            if(HP_VERSION == 'Serveur' || ($from_nomad > 0 && $hp_load < 1)) {
                                $affiche_modif = '';
                                if($check_modif==1) {$checked = 'checked';}
                            }

                            // ---- Modify checkbox ----
                            $tab_html .= "<div style='float:left;width:30px;height:30px;margin-right:50px;'>";
                                $tab_html .= "<div style='".$affiche_modif."'>";
                                    $tab_html .= "<span style='font-size:12px;color:#000;'>".TEXT_MODIFY."</span><br>";
                                    $tab_html .= "<input type='checkbox' name='check_modif_ra' id='check_modif_ra'
                                                  title='".TEXT_MODIFY."'
                                                  style='float:left;width:25px;height:25px;margin-left:0px;' ".$checked.">";
                                $tab_html .= "</div>";
                            $tab_html .= "</div>";

                            // ---- Measurement type display ----
                            $tab_html .= "<p style='width:15%;'>";
                                $tab_html .= "<input type='text' id='station_type' value='".$nom_data."' disabled style='font-weight:bold;font-size:22px;'>";
                            $tab_html .= "</p>";

                            // ---- Station selection ----
                            $id_station_select = (isset($id_station) && $id_station > 0) ? $id_station : 0;

                            $tab_html .= "<div style='float:left;width:600px;margin-top:10px;'>";
                                $tab_html .= "<select name='select_station_ra' id='select_station_ra' class='titre_ra'
                                              style='width:100%;'
                                              onChange='lists_reload();'>";

                                    $tab_html .= "<option value=''";
                                    if ($id_station_select === 0) {$tab_html .= " selected";}
                                    $tab_html .= ">".TEXT_SELECT_STATION."</option>";

                                    if(isset($station_all_array)) {
                                        foreach($station_all_array as $key => $value) {
                                            $selected = ($key == $id_station) ? "selected" : '';
                                            $tab_html .= "<option value='".$key."' ".$selected." style=''>".$value['code_station']." - ".$value['nom_station']."</option>";
                                        }
                                    }

                                $tab_html .= "</select>";
                            $tab_html .= "</div>";

                            // ---- Validation status puce ----
                            if($etat_ra > 0) {
                                $tab_html .= "<img src='".DIR_WS_IMG_ICO."puce_verte.png' id='valid_puce_ok' style='float:left;margin-left:2%;' title='".TEXT_RA_VALIDATED."'>";
                            } else {
                                $tab_html .= "<img src='".DIR_WS_IMG_ICO."puce_rouge.png' id='valid_puce_no' style='float:left;margin-left:2%;' title='".TEXT_RA_NOT_VALIDATED."'>";
                            }

                            // ---- Entry date and agent ----
                            $tab_html .= "<p style='float:right;width:255px;margin-top:0px;'>";
                                $tab_html .= "<span style='font-size:16px;'>".TEXT_ENTERED_ON."</span>";
                                $tab_html .= "<input type='text' name='date_heure_saisie_fr' id='date_heure_saisie_fr' value='".$date_heure_saisie_fr."' readonly>";
                                $tab_html .= "<br>";
                                if(isset($user_list_array[$id_agent_user])) {
                                    $tab_html .= "<span style='font-size:16px;'>".TEXT_BY."</span>";
                                    $tab_html .= "<input type='text' style='padding:0;' name='agent_saisie' id='agent_saisie' value='".$user_list_array[$id_agent_user]['prenom'].' '.$user_list_array[$id_agent_user]['nom']."' disabled>";
                                } else {
                                    $tab_html .= "<span style='font-size:16px;'>".TEXT_BY." - </span>";
                                }
                            $tab_html .= "</p>";

                        $tab_html .= "</td>";
                    $tab_html .= "</tr>";
                $tab_html .= "</table>";


                // ====================================================================
                // LINE 1 : Reading + Device + Device state
                // ====================================================================

                // ---- Box: Data reading ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_READING."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_DATE."</p>";
                        $tab_html .= "<input style='width:80px;' id='date_releve' name='date_releve'
                                      value='".$date_ra."' onFocus='initDatepickers(this)'
                                      type='text' placeholder='dd-mm-yyyy'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_TIME."</p>";
                        $tab_html .= "<input name='heure_releve' id='heure_releve'
                                      value='".$heure_ra."'
                                      placeholder='hh:mm:ss' style='width:55px;' type='text'>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC') 
                    {
                        $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                            $tab_html .= "<p>".TEXT_READING_FILE_NAME."</p>";
                            $tab_html .= "<input name='fichier_releve' id='fichier_releve' value='".$name_file_data."' style='width:200px;' type='text'>";
                        $tab_html .= "</div>";
                    }

                $tab_html .= "</div>"; // end Box: Reading


                // ---- Box: Device ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_DEVICE."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_TYPE."</p>";
                        $tab_html .= "<input type='hidden' value='".$type_appareil."' name='nom_appareil' id='nom_appareil'>";
                        $tab_html .= "<select name='select_type_appareil' id='select_type_appareil' style='width:140px;'></select>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC') 
                    {
                        $tab_html .= "<div id='boxContent-RA-small'>";
                            $tab_html .= "<p>".TEXT_NUMBER."</p>";
                            $tab_html .= "<input type='hidden' value='".$num_appareil."' name='num_appareil' id='num_appareil'>";
                            $tab_html .= "<select name='select_num_appareil' id='select_num_appareil' style='width:90px;'></select>";
                        $tab_html .= "</div>";
                    }

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_TIME."</p>";
                        $tab_html .= "<input name='heure_appareil' id='heure_appareil'
                                      value='".$heure_appareil."' placeholder='hh:mm:ss'
                                      style='width:55px;' type='text'>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Device


                // ---- Box: Device state ----
                $tab_html .= "<div id='boxContent-RA'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_DEVICE_STATE."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_NB_BYTES."</p>";
                        $tab_html .= "<input name='nb_octet' id='nb_octet' value='".$nb_octet."' class='input_texte_xsmall' type='text'>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC')
                    {
                        $tab_html .= "<div id='boxContent-RA-small'>";
                            $tab_html .= "<p>".TEXT_BATTERY_NUM."</p>";
                            $tab_html .= "<input name='num_batterie' id='num_batterie' value='".$num_batterie."' class='input_texte' type='text'>";
                        $tab_html .= "</div>";
                    }

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_BATTERY_VOLTAGE."</p>";
                        $tab_html .= "<input name='tension_batterie' id='tension_batterie' value='".$tension_batterie."' class='input_texte_xsmall' type='text'>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Device state

                $tab_html .= "<hr>";


                // ====================================================================
                // LINE 2 : Probe info + Water level + Hydrology control
                // ====================================================================

                // ---- Box: Probe info ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_PROBE_INFO."</p>";
                    $tab_html .= "</div>";

                    
                    $tab_html .= "<div id='boxContent-RA-small' style='width:90px;'>";
                        $tab_html .= "<p>".TEXT_NUMBER."</p>";
                        $tab_html .= "<input type='hidden' value='".$hydro_num_sonde."' name='hydro_num_sonde' id='hydro_num_sonde'>";
                        $tab_html .= "<select name='select_hydro_num_sonde' id='select_hydro_num_sonde' style='width:110px;'></select>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Probe info


                // ---- Box: Water level ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_WATER_LEVEL."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_TIME."</p>";
                        $tab_html .= "<input name='hydro_heure_cote' id='hydro_heure_cote'
                                      value='".$hydro_heure_cote."'
                                      placeholder='hh:mm:ss'
                                      style='width:55px;' type='text'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_PROBE_HEIGHT."</p>";
                        $tab_html .= "<input name='hydro_h_sonde' id='hydro_h_sonde'
                                      value='".$hydro_h_sonde."'
                                      class='input_texte_small'
                                      style='width:40px;'
                                      type='text'
                                      oninput='hydro_calcDiff()'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_SCALE_HEIGHT."</p>";
                        $tab_html .= "<input name='hydro_h_echelle_1' id='hydro_h_echelle_1'
                                      value='".$hydro_h_echelle_1."'
                                      class='input_texte_small'
                                      style='width:40px;'
                                      type='text'
                                      oninput='hydro_calcDiff()'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_SCALE_HEIGHT_2."</p>";
                        $tab_html .= "<input name='hydro_h_echelle_2' id='hydro_h_echelle_2'
                                      value='".$hydro_h_echelle_2."'
                                      class='input_texte_small'
                                      style='width:40px;'
                                      type='text'
                                      oninput='hydro_calcDiff()'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Water level


                // ---- Box: Hydrology control ----
                $tab_html .= "<div id='boxContent-RA'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_HYDRO_CONTROL."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_SCALE_PROBE_DIFF."</p>";
                        $tab_html .= "<input name='hech_hsonde' id='hech_hsonde'
                                      value='".$hech_hsonde."'
                                      class='input_texte_small'
                                      style='width:40px;'
                                      type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    
                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_PROBE_ADJUSTMENT."</p>";
                        $tab_html .= "<input name='hydro_recalage_sonde' id='hydro_recalage_sonde'
                                      value='".$hydro_recalage_sonde."'
                                      class='input_texte_small'
                                      style='width:40px;'
                                      type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC')
                    {
                        $tab_html .= "<div id='boxContent-RA-small'>";
                            $tab_html .= "<p>".TEXT_PROBE_TIME_ADJUSTMENT."</p>";
                            $tab_html .= "<input name='hydro_recalage_heure_sonde' id='hydro_recalage_heure_sonde'
                                        value='".$hydro_recalage_heure_sonde."'
                                        class='input_texte_small'
                                        style='width:40px;'
                                        type='text'>";
                            $tab_html .= "<span style='margin-left:5px;font-size:14px;'>min</span>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($hydro_purge_sonde>0) {$checked = 'checked';}
                        $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                            $tab_html .= "<p>".TEXT_DATA_PURGE."</p>";
                            $tab_html .= "<input class='input_texte' style='width:25px;height:25px;margin-right:10px;'
                                        name='check_purge_sonde' id='check_purge_sonde' type='checkbox' ".$checked.">";
                        $tab_html .= "</div>";
                    }

                $tab_html .= "</div>"; // end Box: Hydro control

                $tab_html .= "<hr>";


                // ====================================================================
                // LINE 3 : Old equipment (NC only)
                // ====================================================================
                if(INIT_T == 'NC') {

                    $tab_html .= "<p onclick='display_FieldRA()' id='toggleFieldsLink' style='margin-left:5px;cursor:pointer;'>";
                        $tab_html .= TEXT_TOGGLE_SHOW_FIELDS;
                    $tab_html .= "</p>";

                    $tab_html .= "<div id='displayFieldRa' style='display:none;'>";

                        // ---- Box: New cassette ----
                        $tab_html .= "<div id='boxContent-RA'>";

                            $tab_html .= "<div id='boxContent-RA-title'>";
                                $tab_html .= "<p>".TEXT_NEW_CASSETTE."</p>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_CASSETTE_NUM."</p>";
                                $tab_html .= "<input name='num_cassette' id='num_cassette' value='".$num_cassette."' class='input_texte' style='width:70px;' type='text'>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_INIT_TIME."</p>";
                                $tab_html .= "<input name='heure_init_cassette' id='heure_init_cassette'
                                            value='".$heure_init_cassette."'
                                            placeholder='hh:mm:ss'
                                            style='width:55px;' type='text'>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                                $tab_html .= "<p>".TEXT_PROBE_HEIGHT."</p>";
                                $tab_html .= "<input name='hydro_h_sonde_cassette' id='hydro_h_sonde_cassette'
                                            value='".$hydro_h_sonde_cassette."'
                                            class='input_texte_small'
                                            style='width:40px;'
                                            type='text'>";
                                $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                            $tab_html .= "</div>";

                        $tab_html .= "</div>"; // end Box: New cassette


                        // ---- Box: Recording duration ----
                        $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                            $tab_html .= "<div id='boxContent-RA-title'>";
                                $tab_html .= "<p>".TEXT_RECORDING_DURATION."</p>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_DAYS."</p>";
                                $tab_html .= "<input name='cassette_time_save_jj' id='cassette_time_save_jj' value='".$cassette_time_save_jj."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_HOURS."</p>";
                                $tab_html .= "<input name='cassette_time_save_hh' id='cassette_time_save_hh' value='".$cassette_time_save_hh."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_MINUTES."</p>";
                                $tab_html .= "<input name='cassette_time_save_mm' id='cassette_time_save_mm' value='".$cassette_time_save_mm."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                                $tab_html .= "<p>".TEXT_SECONDES."</p>";
                                $tab_html .= "<input name='cassette_time_save_ss' id='cassette_time_save_ss' value='".$cassette_time_save_ss."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                        $tab_html .= "</div>"; // end Box: Recording duration


                        // ---- Box: Last recording ----
                        $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                            $tab_html .= "<div id='boxContent-RA-title'>";
                                $tab_html .= "<p>".TEXT_LAST_RECORDING."</p>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_DAYS."</p>";
                                $tab_html .= "<input name='cassette_last_save_jj' id='cassette_last_save_jj' value='".$cassette_last_save_jj."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_HOURS."</p>";
                                $tab_html .= "<input name='cassette_last_save_hh' id='cassette_last_save_hh' value='".$cassette_last_save_hh."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small'>";
                                $tab_html .= "<p>".TEXT_MINUTES."</p>";
                                $tab_html .= "<input name='cassette_last_save_mm' id='cassette_last_save_mm' value='".$cassette_last_save_mm."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                            $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                                $tab_html .= "<p>".TEXT_SECONDES."</p>";
                                $tab_html .= "<input name='cassette_last_save_ss' id='cassette_last_save_ss' value='".$cassette_last_save_ss."' class='input_texte_xsmall' type='text' readonly>";
                            $tab_html .= "</div>";

                        $tab_html .= "</div>"; // end Box: Last recording

                    $tab_html .= "</div>"; // end #displayFieldRa
                }


                // ====================================================================
                // LINE 4 : Observations + Future actions
                // ====================================================================

                // ---- Box: Observations ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title' style='height:35px;'>";

                        $tab_html .= "<div style='float:left;width:230px;'>";
                            $tab_html .= "<p>".TEXT_OBSERVATIONS."</p>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_faitmarquant>0) {$checked = 'checked';}
                        $tab_html .= "<div style='float:right;width:200px;'>";
                            $tab_html .= "<input class='input_texte' style='float:left;width:25px;height:20px;margin-top:2px;margin-right:8px;' name='check_faitmarquant' id='check_faitmarquant' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;padding-top:4px;font-size:14px;font-weight:bold;'>".TEXT_MARKABLE_ACTION."</span>";
                        $tab_html .= "</div>";

                    $tab_html .= "</div>"; // end title

                    // Textarea
                    $tab_html .= "<div id='boxContent-RA-small' style='margin:0;'>";
                        $tab_html .= "<textarea name='ra_obs' id='ra_obs' style='width:350px;height:70px;font-size:13px;'>".$ra_obs."</textarea>";
                    $tab_html .= "</div>";

                    // Checkboxes - First row
                    $tab_html .= "<div id='boxContent-RA-small' style='margin:0;'>";

                        $checked = '';
                        if($check_jaugeage>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_jaugeage' id='check_jaugeage' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:2px;width:110px;font-size:13px;'>".TEXT_GAUGING."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_debrouss>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_debrouss' id='check_debrouss' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:2px;width:110px;font-size:13px;'>".TEXT_CLEARING."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                    $tab_html .= "</div>"; // end checkboxes row 1

                    // Checkboxes - Second row
                    $tab_html .= "<div id='boxContent-RA-small' style='margin:0;'>";

                        if(INIT_T == 'NC')
                        {
                            $checked = '';
                            if($check_eaubat>0) {$checked = 'checked';}
                            $tab_html .= "<div>";
                                $tab_html .= "<input class='input_texte' style='width:25px;' name='check_eaubat' id='check_eaubat' type='checkbox' ".$checked.">";
                                $tab_html .= "<span style='float:left;margin-top:2px;width:110px;font-size:13px;'>".TEXT_BATTERY_WATER."</span>";
                                $tab_html .= "<hr>";
                            $tab_html .= "</div>";
                        }

                        $checked = '';
                        if($check_transfert>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_transfert' id='check_transfert' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:2px;width:110px;font-size:13px;'>".TEXT_DATA_TRANSFER."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_deletememory>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_deletememory' id='check_deletememory' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:2px;width:110px;font-size:13px;'>".TEXT_MEMORY_CLEARED."</span>";
                        $tab_html .= "</div>";

                    $tab_html .= "</div>"; // end checkboxes row 2

                $tab_html .= "</div>"; // end Box: Observations


                // ---- Box: Future actions ----
                $tab_html .= "<div id='boxContent-RA'>";

                    $tab_html .= "<div id='boxContent-RA-title' style='height:35px;'>";

                        $tab_html .= "<div style='float:left;width:300px;'>";
                            $tab_html .= "<p>".TEXT_FUTURE_ACTIONS."</p>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_premarquant>0) {$checked = 'checked';}
                        $tab_html .= "<div style='float:right;width:180px;'>";
                            $tab_html .= "<input class='input_texte' style='float:left;width:25px;height:20px;margin-top:2px;margin-right:8px;' name='check_premarquant' id='check_premarquant' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;padding-top:4px;font-size:14px;font-weight:bold;'>".TEXT_IMPORTANT_ACTION."</span>";
                        $tab_html .= "</div>";

                    $tab_html .= "</div>"; // end title

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<textarea name='ra_futur' id='ra_futur' style='width:480px;height:70px;font-size:13px;'>".$ra_futur."</textarea>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Future actions

                $tab_html .= "<hr>";


                // ====================================================================
                // LINE 5 : Agents
                // ====================================================================
                $tab_html .= "<div id='boxContent-RA'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_AGENTS_PARTICIPATED."</p>";
                    $tab_html .= "</div>";

                    // Checkboxes for agents
                    $tab_html .= "<div id='boxContent-RA-small' style='float:left;width:600px;'>";

                        if (!isset($agents_complement) || is_null($agents_complement)) {
                            $agents_complement_upper = '';
                        } else {
                            $agents_complement_upper = strtoupper($agents_complement);
                        }

                        if(isset($agent_array)) {
                            foreach($agent_array as $key => $value) {
                                $parts = explode(' ', $value);
                                $agent_nom = strtoupper(end($parts));

                                $checked = (strpos($agents_complement_upper, $agent_nom) !== false) ? 'checked' : '';

                                $tab_html .= "<div style='float:left;'>";
                                    $tab_html .= "<input class='input_texte' style='width:15px;height:15px;padding:0;' name='check_agent_".$key."' id='check_agent_".$key."' type='checkbox' data-value='".$value."' onchange='updateSelectedAgents();' ".$checked.">";
                                    $tab_html .= "<span style='float:left;padding-top:4px;margin-right:8px;font-size:13px;'>".$value."</span>";
                                    $tab_html .= "<hr>";
                                $tab_html .= "</div>";
                            }
                        }

                    $tab_html .= "</div>"; // end agent checkboxes

                    // Free text agents
                    $tab_html .= "<div id='boxContent-RA-small' style='float:left;width:480px;margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_PARTICIPANTS."</p>";
                        $tab_html .= "<textarea name='agents_complement' id='agents_complement' style='width:470px;height:30px;font-size:13px;'>".$agents_complement."</textarea>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Agents

                $tab_html .= "<hr>";


                // ====================================================================
                // FOOTER : Navigation arrows + Save / Cancel
                // ====================================================================
                $tab_html .= "<div id='popup_barredown' style='height:50px;margin-top:0px;'>";

                    // ---- Navigation arrows ----
                    $tab_html .= "<div id='popup_nav' style='width:420px;'>";

                        // Previous arrows
                        $tab_html .= "<div id='content_arrow' class='content_arrow'>";
                            if($num_ra > 1) {
                                $tab_html .= "<div id='arrow_previous'>";

                                    $tab_html .= "<a id='arrow_first_a' href='#' onclick='loadRA(".$first_id_ra.",".$first_type_ra."); return false;'>";
                                        $tab_html .= "<img src='".DIR_WS_IMG_ICO."arrow_first.png' style='width:50px;margin-right:30px;cursor:pointer;' title='".TEXT_FIRST_RA."'
                                                      onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_first_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_first.png';\" >";
                                    $tab_html .= "</a>";

                                    $tab_html .= "<a id='arrow_previous_a' href='#' onclick='loadRA(".$prev_id_ra.",".$prev_type_ra."); return false;'>";
                                        $tab_html .= "<img src='".DIR_WS_IMG_ICO."arrow_previous.png' style='width:25px;cursor:pointer;' title='".TEXT_PREVIOUS_RA."'
                                                      onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_previous_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_previous.png';\" >";
                                    $tab_html .= "</a>";

                                $tab_html .= "</div>";
                            }
                        $tab_html .= "</div>";

                        // RA counter
                        $tab_html .= "<div id='content_arrow' class='content_arrow'>";
                            $tab_html .= "<input type='text' value='".$num_ra." / ".$nb_ra."' id='num_fiche' disabled>";
                        $tab_html .= "</div>";

                        // Next arrows
                        if($num_ra < $nb_ra) {
                            $tab_html .= "<div id='content_arrow' class='content_arrow'>";
                                $tab_html .= "<div id='arrow_next'>";

                                    $tab_html .= "<a id='arrow_next_a' href='#' onclick='loadRA(".$next_id_ra.",".$next_type_ra.",".$check_modif."); return false;'>";
                                        $tab_html .= "<img src='".DIR_WS_IMG_ICO."arrow_next.png' style='width:25px;cursor:pointer;' title='".TEXT_NEXT_RA."'
                                                      onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_next_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_next.png';\" >";
                                    $tab_html .= "</a>";

                                    $tab_html .= "<a id='arrow_last_a' href='#' onclick='loadRA(".$last_id_ra.",".$last_type_ra.",".$check_modif."); return false;'>";
                                        $tab_html .= "<img src='".DIR_WS_IMG_ICO."arrow_end.png' style='width:50px;margin-left:30px;cursor:pointer;' title='".TEXT_LAST_RA."'
                                                      onmouseover=\"this.src='".DIR_WS_IMG_ICO."arrow_end_over.png';\" onmouseout=\"this.src='".DIR_WS_IMG_ICO."arrow_end.png';\" >";
                                    $tab_html .= "</a>";

                                $tab_html .= "</div>";
                            $tab_html .= "</div>";
                        }

                    $tab_html .= "</div>"; // end navigation arrows


                    // ---- Validation + Save / Cancel buttons ----
                    $display_modif_ra = 'display:none';
                    if($check_modif==1) {$display_modif_ra = 'display:block';}

                    $tab_html .= "<div id='popup_nav' class='modif_ok' style='width:650px;margin-left:20px;".$display_modif_ra."'>";

                        $tab_html .= "<table id='stats_select' cellspacing='0'>";
                            $tab_html .= "<tr style='margin:0;'>";

                                $tab_html .= "<td class='bold' style='width:200px;'>";

                                    if(HP_VERSION == 'Serveur') {
                                        $checked = '';
                                        if($etat_ra > 0) {$checked = 'checked';}

                                        $tab_html .= "<div id='bloc_valid_ra'>";
                                            $tab_html .= "<p style='float:left;font-size:14px;text-align:center;padding-top:5px;'>".TEXT_RA_VALIDATION."</p>";
                                            $tab_html .= "<input type='checkbox' name='check_valid_ra' id='check_valid_ra' style='float:left;width:30px;height:30px;margin-left:15px;' ".$checked.">";
                                        $tab_html .= "</div>";

                                        if($etat_ra > 0) {
                                            $tab_html .= "<img src='".DIR_WS_IMG_ICO."puce_verte.png' id='valid_puce_ok' style='float:left;width: 35px;margin-left:15px;' title='".TEXT_RA_VALIDATED."'>";
                                        } else {
                                            $tab_html .= "<img src='".DIR_WS_IMG_ICO."puce_rouge.png' id='valid_puce_no' style='float:left;width: 35px;margin-left:15px;' title='".TEXT_RA_NOT_VALIDATED."'>";
                                        }
                                    }

                                $tab_html .= "</td>";

                                if(HP_VERSION == 'Serveur' || ($from_nomad > 0 && $hp_load < 1)) {
                                    $tab_html .= "<td style='width:30px;'>&nbsp;</td>";
                                    $tab_html .= "<td class='bold'><input type='submit' class='button' id='save_ra' name='save_ra' value='".TEXT_SAVE."' onclick='saveRA(event);' style='margin-bottom: 0px;'></td>";
                                }

                                $tab_html .= "<td style='width:30px;'>&nbsp;</td>";
                                $tab_html .= "<td class='bold'><input type='button' id='button_close' class='button_close' value='".TEXT_CANCEL."' style='margin-bottom: 0px;'></td>";

                            $tab_html .= "</tr>";
                        $tab_html .= "</table>";

                    $tab_html .= "</div>"; // end Validation + Save buttons

                    // ---- PDF download button (always visible, right-aligned) ----
                    $tab_html .= "<div id='ra_pdf_bar' style='float:right;margin-right:20px;'>";
                        $tab_html .= "<button type='button' id='btn_ra_pdf' class='hp-btn' onclick='downloadRA_pdf(".$id_ra.", this);'>";
                            $tab_html .= "<span class='ico'>&#128196;</span> PDF";
                        $tab_html .= "</button>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end popup_barredown


            } else {

                // ====================================================================
                // RA doesn't exist
                // ====================================================================
                $tab_html .= "<table id='tab_titre_popup' cellspacing='0'>";
                    $tab_html .= "<tr style='margin:0;'>";
                        $tab_html .= "<td class='titre' style='border:none;'>";
                            $tab_html .= "<p style='display:none;'>";
                                $tab_html .= "<input type='checkbox' name='check_modif_ra' id='check_modif_ra'
                                              title='".TEXT_MODIFY."'>";
                            $tab_html .= "</p>";
                            $tab_html .= "<p style='width:100%;margin:30px 0;text-align:center;'>".TEXT_RA_NOT_FOUND."</p>";
                        $tab_html .= "</td>";
                    $tab_html .= "</tr>";
                $tab_html .= "</table>";

                $tab_html .= "<div style='float:left;margin:10px 45%;'>";
                    $tab_html .= "<input type='button' id='button_close' class='button_close' value='".TEXT_CANCEL."'>";
                $tab_html .= "</div>";
            }

        } else {

            // ========================================================================
            // Cannot create new RA
            // ========================================================================
            $tab_html .= "<table id='tab_titre_popup' cellspacing='0'>";
                $tab_html .= "<tr>";
                    $tab_html .= "<td class='titre' style='border:none;'>";
                        $tab_html .= "<p style='display:none;'>";
                            $tab_html .= "<input type='checkbox' name='check_modif_ra' id='check_modif_ra'
                                          title='".TEXT_MODIFY."'>";
                        $tab_html .= "</p>";
                        $tab_html .= "<p style='width:100%;margin:30px 0;text-align:center;'>".sprintf(TEXT_CANNOT_CREATE_RA, $nom_data)."</p>";
                    $tab_html .= "</td>";
                $tab_html .= "</tr>";
            $tab_html .= "</table>";

            $tab_html .= "<div style='float:left;margin:10px 45%;'>";
                $tab_html .= "<input type='button' id='button_close' class='button_close' value='".TEXT_CANCEL."'>";
            $tab_html .= "</div>";
        }

        $tab_html .= "</div>"; // end #cadre_limit

    $tab_html .= "</form>";
$tab_html .= "</div>"; // end #cadre_view

// Response
$responseData = array('tab_html' => $tab_html);
echo json_encode($responseData);
?>