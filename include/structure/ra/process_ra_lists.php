<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procedure to manage dropdown lists for field forms
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

// Get JSON data
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extract data
$territoire_id = $dataInfo['territoire_id'];
$timezone_php = $dataInfo['timezone_php'];
$id_user = $dataInfo['id_user'];
$type_data = (int)($dataInfo['typeData'] ?? 0);
$id_station_saisie = (int)($dataInfo['select_raIdStation'] ?? 0);
$nom_appareil = $dataInfo['nomAppareil'];
$num_appareil = $dataInfo['numAppareil'];
$hydro_num_sonde = $dataInfo['hydroNumSonde'];
$piezoInstrument = $dataInfo['piezoInstrument'];
$piezoNumInstrument = $dataInfo['piezoNumInstrument'];
$piezoNatureRepere = $dataInfo['piezoNatureRepere'];

// Time management
date_default_timezone_set($timezone_php);

$now = new DateTime();
$today_date_formatted = $now->format('d-m-Y');
$today_time_formatted = $now->format('H:i:s');
$today_datetime_formatted = $now->format('d-m-Y H:i:s');

// Station filter
$where_station = "";
if($id_station_saisie > 0) {
    $where_station = " AND id_station=".$id_station_saisie;
}

// Get list of device types based on data type
$type_appareil_list = [];
$sql_appareil_type = "SELECT DISTINCT type_appareil FROM ".TABLE_DATA_RA."
                      WHERE id_eq_type = ".$type_data.$where_station;
$appareil_type_query = tep_db_query($sql_link, $sql_appareil_type);
while($appareil_type_tab = tep_db_fetch_array($appareil_type_query)) {
    $type_appareil_list[] = html_entity_decode($appareil_type_tab['type_appareil'] ?? '');
}

// Get list of device numbers based on data type
$num_appareil_list = [];
$sql_appareil_num = "SELECT DISTINCT num_appareil FROM ".TABLE_DATA_RA."
                     WHERE id_eq_type = ".$type_data.$where_station;
$appareil_num_query = tep_db_query($sql_link, $sql_appareil_num);
while($appareil_num_tab = tep_db_fetch_array($appareil_num_query)) {
    $num_appareil_list[] = html_entity_decode($appareil_num_tab['num_appareil'] ?? '');
}

// Get list of probe numbers for hydro stations
$num_sonde_list = [];
$sql_sonde_num = "SELECT DISTINCT hydro_num_sonde FROM ".TABLE_DATA_RA."
                 WHERE id_eq_type = ".$type_data.$where_station;
$sonde_num_query = tep_db_query($sql_link, $sql_sonde_num);
while($num_sonde_tab = tep_db_fetch_array($sonde_num_query)) {
    $num_sonde_list[] = html_entity_decode($num_sonde_tab['hydro_num_sonde'] ?? '');
}

// Get list of piezometer instrument types
$nom_sFixe_list = [];
$sql_sFixe_type = "SELECT DISTINCT piezo_instrument FROM ".TABLE_DATA_RA."
                   WHERE id_eq_type = ".$type_data.$where_station;
$sFixe_type_query = tep_db_query($sql_link, $sql_sFixe_type);
while($sFixe_type_tab = tep_db_fetch_array($sFixe_type_query)) {
    $nom_sFixe_list[] = html_entity_decode($sFixe_type_tab['piezo_instrument'] ?? '');
}

// Get list of piezometer instrument numbers
$num_sFixe_list = [];
$sql_sFixe_num = "SELECT DISTINCT piezo_num_instrument FROM ".TABLE_DATA_RA."
                  WHERE id_eq_type = ".$type_data.$where_station;
$sFixe_num_query = tep_db_query($sql_link, $sql_sFixe_num);
while($sFixe_num_tab = tep_db_fetch_array($sFixe_num_query)) {
    $num_sFixe_list[] = html_entity_decode($sFixe_num_tab['piezo_num_instrument'] ?? '');
}


// Get list of piezometer Nature Repere
$repere_sFixe_list = [];
$sql_sFixe_repere = "SELECT DISTINCT piezo_nature_repere FROM ".TABLE_DATA_RA."
                  WHERE id_eq_type = ".$type_data.$where_station;
$sFixe_repere_query = tep_db_query($sql_link, $sql_sFixe_repere);
while($sFixe_repere_tab = tep_db_fetch_array($sFixe_repere_query)) {
    $repere_sFixe_list[] = html_entity_decode($sFixe_repere_tab['piezo_nature_repere'] ?? '');
}



// Build device type dropdown
$html_typeAppareil = "";
$html_typeAppareil .= "<option value=''>".TEXT_SELECT_DEVICE_TYPE."</option>";

if (!empty($nom_appareil)) {
    $html_typeAppareil .= "<option value='".htmlspecialchars($nom_appareil, ENT_QUOTES)."' selected>"
                        .htmlspecialchars($nom_appareil, ENT_QUOTES).
                        "</option>";
}

