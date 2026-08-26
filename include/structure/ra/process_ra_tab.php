<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure to display current Field Reports (RA) in a table on the RA page
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

// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die(TEXT_DB_CONNECTION_ERROR);
mysqli_query($sql_link, 'SET NAMES UTF8');

// Get JSON data from AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extract data
$territoire_id = $dataInfo['territoire_id'];
$where_ra = $dataInfo['where_ra'];
$order_ra = $dataInfo['order_ra'];
$limit_ra = $dataInfo['limit_ra'];

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

// COMMUNE TABLE
$sql_commune = "SELECT DISTINCT c.id_commune, c.nom_commune
                FROM ".TABLE_COMMUNE." c
                JOIN ".TABLE_REGION." r ON c.id_region=r.id_region
                WHERE r.id_territoire=".$territoire_id."
                ORDER BY c.nom_commune ASC";

$commune_query = tep_db_query($sql_link, $sql_commune);
$commune_array = array();
while ($commune = tep_db_fetch_array($commune_query)) {
    $commune_array[$commune['id_commune']] = $commune['nom_commune'];
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

// EQUIPMENT TYPE TABLE
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_graph
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
        'type_color_border' => $eq_type_tab['type_color_border']
    );
}

// DATA CHRON TYPE TABLE
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode
                  FROM ".TABLE_TYPE_DATA."
                  ORDER BY init_type_data ASC";
$type_chron_query = tep_db_query($sql_link, $sql_type_chron);
$type_chron_array = array();
while ($type_chron_tab = tep_db_fetch_array($type_chron_query)) {
    $axe_nom = '';
    if(isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe'])) {
        $axe_nom = $data_type_axe_array[$type_chron_tab['axe_data']]['axe'];
    }

    $type_chron_array[$type_chron_tab['id_data_type']] = array(
        'init_type_data' => $type_chron_tab['init_type_data'],
        'nom_type_data' => $type_chron_tab['nom_type_data'],
        'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
        'axe_nom' => $axe_nom,
        'unite' => $type_chron_tab['unite'],
        'to_periode' => $type_chron_tab['to_periode'],
        'id_chon_periode' => $type_chron_tab['id_chon_periode']
    );
}

// Initialize Variables
$tab_html = '';
$nb_ra = 0;
$nb_ra_valid = 0;
$num_ra = 0;
$prev_id_ra = 0;
$prev_type_data = 0;
$next_id_ra = 0;
$next_type_data = 0;
$row = 0;

$ra_nav_array = array();

// Query to get RA data
$sql_RA = "SELECT DISTINCT ra.id_ra, ra.from_nomad, ra.new_nomad, ra.hp_load,
                            ra.id_agent_user, s.id_region, s.id_station, s.nom_station, s.code_station, s.id_commune,
                            ra.date_heure_ra, ra.id_eq_type, ra.etat_ra,
                            ra.ra_obs, ra.ra_futur, ra.pre_marquant, ra.fait_marquant,
                            ra.agents_complement
            FROM ".TABLE_DATA_RA." ra
            JOIN ".TABLE_STATION." s ON ra.id_station=s.id_station
            LEFT JOIN ".TABLE_STATION_TO_TOURNEE." t ON t.id_station = s.id_station
            WHERE ".$where_ra."
            ORDER BY ".$order_ra." s.active_station DESC, s.suivi DESC, s.armee ASC ".
            $limit_ra;

$RA_query = tep_db_query($sql_link, $sql_RA);

