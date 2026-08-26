<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export 
- Ce script permet de modifier la période de validité d'une ETL
----------------------------------------
*/

// ----------------------------------------------
// nécessaire pour la configuration du script

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');


// Récupération des données JSON envoyées depuis la requête AJAX
$jsonDataModif = file_get_contents('php://input');

// Décoder les données JSON en un tableau associatif PHP
$dataJson = json_decode($jsonDataModif, true);

// Accéder aux données du tableau récupérer
$id_user = $dataJson['idUser'];
$todayTimeFormatted = $dataJson['todayTimeFormatted'];
$idEtl = $dataJson['idEtl'];
$numEtl = $dataJson['numEtl'];
$id_station = $dataJson['idStation'];

$import_result = '';
$valid_process = false;

// ----------------------------------------------
// Récupération de données dans la base

// TABLE STATION
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, station_type, active_station
					FROM ".TABLE_STATION;
$station_all_query = tep_db_query($sql_link,$sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{	
	$station_all_array[$station_all['id_station']] = array('code_station' => $station_all['code_station'],
															'nom_station' => $station_all['nom_station'],
															'station_type' => $station_all['station_type'],
															);
}


// ----------------------------------------------
// Début du processus de mise à jour

// Démarrer une transaction
mysqli_begin_transaction($sql_link, MYSQLI_TRANS_START_READ_WRITE);

try{

    // Étape 0 : Récupérer les données liées dans TABLE_DATA_ETL
    $sql_etl = "SELECT datetime_first, datetime_end
                    FROM ".TABLE_DATA_ETL." etl
                    WHERE id=$idEtl";
    $etl_query = tep_db_query($sql_link,$sql_etl);
    $etl_tab = tep_db_fetch_array($etl_query);

    $etl_datetime1 = explode(' ',$etl_tab['datetime_first']);
    $date1=dateus_fr($etl_datetime1[0]);
    $heure1=$etl_datetime1[1];

    $etl_datetime2 = explode(' ',$etl_tab['datetime_end']);
    $date2=dateus_fr($etl_datetime2[0]);
    $heure2=$etl_datetime2[1];


    // Étape 1 : Supprimer les données liées dans TABLE_DATA_ETL_DATA
    $query_del_etl_data = "DELETE FROM " . TABLE_DATA_ETL_DATA . " WHERE id_etl = $idEtl";
    tep_db_query($sql_link, $query_del_etl_data);

    // Étape 3 : Supprimer les lignes de TABLE_DATA_ETL
    $query_del_etl = "DELETE FROM " . TABLE_DATA_ETL . " WHERE id = $idEtl";
    tep_db_query($sql_link, $query_del_etl);
        
    // Étape 4 : Enregistrement de l'action Export dans la base action
    $type_action = 33;
    $info_action = "Mise à jour des données ETL - Station : ".$station_all_array[$id_station]['nom_station']." - ETL : $date1 $heure1 → $date2 $heure2";

    $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
                                    VALUES ($id_user,$type_action,'$info_action','$todayTimeFormatted')";
    tep_db_query($sql_link,$query);

    // Étape 6 : Commit de la transaction si tout est correct
    mysqli_commit($sql_link);
    $import_result .= "La relation d'étalonnage - ETL $numEtl : $date1 $heure1 → $date2 $heure2 - a bien été supprimée.\n";
    $valid_process = true;
    
} catch (Exception $e) 
{
    // Annuler la transaction en cas d'erreur
    mysqli_rollback($sql_link);

    // Afficher un message d'erreur
    $import_result .= "Erreur lors de l'exécution de la transaction : " . $e->getMessage();
} 


$responseData = array(
    'js_text' => $import_result,
    'valid_process' => $valid_process
);


// Encodage du tableau associatif en JSON
$jsonResponse = json_encode($responseData);

// Envoi des données coté Client
echo $jsonResponse;

?>