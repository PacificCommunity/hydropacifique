<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export 
- Ce script permet de générer les données de la carte dans la page des index.php (la page d'accueil)
----------------------------------------
*/

// ----------------------------------------------
// Required files for script configuration

require('../../config.php');
require('../../database_tables.php');

require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

// Set UTF-8 charset header
header('Content-Type: text/html; charset=utf-8');

// Database connection	
$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

// -----------------------------------------------
// Load translation strings for the active language
require('../../text_content_' . LANGUAGE . '.php');


// Retrieve JSON data sent from the AJAX request
$jsonData = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataJson = json_decode($jsonData, true);

// Extract values from the decoded array
$territoire_id = $dataJson['territoireId'];

$contenu_fichier_conditions = '';
$chemin_fichier_conditions = '../../../'.DIR_WS_TXT.'conditions_'.LANGUAGE.'.txt'; // Chemin vers le fichier de présentation du service

// Vérifier si le fichier existe
if (file_exists($chemin_fichier_conditions)) 
{
    // Lire le contenu du fichier
    $contenu_fichier_conditions = file_get_contents($chemin_fichier_conditions);
}

//Génération du code HTML
$html = '';

if(!empty($contenu_fichier_conditions)) 
{	
	$html .= 
	"
	    <div class='table-container' style='width:99%;height:60vh;margin:20px 1%;line-height: 1.6;font-size:18px;' >							  
            ".$contenu_fichier_conditions."
        </div>";
}


$responseData = array(
    'js_html' => $html
);


// Encode response as JSON
$jsonResponse = json_encode($responseData);

// Send response to the client
echo $jsonResponse;

?>