if($RA_query) {
    $nb_ra = mysqli_num_rows($RA_query);
    $num_ra = 0;

    // Buffer to store current and next row
    $prev_RA_tab = null;

    $RA_tab = tep_db_fetch_array($RA_query);
    $next_RA_tab = tep_db_fetch_array($RA_query);

    while($RA_tab) {
        $num_ra++;
        $id_ra = $RA_tab['id_ra'];

        $from_nomad = $RA_tab['from_nomad'];
        $new_nomad = $RA_tab['new_nomad'];
        $hp_load = $RA_tab['hp_load'];

        $id_type_ra = $RA_tab['id_eq_type']; // Data type: Flow, Rain, Piezometer

        // Get info needed for previous and next RA navigation
        if($prev_RA_tab) {
            $prev_id_ra = $prev_RA_tab['id_ra'];
            $prev_type_ra = $prev_RA_tab['id_eq_type'];
        }

        if($next_RA_tab) {
            $next_id_ra = $next_RA_tab['id_ra'];
            $next_type_ra = $next_RA_tab['id_eq_type'];
        }

        // Store data in associative array
        $ra_nav_array[$id_ra] = array(
            'id_type_ra' => $id_type_ra,
            'prev_id_ra' => isset($prev_id_ra) ? $prev_id_ra : null,
            'prev_type_ra' => isset($prev_type_ra) ? $prev_type_ra : null,
            'next_id_ra' => isset($next_id_ra) ? $next_id_ra : null,
            'next_type_ra' => isset($next_type_ra) ? $next_type_ra : null,
            'num_ra' => $num_ra,
            'nb_ra' => $nb_ra
        );

        $id_agent_user = $RA_tab['id_agent_user'];
        $id_region = $RA_tab['id_region'];
        $id_station = $RA_tab['id_station'];
        $nom_station = nettoyer_et_echapper($RA_tab['nom_station']);
        $code_station = nettoyer_et_echapper($RA_tab['code_station']);

        $id_commune = $RA_tab['id_commune'];
        $nom_commune = '';
        if(isset($commune_array[$id_commune])) {
            $nom_commune = $commune_array[$id_commune];
        }

        // RA Date
        $date_heure_ra_tab = explode(" ", $RA_tab['date_heure_ra']);
        $date_ra = dateus_fr($date_heure_ra_tab[0]);
        $heure_ra = $date_heure_ra_tab[1];
        $date_heure_ra = $date_ra.' '.$heure_ra;

        // RA Status
        $etat_ra = $RA_tab['etat_ra']; // Field/Validation in progress/Validated
        $puce_ra = "<img src='".DIR_WS_IMG_ICO."puce_rouge.png' style='width:12px;' title='".TEXT_STEP_0."'>";
        if($etat_ra > 0) {
            $nb_ra_valid++;
            $puce_ra = "<img src='".DIR_WS_IMG_ICO."puce_verte.png' style='width:12px;' title='".TEXT_STEP_1."'>";
        }

        $ra_obs = nettoyer_et_echapper($RA_tab['ra_obs']); // text
        $ra_futur = nettoyer_et_echapper($RA_tab['ra_futur']); // text
        $pre_marquant = $RA_tab['pre_marquant'];
        $fait_marquant = $RA_tab['fait_marquant'];

        // Agents List
        $agents_complement = nettoyer_et_echapper($RA_tab['agents_complement']); // text

        // Create row with hover effect
        $row++;
        if(fmod($row,2)==0) {
            $row_l="class='row1' onmouseover=\"this.className='row1hover';\" onmouseout=\"this.className='row1';\" ";
        } else {
            $row_l="class='row2' onmouseover=\"this.className='row2hover';\" onmouseout=\"this.className='row2';\" ";
        }

        $color_type = '';
        if(isset($eq_type_array[$id_type_ra]) && tep_not_null($eq_type_array[$id_type_ra]['type_color_border'])) {
            $color_type = 'color:'.$eq_type_array[$id_type_ra]['type_color_border'].';';
        }

        $tab_html .= "<tr ".$row_l.">";

        // Selection checkbox (for multi-export / multi-PDF)
        $tab_html .= "<td style='text-align:center;'>";
            $tab_html .= "<input type='checkbox' class='ra_select_cb' value='".$id_ra."' data-type='".$id_type_ra."' onclick='event.stopPropagation();raSelectionChanged();'>";
        $tab_html .= "</td>";

        // RA validation status
        $tab_html .= "<td style='text-align:center;cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        >";
            $tab_html .= $puce_ra;
        $tab_html .= "</td>";

        // Date and Time
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        >";
            $tab_html .= $date_heure_ra;
        $tab_html .= "</td>";

        // Data Type (Flow, Rain, Piezometer)
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        >";
            $tab_html .= "<span style='".$color_type."'>".$eq_type_array[$id_type_ra]['nom_eq_type']."</span>";
        $tab_html .= "</td>";

        // Station Code
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        >";
            $tab_html .= $code_station;
        $tab_html .= "</td>";

        // Station Name
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        title='".$nom_station."'
                        >";
            $tab_html .= affichelettres($nom_station, 50);
        $tab_html .= "</td>";

        // Commune Name
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        >";
            $tab_html .= $nom_commune;
        $tab_html .= "</td>";

        // Agents List
        $tab_html .= "<td style='cursor:pointer;'
                        onClick='loadRA(".$id_ra.",".$id_type_ra.");'
                        title='".$agents_complement."'
                        >";
            $tab_html .= $agents_complement;
        $tab_html .= "</td>";

        // RA Delete Link
        if(HP_VERSION == 'Serveur' || ($from_nomad > 0 && $new_nomad > 0 && $hp_load < 1)) {
            $tab_html .= "<td style='text-align:center;'>";

                $tab_html .= "
                    <a style='font-size:12px;font-weight:bold;' id='del_".$id_ra."' onClick='verifDelRA(".$id_ra.");' title='".TEXT_DELETE_RA."'>
                    X
                    </a>";

            $tab_html .= "</td>";
        } else {
            $tab_html .= "<td style='text-align:center;'>-</td>";
        }

        $tab_html .= "</tr>";

        // Move buffer forward
        $prev_RA_tab = $RA_tab;
        $RA_tab = $next_RA_tab;
        $next_RA_tab = tep_db_fetch_array($RA_query);
    }

    // Convert PHP array to JSON for use in loadRA() function
    $ra_nav_json = json_encode($ra_nav_array);
} else {
    $tab_html .= "<div id='boxpopup' style='margin-left: 1%;'>";
        $tab_html .= "<p class='alert'>".TEXT_NO_RA_FOUND."</p>";
    $tab_html .= "</div>";
}

// Response data
$responseData = array(
    'nb_ra' => $nb_ra,
    'nb_ra_valid' => $nb_ra_valid,
    'tab_html' => $tab_html,
    'ra_nav_json' => $ra_nav_json
);

// Encode and send response
echo json_encode($responseData);
?>
