<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement des Codes Qualités informations et description
*/


// Modification des Codes Qualités existants

$sql_quality = "SELECT DISTINCT id_data_qualite
				FROM ".TABLE_DATA_QUALITE."
				WHERE init_qualite_data<>'' ORDER BY init_qualite_data ASC";
$quality_query = tep_db_query($sql_link,$sql_quality);
while ($quality = tep_db_fetch_array($quality_query)) 
{
    $id_quality = $quality['id_data_qualite'];

	$init = post_secure($sql_link,$_POST['init_'.$id_quality]);
	$nom = post_secure($sql_link,$_POST['nom_'.$id_quality]);
	$info = post_secure($sql_link,$_POST['info_'.$id_quality]);		
	$info = post_secure($sql_link,$_POST['info_'.$id_quality]);	
	$select_type = post_secure($sql_link,$_POST['select_type_'.$id_quality]);
	

	tep_db_query($sql_link,"UPDATE ".TABLE_DATA_QUALITE." SET init_qualite_data='".$init."',
														   nom_qualite_data='".$nom."',
														   info_qualite_data='".$info."',
														   id_eq_type='".$select_type."'
														WHERE id_data_qualite=".$id_quality);
}
$message_info .= htmlaccent('La liste des Codes Qualités a bien été mise à jour.');



// Nouveau Code Qualité

if(tep_not_null($_POST['init']))
{
	$init_0 = post_secure($sql_link,$_POST['init']);
	$nom_0 = post_secure($sql_link,$_POST['nom']);
	$info_0 = post_secure($sql_link,$_POST['info']);	
	$select_type_0 = post_secure($sql_link,$_POST['select_type']);
	
	$sql_verifquality = "SELECT DISTINCT id_data_qualite
						FROM ".TABLE_DATA_QUALITE."
						WHERE init_qualite_data='".$init_0."'";
	$verifquality_query = tep_db_query($sql_link,$sql_verifquality);	
	$verifquality_array = tep_db_fetch_array($verifquality_query);

	if(isset($verifquality_array['id_data_qualite']) && tep_not_null($verifquality_array['id_data_qualite']))	// Si un code qualité identique existe déjà
    {
		$message_info .= "<br><br>";     
		$message_info .= htmlaccent('Un Code Qualité avec un intitulé identique - '.$init_0.' - existe déjà. Il ne peut pas être ajouter une seconde fois.');
	}
	else
	{
		tep_db_query($sql_link,"INSERT INTO ".TABLE_DATA_QUALITE." (init_qualite_data,nom_qualite_data,info_qualite_data,id_eq_type) 
													VALUES ('".$init_0."','".$nom_0."','".$info_0."','".$select_type_0."')");	
		
		$message_info .= "<br><br>";     
		$message_info .= htmlaccent('Le nouveau Code Qualité - '.$init_0.' - a bien été enregistré.');
	}
}


?>
