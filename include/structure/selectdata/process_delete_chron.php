<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Delete Data 
- Ce script permet de supprimer des données sélectionnées
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
require('../../function/sql_function.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

// connexion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');
mysqli_query ($sql_link,'SET NAMES UTF8');


// Récupération des données JSON envoyées depuis la requête AJAX
$jsonDataInfo = file_get_contents('php://input');

// Décoder les données JSON en un tableau associatif PHP
$dataInfo = json_decode($jsonDataInfo, true);

// Accéder aux données du tableau récupérer
$checkedTabData = $dataInfo['checkedTabData'];
$date_1 = datefr_us($dataInfo['date_1']);
$date_2 = datefr_us($dataInfo['date_2']);


// Chargement de tables nécessaires au traitement de l'algorithme


// TABLE STATION
$sql_station_all = "SELECT DISTINCT id_station, nom_station, code_station, active_station, station_type
				FROM ".TABLE_STATION;
$station_all_query = tep_db_query($sql_link,$sql_station_all);
while ($station_all = tep_db_fetch_array($station_all_query))
{	
	$station_all_array[$station_all['id_station']] = array('nom_station' => $station_all['nom_station'],
													        'code_station' => html_entity_decode($station_all['code_station'] ?? ''),
													        'type_station' => html_entity_decode($station_all['station_type'] ?? '')
                                                        );
}


// TABLE DATA_CHRON (TYPE CHRON - CI,PI, CIE, ...)
$sql_type_chron = "SELECT DISTINCT id_data_type, init_type_data, nom_type_data, id_eq_type_data, axe_data, unite, to_periode, id_chon_periode
				  FROM ".TABLE_TYPE_DATA." 
				  ORDER BY init_type_data ASC";
			  

$type_chron_query = tep_db_query($sql_link,$sql_type_chron);									
while ($type_chron_tab = tep_db_fetch_array($type_chron_query))
{
    $axe_nom = '';
    if(isset($data_type_axe_array[$type_chron_tab['axe_data']]['axe'])){$axe_nom = $data_type_axe_array[$type_chron_tab['axe_data']]['axe'];}

	$type_chron_array[$type_chron_tab['id_data_type']] = array('init_type_data' => $type_chron_tab['init_type_data'],
															'nom_type_data' => $type_chron_tab['nom_type_data'],
															'id_eq_type_data' => $type_chron_tab['id_eq_type_data'],
															'axe_nom' => $axe_nom,
															'unite' => $type_chron_tab['unite'],
															'to_periode' => $type_chron_tab['to_periode'],
															'id_chon_periode' => $type_chron_tab['id_chon_periode']
															);
}

$detail_delete = "Données supprimées : \n\n";
$rows_deleted = 0;

foreach($checkedTabData as $value_chron)
{
    $chron_array = explode("_", $value_chron); 
    $station_chron = $chron_array[0]; 
    $typedata_station = $chron_array[1];
    $typedata_chron = $chron_array[2];

    $init_station = $station_all_array[$station_chron]['code_station'];
    $nom_station = $station_all_array[$station_chron]['nom_station'];

    $init_type_data = $type_chron_array[$typedata_chron]['init_type_data'];
    $nom_type_data = $type_chron_array[$typedata_chron]['nom_type_data'];

    $rows_deleted = deleteDataAndMeta($sql_link,$station_chron, $typedata_chron, $date_1, $date_2);

    $detail_delete .= "Station : ".$init_station." ".$nom_station;
    $detail_delete .= " - ".$init_type_data." : ".$nom_type_data;
    $detail_delete .= " - Nb Data = ".$rows_deleted;
    $detail_delete .= "\n";
}	

/*
$type_action = 37;
$info_action = "Importation des données - Fichier : ".$import_data['file_import']." - Station : ".$station_all_array[$import_data['id_station']]['nom_station'];


$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure,id_import) 
                                VALUES (".$import_data['id_user'].",'".$type_action."','".$info_action."','".$import_data['dateheure']."','".$idImport."')";
tep_db_query($sql_link,$query);
*/


$responseData = array(
    'js_text' =>  $detail_delete
);

// Encodage du tableau associatif en JSON
$jsonResponse = json_encode($responseData);

// Envoi des données coté Client
echo $jsonResponse;
?>