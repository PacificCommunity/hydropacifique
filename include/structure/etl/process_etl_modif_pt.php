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
$ids = $dataJson['ids'];
$newX = $dataJson['newX'];
$newY = $dataJson['newY'];

$import_result = '';
$valid_process = false;

// ----------------------------------------------
// Début du processus de mise à jour
$sql_ETL_pt = "SELECT id FROM ".TABLE_DATA_ETL_DATA."
                WHERE id=".$ids;
$ETL_pt_query = tep_db_query($sql_link,$sql_ETL_pt);
$ETL_pt = tep_db_fetch_array($ETL_pt_query);

if($ETL_pt)
{
    $sql_update = "UPDATE ".TABLE_DATA_ETL_DATA."
                    SET hauteur = '$newX', debit = '$newY'
                    WHERE id = $ids";
    $ETL_update_query = tep_db_query($sql_link,$sql_update);

    $import_result .= "Les coordonnées du point ont bien été mises à jour\n";
    $valid_process = true;
}
else
{
    $import_result .= "Une erreur est intervenue, le point n'a pas pû être modifié.\n";
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