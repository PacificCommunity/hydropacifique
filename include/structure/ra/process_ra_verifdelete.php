<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure to display RA deletion confirmation
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
$id_ra = $dataInfo['id_ra'];


// =============================================================================
// GET REFERENCE DATA
// =============================================================================

// Station list
$sql_station_all = "SELECT DISTINCT s.id_station, s.nom_station, s.code_station, s.station_type
                    FROM ".TABLE_STATION." s";
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
$sql_eq_type = "SELECT DISTINCT id_eq_type, nom_eq_type, unite_eq_type, valeur_data_type, type_color_border, type_color_background, type_graph
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

// =============================================================================
// INITIALIZE VARIABLES
// =============================================================================
$tab_html = '';

// =============================================================================
// GET RA DATA
// =============================================================================
$sql_RA = "SELECT DISTINCT ra.id_ra, ra.id_station, ra.date_heure_ra, ra.id_eq_type
           FROM ".TABLE_DATA_RA." ra
           WHERE id_ra = ".$id_ra;

$RA_query = tep_db_query($sql_link, $sql_RA);
$RA_tab = tep_db_fetch_array($RA_query);

$id_station = $RA_tab['id_station'];
$code_station = $station_all_array[$id_station]['code_station'];
$nom_station = $station_all_array[$id_station]['nom_station'];
$info_station = $code_station.' - '.$nom_station;

$date_heure_ra_tab = explode(" ", $RA_tab['date_heure_ra']);
$date_ra = dateus_fr($date_heure_ra_tab[0]);
$heure_ra = $date_heure_ra_tab[1];
$date_heure_ra_fr = $date_ra.' '.$heure_ra;

    // =============================================================================
    // BUILD HTML CONFIRMATION DIALOG
    // Look uniformisé avec la suppression de station :
    //  - header rouge
    //  - récap station + date
    //  - challenge mathématique (bouton Supprimer grisé tant que faux)
    // =============================================================================

    // Carte de la popup
    $tab_html .= "<div style='position:relative;width:520px;margin:8% auto 0 auto;"
              .  "background-color:#FBF9F1;border-radius:6px;overflow:hidden;"
              .  "box-shadow:0 8px 30px rgba(0,0,0,0.35);'>";

        // Header rouge
        $tab_html .= "<p style='margin:0;padding:14px 20px;font-size:17px;font-weight:bold;"
                  .  "color:#fff;background-color:#a52834;'>";
        $tab_html .= TEXT_RA_DELETE_CONFIRMATION;
        $tab_html .= "</p>\n";

        $tab_html .= "<div style='padding:18px 22px;'>";

            // Récap station + date (bloc encadré rouge à gauche)
            $tab_html .= "<div style='border-left:4px solid #a52834;background-color:#fbeaec;"
                      .  "padding:10px 14px;margin-bottom:18px;'>";
                $tab_html .= "<p style='margin:0;font-size:14px;color:#333;'>";
                $tab_html .= "<span style='font-weight:bold;'>".TEXT_RA_STATION_INFO." : </span>".$info_station;
                $tab_html .= "</p>\n";
                $tab_html .= "<p style='margin:6px 0 0 0;font-size:14px;color:#333;'>";
                $tab_html .= "<span style='font-weight:bold;'>".TEXT_RA_DATE_INFO." : </span>".$date_heure_ra_fr;
                $tab_html .= "</p>\n";
            $tab_html .= "</div>";

            // Bloc challenge mathématique
            $tab_html .= "<div style='border:1px solid #ddd;border-radius:4px;"
                      .  "padding:14px 16px;margin-bottom:18px;background:#fff;'>";
                $tab_html .= "<p style='margin:0 0 10px 0;font-size:13px;color:#666;'>";
                $tab_html .= TEXT_STATION_DEL_CHALLENGE_LABEL;
                $tab_html .= "</p>";
                $tab_html .= "<div style='display:flex;align-items:center;gap:10px;'>";
                    $tab_html .= "<span id='challenge_question_ra' style='font-size:18px;"
                              .  "font-weight:bold;color:#000;'></span>";
                    $tab_html .= "<input type='text' id='challenge_answer_ra' autocomplete='off'"
                              .  " style='width:80px;height:30px;font-size:16px;text-align:center;'>";
                    $tab_html .= "<span id='challenge_feedback_ra' style='font-size:18px;"
                              .  "font-weight:bold;'></span>";
                $tab_html .= "</div>";
            $tab_html .= "</div>";

            // Boutons
            $tab_html .= "<div style='display:flex;justify-content:flex-end;gap:12px;'>";
                $tab_html .= "<input type='button' id='button_close' class='button_close'"
                          .  " value='".TEXT_CANCEL_BUTTON."' style='width:120px;'"
                          .  " onClick=\"document.getElementById('box_del_ra').style.display='none'\">";
                $tab_html .= "<input type='button' class='button' id='del_ra' name='del_ra'"
                          .  " value='".TEXT_DELETE_BUTTON."' disabled"
                          .  " style='width:120px;opacity:0.45;cursor:not-allowed;'"
                          .  " onClick='delRA(".$id_ra.");'>";
            $tab_html .= "</div>";

        $tab_html .= "</div>";

    $tab_html .= "</div>";

// =============================================================================
// CLIENT RESPONSE
// =============================================================================
$responseData = array(
    'tab_html' => $tab_html
);

echo json_encode($responseData);
?>