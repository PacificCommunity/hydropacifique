<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Procédure pour supprimer une station (asynchrone AJAX côté serveur).
Reprend la logique de suppression de suppr_station.php :
une station ne peut être supprimée que si elle ne contient
aucune donnée (META) ni aucun RA. Les tables liées (photos,
repère piézo, caractéristiques piézo) sont supprimées en cascade.
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
$id_station    = isset($dataInfo['id_station'])    ? (int)$dataInfo['id_station']    : 0;
$id_user_agent = isset($dataInfo['id_user_agent']) ? (int)$dataInfo['id_user_agent'] : 0;

// =============================================================================
// INITIALISATION DES VARIABLES
// =============================================================================
$type_action      = 2; // Action Station
$dateheure_action = date("Y-m-d H:i:s");
$msg_info = '';
$del      = false;

// =============================================================================
// SUPPRESSION DE LA STATION
// =============================================================================

// Sécurisation de l'identifiant
$sta_id = mysqli_real_escape_string($sql_link, $id_station);

// Requête pour récupérer les informations de la station
$sql_del = "SELECT DISTINCT id_station, station_type, nom_station, code_station
            FROM ".TABLE_STATION."
            WHERE id_station=".$sta_id;
$del_query = tep_db_query($sql_link, $sql_del);
$del_a     = tep_db_fetch_array($del_query);

if (isset($del_a['id_station'])) {

    $nom_station  = htmlaccent(html_entity_decode($del_a['nom_station'] ?? ''));
    $code_station = $del_a['code_station'];
    $info_station = $code_station.' - '.$nom_station;

    // -------------------------------------------------------------------------
    // Vérifie qu'aucune donnée n'est rattachée à la station avant de la
    // supprimer. Toutes ces tables sont liées par id_station ; si l'une
    // d'entre elles contient au moins une ligne, la suppression est refusée.
    $tables_check = array(
        TABLE_DATA_META,
        TABLE_DATA_RA,
        TABLE_DATA_META_CORRECTION,
        TABLE_DATA_ETL_CORRECTION,
        TABLE_DATA_ETL,
        TABLE_DATA_JGE,
        TABLE_DATA_LAB,
        TABLE_DATA_TOT
    );

    $has_records = false;
    foreach ($tables_check as $table_check) {
        $sql_check   = "SELECT 1 FROM ".$table_check." WHERE id_station=".$sta_id." LIMIT 1";
        $check_query = tep_db_query($sql_link, $sql_check);
        if (tep_db_fetch_array($check_query)) {
            $has_records = true;
            break;
        }
    }

    if (!$has_records) {

        // Suppression de la station et des tables liées
        tep_db_query($sql_link, "DELETE FROM ".TABLE_STATION."                     WHERE id_station=".$sta_id);
        tep_db_query($sql_link, "DELETE FROM ".TABLE_STATION_PHOTOS."              WHERE id_station=".$sta_id);
        tep_db_query($sql_link, "DELETE FROM ".TABLE_STATION_PIEZO_REPERE."        WHERE id_station=".$sta_id);
        tep_db_query($sql_link, "DELETE FROM ".TABLE_STATION_PIEZO_CARACTERISTIQUE." WHERE id_station=".$sta_id);

        // Message de confirmation avec constantes de traduction
        $msg_info .= "<span style='font-size:16px;'>".TEXT_STATION_DELETE_SUCCESS."</span><br><br>";
        $msg_info .= TEXT_STATION_INFO." : ".$info_station;

        $del = true;

        // Enregistrement de l'action dans la table ACTION
        $info_action = "Suppression Station <br>Station : ".$info_station;
        $info_action = post_secure($sql_link, $info_action);

        $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure)
                  VALUES (".$id_user_agent.",'".$type_action."','".$info_action."','".$dateheure_action."')";
        tep_db_query($sql_link, $query);

    } else {
        // La station contient des enregistrements : suppression refusée
        $msg_info .= "<span style='font-size:16px;'>".TEXT_STATION_DELETE_HAS_RECORDS."</span>";
    }

} else {
    // La station n'existe pas
    $msg_info .= "<span style='font-size:16px;'>".TEXT_STATION_DELETE_NOT_FOUND."</span>";
}

// =============================================================================
// RÉPONSE AU CLIENT
// =============================================================================
$responseData = array(
    'msg_info' => $msg_info,
    'del'      => $del
);

// Encodage et envoi de la réponse
echo json_encode($responseData);
?>