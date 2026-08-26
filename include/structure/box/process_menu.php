<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Processus permettant d'enregistrer l'état du menu
Protocole sur serveur (AJAX) 
Appelé depuis include/structure/box/nav_accueil.php
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
$jsonDataInfo = file_get_contents('php://input');

// Decode JSON data into a PHP associative array
$dataInfo = json_decode($jsonDataInfo, true);

// Extract values from the decoded array
$id_user = $dataInfo['id_user'];
$menu_id = $dataInfo['menu_id'];
$is_open = $dataInfo['is_open'];

// Mise à jour de la table TABLE_USER_MENU 
$sql_menu = "INSERT INTO ".TABLE_USER_MENU." (id_user, menu_id, is_open)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE is_open = VALUES(is_open)";
  

$stmt = $sql_link->prepare($sql_menu);
if($stmt) 
{
    $stmt->bind_param("isi", $id_user, $menu_id, $is_open);
    $stmt->execute();
    $stmt->close();
}

// Fermeture de la connexion
$sql_link->close();

?>