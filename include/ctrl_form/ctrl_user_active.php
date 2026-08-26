<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Ce code permet d'activer les fiches utilisateurs pour qu'ils puissent se connecter
*/


$action = true;
$action_result = true;
$message_action = '';



$sql_tech = "SELECT DISTINCT id FROM ".TABLE_USER." WHERE admin=0 ORDER BY login";
$user_query = tep_db_query($sql_link,$sql_tech);
while ($user = tep_db_fetch_array($user_query))
{		
	if(isset($_POST['active_'.$user['id']]))
	{
		tep_db_query($sql_link,"UPDATE ".TABLE_USER." SET active='1' WHERE id=".$user['id']);
	}
	else
	{
		tep_db_query($sql_link,"UPDATE ".TABLE_USER." SET active='0' WHERE id=".$user['id']); 
	}
	
}


$message_action = htmlaccent('La liste des utilisateurs a bien été mise à jour');


// Enregistrement de l'action Administration
$type_action = 31;
$info_action = "Modification des accès utilisateurs";

// Enregistrement de l'action
$today_us = date('Y-m-d H:i:s'); 
	
$query = "INSERT INTO ".TABLE_ACTIONS." (id_user, type_action, info, dateheure) 
									VALUES (".$id_user.",'".$type_action."','".$info_action."','".$today_us."')";
tep_db_query($sql_link,$query);

?>
