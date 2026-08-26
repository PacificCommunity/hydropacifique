<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Génération d'un nouveau mot de passe
----------------------------------------
*/

require('../../config.php');
require('../../database_tables.php');

require('../../function/database.php');	
require('../../function/html_output.php');
require('../../function/general.php');
require('../../function/password.php');

// pour solutionner les pb d'accents
header('Content-Type: text/html; charset=utf-8');

$today_us = date('Y-m-d H:i:s'); 

$id_user_appli = htmlspecialchars(stripslashes($_POST['id_user_appli']),ENT_QUOTES);
$id_user_admin = htmlspecialchars(stripslashes($_POST['id_user_admin']),ENT_QUOTES);

// connextion à la base de données	
$sql_link = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD,DB_DATABASE) or die('Impossible de se connecter à la base de données!');


$sql_users = "SELECT DISTINCT id, login, nom, prenom, info 
            FROM ".TABLE_USER."
            WHERE id=".$id_user_appli;
$users_query = tep_db_query($sql_link,$sql_users);
$users_tab = tep_db_fetch_array($users_query);

$login = $users_tab['login'];
$nom = $users_tab['nom'];
$prenom = $users_tab['prenom'];

//génération automatique d'un nouveau pass
$pass = pass_alea();
$mdp_encryp = tep_encrypt_password($pass);


$query_update_pass = "UPDATE ".TABLE_USER." SET password='".$mdp_encryp."' WHERE id=" . $id_user_appli;
tep_db_query($sql_link,$query_update_pass); 


// Enregistrement de l'action Administration
$type_action = 31;
$info_action = htmlaccent('Réinitialisation du mot de passe - Utilisateur : '.$login.' ('.$prenom.' '.$nom.')');

$query_action = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
										VALUES (".$id_user_admin.",'".$type_action."','".$info_action."','".$today_us."')";
tep_db_query($sql_link,$query_action);


echo $pass;

?>
