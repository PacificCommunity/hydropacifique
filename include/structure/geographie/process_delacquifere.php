<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Processus permettant de supprimer une Rivière
Appelé depuis gestion_geo.php -> form_geo_acquifere.php
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
$jsonDataInfo = file_get_contents('php://input');

// Décoder les données JSON en un tableau associatif PHP
$dataInfo = json_decode($jsonDataInfo, true);

// Accéder aux données du tableau récupérer
$id_acquifere = $dataInfo['id_acquifere'];

// Initialisation Variables
$del_acquifere = true;
$message_info = '';

// Requête sur la table TABLE_REGION

$sql_acquifere = "SELECT DISTINCT id, nom FROM ".TABLE_GEO_ACQUIFERE."
                                        WHERE id = ".$id_acquifere;
$acquifere_query = tep_db_query($sql_link,$sql_acquifere);
$acquifere_tab = tep_db_fetch_array($acquifere_query);

// Suppression de la Rivière
if(isset($acquifere_tab))
{   
    // On vérifie si la acquifere n'est pas liée à une Station
    $sql_ctrl_acquifere_station = "SELECT DISTINCT count(*) as nb_station_acquifere FROM ".TABLE_STATION." WHERE id_acquifere=".$id_acquifere;
    $ctrl_acquifere_station_query = tep_db_query($sql_link,$sql_ctrl_acquifere_station);
    $ctrl_acquifere_station_tab = tep_db_fetch_array($ctrl_acquifere_station_query);

    if($ctrl_acquifere_station_tab['nb_station_acquifere'] < 1)
    {
        $sql_delete_acquifere = "DELETE FROM " . TABLE_GEO_ACQUIFERE . " WHERE id = " . $id_acquifere;
        $result_delete = tep_db_query($sql_link, $sql_delete_acquifere);

        $message_info .= "L'Acquifere - ".$acquifere_tab['nom']." - a bien été supprimé.";
    }
    else
    {
        $del_acquifere = false;
        $message_info .= "L'Acquifere - ".$acquifere_tab['nom']." - n'a pas pû être supprimée.<br>";
        $message_info .= "Il est lié à au moins une Station.";
    }
    
}
else
{
    $del_acquifere = false;
    $message_info .= "L'Acquifere n'existe pas.";
}


// Remplissage du tableau de retour

$responseData = array(
    'del_acquifere' => $del_acquifere,
    'message_info' => $message_info
);

// Encodage du tableau associatif en JSON
$jsonResponse = json_encode($responseData);

// Envoi des données coté Client
echo $jsonResponse;

?>