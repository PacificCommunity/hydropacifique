<?php
/*  
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
Suppression des lignes Régions Hydrologiques
*/


// Région Hydrologiques

// Récupération de la variable indiquant l'identifiant à supprimer
$id_rh = mysqli_real_escape_string($sql_link,trim(addslashes($_GET['id_rh'])));


$sql_del_rh = "SELECT DISTINCT id, nom FROM ".TABLE_REGIONHYDRO." WHERE id=".$id_rh;
$del_query = tep_db_query($sql_link,$sql_del_rh);
$del_a = tep_db_fetch_array($del_query);


if(isset($del_a['id']) && tep_not_null($del_a['id']))
{
    // On vérifie si la région hydro est liée à une station hydrologique
	$sql_station_rh = "SELECT EXISTS (
										SELECT 1 FROM ".TABLE_STATION." WHERE id_regionhydro=".$id_rh." LIMIT 1
									 ) AS station_exists";
	
	$station_rh_query = tep_db_query($sql_link,$sql_station_rh);
	$del_station_rh = tep_db_fetch_array($station_rh_query);
	
	if($del_station_rh['station_exists'] == 1) // Si la région est liée à une station on ne peut pas supprimée la région hydro
	{
		$message_suprr_geo = htmlaccent('La Région Hydrologique - '.htmlaccent($del_a['nom']).' - ne peut pas être supprimée car elle est liée à au moins une station de mesure.');	
    }	
	else // Si la région hydro n'est liée à aucune station
	{
		tep_db_query($sql_link,"DELETE FROM ".TABLE_REGIONHYDRO." WHERE id=".$id_rh);
		$message_suprr_geo = htmlaccent('La Région Hydrologique - '.htmlaccent($del_a['nom']).' - a bien été supprimée.');
	}	
}
else
{
	$message_suprr_geo = htmlaccent('La Région Hydrologique n\'existe pas, elle ne peut pas être supprimée.');
}
	


?>
