<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Enregistrement des données Géographique - Regions hydros et Tournées
*/
$error = 0;
$error_0 = 0;

		
/*  
----------------------------------------
ENREGISTREMENT DS LES BASES 
----------------------------------------
*/

// Régions Hydrologiques

$sql_regionhydro = "SELECT DISTINCT id FROM ".TABLE_REGIONHYDRO." ORDER BY id";
$regionhydro_query = tep_db_query($sql_link,$sql_regionhydro);
while ($regionhydro = tep_db_fetch_array($regionhydro_query)) 
{
	${'regionhydro_nom_'.$regionhydro['id']} = post_secure($sql_link,$_POST['regionhydro_nom_'.$regionhydro['id']]);
	${'regionhydro_description_'.$regionhydro['id']} = post_secure($sql_link,$_POST['regionhydro_description_'.$regionhydro['id']]);

	tep_db_query($sql_link,"UPDATE ".TABLE_REGIONHYDRO." SET nom='".${'regionhydro_nom_'.$regionhydro['id']}."',
                                                            description='".${'regionhydro_description_'.$regionhydro['id']}."'
														WHERE id=".$regionhydro['id']);
}
$message_info .= htmlaccent('La liste des Régions Hydrologiques a bien été mise à jour.');


// Nouvelle Région

if(tep_not_null($_POST['regionhydro_nom']))
{
	$nom_regionhydro_0 = post_secure($sql_link,$_POST['regionhydro_nom']);    
	$description_regionhydro_0 = post_secure($sql_link,$_POST['regionhydro_description']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_REGIONHYDRO." (nom,description,id_territoire) 
													VALUES ('".$nom_regionhydro_0."','".$description_regionhydro_0."','".$territoire_id."')");	
		
    $message_info .= "<br><br>";                                                
    $message_info .= htmlaccent('La nouvelle Région Hydrologique - "'.$nom_regionhydro_0.'" - a bien été enregistrée.');
}


// ------

// Tournées

$sql_tournee = "SELECT DISTINCT id FROM ".TABLE_TOURNEE." ORDER BY id";
$tournee_query = tep_db_query($sql_link,$sql_tournee);
while ($tournee = tep_db_fetch_array($tournee_query)) 
{
	${'tournee_nom_'.$tournee['id']} = post_secure($sql_link,$_POST['tournee_nom_'.$tournee['id']]);
	${'tournee_description_'.$tournee['id']} = post_secure($sql_link,$_POST['tournee_description_'.$tournee['id']]);

	tep_db_query($sql_link,"UPDATE ".TABLE_TOURNEE." SET nom='".${'tournee_nom_'.$tournee['id']}."',
                                                        description='".${'tournee_description_'.$tournee['id']}."'
														WHERE id=".$tournee['id']);
}
$message_info .= "<br><br>";                                                
$message_info .= htmlaccent('La liste des Tournées a bien été mise à jour.');


// Nouvelle Tournée

if(tep_not_null($_POST['tournee_nom']))
{
	$nom_tournee_0 = post_secure($sql_link,$_POST['tournee_nom']);    
	$description_tournee_0 = post_secure($sql_link,$_POST['tournee_description']);
	
	tep_db_query($sql_link,"INSERT INTO ".TABLE_TOURNEE." (nom,description,id_territoire) 
													VALUES ('".$nom_tournee_0."','".$description_tournee_0."','".$territoire_id."')");	
	
    $message_info .= "<br><br>";                                                
	$message_info .= htmlaccent('La nouvelle Tournée - "'.$nom_tournee_0.'" - a bien été enregistrée.');
}

?>
