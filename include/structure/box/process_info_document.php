<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Export 
- Script générique pour charger le contenu d'un fichier d'information
  (conditions, licence, etc.) selon la langue active
----------------------------------------
*/
// ----------------------------------------------
require('../../config.php');
require('../../database_tables.php');
require('../../function/date.php');	
require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');

header('Content-Type: text/html; charset=utf-8');

$sql_link = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE)
    or die('Impossible de se connecter à la base de données!');
mysqli_query($sql_link, 'SET NAMES UTF8');

require('../../text_content_' . LANGUAGE . '.php');

// -----------------------------------------------
// Récupération des données JSON envoyées par l'AJAX
$jsonData = file_get_contents('php://input');
$dataJson = json_decode($jsonData, true);

$type          = $dataJson['type']         ?? ''; // 'conditions' ou 'licence'

// -----------------------------------------------
$chemin_fichier = '../../../' . DIR_WS_TXT . $type . '_' . LANGUAGE . '.txt';
if (file_exists($chemin_fichier))
{
    $contenu_fichier = file_get_contents($chemin_fichier);
}

// -----------------------------------------------
// Génération du HTML
$html = '';
if (!empty($contenu_fichier))
{
    $html .= "
        <div class='table-container' style='width:99%;height:60vh;margin:20px 1%;line-height:1.6;font-size:18px;'>
            " . $contenu_fichier . "
        </div>";
}

$responseData = array('js_html' => $html);
echo json_encode($responseData);
?>