if(isset($type_appareil_list) && is_array($type_appareil_list)) {
    foreach($type_appareil_list as $text) {
        if($text !== $nom_appareil) {
            $html_typeAppareil .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                .htmlspecialchars($text, ENT_QUOTES).
                                "</option>";
        }
    }
}

// Build device number dropdown
$html_numAppareil = "";
$html_numAppareil .= "<option value=''>".TEXT_SELECT_DEVICE_NUMBER."</option>";

if (!empty($num_appareil)) {
    $html_numAppareil .= "<option value='".htmlspecialchars($num_appareil, ENT_QUOTES)."' selected>"
                        .htmlspecialchars($num_appareil, ENT_QUOTES).
                        "</option>";
}

if(isset($num_appareil_list) && is_array($num_appareil_list)) {
    foreach($num_appareil_list as $text) {
        if($text !== $num_appareil) {
            $html_numAppareil .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                .htmlspecialchars($text, ENT_QUOTES).
                                "</option>";
        }
    }
}

// Build hydro probe number dropdown
$html_hydroNumSonde = "";
$html_hydroNumSonde .= "<option value=''>".TEXT_SELECT_PROBE_NUMBER."</option>";

if (!empty($hydro_num_sonde)) {
    $html_hydroNumSonde .= "<option value='".htmlspecialchars($hydro_num_sonde, ENT_QUOTES)."' selected>"
                        .htmlspecialchars($hydro_num_sonde, ENT_QUOTES).
                        "</option>";
}

if(isset($num_sonde_list) && is_array($num_sonde_list)) {
    foreach($num_sonde_list as $text) {
        if($text !== $hydro_num_sonde) {
            $html_hydroNumSonde .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                .htmlspecialchars($text, ENT_QUOTES).
                                "</option>";
        }
    }
}

// Build piezometer instrument type dropdown
$html_piezoNomSondeManuelle = "";
$html_piezoNomSondeManuelle .= "<option value=''>".TEXT_SELECT_INSTRUMENT_TYPE."</option>";

if (!empty($piezoInstrument)) {
    $html_piezoNomSondeManuelle .= "<option value='".htmlspecialchars($piezoInstrument, ENT_QUOTES)."' selected>"
                                .htmlspecialchars($piezoInstrument, ENT_QUOTES).
                                "</option>";
}

if(isset($nom_sFixe_list) && is_array($nom_sFixe_list)) {
    foreach($nom_sFixe_list as $text) {
        if($text !== $piezoInstrument) {
            $html_piezoNomSondeManuelle .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                        .htmlspecialchars($text, ENT_QUOTES).
                                        "</option>";
        }
    }
}

// Build piezometer instrument number dropdown
$html_piezoNumSondeManuelle = "";
$html_piezoNumSondeManuelle .= "<option value=''>".TEXT_SELECT_INSTRUMENT_NUMBER."</option>";

if (!empty($piezoNumInstrument)) {
    $html_piezoNumSondeManuelle .= "<option value='".htmlspecialchars($piezoNumInstrument, ENT_QUOTES)."' selected>"
                                .htmlspecialchars($piezoNumInstrument, ENT_QUOTES).
                                "</option>";
}


if(isset($num_sFixe_list) && is_array($num_sFixe_list)) {
    foreach($num_sFixe_list as $text) {
        if($text !== $piezoNumInstrument) {
            $html_piezoNumSondeManuelle .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                        .htmlspecialchars($text, ENT_QUOTES).
                                        "</option>";
        }
    }
}


// Benchmark type
$html_piezoNatureRepere = "";
$html_piezoNatureRepere .= "<option value=''>".TEXT_SELECT_NATURE_REPERE."</option>";

if (!empty($piezoNatureRepere)) {
    $html_piezoNatureRepere .= "<option value='".htmlspecialchars($piezoNatureRepere, ENT_QUOTES)."' selected>"
                                .htmlspecialchars($piezoNatureRepere, ENT_QUOTES).
                                "</option>";
}

if(isset($repere_sFixe_list) && is_array($repere_sFixe_list)) {
    foreach($repere_sFixe_list as $text) {
        if($text !== $piezoNatureRepere) {
            $html_piezoNatureRepere .= "<option value='".htmlspecialchars($text, ENT_QUOTES)."'>"
                                        .htmlspecialchars($text, ENT_QUOTES).
                                        "</option>";
        }
    }
}




// Response data
$responseData = array(
    'html_typeAppareil' => $html_typeAppareil,
    'html_numAppareil' => $html_numAppareil,
    'html_hydroNumSonde' => $html_hydroNumSonde,
    'html_piezoNomSondeManuelle' => $html_piezoNomSondeManuelle,
    'html_piezoNumSondeManuelle' => $html_piezoNumSondeManuelle,
    'html_piezoNatureRepere' => $html_piezoNatureRepere
);

// Encode and send response
echo json_encode($responseData);
?>
