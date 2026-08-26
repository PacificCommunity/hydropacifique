<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure to automatically display information from the last RA entered for the same station
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
require('../../function/gestion_erreur.php');

// UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

// Database connection
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die('Unable to connect to database!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// Get JSON data from AJAX request
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extract data
$territoire_id = $dataInfo['territoire_id'];
$timezone_php = $dataInfo['timezone_php'];
$id_user = $dataInfo['id_user'];
$id_station_saisie = $dataInfo['select_raIdStation'];
$date_ra_saisie = $dataInfo['saisie_raDataReleve'];
$heure_ra_saisie = $dataInfo['saisie_raHeureReleve'];

// Time management
date_default_timezone_set($timezone_php);

$now = new DateTime();
$today_date_formatted = $now->format('d-m-Y');
$today_time_formatted = $now->format('H:i:s');
$today_datetime_formatted = $now->format('d-m-Y H:i:s');

$unite_ra = "mm";
$dateTime_ra = "";
$tab_html = "";

// Input data validation
if(!empty($id_station_saisie)) {
    $id_station_clean = intval($id_station_saisie);

    // Validate date format
    if(!validDate($date_ra_saisie)) {$date_ra_saisie = $today_date_formatted;}

    // Validate time format
    if(!validTime($heure_ra_saisie)) {$heure_ra_saisie = '23:59:59';}

    $dateTime_ra_saisie = datefr_us($date_ra_saisie).' '.$heure_ra_saisie;

    // SQL query to get last RA data
    $sql_RA = "SELECT ra.id_ra, ra.id_station, ra.date_heure_ra, ra.plu_tot_last, ra.plu_nb_basculement
                FROM ".TABLE_DATA_RA." ra
                WHERE id_station = ".$id_station_clean."
                AND date_heure_ra < '".$dateTime_ra_saisie."'
                ORDER BY date_heure_ra DESC
                LIMIT 1";

    $RA_query = tep_db_query($sql_link, $sql_RA);
    $ra_tab = tep_db_fetch_array($RA_query);

    if($ra_tab) {
        $date_heure_ra = new DateTime($ra_tab['date_heure_ra']);
        $date_heure_ra_formatFr = $date_heure_ra->format('d-m-Y');

        $plu_tot_last = $ra_tab['plu_tot_last'];
        if(empty($plu_tot_last)) {$plu_tot_last = "-";}

        $plu_nb_basc = $ra_tab['plu_nb_basculement'];

        $tab_html .= "<b>".TEXT_LAST_RA_DATE." : </b><span>".$date_heure_ra_formatFr."</span>";
        $tab_html .= "<br>";
        $tab_html .= "<b>".TEXT_LAST_RA_TOTAL." : </b>";
        $tab_html .= "<input type='text' id='last_plu_tot' value='".$plu_tot_last."' readonly
                      style='padding:1px 0;border:none; background:transparent; width:30px; outline:none; font-family:inherit; font-size:inherit; color:inherit;'>";
        $tab_html .= $unite_ra;
        $tab_html .= "<br>";
        $tab_html .= "<b>".TEXT_LAST_RA_TIPPINGS." : </b>";
        $tab_html .= "<input type='text' id='last_plu_basc' value='".$plu_nb_basc."' readonly
                      style='padding:1px 0;border:none; background:transparent; width:30px; outline:none; font-family:inherit; font-size:inherit; color:inherit;'>";
    } else {
        $tab_html .= TEXT_NO_PREVIOUS_DATA;
    }
} else {
    $tab_html .= TEXT_NO_PREVIOUS_DATA;
}

// Response data
$responseData = array(
    'tab_html' => $tab_html,
);

// Encode and send response
echo json_encode($responseData);
?>
