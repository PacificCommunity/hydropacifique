<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Module pour la suppression d'un utilisateur de la plateforme
Appelé depuis lis_actions.php

*/

$action = true;
$action_result = false;
$message_action = '';


$fiche_user_del = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['del'])));


$sql_del = "SELECT DISTINCT id, login, nom, prenom, nb_log FROM ".TABLE_USER." WHERE id=".$fiche_user_del;
$del_query = tep_db_query($sql_link,$sql_del);
$del_a = tep_db_fetch_array($del_query);


if(isset($del_a['id'])) // On vérifie que l'utilisateur existe bien
{
	$login_del = htmlaccent($del_a['login']);
	$nom_del = htmlaccent($del_a['nom']);
	$prenom_del = htmlaccent($del_a['prenom']);

	// Un utilisateur ne peut être supprimé que s'il n'y a pas eu de connexion. 
	// S'il a déjà réalisé une action, il doit être conservé, sinon il y aura un bug de relation entre les tables
	if($del_a['nb_log'] > 0) 
	{
		$message_action = "Cette fiche ne peut être supprimée, l'utilisateur a réalisé plusieurs actions sur la plateforme.";
		$message_action .= "<br><br>";
		$message_action .= "L'accès ne peut être que désactivé.";
	}
	else
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_USER." WHERE id=".$fiche_user_del);
		tep_db_query($sql_link,"DELETE FROM ".TABLE_USER_ACCES." WHERE id=".$fiche_user_del);
			
		$action_result = true;
		$message_action = htmlaccent('La fiche de l\'utilisateur - '.$login_del.' - a bien été supprimée.');


		// Enregistrement de l'action Administration
		$type_action = 31;
		$info_action = "Suppression de l\'utilisateur : ".$login_del." (".$prenom_del." ".$nom_del.") - ".$info_user;

		// Enregistrement de l'action
		$today_us = date('Y-m-d H:i:s'); 
		
		$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
											VALUES (".$id_user.",'".$type_action."','".$info_action."','".$today_us."')";
		tep_db_query($sql_link,$query);
	}

	
}
else
{
	$message_action = "La fiche utilisateur n'existe pas, elle ne peut être supprimée";
}

?>
