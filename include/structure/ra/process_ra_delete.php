<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procédure pour supprimer un RA dans le bloc d'affichage
Processus asynchrone AJAX côté serveur
----------------------------------------
*/

// Configuration nécessaire
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');
require('../../function/database.php');
require('../../function/html_output.php');
require('../../function/general.php');

// Encodage UTF-8
header('Content-Type: text/html; charset=utf-8');

// Connexion à la base de données
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');


// Text for Translate
require('../../text_content_'.LANGUAGE.'.php');

// Récupération des données JSON
$jsonDataInfo = file_get_contents('php://input');
$dataInfo = json_decode($jsonDataInfo, true);

// Extraction des données
$id_ra = $dataInfo['id_ra'];
$id_user_agent = $dataInfo['id_user_agent'];

// =============================================================================
// RÉCUPÉRATION DES DONNÉES DE RÉFÉRENCE
// =============================================================================

// --- Liste des stations ---
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

// --- Liste des types d'équipement ---
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
// INITIALISATION DES VARIABLES
// =============================================================================
$type_action = 1; // Action Rapport d'activité
$dateheure_action = date("Y-m-d H:i:s");
$msg_info = '';
$del = false;

// =============================================================================
// SUPPRESSION DU RA
// =============================================================================

// Requête pour récupérer les informations du RA
$sql_RA = "SELECT DISTINCT ra.id_ra, ra.id_station, ra.date_heure_ra, ra.id_eq_type
           FROM ".TABLE_DATA_RA." ra
           WHERE id_ra = ".$id_ra;

$RA_query = tep_db_query($sql_link, $sql_RA);
$RA_tab = tep_db_fetch_array($RA_query);

if(isset($RA_tab)) {
    // Récupération des informations du RA
    $id_station = $RA_tab['id_station'];
    $code_station = $station_all_array[$id_station]['code_station'];
    $nom_station = $station_all_array[$id_station]['nom_station'];
    $info_station = $code_station.' - '.$nom_station;

    // Formatage de la date
    $date_heure_ra_tab = explode(" ", $RA_tab['date_heure_ra']);
    $date_ra = dateus_fr($date_heure_ra_tab[0]);
    $heure_ra = $date_heure_ra_tab[1];
    $date_heure_ra_fr = $date_ra.' '.$heure_ra;

    // Suppression des données du RA
    tep_db_query($sql_link, "DELETE FROM ".TABLE_DATA_RA." WHERE id_ra=".$id_ra);
    tep_db_query($sql_link, "DELETE FROM ".TABLE_DATA_RA_PIEZO_PROFIL." WHERE id_ra = ".$id_ra);

    // Préparation du message de confirmation avec constantes de traduction
    $msg_info .= "<span style='font-size:16px;'>".TEXT_RA_DELETE_SUCCESS."</span><br><br>";
    $msg_info .= TEXT_RA_STATION_INFO." : ".$info_station." - ".TEXT_RA_DATE_INFO." : ".$date_heure_ra_fr;

    $del = true;

    // Enregistrement de l'action dans la table ACTION
    $info_action = "Suppression RA <br>Station : ".$info_station." - Date : ".$date_heure_ra_fr;
    $info_action = post_secure($sql_link, $info_action);

    $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure)
              VALUES (".$id_user_agent.",'".$type_action."','".$info_action."','".$dateheure_action."')";
    tep_db_query($sql_link, $query);
} else {
    // Message d'erreur avec constante de traduction
    $msg_info .= "<span style='font-size:16px;'>".TEXT_RA_DELETE_ERROR."</span>";
}

// =============================================================================
// RÉPONSE AU CLIENT
// =============================================================================
$responseData = array(
    'msg_info' => $msg_info,
    'del' => $del
);

// Encodage et envoi de la réponse
echo json_encode($responseData);
?>
