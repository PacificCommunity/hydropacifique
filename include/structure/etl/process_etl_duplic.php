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
$date1 = $dataJson['date1'];
$date2 = $dataJson['date2'];
$heure1 = $dataJson['heure1'];
$heure2 = $dataJson['heure2'];
$id_station = $dataJson['idStation'];

$datetime1 = datefr_us($date1).' '.$heure1;
$datetime2 = datefr_us($date2).' '.$heure2;

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

/*
$sql_ETL_valid = "SELECT COUNT(*) as nb
                FROM ".TABLE_DATA_ETL." etl
                WHERE id_station=$id_station
                AND id <> $idEtl
                AND NOT (datetime_end <= '$datetime1' OR datetime_first >= '$datetime2')";
*/                
$sql_ETL_valid = "SELECT COUNT(*) as nb
                FROM ".TABLE_DATA_ETL." etl
                WHERE id_station=$id_station
                AND NOT (datetime_end <= '$datetime1' OR datetime_first >= '$datetime2')";

$ETL_valid_query = tep_db_query($sql_link,$sql_ETL_valid);
$ETL_data_tab = tep_db_fetch_array($ETL_valid_query);

if($ETL_data_tab['nb'] < 1)
{
    $sql_insert = "INSERT INTO ".TABLE_DATA_ETL." (id_station, datetime_first, datetime_end)
                   VALUES ($id_station, '$datetime1', '$datetime2')";
    $ETL_insert_query = tep_db_query($sql_link,$sql_insert);

    // Vérifier si l'insertion a réussi
    if ($ETL_insert_query) 
    {
        // Récupérer le dernier ID inséré
        $new_id_ETL = mysqli_insert_id($sql_link);

        // Dupliquer les données dans TABLE_DATA_ETL_DATA
        $sql_duplicate = "INSERT INTO ".TABLE_DATA_ETL_DATA." (`id_etl`, `hauteur`, `debit`) 
                            SELECT $new_id_ETL, `hauteur`, `debit`
                            FROM ".TABLE_DATA_ETL_DATA."
                            WHERE id_etl = $idEtl";

        $duplicate_query = tep_db_query($sql_link, $sql_duplicate);

        if ($duplicate_query) 
        {
            // Enregistrement de l'action Export dans la base action
            $type_action = 33;
            $info_action = "Mise à jour des données ETL - Station : ".$station_all_array[$id_station]['nom_station']." - ETL : ".$date1." ".$heure1." → ".$date2." ".$heure2;

            $query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
                                            VALUES (".$id_user.",'".$type_action."','".$info_action."','".$todayTimeFormatted."')";
            tep_db_query($sql_link,$query);

            $import_result .= "La nouvelle relation d'étalonnage 'ETL-".$numEtl." : ".$date1." ".$heure1." → ".$date2." ".$heure2." a bien été crée.\n";
            $valid_process = true;
        }
        else{$import_result .= "Une erreur s'est produite lors de la duplication des données.\n";}
    }
    else{$import_result .= "Une erreur s'est produite lors de la duplication des données.\n";}
}
else
{
    $import_result .= "La période choisie est déjà couverte par une autre relation d'étalonnage : ".$date1." ".$heure1." → ".$date2." ".$heure2."\n";
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