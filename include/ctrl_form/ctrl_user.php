<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Script d'enregistrement des utilisateurs et de leurs autorisations

*/
$action = true;
$action_result = true;
$message_action = '';

$new_id = -1;


/*  
----------------------------------------
RECUPERATION DES DONNEES FORMULAIRES
----------------------------------------
*/

$gestion_data_u=0;
$parametre_u=0;
$config_u=0;

$login = post_secure($sql_link,$_POST['login']);
$nom = post_secure($sql_link,$_POST['nom']);
$prenom = post_secure($sql_link,$_POST['prenom']);
$info_user = post_secure($sql_link,$_POST['info_user']);

if(isset($_POST['gestion_data'])){$gestion_data_u = 1;}
if(isset($_POST['gestion_data_expert'])){$gestion_data_u = 2;}
if(isset($_POST['parametre'])){$parametre_u = 1;}
if(isset($_POST['config'])){$config_u = 1;}


/*  
----------------------------------------
VALIDATION ET VERIFICATION DES DONNEES FORMULAIRES 
----------------------------------------
*/

// Champ vide

if(!tep_not_null($login))
{
	$action_result=false;
	$message_action .= gestion_erreur_text('Le champ Login doit être saisi',0);
}

$sql_log = "SELECT DISTINCT * FROM ".TABLE_USER." WHERE login='".$login."'";
$log_query = tep_db_query($sql_link,$sql_log);
$log = tep_db_fetch_array($log_query);

if(isset($log)) // On vérifie si un login identique existe déjà
{
	if(!$modif && $action_result && tep_not_null($log['id']))
	{
		$action_result=false;
		$message_action .= htmlaccent('Un utilisateur utilise déjà ce login. Veuillez en saisir un autre.');
	}
}

if($action_result && preg_match('/([^.a-z0-9]+)/i',$login))
{
	$action_result=false;
	$message_action .= htmlaccent('Le login ne peut contenir ni espace, ni accent, ni caractères spéciaux.');
}


		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/
$today_us = date('Y-m-d H:i:s'); 

if($action_result)
{
	if(!$modif)
	{
		tep_db_query($sql_link,"INSERT INTO ".TABLE_USER." (login,nom,prenom,info,date_creation,active) ".
													  " VALUES ('".$login.
													  "','".$nom.
													  "', '".$prenom.
													  "','".$info_user.
													  "', '".$today_us.
													  "', 0)");
													  
		$new_id_query = tep_db_query($sql_link,"SELECT max(id) as last_id FROM ".TABLE_USER);
		$new_id = tep_db_fetch_array($new_id_query);
		$ref_id = $new_id['last_id'];	
		
		
		tep_db_query($sql_link,"INSERT INTO ".TABLE_USER_ACCES." (id,gestion_data,parametre,config) ".
													  " VALUES ('".$ref_id.
													  "','".$gestion_data_u.
													  "','".$parametre_u.
													  "', '".$config_u."')");										  											  
													  
									
		$modif = true;
		
		$message_action .= htmlaccent('La fiche utilisateur a bien été crée'); 

		// Enregistrement de l'action Administration
		$type_action = 31;
		$info_action = "Création d\'un nouvel utilisateur : ".$login." (".$prenom." ".$nom.") - ".$info_user;

		
	}	
	else
	{		
		tep_db_query($sql_link,"UPDATE ".TABLE_USER." SET login='" . $login.
												"', nom='" . $nom . 
												"', prenom='" . $prenom . 
												"', info='" . $info_user . 
												"', date_modif='" . $today_us . 
												"' WHERE id=" . $ref_id); 
												
												
		tep_db_query($sql_link,"UPDATE ".TABLE_USER_ACCES." SET gestion_data='" . $gestion_data_u .
												"', parametre='" . $parametre_u . 
												"', config='" . $config_u . 
												"' WHERE id=" . $ref_id); 																		
												
	
		
		$message_action .= htmlaccent('La fiche utilisateur a bien été modifiée'); 


		// Enregistrement de l'action Administration
		$type_action = 31;
		$info_action = "Modification des informations de l\'utilisateur : ".$login." (".$prenom." ".$nom.") - ".$info_user;
	}   



	// Enregistrement de l'action
	
	
	$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
										VALUES (".$id_user.",'".$type_action."','".$info_action."','".$today_us."')";
	tep_db_query($sql_link,$query);
	
}



?>
