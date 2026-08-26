<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Script permettant d'enregistrer un nouveau mot de passe
*/

$error = 0;
$error_pass = -1;
$mdp_encryp = '';


/***************************************************/
/* RECUPERATION DES DONNEES FORMULAIRES */
/***************************************************/

$id_user_pass = htmlspecialchars(stripslashes($_POST['id']),ENT_QUOTES);

$sql_user_pass = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_USER." WHERE id='".$id_user_pass."'";
$user_pass_query = tep_db_query($sql_link,$sql_user_pass);
$user_pass_info = tep_db_fetch_array($user_pass_query);

$login_user = $user_pass_info['login'];
$nom_user = $user_pass_info['nom'];
$prenom_user = $user_pass_info['prenom'];


$old_pass_table = htmlspecialchars(stripslashes($_POST['old_pass_table']),ENT_QUOTES);
$old_pass = htmlspecialchars(stripslashes($_POST['old_pass']),ENT_QUOTES);
$new_pass = htmlspecialchars(stripslashes($_POST['new_pass']),ENT_QUOTES);
$new_pass_confirm = htmlspecialchars(stripslashes($_POST['new_pass_confirm']),ENT_QUOTES);



/********************************/
/* VALIDATION DE LA MODIF PASS */
/*******************************/
if(tep_not_null($old_pass) && tep_not_null($new_pass) && tep_not_null($new_pass_confirm))
{
	if(!tep_validate_password($old_pass,$old_pass_table)){$error_pass=1;}
	
	if($error_pass<0)
	{
		if(strlen($new_pass)<6){$error_pass=2;}
	}
	
	if($error_pass<0)
	{
		if($new_pass!=$new_pass_confirm){$error_pass=3;}
		else{$error_pass=0;}
	}
}
else
{$error_pass=4;}

if(!tep_not_null($old_pass) && !tep_not_null($new_pass) && !tep_not_null($new_pass_confirm))
{
	$error_pass=-1;
}



/*******************************/
/* ENREGISTREMENT DS LES BASES */
/*******************************/


if($error_pass==0)
{
	$mdp_encryp = tep_encrypt_password($new_pass);
	tep_db_query($sql_link,"UPDATE ".TABLE_USER." SET password='" . $mdp_encryp . 
					     "', date_modif=now()" .
					     " WHERE id=" . $id_user_pass);

	// Enregistrement de l'action Administration
	$type_action = 31;
	$info_action = "Modification du mot de passe utilisateur : ".$login_user." (".$prenom_user." ".$nom_user.")";			
	
	// Enregistrement de l'action
	$today_us = date('Y-m-d H:i:s'); 

	$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
										VALUES (".$id_user.",'".$type_action."','".$info_action."','".$today_us."')";
	tep_db_query($sql_link,$query);
}

$message_info .= gestion_erreur_pass('Votre nouveau mot de passe',$error_pass);

?>
