<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Field form display - Piezometry Groundwater
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

// Optional: pre-select a station in the form's "Select a Station..."
// dropdown when creating a new RA (id_ra=0). Passed by deep links
// like Well log popup "+" → list_ra.php?st=42&new_ra=5.
$preselect_station = isset($dataInfo['preselect_station']) ? (int) $dataInfo['preselect_station'] : 0;

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
$type_data = 5; // 5: Piezometry

if($id_ra > 0 && isset($ra_nav_array[$id_ra])) {
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
$heure_ra = $today_time_formatted;

$id_agent_user = $id_user;
$id_station = 0;

$name_file_data = '';

// Equipment variables
$type_appareil = '';
$num_appareil = '';
$heure_appareil = '';

$nb_octet = '';
$num_batterie = '';
$tension_batterie = '';

$type_appareil_manuel = '';
$num_appareil_manuel = '';

// Old equipment variables
$num_cassette = '';
$heure_init_cassette = '';

// Piezometry variables
$piezo_toitnappesonde = '';
$piezo_conductivite = '';
$piezo_temperature = '';
$piezo_recalage_diff = '';
$piezo_recalage_sonde = '';
$piezo_recalage_heure_sonde = '';

$piezo_nature_repere = '';
$piezo_instrument = '';
$piezo_num_instrument = '';
$piezo_prof_toitnappe = '';
$piezo_prof_totale = '';
$piezo_x_terrain = '';
$piezo_y_terrain = '';
$piezo_gps_precision = '';
$piezo_systeme_coord = '';

$check_pompage_encours = 0;
$check_pompage_proche = 0;
$check_deletememory = 0;
$check_pluie_crue = 0;
$check_temps_sec = 0;
$check_photos = 0;

// Observations
$check_debrouss = 0;
$ra_obs = '';
$ra_futur = '';

$obs_file_data = '';
$check_premarquant = 0;
$check_faitmarquant = 0;

// Old variables
$duree_nb_jour = '';
$duree_nb_heure = '';
$duree_nb_min = '';
$dernier_nb_jour = '';
$dernier_nb_heure = '';
$dernier_nb_min = '';

$agents_complement = '';

if($id_ra > 0) {
    $sql_RA = "SELECT DISTINCT ra.id_ra, from_nomad, new_nomad, hp_load,
                ra.datetime_saisie, ra.id_agent_user, ra.id_station,
                ra.date_heure_ra, ra.id_eq_type,
                ra.type_appareil, ra.num_appareil, ra.heure_appareil, ra.etat_ra,
                ra.nb_octet, ra.num_batterie, ra.tension_batterie, ra.num_cassette, ra.heure_init_cassette,
                ra.ra_debroussaillage,
                ra.piezo_toitnappesonde, ra.piezo_conductivite, ra.piezo_temperature, ra.piezo_recalage_diff,
                ra.piezo_recalage_sonde, ra.piezo_recalage_heure_sonde,
                ra.piezo_nature_repere,
                ra.piezo_instrument, ra.piezo_num_instrument, ra.piezo_prof_toitnappe, ra.piezo_prof_totale,
                ra.piezo_x_terrain, ra.piezo_y_terrain, ra.piezo_gps_precision, ra.piezo_systeme_coord,
                ra.ra_delete_memory, ra.piezo_pompage_encours, ra.piezo_pompage_proche,
                ra.piezo_pluie_crue, ra.piezo_temps_sec, ra.piezo_photos,
                ra.ra_obs, ra.ra_futur, ra.name_file_data, ra.obs_file_data, ra.pre_marquant, ra.fait_marquant, ra.agents_complement
                FROM ".TABLE_DATA_RA." ra
                WHERE id_ra = ".$id_ra;

    $RA_query = tep_db_query($sql_link, $sql_RA);
    $ra_exist = false;

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

        $nb_octet = nettoyer_et_echapper($RA_tab['nb_octet']);
        $num_batterie = nettoyer_et_echapper($RA_tab['num_batterie']);
        $tension_batterie = nettoyer_et_echapper($RA_tab['tension_batterie']);

        $piezo_toitnappesonde = $RA_tab['piezo_toitnappesonde'];
        if($piezo_toitnappesonde == '0') {$piezo_toitnappesonde = '';}
        $piezo_conductivite = $RA_tab['piezo_conductivite'];
        if($piezo_conductivite == '0') {$piezo_conductivite = '';}
        $piezo_temperature = $RA_tab['piezo_temperature'];
        if($piezo_temperature == '0') {$piezo_temperature = '';}
        $piezo_recalage_diff = $RA_tab['piezo_recalage_diff'];
        if($piezo_recalage_diff == '0') {$piezo_recalage_diff = '';}
        $piezo_recalage_sonde = $RA_tab['piezo_recalage_sonde'];

        $piezo_recalage_heure_sonde = $RA_tab['piezo_recalage_heure_sonde'];
        if($piezo_recalage_heure_sonde == '00:00:00') {$piezo_recalage_heure_sonde = '';}

        $piezo_nature_repere = nettoyer_et_echapper($RA_tab['piezo_nature_repere']);
        $piezo_instrument = nettoyer_et_echapper($RA_tab['piezo_instrument']);
        $piezo_num_instrument = nettoyer_et_echapper($RA_tab['piezo_num_instrument']);
        $piezo_prof_toitnappe = $RA_tab['piezo_prof_toitnappe'];
        if($piezo_prof_toitnappe == '0') {$piezo_prof_toitnappe = '';}
        $piezo_prof_totale = $RA_tab['piezo_prof_totale'];
        if($piezo_prof_totale == '0') {$piezo_prof_totale = '';}
        $piezo_x_terrain = $RA_tab['piezo_x_terrain'];
        if($piezo_x_terrain == '0') {$piezo_x_terrain = '';}
        $piezo_y_terrain = $RA_tab['piezo_y_terrain'];
        if($piezo_y_terrain == '0') {$piezo_y_terrain = '';}
        $piezo_gps_precision = $RA_tab['piezo_gps_precision'];
        $piezo_systeme_coord = $RA_tab['piezo_systeme_coord'];

        $check_deletememory = $RA_tab['ra_delete_memory'];
        $check_pompage_encours = $RA_tab['piezo_pompage_encours'];
        $check_pompage_proche = $RA_tab['piezo_pompage_proche'];
        $check_pluie_crue = $RA_tab['piezo_pluie_crue'];
        $check_temps_sec = $RA_tab['piezo_temps_sec'];
        $check_photos = $RA_tab['piezo_photos'];

        $ra_obs = nettoyer_et_echapper($RA_tab['ra_obs']);
        $ra_futur = nettoyer_et_echapper($RA_tab['ra_futur']);

        $name_file_data = nettoyer_et_echapper($RA_tab['name_file_data']);
        $obs_file_data = nettoyer_et_echapper($RA_tab['obs_file_data']);

        $check_premarquant = $RA_tab['pre_marquant'];
        $check_faitmarquant = $RA_tab['fait_marquant'];

        $agents_complement = nettoyer_et_echapper($RA_tab['agents_complement']);

        // Get piezometric profile data
        $p = 1;
        $sql_piezo_profil = "SELECT DISTINCT id, profondeur, conductivite, temperature
                            FROM ".TABLE_DATA_RA_PIEZO_PROFIL."
                            WHERE id_ra=".$id_ra."
                            ORDER BY profondeur";
        $piezo_profil_query = tep_db_query($sql_link, $sql_piezo_profil);
        while($piezo_profil = tep_db_fetch_array($piezo_profil_query)) {
            $id_valeurProfil = $piezo_profil['id'];
            $tab_piezoProfil[$id_valeurProfil]['prof'] = $piezo_profil['profondeur'];
            $tab_piezoProfil[$id_valeurProfil]['conduct'] = $piezo_profil['conductivite'];
            $tab_piezoProfil[$id_valeurProfil]['temp'] = $piezo_profil['temperature'];
            $p++;
        }
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
                            // When we're in "new RA" mode (id_ra == 0),
                            // $id_station was initialized to 0. If the
                            // caller passed a preselect_station id (e.g.
                            // from a deep link like ?st=42&new_ra=5), we
                            // adopt it so the dropdown opens already on
                            // the right station.
                            if ($id_ra == 0 && $preselect_station > 0) {
                                $id_station = $preselect_station;
                            }
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
                                    $tab_html .= "<input type='text' style='' name='agent_saisie' id='agent_saisie' value='".$user_list_array[$id_agent_user]['prenom'].' '.$user_list_array[$id_agent_user]['nom']."' disabled>";
                                } else {
                                    $tab_html .= "<span style='font-size:16px;'>".TEXT_BY." - </span>";
                                }
                            $tab_html .= "</p>";

                        $tab_html .= "</td>";
                    $tab_html .= "</tr>";
                $tab_html .= "</table>";


                // ====================================================================
                // LINE 1 : Reading + Measurement position
                // ====================================================================

                // ---- Box: Data reading ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_READING."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_DATE."</p>";
                        $tab_html .= "<input class='input_texte' style='width:80px;' name='date_releve' id='date_releve' type='text' value='".$date_ra."' onFocus='initDatepickers(this)' placeholder='dd-mm-yyyy'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_TIME."</p>";
                        $tab_html .= "<input name='heure_releve' id='heure_releve' value='".$heure_ra."' onfocus='setHeureNow(this)' placeholder='hh:mm:ss' style='width:55px;' type='text'>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Reading


                // ---- Box: Measurement position ----
                $tab_html .= "<div id='boxContent-RA' style=''>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_MEASUREMENT_POSITION."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_X_GPS_POSITION."</p>";
                        $tab_html .= "<input name='piezo_x_terrain' id='piezo_x_terrain' value='".$piezo_x_terrain."' class='input_texte_small' type='text'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_Y_GPS_POSITION."</p>";
                        $tab_html .= "<input name='piezo_y_terrain' id='piezo_y_terrain' value='".$piezo_y_terrain."' class='input_texte_small' type='text'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_COORD_SYSTEM."</p>";
                        $tab_html .= "<input name='piezo_systeme_coord' id='piezo_systeme_coord' value='".$piezo_systeme_coord."' class='input_texte' type='text'>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC')
                    {
                        $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                            $tab_html .= "<p>".TEXT_GPS_PRECISION."</p>";
                            $tab_html .= "<input name='piezo_gps_precision' id='piezo_gps_precision' value='".$piezo_gps_precision."' class='input_texte_small' type='text'>";
                        $tab_html .= "</div>";
                    }

                $tab_html .= "</div>"; // end Box: Measurement position


                // ---- Conductivity profile button ----
                $tab_html .= "<div style='float:left;margin-top:10px;'>";
                    $tab_html .= "<input type='button' class='button_profil' name='buttonProfil' id='buttonProfil' value='".TEXT_CONDUCTIVITY_PROFILE."' onClick='affiche_RA_piezoprofil();'>";

                    // Reminder banner shown after the user has edited
                    // points in the profile popup AND closed it without
                    // saving. The JS in list_ra.php toggles its
                    // visibility (display none/block) — it stays hidden
                    // until the first dirty close.
                    // The width + margin-left match the .button_profil
                    // class (200px wide, 20px left margin) so the banner
                    // sits exactly under the button.
                    $tab_html .= "<div id='profil_save_reminder'"
                              . " style='display:none;margin-top:8px;margin-left:20px;"
                              . "width:200px;box-sizing:border-box;padding:6px 10px;"
                              . "background:#fff7d6;border:1px solid #e4d27a;border-radius:4px;"
                              . "color:#6b5500;font-size:11px;line-height:1.4;'>"
                              . "&#9888; " . TEXT_PIEZO_PROFIL_SAVE_REMINDER
                              . "</div>";
                $tab_html .= "</div>";

                $tab_html .= "<hr>";


                // ====================================================================
                // LINE 2 : Fixed probe characteristics + Manual probe + Adjustment
                // ====================================================================

                // ---- Box: Fixed probe characteristics ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_FIXED_PROBE_CHARACTERISTICS."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_TYPE."</p>";
                        $tab_html .= "<input type='hidden' value='".$type_appareil."' name='nom_appareil' id='nom_appareil'>";
                        $tab_html .= "<select name='select_type_appareil' id='select_type_appareil' style='width:140px;'></select>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_NUMBER."</p>";
                        $tab_html .= "<input type='hidden' value='".$num_appareil."' name='num_appareil' id='num_appareil'>";
                        $tab_html .= "<select name='select_num_appareil' id='select_num_appareil' style='width:90px;'></select>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC')
                    {
                        $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                            $tab_html .= "<p>".TEXT_TIME."</p>";
                            $tab_html .= "<input name='heure_appareil' id='heure_appareil' value='".$heure_appareil."' placeholder='hh:mm:ss' style='width:55px;' type='text'>";
                        $tab_html .= "</div>";
                    }

                    $tab_html .= "<hr>";

                    // ---- Sub-section: Fixed probe measurement ----
                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_FIXED_PROBE_MEASUREMENT."</p>";
                    $tab_html .= "</div>";

                    $unite_ra = 'm';
                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_WATER_TABLE_DEPTH."</p>";
                        $tab_html .= "<input name='piezo_toitnappesonde' id='piezo_toitnappesonde' value='".$piezo_toitnappesonde."' style='width:40px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $unite_ra = '&mu;S/cm';
                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_CONDUCTIVITY."</p>";
                        $tab_html .= "<input name='piezo_conductivite' id='piezo_conductivite' value='".$piezo_conductivite."' style='width:40px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $unite_ra = '°C';
                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_TEMPERATURE."</p>";
                        $tab_html .= "<input name='piezo_temperature' id='piezo_temperature' value='".$piezo_temperature."' style='width:40px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Fixed probe characteristics


                // ---- Box: Manual probe characteristics ----
                $tab_html .= "<div id='boxContent-RA' style='margin-right:5px;'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_MANUAL_PROBE_CHARACTERISTICS."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_TYPE."</p>";
                        $tab_html .= "<input type='hidden' value='".$piezo_instrument."' name='piezo_instrument' id='piezo_instrument'>";
                        $tab_html .= "<select name='select_piezo_instrument' id='select_piezo_instrument' style='width:140px;'></select>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_NUMBER."</p>";
                        $tab_html .= "<input type='hidden' value='".$piezo_num_instrument."' name='piezo_num_instrument' id='piezo_num_instrument'>";
                        $tab_html .= "<select name='select_piezo_num_instrument' id='select_piezo_num_instrument' style='width:110px;'></select>";
                    $tab_html .= "</div>";

                    $tab_html .= "<hr>";

                    // ---- Sub-section: Manual probe measurement ----
                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_MANUAL_PROBE_MEASUREMENT."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_MARKER_NATURE."</p>";
                        $tab_html .= "<input type='hidden' value='".$piezo_nature_repere."' name='piezo_nature_repere' id='piezo_nature_repere'>";
                        $tab_html .= "<select name='select_piezo_nature_repere' id='select_piezo_nature_repere' style='width:140px;'></select>";
                    $tab_html .= "</div>";

                    $unite_ra = 'm';
                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_WATER_TABLE_DEPTH."</p>";
                        $tab_html .= "<input name='piezo_prof_toitnappe' id='piezo_prof_toitnappe' value='".$piezo_prof_toitnappe."' style='width:40px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $unite_ra = 'm';
                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_TOTAL_DEPTH."</p>";
                        $tab_html .= "<input name='piezo_prof_totale' id='piezo_prof_totale' value='".$piezo_prof_totale."' style='width:40px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Manual probe characteristics


                // ---- Box: Probe adjustment + Device state ----
                $tab_html .= "<div id='boxContent-RA'>";

                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_FIXED_PROBE_ADJUSTMENT."</p>";
                    $tab_html .= "</div>";

                    $unite_ra = 'm';
                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_DIFF_MANUAL_FIXED."</p>";
                        $tab_html .= "<input name='piezo_recalage_diff' id='piezo_recalage_diff' value='".$piezo_recalage_diff."' style='width:60px;' type='text'>";
                        $tab_html .= "<span style='margin-left:5px;font-size:14px;'>".$unite_ra."</span>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_PROBE_ADJUSTMENT."</p>";
                        $tab_html .= "<input name='piezo_recalage_sonde' id='piezo_recalage_sonde' value='".$piezo_recalage_sonde."' style='width:60px;' type='text'>";
                    $tab_html .= "</div>";

                    if(INIT_T == 'NC')
                    {
                        $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                            $tab_html .= "<p>".TEXT_TIME_ADJUSTMENT."</p>";
                            $tab_html .= "<input name='piezo_recalage_heure_sonde' id='piezo_recalage_heure_sonde' value='".$heure_appareil."' placeholder='hh:mm:ss' style='width:55px;' type='text'>";
                        $tab_html .= "</div>";
                    }

                    $tab_html .= "<hr>";

                    // ---- Sub-section: Device state ----
                    $tab_html .= "<div id='boxContent-RA-title'>";
                        $tab_html .= "<p>".TEXT_DEVICE_STATE_FIXED_PROBE."</p>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small'>";
                        $tab_html .= "<p>".TEXT_NB_DATA."</p>";
                        $tab_html .= "<input name='nb_octet' id='nb_octet' value='".$nb_octet."' class='input_texte_xsmall' type='text'>";
                    $tab_html .= "</div>";

                    $tab_html .= "<div id='boxContent-RA-small' style='margin-right:5px;'>";
                        $tab_html .= "<p>".TEXT_BATTERY_PERCENT."</p>";
                        $tab_html .= "<input name='tension_batterie' id='tension_batterie' value='".$tension_batterie."' class='input_texte_xsmall' type='text'>";
                    $tab_html .= "</div>";

                $tab_html .= "</div>"; // end Box: Probe adjustment

                $tab_html .= "<hr>";


                // ====================================================================
                // LINE 3 : Observations + Future actions
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

                    // Checkboxes - First row (pumping / memory)
                    $tab_html .= "<div id='boxContent-RA-small' style='margin:0;'>";

                        $checked = '';
                        if($check_pompage_encours>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_pompage_encours' id='check_pompage_encours' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:110px;font-size:12px;'>".TEXT_PUMPING_IN_PROGRESS."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_pompage_proche>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_pompage_proche' id='check_pompage_proche' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:100px;font-size:12px;'>".TEXT_NEARBY_PUMPING."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_deletememory>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_deletememory' id='check_deletememory' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:90px;font-size:12px;'>".TEXT_MEMORY_CLEARED."</span>";
                        $tab_html .= "</div>";

                    $tab_html .= "</div>"; // end checkboxes row 1

                    // Checkboxes - Second row (rain / dry / photos)
                    $tab_html .= "<div id='boxContent-RA-small' style='margin:0;'>";

                        $checked = '';
                        if($check_pluie_crue>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_piezo_pluie_crue' id='check_piezo_pluie_crue' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:100px;font-size:12px;'>".TEXT_RAIN_FLOOD."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_temps_sec>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_piezo_temps_sec' id='check_piezo_temps_sec' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:100px;font-size:12px;'>".TEXT_DRY_DAY."</span>";
                            $tab_html .= "<hr>";
                        $tab_html .= "</div>";

                        $checked = '';
                        if($check_photos>0) {$checked = 'checked';}
                        $tab_html .= "<div>";
                            $tab_html .= "<input class='input_texte' style='width:25px;' name='check_piezo_photos' id='check_piezo_photos' type='checkbox' ".$checked.">";
                            $tab_html .= "<span style='float:left;margin-top:5px;width:100px;font-size:12px;'>".TEXT_PHOTOS."</span>";
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
                // LINE 4 : Agents
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
                        //$tab_html .= "<p>".TEXT_PARTICIPANTS."</p>";
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


// ============================================================================
// CONDUCTIVITY PROFILE OVERLAY (piezometry-specific)
// ----------------------------------------------------------------------------
// Side panel that appears on top when user clicks the "Conductivity profile"
// button. Contains a depth/conductivity/temperature table + plot area.
// ============================================================================
if(isset($station_all_array) && sizeof($station_all_array) > 0) {
    if($type_data == 5) {

        // ---- Conductivity profile popup ----
        // Visually aligned on the Well log popup:
        // - draggable header (cursor:move)
        // - teal header bar (auto-styled by #cadre_view_2 > *:first-child)
        // - body split into a left table + a right Plotly chart
        // - permanent yellow banner reminding the user that point edits
        //   are only persisted when the parent RA form is saved
        // No resize handle — the chart needs a stable size to keep the
        // axis ratios predictable when dragging points.
        $tab_html .= "
        <div id='box_ra_piezoprofil' class='block_view'
             style='display:none;position:fixed;top:8vh;left:20vw;width:60vw;height:75vh;
             background:transparent;z-index:1700;overflow:hidden;
             min-width:780px;min-height:420px;border:1px solid #c0c0c0;border-radius:4px;
             box-shadow:0 4px 16px rgba(0,0,0,0.15);'>

            <div id='cadre_view_2'
                 style='padding:0;margin:0 !important;margin-top:0 !important;background-color:#FBF9F1;
                 border-radius:4px;overflow:hidden;height:100%;display:flex;flex-direction:column;'>

                <div id='box_ra_piezoprofil_header'
                     style='cursor:move;user-select:none;flex-shrink:0;'>
                    <span>" . TEXT_DEPTH_PROFILE . "</span>
                    <span id='button_close'
                          onclick=\"document.getElementById('box_ra_piezoprofil').style.display='none';\"
                          title='Fermer'>&times;</span>
                </div>

                <div style='background:#fff7d6;border-bottom:1px solid #e4d27a;color:#6b5500;
                            padding:6px 14px;font-size:11px;flex-shrink:0;line-height:1.4;'>
                    &#9888; " . TEXT_PIEZO_PROFIL_SAVE_WARNING . "
                </div>

                <div style='display:grid;grid-template-columns:400px 1fr;gap:0;flex:1 1 0;
                     min-height:0;overflow:hidden;'>

                    <div id='cadre_liste'
                         style='padding:12px 14px;border-right:1px solid #e0e0e0;overflow-y:auto;
                         overflow-x:hidden;min-height:0;height:100%;box-sizing:border-box;'>

                        <table id='table_tri' cellspacing='0' style='width:100%;'>
                            <thead>
                                <tr>
                                    <th style='width:90px;color:#000;font-size:11px;padding:3px 4px;border-bottom:1px solid #ccc;text-align:left;'>
                                        ".TEXT_DEPTH." [m]
                                    </th>
                                    <th style='width:110px;color:#000;font-size:11px;padding:3px 4px;border-bottom:1px solid #ccc;text-align:left;'>
                                        ".TEXT_CONDUCTIVITY." [&mu;S/cm]
                                    </th>
                                    <th style='width:90px;color:#000;font-size:11px;padding:3px 4px;border-bottom:1px solid #ccc;text-align:left;'>
                                        ".TEXT_TEMPERATURE." [&deg;C]
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
        ";

        // Loop over existing profile data
        $pp = 1;
        if(isset($tab_piezoProfil)) {
            foreach($tab_piezoProfil as $key_profil => $value_profil) {
                $tab_html .= "
                                <tr>
                                    <td style='padding:2px 4px;'><input type='text' style='width:80px;' id='piezo_profil_prof_".$pp."' name='piezo_profil_prof_".$pp."' value='".$value_profil['prof']."'></td>
                                    <td style='padding:2px 4px;'><input type='text' style='width:100px;' id='piezo_profil_conduct_".$pp."' name='piezo_profil_conduct_".$pp."' value='".$value_profil['conduct']."'></td>
                                    <td style='padding:2px 4px;'><input type='text' style='width:80px;' id='piezo_profil_temp_".$pp."' name='piezo_profil_temp_".$pp."' value='".$value_profil['temp']."'></td>
                                </tr>
                ";
                $pp++;
            }
        }

        // Pad up to 30 empty rows
        while($pp <= 30) {
            $tab_html .= "
                                <tr>
                                    <td style='padding:2px 4px;'><input type='text' style='width:80px;' id='piezo_profil_prof_".$pp."' name='piezo_profil_prof_".$pp."' value=''></td>
                                    <td style='padding:2px 4px;'><input type='text' style='width:100px;' id='piezo_profil_conduct_".$pp."' name='piezo_profil_conduct_".$pp."' value=''></td>
                                    <td style='padding:2px 4px;'><input type='text' style='width:80px;' id='piezo_profil_temp_".$pp."' name='piezo_profil_temp_".$pp."' value=''></td>
                                </tr>
            ";
            $pp++;
        }

        $tab_html .= "
                            </tbody>
                        </table>

                    </div>

                    <div id='cadre_graph'
                         style='padding:12px 14px;display:flex;flex-direction:column;
                         min-height:0;overflow:hidden;'>

                        <!-- Tabs: switch the chart between Conductivity and Temperature.
                             The active tab is also the one being edited (drag, add, remove). -->
                        <div id='profil_tabs' style='display:flex;gap:0;flex-shrink:0;margin-bottom:8px;border-bottom:1px solid #d4d8dd;'>
                            <button type='button' id='profil_tab_cond' data-tab='cond'
                                    class='profil-tab is-active'
                                    style='padding:6px 14px;font-size:12px;font-weight:600;border:1px solid #d4d8dd;border-bottom:none;border-radius:4px 4px 0 0;background:#176B87;color:#fff;cursor:pointer;'>
                                ".TEXT_CONDUCTIVITY."
                            </button>
                            <button type='button' id='profil_tab_temp' data-tab='temp'
                                    class='profil-tab'
                                    style='padding:6px 14px;font-size:12px;font-weight:600;border:1px solid #d4d8dd;border-bottom:none;border-radius:4px 4px 0 0;background:#fff;color:#176B87;cursor:pointer;margin-left:4px;'>
                                ".TEXT_TEMPERATURE."
                            </button>
                        </div>

                        <div id='plot_profil'
                             style='flex:1;min-height:0;background:#fff;border:1px solid #e0e0e0;border-radius:3px;'></div>

                        <div style='margin-top:6px;font-size:11px;color:#666;flex-shrink:0;'>
                            " . TEXT_DG_EDIT_HINT_DRAG
                          . " &middot; " . TEXT_DG_EDIT_HINT_RDEL
                          . " &middot; " . TEXT_DG_EDIT_HINT_RADD . "
                        </div>

                    </div>

                </div>
            </div>
        </div>
        ";
    }
}
 //echo "<div id='plotDiag' style='width:95%;height:calc(100% - 10px);padding:15px;display:none;'></div>\n";

    $tab_html .= "</form>";
$tab_html .= "</div>"; // end #cadre_view

// Response
$responseData = array('tab_html' => $tab_html);
echo json_encode($responseData);
?>